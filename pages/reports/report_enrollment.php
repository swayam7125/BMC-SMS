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
    
    // The HTML for the PDF is sent via a hidden input field
    $html = $_POST['pdf_html'];
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $dompdf->stream("Enrollment_Report.pdf", ["Attachment" => 1]);
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

// Get School ID
$school_id = null;
if ($role === 'principal') {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
}

$report_data = [];
$school_name = "School";
$errorMessage = '';
$labels = [];
$counts = [];
$area_chart_labels = [];
$area_chart_data = [];
$academic_years = [];
$selected_academic_year = date('Y') . '-' . (date('Y') + 1);
$new_admissions = 0;
$students_left = 0;

// New variables for Demographic Analysis
$gender_data = [];
$transport_data = [];

if (isset($_GET['academic_year']) && !empty($_GET['academic_year'])) {
    $selected_academic_year = $_GET['academic_year'];
}

if ($school_id) {
    try {
        $school_stmt = $conn->prepare("SELECT school_name FROM school WHERE id = ?");
        $school_stmt->execute([$school_id]);
        $school_name = $school_stmt->fetchColumn();

        // Query for the table and bar chart
        $query = "SELECT std, COUNT(*) as student_count FROM student WHERE school_id = ? GROUP BY std ORDER BY std ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$school_id]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($report_data as $row) {
            $labels[] = "Std " . $row['std'];
            $counts[] = $row['student_count'];
        }
        
        // Query for the area chart (enrollment trend over the last 5 years)
        $area_chart_query = "SELECT TO_CHAR(date_of_joining, 'YYYY') as year, COUNT(id) as new_students FROM student WHERE school_id = ? AND date_of_joining >= NOW() - INTERVAL '5 years' GROUP BY year ORDER BY year ASC";
        $area_stmt = $conn->prepare($area_chart_query);
        $area_stmt->execute([$school_id]);
        $area_chart_result = $area_stmt->fetchAll(PDO::FETCH_ASSOC);

        $enrollment_data = [];
        foreach ($area_chart_result as $row) {
            $enrollment_data[$row['year']] = $row['new_students'];
        }

        for ($i = 4; $i >= 0; $i--) {
            $year_key = date('Y', strtotime("-$i years"));
            $area_chart_labels[] = $year_key;
            $area_chart_data[] = $enrollment_data[$year_key] ?? 0;
        }

        // Queries for Admissions vs Left Report
        $years_query = "(SELECT DISTINCT academic_year FROM student WHERE school_id = :school_id AND academic_year IS NOT NULL) UNION (SELECT DISTINCT academic_year FROM deleted_students WHERE school_id = :school_id AND academic_year IS NOT NULL) ORDER BY academic_year DESC";
        $years_stmt = $conn->prepare($years_query);
        $years_stmt->execute([':school_id' => $school_id]);
        $academic_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);

        $admissions_query = "SELECT COUNT(id) FROM student WHERE school_id = ? AND academic_year = ?";
        $admissions_stmt = $conn->prepare($admissions_query);
        $admissions_stmt->execute([$school_id, $selected_academic_year]);
        $new_admissions = $admissions_stmt->fetchColumn();

        $left_query = "SELECT COUNT(id) FROM deleted_students WHERE school_id = ? AND academic_year = ?";
        $left_stmt = $conn->prepare($left_query);
        $left_stmt->execute([$school_id, $selected_academic_year]);
        $students_left = $left_stmt->fetchColumn();

        // --- NEW: Queries for Demographic Analysis ---
        $gender_query = "SELECT gender, COUNT(*) as count FROM student WHERE school_id = ? GROUP BY gender";
        $gender_stmt = $conn->prepare($gender_query);
        $gender_stmt->execute([$school_id]);
        $gender_data = $gender_stmt->fetchAll(PDO::FETCH_ASSOC);

        $transport_query = "SELECT transport_mode, COUNT(*) as count FROM student WHERE school_id = ? GROUP BY transport_mode";
        $transport_stmt = $conn->prepare($transport_query);
        $transport_stmt->execute([$school_id]);
        $transport_data = $transport_stmt->fetchAll(PDO::FETCH_ASSOC);


    } catch (Exception $e) {
        $errorMessage = "An error occurred: " . $e->getMessage();
    }
} else if ($role === 'principal') {
    $errorMessage = "Could not determine your school.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enrollment Report</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Enrollment Report</h1>
                        <button id="download-full-report-btn" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Generate Full Report
                        </button>
                    </div>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>
                    
                    <div id="report-content">
                        <!-- Full Width Area Chart -->
                        <div class="card shadow mb-4" id="yearly-trend-section">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Yearly Enrollment Trend (Last 5 Years)</h6>
                                <a href="#" class="download-section-btn" data-section="yearly-trend" title="Download this section"><i class="fas fa-download fa-sm"></i></a>
                            </div>
                            <div class="card-body">
                                <div class="chart-area" style="height: 320px;"><canvas id="enrollmentAreaChart"></canvas></div>
                            </div>
                        </div>

                        <!-- Admissions vs Left Report -->
                        <div class="card shadow mb-4" id="admissions-left-section">
                             <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Admissions vs. Left Students</h6>
                                <div class="d-flex align-items-center">
                                    <form method="GET" action="" class="form-inline mr-2">
                                        <div class="form-group">
                                            <label for="academic_year" class="mr-2">Year:</label>
                                            <select name="academic_year" id="academic_year" class="form-control form-control-sm" onchange="this.form.submit()">
                                                <?php foreach($academic_years as $year): ?>
                                                    <option value="<?php echo htmlspecialchars($year); ?>" <?php if ($year == $selected_academic_year) echo 'selected'; ?>>
                                                        <?php echo htmlspecialchars($year); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </form>
                                    <a href="#" class="download-section-btn" data-section="admissions-left" title="Download this section"><i class="fas fa-download fa-sm"></i></a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="table-responsive" id="admissions-table">
                                            <table class="table table-bordered">
                                                <thead><tr><th>Category</th><th>Count</th></tr></thead>
                                                <tbody>
                                                    <tr><td>New Admissions</td><td><?php echo $new_admissions; ?></td></tr>
                                                    <tr><td>Students Who Left</td><td><?php echo $students_left; ?></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-lg-6"><div class="chart-bar" style="height: 250px;"><canvas id="admissionsLeftChart"></canvas></div></div>
                                </div>
                            </div>
                        </div>

                        <!-- Demographic Analysis Section -->
                        <div class="card shadow mb-4" id="demographics-section">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Demographic Analysis</h6>
                                <a href="#" class="download-section-btn" data-section="demographics" title="Download this section"><i class="fas fa-download fa-sm"></i></a>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <h6 class="font-weight-bold text-center">Gender Ratio</h6>
                                        <div class="chart-pie pt-4" style="height: 250px;"><canvas id="genderPieChart"></canvas></div>
                                        <div class="table-responsive mt-4" id="gender-table">
                                            <table class="table table-sm table-bordered">
                                                <tbody>
                                                <?php foreach($gender_data as $row) echo "<tr><td>".htmlspecialchars($row['gender'])."</td><td>".$row['count']."</td></tr>"; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <h6 class="font-weight-bold text-center">Transport Mode Usage</h6>
                                        <div class="chart-pie pt-4" style="height: 250px;"><canvas id="transportPieChart"></canvas></div>
                                        <div class="table-responsive mt-4" id="transport-table">
                                            <table class="table table-sm table-bordered">
                                                <tbody>
                                                <?php foreach($transport_data as $row) echo "<tr><td>".htmlspecialchars($row['transport_mode'])."</td><td>".$row['count']."</td></tr>"; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Student Count Section -->
                        <div class="card shadow mb-4" id="std-count-section">
                             <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Student Count per Standard</h6>
                                <a href="#" class="download-section-btn" data-section="std-count" title="Download this section"><i class="fas fa-download fa-sm"></i></a>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="table-responsive" id="std-count-table">
                                            <table class="table table-bordered">
                                                <thead><tr><th>Standard</th><th>Number of Students</th></tr></thead>
                                                <tbody>
                                                    <?php foreach ($report_data as $row): ?>
                                                    <tr><td><?php echo htmlspecialchars($row['std']); ?></td><td><?php echo $row['student_count']; ?></td></tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-lg-6"><div class="chart-bar" style="height: 320px;"><canvas id="enrollmentBarChart"></canvas></div></div>
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
    // Chart Instances
    var myBarChart = new Chart(document.getElementById("enrollmentBarChart"), { type: 'bar', data: { labels: <?php echo json_encode($labels); ?>, datasets: [{ label: "Students", data: <?php echo json_encode($counts); ?>, backgroundColor: "#4e73df", barPercentage: 0.6 }] }, options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } } });
    var myAreaChart = new Chart(document.getElementById("enrollmentAreaChart"), { type: 'line', data: { labels: <?php echo json_encode($area_chart_labels); ?>, datasets: [{ label: "Enrollments", data: <?php echo json_encode($area_chart_data); ?>, lineTension: 0.3, backgroundColor: "rgba(78, 115, 223, 0.05)", borderColor: "#4e73df", pointRadius: 3 }] }, options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } } });
    var admissionsChart = new Chart(document.getElementById("admissionsLeftChart"), { type: 'bar', data: { labels: ['New Admissions', 'Students Left'], datasets: [{ label: 'Count', data: [<?php echo $new_admissions; ?>, <?php echo $students_left; ?>], backgroundColor: ['#1cc88a', '#e74a3b'] }] }, options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } } });
    var genderChart = new Chart(document.getElementById("genderPieChart"), { type: 'pie', data: { labels: <?php echo json_encode(array_column($gender_data, 'gender')); ?>, datasets: [{ data: <?php echo json_encode(array_column($gender_data, 'count')); ?>, backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'] }] }, options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
    var transportChart = new Chart(document.getElementById("transportPieChart"), { type: 'pie', data: { labels: <?php echo json_encode(array_column($transport_data, 'transport_mode')); ?>, datasets: [{ data: <?php echo json_encode(array_column($transport_data, 'count')); ?>, backgroundColor: ['#f6c23e', '#e74a3b', '#858796'] }] }, options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });

    // --- PDF Download Logic ---
    function generateAndSubmitPdf(htmlContent) {
        const form = document.createElement('form');
        form.method = 'POST'; form.action = '?';
        const hiddenInputHtml = document.createElement('input');
        hiddenInputHtml.type = 'hidden'; hiddenInputHtml.name = 'pdf_html'; hiddenInputHtml.value = htmlContent;
        form.appendChild(hiddenInputHtml);
        const hiddenInputFlag = document.createElement('input');
        hiddenInputFlag.type = 'hidden'; hiddenInputFlag.name = 'download_pdf'; hiddenInputFlag.value = '1';
        form.appendChild(hiddenInputFlag);
        document.body.appendChild(form);
        form.submit();
    }

    // Listener for the main download button
    document.getElementById('download-full-report-btn').addEventListener('click', function() {
        const pdfHtml = `
            <!DOCTYPE html><html><head><title>Full Enrollment Report</title><style>
                body { font-family: sans-serif; } .header { text-align: center; margin-bottom: 20px; }
                .header h1 { margin: 0; } .header h2 { margin: 0; font-size: 1.2em; font-weight: normal; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
                th, td { border: 1px solid #ddd; padding: 6px; } th { background-color: #f2f2f2; }
                .chart-container { text-align: center; margin-top: 20px; page-break-inside: avoid; }
                .chart-container img { max-width: 100%; height: auto; }
                .row { width: 100%; display: table; margin-top: 20px; } .col-6 { width: 48%; display: table-cell; padding: 1%; }
            </style></head><body>
                <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>Full Enrollment Report</h2></div>
                <h3>Yearly Enrollment Trend</h3><div class="chart-container"><img src="${myAreaChart.toBase64Image()}"></div>
                <h3>Admissions vs. Left Students for ${'<?php echo $selected_academic_year; ?>'}</h3><div class="row"><div class="col-6">${document.getElementById('admissions-table').innerHTML}</div><div class="col-6"><div class="chart-container"><img src="${admissionsChart.toBase64Image()}"></div></div></div>
                <h3>Demographic Analysis</h3><div class="row"><div class="col-6">${document.getElementById('gender-table').innerHTML}<div class="chart-container"><img src="${genderChart.toBase64Image()}"></div></div><div class="col-6">${document.getElementById('transport-table').innerHTML}<div class="chart-container"><img src="${transportChart.toBase64Image()}"></div></div></div>
                <h3>Student Count per Standard</h3><div class="row"><div class="col-6">${document.getElementById('std-count-table').innerHTML}</div><div class="col-6"><div class="chart-container"><img src="${myBarChart.toBase64Image()}"></div></div></div>
            </body></html>`;
        generateAndSubmitPdf(pdfHtml);
    });

    // Listener for individual section download buttons
    document.querySelectorAll('.download-section-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.dataset.section;
            const sectionElement = document.getElementById(sectionId + '-section');
            const title = sectionElement.querySelector('.card-header h6').textContent;
            
            let chartImage = '';
            let tableHtml = '';
            let contentHtml = '';

            if (sectionId === 'yearly-trend') chartImage = myAreaChart.toBase64Image();
            if (sectionId === 'admissions-left') chartImage = admissionsChart.toBase64Image();
            if (sectionId === 'demographics') {
                 const genderImg = genderChart.toBase64Image();
                 const transportImg = transportChart.toBase64Image();
                 const genderTable = document.getElementById('gender-table').innerHTML;
                 const transportTable = document.getElementById('transport-table').innerHTML;
                 contentHtml = `<div class="row"><div class="col-6">${genderTable}<div class="chart-container"><img src="${genderImg}"></div></div><div class="col-6">${transportTable}<div class="chart-container"><img src="${transportImg}"></div></div></div>`;
            }
            if (sectionId === 'std-count') chartImage = myBarChart.toBase64Image();
            
            const tableElement = sectionElement.querySelector('.table-responsive');
            if(tableElement) tableHtml = tableElement.innerHTML;
            
            if (!contentHtml) {
                contentHtml = `<div class="row"><div class="col-6">${tableHtml}</div><div class="col-6"><div class="chart-container"><img src="${chartImage}"></div></div></div>`;
                if(!tableHtml) contentHtml = `<div class="chart-container"><img src="${chartImage}"></div>`;
            }

            const pdfHtml = `
                <!DOCTYPE html><html><head><title>${title}</title><style>
                    body { font-family: sans-serif; } .header { text-align: center; margin-bottom: 20px; }
                    h1, h2 { margin: 0; } h2 { font-size: 1.2em; font-weight: normal; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
                    th, td { border: 1px solid #ddd; padding: 6px; } th { background-color: #f2f2f2; }
                    .chart-container { text-align: center; margin-top: 20px; page-break-inside: avoid; }
                    .chart-container img { max-width: 100%; height: auto; }
                    .row { width: 100%; display: table; } .col-6 { width: 48%; display: table-cell; padding: 1%; }
                </style></head><body>
                    <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>${title}</h2></div>
                    ${contentHtml}
                </body></html>`;
            generateAndSubmitPdf(pdfHtml);
        });
    });
    </script>
</body>
</html>
