<?php
// --- FILE INCLUDES ---
include_once '../../includes/connect.php';
include_once '../../encryption.php';
require_once '../../includes/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// --- DYNAMIC REPORT DATA FETCHER ---
function get_report_data_by_filters($conn, $school_id, $selected_year, $selected_std) {
    $data = [];
    $params = [];
    $where_conditions = [];

    if ($school_id) {
        $where_conditions[] = 'school_id = ?';
        $params[] = $school_id;
    }
    if ($selected_year) {
        $where_conditions[] = 'academic_year = ?';
        $params[] = $selected_year;
    }
    
    $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // 1. Average marks per subject and standard
    $perf_query = "SELECT std, subject_name, ROUND(SUM(marks_obtained) * 100.0 / SUM(total_marks), 2) AS average_percentage
                   FROM student_marks
                   $where_sql
                   GROUP BY std, subject_name
                   ORDER BY std, subject_name";
    $perf_stmt = $conn->prepare($perf_query);
    $perf_stmt->execute($params);
    $data['performance_by_subject'] = $perf_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Comparison of performance across different exams
    $exam_comp_params = $params;
    $exam_comp_where_sql = $where_sql;
    if ($selected_std !== 'all') {
        $exam_comp_where_sql .= (empty($exam_comp_where_sql) ? 'WHERE ' : ' AND ') . 'std = ?';
        $exam_comp_params[] = $selected_std;
    }

    $exam_comp_query = "SELECT std, exam_type, ROUND(SUM(marks_obtained) * 100.0 / SUM(total_marks), 2) AS average_percentage
                        FROM student_marks
                        $exam_comp_where_sql
                        GROUP BY std, exam_type
                        ORDER BY std, exam_type";
    $exam_comp_stmt = $conn->prepare($exam_comp_query);
    $exam_comp_stmt->execute($exam_comp_params);
    $data['performance_by_exam'] = $exam_comp_stmt->fetchAll(PDO::FETCH_ASSOC);

    return $data;
}

// --- Helper function to get data for a specific section (for CSV/Excel downloads) ---
function get_section_data($conn, $section_id, $school_id, $selected_year, $selected_std) {
    $data_params = [];
    $data_where_conditions = [];

    if ($school_id) {
        $data_where_conditions[] = 'school_id = ?';
        $data_params[] = $school_id;
    }
    if ($selected_year) {
        $data_where_conditions[] = 'academic_year = ?';
        $data_params[] = $selected_year;
    }
    $where_sql = !empty($data_where_conditions) ? 'WHERE ' . implode(' AND ', $data_where_conditions) : '';

    switch ($section_id) {
        case 'performance-table':
            $query = "SELECT std, subject_name, ROUND(SUM(marks_obtained) * 100.0 / SUM(total_marks), 2) AS average_percentage
                      FROM student_marks
                      $where_sql
                      GROUP BY std, subject_name
                      ORDER BY std, subject_name";
            $stmt = $conn->prepare($query);
            $stmt->execute($data_params);
            return [
                'title' => 'Average Performance by Subject & Standard',
                'labels' => ['Standard', 'Subject', 'Average Percentage (%)'],
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        case 'exam-chart':
            $exam_comp_params = $data_params;
            $exam_comp_where_sql = $where_sql;
            if ($selected_std !== 'all') {
                $exam_comp_where_sql .= (empty($exam_comp_where_sql) ? 'WHERE ' : ' AND ') . 'std = ?';
                $exam_comp_params[] = $selected_std;
            }
            $query = "SELECT std, exam_type, ROUND(SUM(marks_obtained) * 100.0 / SUM(total_marks), 2) AS average_percentage
                      FROM student_marks
                      $exam_comp_where_sql
                      GROUP BY std, exam_type
                      ORDER BY std, exam_type";
            $stmt = $conn->prepare($query);
            $stmt->execute($exam_comp_params);
            return [
                'title' => 'Exam Performance Comparison',
                'labels' => ['Standard', 'Exam Type', 'Average Percentage (%)'],
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        default:
            return null;
    }
}

// --- PDF GENERATION LOGIC ---
if (isset($_POST['download_pdf'])) {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $html = $_POST['pdf_html'];
    $filename = $_POST['pdf_filename'] ?? 'Academic_Report.pdf';
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($filename, ["Attachment" => 1]);
    exit();
}

// --- CSV/EXCEL GENERATION LOGIC for INDIVIDUAL SECTIONS ---
if (isset($_POST['download_section_csv']) || isset($_POST['download_section_excel'])) {
    $file_type = isset($_POST['download_section_csv']) ? 'csv' : 'xls';
    $section_id = $_POST['section_id'];

    $role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
    $userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
    $school_id = get_school_id_by_user_id($conn, $role, $userId);

    $selected_year = $_POST['selected_year'] ?? null;
    $selected_std = $_POST['selected_std'] ?? 'all';
    
    $section_data = get_section_data($conn, $section_id, $school_id, $selected_year, $selected_std);

    if ($file_type === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $section_data['title']) . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, $section_data['labels']);
        foreach ($section_data['data'] as $row) {
            fputcsv($output, is_array($row) ? $row : array_values($row));
        }
        fclose($output);
    } else {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $section_data['title']) . '.xls"');
        echo '<table><thead><tr>';
        foreach ($section_data['labels'] as $label) {
            echo '<th>' . htmlspecialchars($label) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($section_data['data'] as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars(is_array($cell) ? $cell[0] : $cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    exit();
}

// --- CSV/EXCEL GENERATION LOGIC for FULL REPORT ---
if (isset($_POST['download_full_csv']) || isset($_POST['download_full_excel'])) {
    $file_type = isset($_POST['download_full_csv']) ? 'csv' : 'xls';

    $role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
    $userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
    $school_id = get_school_id_by_user_id($conn, $role, $userId);
    $selected_year = $_POST['selected_year'] ?? null;
    $selected_std = $_POST['selected_std'] ?? 'all';
    
    $report_data = get_report_data_by_filters($conn, $school_id, $selected_year, $selected_std);
    
    $performance_table_data = get_section_data($conn, 'performance-table', $school_id, $selected_year, $selected_std);
    $exam_comparison_data = get_section_data($conn, 'exam-chart', $school_id, $selected_year, $selected_std);
    
    if ($file_type === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="Full_Academic_Report.csv"');
        $output = fopen('php://output', 'w');
        
        // Section 1: Exam Performance Comparison
        fputcsv($output, ['Exam Performance Comparison (Full Report)']);
        fputcsv($output, $exam_comparison_data['labels']);
        foreach ($exam_comparison_data['data'] as $row) {
            fputcsv($output, array_values($row));
        }
        fputcsv($output, ['']);

        // Section 2: Average Performance by Subject & Standard
        fputcsv($output, ['Average Performance by Subject & Standard']);
        fputcsv($output, $performance_table_data['labels']);
        foreach ($performance_table_data['data'] as $row) {
            fputcsv($output, array_values($row));
        }
        fclose($output);
        
    } else {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="Full_Academic_Report.xls"');
        echo '<html><body><table>';
        
        // Section 1: Exam Performance Comparison
        echo '<tr><th colspan="3">Exam Performance Comparison</th></tr>';
        echo '<tr><th>Standard</th><th>Exam Type</th><th>Average Percentage (%)</th></tr>';
        foreach ($exam_comparison_data['data'] as $row) {
            echo '<tr><td>' . htmlspecialchars($row['std']) . '</td><td>' . htmlspecialchars($row['exam_type']) . '</td><td>' . htmlspecialchars($row['average_percentage']) . '</td></tr>';
        }
        echo '<tr><td colspan="3"></td></tr>';

        // Section 2: Average Performance by Subject & Standard
        echo '<tr><th colspan="3">Average Performance by Subject & Standard</th></tr>';
        echo '<tr><th>Standard</th><th>Subject</th><th>Average Percentage (%)</th></tr>';
        foreach ($performance_table_data['data'] as $row) {
            echo '<tr><td>' . htmlspecialchars($row['std']) . '</td><td>' . htmlspecialchars($row['subject_name']) . '</td><td>' . htmlspecialchars($row['average_percentage']) . '</td></tr>';
        }
        echo '</table></body></html>';
    }
    exit();
}

function get_school_id_by_user_id($conn, $role, $userId) {
    if ($role === 'principal') {
        $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    return null;
}

// --- Main Page Logic ---
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Authorization check
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if (!in_array($role, ['principal', 'superadmin'])) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// --- Role-Based School ID Logic ---
$school_id = null;
$schools = [];
if ($role === 'principal') {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
} else if ($role === 'superadmin') {
    $schools_stmt = $conn->query("SELECT id, school_name FROM school ORDER BY school_name ASC");
    $schools = $schools_stmt->fetchAll(PDO::FETCH_ASSOC);
    $school_id = isset($_GET['school_id']) && is_numeric($_GET['school_id']) ? (int)$_GET['school_id'] : null;
}

// --- INITIALIZE VARIABLES & FILTERS ---
$report_title = "Academic Performance Report";
$school_name = "All Schools";
$errorMessage = '';
$performance_data = [];
$exam_comparison_data = [];
$available_years = [];
$available_standards = [];

try {
    $years_query = "SELECT DISTINCT academic_year FROM student_marks ORDER BY academic_year DESC";
    $years_stmt = $conn->prepare($years_query);
    $years_stmt->execute();
    $available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);

    $standards_query = "SELECT DISTINCT std FROM student_marks ORDER BY std ASC";
    $standards_stmt = $conn->prepare($standards_query);
    $standards_stmt->execute();
    $available_standards = $standards_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $errorMessage = "Error fetching filter data: " . $e->getMessage();
}

$selected_year = isset($_GET['academic_year']) && in_array($_GET['academic_year'], $available_years) ? $_GET['academic_year'] : ($available_years[0] ?? null);
$selected_std = isset($_GET['std']) && in_array($_GET['std'], $available_standards) ? $_GET['std'] : 'all';

$data_params = [];
$data_where_conditions = [];

if ($school_id) {
    $data_where_conditions[] = 'school_id = ?';
    $data_params[] = $school_id;
}
if ($selected_year) {
    $data_where_conditions[] = 'academic_year = ?';
    $data_params[] = $selected_year;
}

try {
    if ($school_id) {
        $school_stmt = $conn->prepare("SELECT school_name FROM school WHERE id = ?");
        $school_stmt->execute([$school_id]);
        $school_name = $school_stmt->fetchColumn();
        $report_title = "Academic Report for " . htmlspecialchars($school_name);
    } else {
        $report_title = "Academic Report (All Schools)";
    }

    $pdf_filename = "Academic_Report.pdf";
    if ($school_id) {
        $safe_school_name = preg_replace('/[^a-zA-Z0-9]+/', '_', $school_name);
        $pdf_filename = trim($safe_school_name, '_') . "_Academic_Report.pdf";
    } else if ($role === 'superadmin' && !$school_id) {
        $pdf_filename = "All_School_Academic_Report.pdf";
    }

    if ($selected_year) {
        $where_sql = !empty($data_where_conditions) ? 'WHERE ' . implode(' AND ', $data_where_conditions) : '';
        $perf_query = "SELECT std, subject_name, ROUND(SUM(marks_obtained) * 100.0 / SUM(total_marks), 2) AS average_percentage
                       FROM student_marks
                       $where_sql
                       GROUP BY std, subject_name
                       ORDER BY std, subject_name";
        $perf_stmt = $conn->prepare($perf_query);
        $perf_stmt->execute($data_params);
        $performance_data = $perf_stmt->fetchAll(PDO::FETCH_ASSOC);

        $exam_comp_params = $data_params;
        $exam_comp_where_sql = $where_sql;
        if ($selected_std !== 'all') {
            $exam_comp_where_sql .= (empty($exam_comp_where_sql) ? 'WHERE ' : ' AND ') . 'std = ?';
            $exam_comp_params[] = $selected_std;
        }

        $exam_comp_query = "SELECT std, exam_type, ROUND(SUM(marks_obtained) * 100.0 / SUM(total_marks), 2) AS average_percentage
                            FROM student_marks
                            $exam_comp_where_sql
                            GROUP BY std, exam_type
                            ORDER BY std, exam_type";
        $exam_comp_stmt = $conn->prepare($exam_comp_query);
        $exam_comp_stmt->execute($exam_comp_params);
        $exam_results = $exam_comp_stmt->fetchAll(PDO::FETCH_ASSOC);

        $chart_labels = [];
        $temp_data = [];

        foreach ($exam_results as $row) {
            $std_label = "Std " . $row['std'];
            if (!in_array($std_label, $chart_labels)) {
                $chart_labels[] = $std_label;
            }
            if (!isset($temp_data[$row['exam_type']])) {
                $temp_data[$row['exam_type']] = [];
            }
            $temp_data[$row['exam_type']][$std_label] = $row['average_percentage'];
        }

        $chart_datasets = [];
        $exam_order = ['term_1', 'term_2', 'final_exam'];
        $colors = ['#4e73df', '#1cc88a', '#e74a3b', '#f6c23e', '#36b9cc'];
        $color_index = 0;

        foreach ($exam_order as $exam_type) {
            if (isset($temp_data[$exam_type])) {
                $data = $temp_data[$exam_type];
                $dataset = [
                    'label' => ucwords(str_replace('_', ' ', $exam_type)),
                    'backgroundColor' => $colors[$color_index % count($colors)],
                    'data' => []
                ];
                foreach ($chart_labels as $label) {
                    $dataset['data'][] = $data[$label] ?? 0;
                }
                $chart_datasets[] = $dataset;
                $color_index++;
            }
        }
    }
} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($report_title); ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/responsive.css" />

    <style>
        form.form-inline {
            display: flex;
            flex-flow: row wrap;
            align-items: center;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php
        if (!$is_ajax_request) {
            include '../../includes/sidebar.php';
        }
        ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
                if (!$is_ajax_request) {
                    include '../../includes/header.php';
                }
                ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><?php echo htmlspecialchars($report_title); ?></h1>
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="fullReportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-download fa-sm text-white-50"></i> Generate Full Report
                            </button>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="fullReportDropdown">
                                <a class="dropdown-item" href="#" id="download-full-pdf">PDF Report</a>
                                <a class="dropdown-item" href="#" id="download-full-csv">CSV Report</a>
                                <a class="dropdown-item" href="#" id="download-full-excel">Excel Report</a>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline align-items-center">
                                <?php if ($role === 'superadmin'): ?>
                                    <div class="form-group mr-3 mb-2">
                                        <label for="school_id" class="mr-2"><strong>School:</strong></label>
                                        <select name="school_id" id="school_id" class="form-control">
                                            <option value="">-- All Schools --</option>
                                            <?php foreach ($schools as $school): ?>
                                                <option value="<?php echo $school['id']; ?>"
                                                    <?php if ($school['id'] == $school_id) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($school['school_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group mr-3 mb-2">
                                    <label for="academic_year" class="mr-2"><strong>Academic Year:</strong></label>
                                    <select name="academic_year" id="academic_year" class="form-control">
                                        <?php if (empty($available_years)): ?>
                                            <option value="">No years found</option>
                                            <?php else: foreach ($available_years as $year): ?>
                                                <option value="<?php echo $year; ?>"
                                                    <?php if ($year == $selected_year) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($year); ?>
                                                </option>
                                        <?php endforeach;
                                        endif; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-3 mb-2">
                                    <label for="std" class="mr-2"><strong>Standard:</strong></label>
                                    <select name="std" id="std" class="form-control">
                                        <option value="all">-- All --</option>
                                        <?php foreach ($available_standards as $standard): ?>
                                            <option value="<?php echo $standard; ?>"
                                                <?php if ($standard == $selected_std) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($standard); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary mb-2">Apply Filters</button>
                            </form>
                        </div>
                    </div>

                    <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>

                    <div id="report-content">
                        <div class="card shadow mb-4" id="exam-chart-section">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Exam Performance
                                    Comparison<?php echo ($selected_std !== 'all') ? ' for Standard ' . htmlspecialchars($selected_std) : ''; ?>
                                    (<?php echo htmlspecialchars($selected_year ?? ''); ?>)</h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-download fa-sm"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                        <a class="dropdown-item download-section-pdf-btn" href="#" data-section="exam-chart">PDF</a>
                                        <a class="dropdown-item download-section-csv-btn" href="#" data-section="exam-chart">CSV</a>
                                        <a class="dropdown-item download-section-excel-btn" href="#" data-section="exam-chart">Excel</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($chart_datasets)): ?>
                                    <div class="text-center text-muted">No data available for the selected filters.</div>
                                <?php else: ?>
                                    <div class="chart-bar"><canvas id="examComparisonChart"></canvas></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card shadow mb-4" id="performance-table-section">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Average Performance by Subject & Standard
                                    (<?php echo htmlspecialchars($selected_year ?? ''); ?>)</h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-download fa-sm"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                        <a class="dropdown-item download-section-pdf-btn" href="#" data-section="performance-table">PDF</a>
                                        <a class="dropdown-item download-section-csv-btn" href="#" data-section="performance-table">CSV</a>
                                        <a class="dropdown-item download-section-excel-btn" href="#" data-section="performance-table">Excel</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="performanceTable" width="100%"
                                        cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Standard</th>
                                                <th>Subject</th>
                                                <th>Average Performance (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($performance_data)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No performance data available.</td>
                                                </tr>
                                                <?php else: foreach ($performance_data as $row): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['std']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                                        <td><?php echo $row['average_percentage']; ?>%</td>
                                                    </tr>
                                            <?php endforeach;
                                            endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            if (!$is_ajax_request) {
                include '../../includes/footer.php';
            }
            ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="/BMC-SMS/assets/js/global-ajax-filters.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>
    <script>
        $(document).ready(function() {
            $('#performanceTable').DataTable({
                "pageLength": 10
            });
        });

        var examChart = new Chart(document.getElementById("examComparisonChart"), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels ?? []); ?>,
                datasets: <?php echo json_encode($chart_datasets ?? []); ?>
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + "%"
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // --- Full Report Download Logic ---
        document.getElementById('download-full-pdf').addEventListener('click', function(e) {
            e.preventDefault();
            const examChartImg = examChart.toBase64Image();
            const performanceTableHtml = document.getElementById('performanceTable').parentElement.innerHTML
                .replace(/ id="performanceTable"/g, '').replace(/<input.*?>/g, '');

            const pdfHtml = `
            <!DOCTYPE html><html><head><title>Academic Report</title><style>
                body{font-family:sans-serif} .header{text-align:center;margin-bottom:20px} h1,h2,h3{margin:0} h2{font-size:1.2em;font-weight:normal} table{width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12px} th,td{border:1px solid #ddd;padding:6px} th{background-color:#f2f2f2} .chart-container{text-align:center;margin-top:20px;page-break-inside:avoid} .chart-container img{max-width:95%;height:auto}
            </style></head><body>
                <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>Academic Performance Report (<?php echo htmlspecialchars($selected_year ?? ''); ?>)</h2></div>
                <h3>Exam Performance Comparison</h3><div class="chart-container"><img src="${examChartImg}"></div>
                <h3>Average Performance by Subject & Standard</h3>${performanceTableHtml}
            </body></html>`;
            generateAndSubmitPdf(pdfHtml, '<?php echo $pdf_filename; ?>');
        });
        
        function submitFormForDownload(type, sectionId = null) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?';
            
            const selectedYearInput = document.createElement('input');
            selectedYearInput.type = 'hidden';
            selectedYearInput.name = 'selected_year';
            selectedYearInput.value = document.getElementById('academic_year').value;
            form.appendChild(selectedYearInput);

            const selectedStdInput = document.createElement('input');
            selectedStdInput.type = 'hidden';
            selectedStdInput.name = 'selected_std';
            selectedStdInput.value = document.getElementById('std').value;
            form.appendChild(selectedStdInput);
            
            if (sectionId) {
                const sectionIdInput = document.createElement('input');
                sectionIdInput.type = 'hidden';
                sectionIdInput.name = 'section_id';
                sectionIdInput.value = sectionId;
                form.appendChild(sectionIdInput);

                const typeInput = document.createElement('input');
                typeInput.type = 'hidden';
                typeInput.name = 'download_section_' + type;
                typeInput.value = '1';
                form.appendChild(typeInput);

            } else {
                const typeInput = document.createElement('input');
                typeInput.type = 'hidden';
                typeInput.name = 'download_full_' + type;
                typeInput.value = '1';
                form.appendChild(typeInput);
            }
            
            document.body.appendChild(form);
            form.submit();
        }

        document.getElementById('download-full-csv').addEventListener('click', function(e) {
            e.preventDefault();
            submitFormForDownload('csv');
        });
        document.getElementById('download-full-excel').addEventListener('click', function(e) {
            e.preventDefault();
            submitFormForDownload('excel');
        });
        
        document.querySelectorAll('.download-section-csv-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const sectionId = this.getAttribute('data-section');
                submitFormForDownload('csv', sectionId);
            });
        });

        document.querySelectorAll('.download-section-excel-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const sectionId = this.getAttribute('data-section');
                submitFormForDownload('excel', sectionId);
            });
        });
        
        function generateAndSubmitPdf(htmlContent, filename) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?';
            const hiddenInputHtml = document.createElement('input');
            hiddenInputHtml.type = 'hidden';
            hiddenInputHtml.name = 'pdf_html';
            hiddenInputHtml.value = htmlContent;
            form.appendChild(hiddenInputHtml);
            const hiddenInputFilename = document.createElement('input');
            hiddenInputFilename.type = 'hidden';
            hiddenInputFilename.name = 'pdf_filename';
            hiddenInputFilename.value = filename;
            form.appendChild(hiddenInputFilename);
            const hiddenInputFlag = document.createElement('input');
            hiddenInputFlag.type = 'hidden';
            hiddenInputFlag.name = 'download_pdf';
            hiddenInputFlag.value = '1';
            form.appendChild(hiddenInputFlag);
            document.body.appendChild(form);
            form.submit();
        }

        document.querySelectorAll('.download-section-pdf-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const sectionId = this.getAttribute('data-section');
                let pdfHtml = '';
                let filename = '';
                
                if (sectionId === 'exam-chart') {
                    const examChartImg = examChart.toBase64Image();
                    const title = `Exam Performance Comparison <?php echo ($selected_std !== 'all') ? ' for Standard ' . htmlspecialchars($selected_std) : ''; ?> (<?php echo htmlspecialchars($selected_year ?? ''); ?>)`;
                    filename = `Exam_Performance_Comparison_${'<?php echo $selected_year ?? ''; ?>'}.pdf`;
                    pdfHtml = `
                        <!DOCTYPE html><html><head><title>${title}</title><style>
                            body{font-family:sans-serif} .header{text-align:center;margin-bottom:20px} h1,h2,h3{margin:0} h2{font-size:1.2em;font-weight:normal} table{width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12px} th,td{border:1px solid #ddd;padding:6px} th{background-color:#f2f2f2} .chart-container{text-align:center;margin-top:20px;page-break-inside:avoid} .chart-container img{max-width:95%;height:auto}
                        </style></head><body>
                            <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>${title}</h2></div>
                            <div class="chart-container"><img src="${examChartImg}"></div>
                        </body></html>`;
                } else if (sectionId === 'performance-table') {
                    const performanceTableHtml = document.getElementById('performanceTable').parentElement.innerHTML.replace(/ id="performanceTable"/g, '').replace(/<input.*?>/g, '');
                    const title = `Average Performance by Subject & Standard (<?php echo htmlspecialchars($selected_year ?? ''); ?>)`;
                    filename = `Subject_Performance_Report_${'<?php echo $selected_year ?? ''; ?>'}.pdf`;
                    pdfHtml = `
                        <!DOCTYPE html><html><head><title>${title}</title><style>
                            body{font-family:sans-serif} .header{text-align:center;margin-bottom:20px} h1,h2,h3{margin:0} h2{font-size:1.2em;font-weight:normal} table{width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12px} th,td{border:1px solid #ddd;padding:6px} th{background-color:#f2f2f2} .chart-container{text-align:center;margin-top:20px;page-break-inside:avoid} .chart-container img{max-width:95%;height:auto}
                        </style></head><body>
                            <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>${title}</h2></div>
                            ${performanceTableHtml}
                        </body></html>`;
                }
                
                if (pdfHtml && filename) {
                    generateAndSubmitPdf(pdfHtml, filename);
                }
            });
        });
    </script>
</body>
</html>