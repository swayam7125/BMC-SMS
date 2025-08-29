<?php
// --- PDF GENERATION SETUP ---
require_once '../../includes/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

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

include_once '../../includes/connect.php';
include_once '../../encryption.php';

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

// ⭐ START: MODIFIED LOGIC FOR FILTERS
// Fetch available academic years and standards for filters SYSTEM-WIDE, ignoring school selection.
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

// Set selected filters or defaults
$selected_year = isset($_GET['academic_year']) && in_array($_GET['academic_year'], $available_years) ? $_GET['academic_year'] : ($available_years[0] ?? null);
$selected_std = isset($_GET['std']) && in_array($_GET['std'], $available_standards) ? $_GET['std'] : 'all';

// --- DYNAMIC WHERE CLAUSE for DATA queries ---
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
// ⭐ END: MODIFIED LOGIC FOR FILTERS


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

        // 1. Average marks per subject and standard
        $perf_query = "SELECT std, subject_name, ROUND(SUM(marks_obtained) * 100.0 / SUM(total_marks), 2) AS average_percentage
                       FROM student_marks
                       $where_sql
                       GROUP BY std, subject_name
                       ORDER BY std, subject_name";
        $perf_stmt = $conn->prepare($perf_query);
        $perf_stmt->execute($data_params);
        $performance_data = $perf_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Comparison of performance across different exams
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

        // Process data for Chart.js
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

} catch (Exception $e) { $errorMessage = "An error occurred: " . $e->getMessage(); }
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
    <style>
        form.form-inline { display: flex; flex-flow: row wrap; align-items: center; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><?php echo htmlspecialchars($report_title); ?></h1>
                        <button id="download-full-report-btn" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Full Report</button>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline align-items-center">
                                <?php if ($role === 'superadmin'): ?>
                                <div class="form-group mr-3 mb-2">
                                    <label for="school_id" class="mr-2"><strong>School:</strong></label>
                                    <select name="school_id" id="school_id" class="form-control">
                                        <option value="">-- All Schools --</option>
                                        <?php foreach($schools as $school): ?>
                                            <option value="<?php echo $school['id']; ?>" <?php if ($school['id'] == $school_id) echo 'selected'; ?>>
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
                                        <?php else: foreach($available_years as $year): ?>
                                            <option value="<?php echo $year; ?>" <?php if ($year == $selected_year) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($year); ?>
                                            </option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-3 mb-2">
                                    <label for="std" class="mr-2"><strong>Standard:</strong></label>
                                    <select name="std" id="std" class="form-control">
                                        <option value="all">-- All --</option>
                                        <?php foreach($available_standards as $standard): ?>
                                            <option value="<?php echo $standard; ?>" <?php if ($standard == $selected_std) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($standard); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary mb-2">Apply Filters</button>
                            </form>
                        </div>
                    </div>

                    <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo $errorMessage; ?></div><?php endif; ?>
                    
                    <div id="report-content">
                        <div class="card shadow mb-4" id="exam-chart-section">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Exam Performance Comparison<?php echo ($selected_std !== 'all') ? ' for Standard ' . htmlspecialchars($selected_std) : ''; ?> (<?php echo htmlspecialchars($selected_year ?? ''); ?>)</h6></div>
                            <div class="card-body">
                                <?php if (empty($chart_datasets)): ?>
                                    <div class="text-center text-muted">No data available for the selected filters.</div>
                                <?php else: ?>
                                    <div class="chart-bar"><canvas id="examComparisonChart"></canvas></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card shadow mb-4" id="performance-table-section">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Average Performance by Subject & Standard (<?php echo htmlspecialchars($selected_year ?? ''); ?>)</h6></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="performanceTable" width="100%" cellspacing="0">
                                        <thead><tr><th>Standard</th><th>Subject</th><th>Average Performance (%)</th></tr></thead>
                                        <tbody>
                                            <?php if (empty($performance_data)): ?>
                                                <tr><td colspan="3" class="text-center">No performance data available.</td></tr>
                                            <?php else: foreach ($performance_data as $row): ?>
                                            <tr><td><?php echo htmlspecialchars($row['std']); ?></td><td><?php echo htmlspecialchars($row['subject_name']); ?></td><td><?php echo $row['average_percentage']; ?>%</td></tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
    $(document).ready(function() { $('#performanceTable').DataTable({"pageLength": 10}); });

    var examChart = new Chart(document.getElementById("examComparisonChart"), {
        type:'bar',
        data: {
            labels: <?php echo json_encode($chart_labels ?? []); ?>,
            datasets: <?php echo json_encode($chart_datasets ?? []); ?>
        },
        options:{maintainAspectRatio: false, scales:{y:{beginAtZero:true, max: 100, ticks: {callback: function(value) {return value + "%"}}}}, plugins:{legend:{position:'bottom'}}}
    });

    function generateAndSubmitPdf(htmlContent, filename) {
        const form = document.createElement('form'); form.method = 'POST'; form.action = '?';
        const hiddenInputHtml = document.createElement('input'); hiddenInputHtml.type = 'hidden'; hiddenInputHtml.name = 'pdf_html'; hiddenInputHtml.value = htmlContent; form.appendChild(hiddenInputHtml);
        const hiddenInputFilename = document.createElement('input'); hiddenInputFilename.type = 'hidden'; hiddenInputFilename.name = 'pdf_filename'; hiddenInputFilename.value = filename; form.appendChild(hiddenInputFilename);
        const hiddenInputFlag = document.createElement('input'); hiddenInputFlag.type = 'hidden'; hiddenInputFlag.name = 'download_pdf'; hiddenInputFlag.value = '1'; form.appendChild(hiddenInputFlag);
        document.body.appendChild(form); form.submit();
    }

    const mainPdfFilename = '<?php echo $pdf_filename; ?>';

    document.getElementById('download-full-report-btn').addEventListener('click', function() {
        const examChartImg = examChart.toBase64Image();
        const performanceTableHtml = document.getElementById('performanceTable').parentElement.innerHTML.replace(/ id="performanceTable"/g, '').replace(/<input.*?>/g, '');

        const pdfHtml = `
            <!DOCTYPE html><html><head><title>Academic Report</title><style>
                body{font-family:sans-serif} .header{text-align:center;margin-bottom:20px} h1,h2,h3{margin:0} h2{font-size:1.2em;font-weight:normal} table{width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12px} th,td{border:1px solid #ddd;padding:6px} th{background-color:#f2f2f2} .chart-container{text-align:center;margin-top:20px;page-break-inside:avoid} .chart-container img{max-width:95%;height:auto}
            </style></head><body>
                <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>Academic Performance Report (<?php echo htmlspecialchars($selected_year ?? ''); ?>)</h2></div>
                <h3>Exam Performance Comparison</h3><div class="chart-container"><img src="${examChartImg}"></div>
                <h3>Average Performance by Subject & Standard</h3>${performanceTableHtml}
            </body></html>`;
        generateAndSubmitPdf(pdfHtml, mainPdfFilename);
    });
    </script>
</body>
</html>