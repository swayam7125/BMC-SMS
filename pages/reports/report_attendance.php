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

    // Get the dynamic filename from the POST request
    $filename = $_POST['pdf_filename'] ?? 'Attendance_Report.pdf';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Use the dynamic filename for the download
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

// --- MODIFIED LOGIC: Determine School ID based on role ---
$school_id = null;
$schools = [];
if ($role === 'principal') {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
}
// For Superadmins, School ID comes from a filter
else if ($role === 'superadmin') {
    // Fetch all schools for the filter dropdown
    $schools_stmt = $conn->query("SELECT id, school_name FROM school ORDER BY school_name ASC");
    $schools = $schools_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the selected school ID from the filter
    $school_id = isset($_GET['school_id']) && is_numeric($_GET['school_id']) ? (int)$_GET['school_id'] : null;
}


// --- INITIALIZE VARIABLES ---
$school_name = "School";
$report_title = "Attendance Analysis Report";
$errorMessage = '';
$selected_date = isset($_GET['report_date']) ? $_GET['report_date'] : date('Y-m-d');
$selected_month = isset($_GET['report_month']) ? $_GET['report_month'] : date('Y-m');
list($year_month, $month_val) = explode('-', $selected_month);
$low_attendance_threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 75;
$filter_staff_role = isset($_GET['staff_role']) ? $_GET['staff_role'] : 'all';

$daily_percentage = 0;
$monthly_percentage = 0;
$low_attendance_students = [];
$staff_attendance_summary = [];
$individual_staff_attendance = [];

// --- DYNAMIC WHERE CLAUSE ---
$where_clause = '';
$and_clause = '';
$params = [];
if ($school_id) {
    $where_clause = 'WHERE school_id = ?';
    $and_clause = 'AND school_id = ?';
    $params[] = $school_id;
}

try {
    if ($school_id) {
        $school_stmt = $conn->prepare("SELECT school_name FROM school WHERE id = ?");
        $school_stmt->execute([$school_id]);
        $school_name = $school_stmt->fetchColumn();
        $report_title = "Attendance Report for " . htmlspecialchars($school_name);
    } else {
        $school_name = "All Schools";
        $report_title = "Attendance Report (All Schools)";
    }

    // Logic to determine the PDF filename
    $pdf_filename = "Attendance_Report.pdf"; // Default fallback
    if ($school_id) {
        // This covers both principal and superadmin with a selected school
        $safe_school_name = preg_replace('/[^a-zA-Z0-9]+/', '_', $school_name);
        $pdf_filename = trim($safe_school_name, '_') . "_Attendance_Report.pdf";
    } else if ($role === 'superadmin' && !$school_id) {
        // This is only for the "All Schools" view for a superadmin
        $pdf_filename = "All_School_Attendance_Report.pdf";
    }

    // 1. Overall Daily Attendance Percentage
    $daily_query = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Present' THEN 1 WHEN status = 'Half Day' THEN 0.5 ELSE 0 END) as present_total FROM attendance WHERE attendance_date = ? $and_clause";
    $daily_stmt = $conn->prepare($daily_query);
    $daily_params = array_merge([$selected_date], $params);
    $daily_stmt->execute($daily_params);
    $daily_result = $daily_stmt->fetch(PDO::FETCH_ASSOC);
    if ($daily_result && $daily_result['total'] > 0) {
        $daily_percentage = round(($daily_result['present_total'] / $daily_result['total']) * 100, 2);
    }

    // 2. Overall Monthly Attendance Percentage
    $monthly_query = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Present' THEN 1 WHEN status = 'Half Day' THEN 0.5 ELSE 0 END) as present_total FROM attendance WHERE EXTRACT(YEAR FROM attendance_date) = ? AND EXTRACT(MONTH FROM attendance_date) = ? $and_clause";
    $monthly_stmt = $conn->prepare($monthly_query);
    $monthly_params = array_merge([$year_month, $month_val], $params);
    $monthly_stmt->execute($monthly_params);
    $monthly_result = $monthly_stmt->fetch(PDO::FETCH_ASSOC);
    if ($monthly_result && $monthly_result['total'] > 0) {
        $monthly_percentage = round(($monthly_result['present_total'] / $monthly_result['total']) * 100, 2);
    }

    // 3. Students with Low Attendance
    $low_att_query = "SELECT s.student_name, s.std, CAST(SUM(CASE WHEN a.status = 'Present' THEN 1.0 WHEN a.status = 'Half Day' THEN 0.5 ELSE 0 END) * 100.0 / COUNT(a.id) AS DECIMAL(5,2)) AS attendance_percentage
                    FROM attendance a JOIN student s ON a.student_id = s.id
                    " . ($school_id ? "WHERE s.school_id = ?" : "") . "
                    GROUP BY s.id, s.student_name, s.std
                    HAVING (SUM(CASE WHEN a.status = 'Present' THEN 1.0 WHEN a.status = 'Half Day' THEN 0.5 ELSE 0 END) * 100.0 / COUNT(a.id)) < ?";
    $low_att_stmt = $conn->prepare($low_att_query);
    $low_att_params = $school_id ? [$school_id, $low_attendance_threshold] : [$low_attendance_threshold];
    $low_att_stmt->execute($low_att_params);
    $low_attendance_students = $low_att_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Staff Attendance Data (Individual and Summary)
    $roles_to_query = [];
    if ($filter_staff_role === 'all') {
        $roles_to_query = ['teacher', 'librarian', 'principal', 'hr'];
    } else {
        $roles_to_query[] = $filter_staff_role;
    }

    foreach ($roles_to_query as $current_role) {
        $table_map = [
            'teacher' => ['name_col' => 'teacher_name', 'att_table' => 'teacher_attendance', 'id_col' => 'teacher_id'],
            'librarian' => ['name_col' => 'librarian_name', 'att_table' => 'librarian_attendance', 'id_col' => 'librarian_id'],
            'principal' => ['name_col' => 'principal_name', 'att_table' => 'principal_attendance', 'id_col' => 'principal_id'],
            // FIX: Corrected the column name from 'payroll_id' to 'hr_id' to match the database schema.
            'hr' => ['name_col' => 'hr_name', 'att_table' => 'hr_attendance', 'id_col' => 'hr_id'] 
        ];
        
        if (!isset($table_map[$current_role])) continue;

        $map = $table_map[$current_role];

        $staff_list_query = "SELECT id, {$map['name_col']} as name FROM {$current_role} $where_clause";
        $staff_list_stmt = $conn->prepare($staff_list_query);
        $staff_list_stmt->execute($params);
        $staff_members = $staff_list_stmt->fetchAll(PDO::FETCH_ASSOC);

        $staff_ids = array_column($staff_members, 'id');
        if (!empty($staff_ids)) {
            $placeholders = implode(',', array_fill(0, count($staff_ids), '?'));
            $att_query = "SELECT {$map['id_col']} as staff_id, status, COUNT(*) as count 
                        FROM {$map['att_table']} 
                        WHERE {$map['id_col']} IN ($placeholders) AND EXTRACT(YEAR FROM attendance_date) = ? AND EXTRACT(MONTH FROM attendance_date) = ? 
                        GROUP BY {$map['id_col']}, status";
            try {
                $att_stmt = $conn->prepare($att_query);
                $att_params_staff = array_merge($staff_ids, [$year_month, $month_val]);
                $att_stmt->execute($att_params_staff);
                $all_att_results = $att_stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

                foreach ($staff_members as $staff) {
                    $att_records = $all_att_results[$staff['id']] ?? [];
                    $counts = ['Present' => 0, 'Absent' => 0, 'Leave' => 0, 'Half Day' => 0];
                    foreach ($att_records as $row) {
                        if (isset($counts[$row['status']])) {
                            $counts[$row['status']] = $row['count'];
                        }
                    }
                    $individual_staff_attendance[] = [
                        'name' => $staff['name'],
                        'role' => ucfirst($current_role),
                        'present' => $counts['Present'] + ($counts['Half Day'] * 0.5),
                        'absent' => $counts['Absent'],
                        'leave' => $counts['Leave']
                    ];
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "relation \"{$map['att_table']}\" does not exist") !== false) {
                } else {
                    throw $e; 
                }
            }
        }
    }

    foreach ($individual_staff_attendance as $record) {
        $role_name = $record['role'];
        if (!isset($staff_attendance_summary[$role_name])) {
            $staff_attendance_summary[$role_name] = ['present' => 0, 'absent' => 0, 'leave' => 0];
        }
        $staff_attendance_summary[$role_name]['present'] += $record['present'];
        $staff_attendance_summary[$role_name]['absent'] += $record['absent'];
        $staff_attendance_summary[$role_name]['leave'] += $record['leave'];
    }

} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
}


if ($role === 'principal' && !$school_id) {
    $errorMessage = "Could not determine your school.";
}

// Calculate totals for the pie chart
$total_present = array_sum(array_column($individual_staff_attendance, 'present'));
$total_absent = array_sum(array_column($individual_staff_attendance, 'absent'));
$total_leave = array_sum(array_column($individual_staff_attendance, 'leave'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance Analysis Report</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .staff-list-container {
            max-height: 250px; /* Same as chart height */
            overflow-y: auto;
            position: relative;
        }
        .staff-list-container .table thead th {
            position: sticky;
            top: 0;
            background-color: #f8f9fc; /* A light background to match the theme */
            z-index: 1;
        }
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
                        <button id="download-full-report-btn" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Generate Full Report
                        </button>
                    </div>

                    <?php if ($role === 'superadmin'): ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="school_id" class="mr-2"><strong>Filter by School:</strong></label>
                                    <select name="school_id" id="school_id" class="form-control" onchange="this.form.submit()">
                                        <option value="">-- All Schools --</option>
                                        <?php foreach($schools as $school): ?>
                                            <option value="<?php echo $school['id']; ?>" <?php if ($school['id'] == $school_id) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($school['school_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo $errorMessage; ?></div><?php endif; ?>
                    
                    <div id="report-content">
                        <div class="card shadow mb-4" id="overall-attendance-section">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Overall School Attendance</h6>
                                <a href="#" class="download-section-btn" data-section="overall-attendance" title="Download this section"><i class="fas fa-download fa-sm"></i></a>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <form method="GET" class="form-inline">
                                            <input type="hidden" name="school_id" value="<?php echo $school_id; ?>">
                                            <label for="report_date" class="mr-2">Daily Attendance For:</label>
                                            <input type="date" id="report_date" name="report_date" value="<?php echo $selected_date; ?>" class="form-control mr-2" onchange="this.form.submit()">
                                        </form>
                                        <div class="mt-3"><h4><?php echo $daily_percentage; ?>%</h4><p class="text-muted">On <?php echo date("d M, Y", strtotime($selected_date)); ?></p></div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <form method="GET" class="form-inline">
                                            <input type="hidden" name="school_id" value="<?php echo $school_id; ?>">
                                            <label for="report_month" class="mr-2">Monthly Attendance For:</label>
                                            <input type="month" id="report_month" name="report_month" value="<?php echo $selected_month; ?>" class="form-control mr-2" onchange="this.form.submit()">
                                        </form>
                                        <div class="mt-3"><h4><?php echo $monthly_percentage; ?>%</h4><p class="text-muted">In <?php echo date("F Y", strtotime($selected_month)); ?></p></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow mb-4" id="low-attendance-section">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Students with Low Attendance</h6>
                                <div class="d-flex align-items-center">
                                    <form method="GET" class="form-inline mr-2">
                                        <input type="hidden" name="school_id" value="<?php echo $school_id; ?>">
                                        <label for="threshold" class="mr-2">Threshold (%):</label>
                                        <input type="number" name="threshold" id="threshold" value="<?php echo $low_attendance_threshold; ?>" class="form-control form-control-sm" style="width: 80px;" onchange="this.form.submit()">
                                    </form>
                                    <a href="#" class="download-section-btn" data-section="low-attendance" title="Download this section"><i class="fas fa-download fa-sm"></i></a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" id="low-attendance-table">
                                    <table class="table table-bordered">
                                        <thead><tr><th>Student Name</th><th>Standard</th><th>Attendance %</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($low_attendance_students as $student): ?>
                                            <tr><td><?php echo htmlspecialchars($student['student_name']); ?></td><td><?php echo htmlspecialchars($student['std']); ?></td><td><?php echo $student['attendance_percentage']; ?>%</td></tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card shadow mb-4" id="staff-attendance-section">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Staff Attendance (<?php echo date("F Y", strtotime($selected_month)); ?>)</h6>
                                <div class="d-flex align-items-center">
                                    <form method="GET" class="form-inline mr-2">
                                        <input type="hidden" name="school_id" value="<?php echo $school_id; ?>">
                                        <label for="staff_role" class="mr-2">Role:</label>
                                        <select name="staff_role" id="staff_role" class="form-control form-control-sm" onchange="this.form.submit()">
                                            <option value="all" <?php if ($filter_staff_role == 'all') echo 'selected'; ?>>All Staff</option>
                                            <option value="teacher" <?php if ($filter_staff_role == 'teacher') echo 'selected'; ?>>Teachers</option>
                                            <option value="librarian" <?php if ($filter_staff_role == 'librarian') echo 'selected'; ?>>Librarians</option>
                                            <option value="principal" <?php if ($filter_staff_role == 'principal') echo 'selected'; ?>>Principals</option>
                                            <option value="hr" <?php if ($filter_staff_role == 'hr') echo 'selected'; ?>>HR</option>
                                        </select>
                                    </form>
                                    <a href="#" class="download-section-btn" data-section="staff-attendance" title="Download this section"><i class="fas fa-download fa-sm"></i></a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="staff-list-container" id="staff-list-wrapper">
                                            <table class="table table-bordered">
                                                <thead><tr><th>Staff Name</th><th>Role</th><th>Present</th><th>Absent</th><th>On Leave</th></tr></thead>
                                                <tbody>
                                                <?php foreach($individual_staff_attendance as $record): ?>
                                                    <tr><td><?php echo htmlspecialchars($record['name']); ?></td><td><?php echo htmlspecialchars($record['role']); ?></td><td><?php echo $record['present']; ?></td><td><?php echo $record['absent']; ?></td><td><?php echo $record['leave']; ?></td></tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="chart-pie pt-4 pb-2" style="height: 250px;"><canvas id="staffAttendanceChart"></canvas></div>
                                    </div>
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
    <script>
    // Staff Attendance Pie Chart
    var ctxStaff = document.getElementById("staffAttendanceChart");
    var staffChart = new Chart(ctxStaff, {
        type: 'pie',
        data: {
            labels: ['Present', 'Absent', 'On Leave'],
            datasets: [{
                data: [<?php echo $total_present; ?>, <?php echo $total_absent; ?>, <?php echo $total_leave; ?>],
                backgroundColor: ['#1cc88a', '#e74a3b', '#f6c23e'],
                hoverBackgroundColor: ['#17a673', '#c73e31', '#d4a12c'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: {
                display: true,
                position: 'bottom'
            }
        },
    });

    // MODIFIED PDF Download Logic
    function generateAndSubmitPdf(htmlContent, filename) {
        const form = document.createElement('form');
        form.method = 'POST'; form.action = '?';
        
        const hiddenInputHtml = document.createElement('input');
        hiddenInputHtml.type = 'hidden'; hiddenInputHtml.name = 'pdf_html'; hiddenInputHtml.value = htmlContent;
        form.appendChild(hiddenInputHtml);
        
        // NEW: Add the filename to the form
        const hiddenInputFilename = document.createElement('input');
        hiddenInputFilename.type = 'hidden'; hiddenInputFilename.name = 'pdf_filename'; hiddenInputFilename.value = filename;
        form.appendChild(hiddenInputFilename);

        const hiddenInputFlag = document.createElement('input');
        hiddenInputFlag.type = 'hidden'; hiddenInputFlag.name = 'download_pdf'; hiddenInputFlag.value = '1';
        form.appendChild(hiddenInputFlag);
        
        document.body.appendChild(form);
        form.submit();
    }

    // Get the main filename determined by PHP
    const mainPdfFilename = '<?php echo $pdf_filename; ?>';

    document.getElementById('download-full-report-btn').addEventListener('click', function() {
        const staffChartImage = staffChart.toBase64Image();
        const overallHtml = `<div class="row"><div class="col-6"><h4>Daily: <?php echo $daily_percentage; ?>%</h4></div><div class="col-6"><h4>Monthly: <?php echo $monthly_percentage; ?>%</h4></div></div>`;
        const lowAttHtml = document.getElementById('low-attendance-table').innerHTML;
        const staffTableHtml = document.getElementById('staff-list-wrapper').innerHTML;

        const pdfHtml = `
            <!DOCTYPE html><html><head><title>Full Attendance Report</title><style>
                body{font-family:sans-serif} .header{text-align:center;margin-bottom:20px} h1,h2,h3{margin:0} h2{font-size:1.2em;font-weight:normal} table{width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12px} th,td{border:1px solid #ddd;padding:6px} th{background-color:#f2f2f2} .chart-container{text-align:center;margin-top:20px;page-break-inside:avoid} .chart-container img{max-width:100%;height:auto} .row{width:100%;display:table} .col-6{width:48%;display:table-cell;padding:1%}
            </style></head><body>
                <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>Full Attendance Report</h2></div>
                <h3>Overall Attendance</h3>${overallHtml}
                <h3>Students Below <?php echo $low_attendance_threshold; ?>% Attendance</h3>${lowAttHtml}
                <h3>Staff Attendance Summary (<?php echo date("F Y", strtotime($selected_month)); ?>)</h3>
                <div class="row"><div class="col-6">${staffTableHtml}</div><div class="col-6"><div class="chart-container"><img src="${staffChartImage}"></div></div></div>
            </body></html>`;
        
        // Pass the dynamic filename
        generateAndSubmitPdf(pdfHtml, mainPdfFilename);
    });

    document.querySelectorAll('.download-section-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.dataset.section;
            const sectionElement = document.getElementById(sectionId + '-section');
            const title = sectionElement.querySelector('.card-header h6').textContent;
            let contentHtml = '';

            if (sectionId === 'overall-attendance') {
                contentHtml = `<div class="row"><div class="col-6"><h4>Daily Attendance (<?php echo $selected_date; ?>):</h4><p style="font-size:24px;">${'<?php echo $daily_percentage; ?>'}</p></div><div class="col-6"><h4>Monthly Attendance (<?php echo $selected_month; ?>):</h4><p style="font-size:24px;">${'<?php echo $monthly_percentage; ?>'}</p></div></div>`;
            } else if (sectionId === 'low-attendance') {
                contentHtml = document.getElementById('low-attendance-table').innerHTML;
            } else if (sectionId === 'staff-attendance') {
                const staffChartImage = staffChart.toBase64Image();
                const staffTableHtml = document.getElementById('staff-list-wrapper').innerHTML;
                contentHtml = `<div class="row"><div class="col-6">${staffTableHtml}</div><div class="col-6"><div class="chart-container"><img src="${staffChartImage}"></div></div></div>`;
            }
            
            const sectionFilename = title.replace(/[^a-zA-Z0-9]+/g, '_') + '_Report.pdf';
            const pdfHtml = `
                <!DOCTYPE html><html><head><title>${title}</title><style>
                    body{font-family:sans-serif} .header{text-align:center;margin-bottom:20px} h1,h2{margin:0} h2{font-size:1.2em;font-weight:normal} table{width:100%;border-collapse:collapse;font-size:12px} th,td{border:1px solid #ddd;padding:6px} th{background-color:#f2f2f2} .chart-container{text-align:center;margin-top:20px} .chart-container img{max-width:100%;height:auto} .row{width:100%;display:table} .col-6{width:48%;display:table-cell;padding:1%}
                </style></head><body>
                    <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>${title}</h2></div>
                    ${contentHtml}
                </body></html>`;
            
            // Pass the dynamic filename for the section
            generateAndSubmitPdf(pdfHtml, sectionFilename);
        });
    });
    </script>
</body>
</html>