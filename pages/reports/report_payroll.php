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
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Payroll_Report.pdf", ["Attachment" => 1]);
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

// --- INITIALIZE VARIABLES ---
$school_name = "School";
$errorMessage = '';
$selected_month = isset($_GET['report_month']) ? $_GET['report_month'] : date('Y-m');
list($year, $month) = explode('-', $selected_month);

$total_salary_disbursed = 0;
$total_incentives = 0;
$total_deductions = 0;
$salary_by_role = ['Teachers' => 0, 'Librarians' => 0, 'Principals' => 0];
$detailed_payroll_data = [];
$incentive_details = [];

if ($school_id) {
    try {
        $school_stmt = $conn->prepare("SELECT school_name FROM school WHERE id = ?");
        $school_stmt->execute([$school_id]);
        $school_name = $school_stmt->fetchColumn();

        // 1. Total Salary Disbursed by Role
        $teacher_salary_query = "SELECT SUM(net_salary_paid) FROM teacher_payroll WHERE school_id = ? AND salary_year = ? AND salary_month = ?";
        $stmt = $conn->prepare($teacher_salary_query);
        $stmt->execute([$school_id, $year, $month]);
        $salary_by_role['Teachers'] = $stmt->fetchColumn() ?: 0;

        $librarian_salary_query = "SELECT SUM(net_salary_paid) FROM librarian_payroll WHERE school_id = ? AND salary_year = ? AND salary_month = ?";
        $stmt = $conn->prepare($librarian_salary_query);
        $stmt->execute([$school_id, $year, $month]);
        $salary_by_role['Librarians'] = $stmt->fetchColumn() ?: 0;
        
        $principal_salary_query = "SELECT SUM(net_salary_paid) FROM principal_payroll WHERE school_id = ? AND salary_year = ? AND salary_month = ?";
        $stmt = $conn->prepare($principal_salary_query);
        $stmt->execute([$school_id, $year, $month]);
        $salary_by_role['Principals'] = $stmt->fetchColumn() ?: 0;

        $total_salary_disbursed = array_sum($salary_by_role);

        // 2. Summary of Incentives and Deductions
        $incentives_query = "SELECT i.incentive_name, i.type, SUM(si.amount) as total_amount
                            FROM staff_incentives si
                            JOIN incentives i ON si.incentive_id = i.id
                            WHERE i.school_id = ? AND si.salary_year = ? AND si.salary_month = ?
                            GROUP BY i.incentive_name, i.type";
        $stmt = $conn->prepare($incentives_query);
        $stmt->execute([$school_id, $year, $month]);
        $incentive_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_incentives = array_sum(array_column(array_filter($incentive_details, fn($i) => $i['type'] == 'Addition'), 'total_amount'));
        $incentive_deductions = array_sum(array_column(array_filter($incentive_details, fn($i) => $i['type'] == 'Subtraction'), 'total_amount'));

        // Get deductions from payroll tables
        $teacher_deduction_query = "SELECT SUM(deduction_amount) FROM teacher_payroll WHERE school_id = ? AND salary_year = ? AND salary_month = ?";
        $stmt = $conn->prepare($teacher_deduction_query);
        $stmt->execute([$school_id, $year, $month]);
        $teacher_deductions = $stmt->fetchColumn() ?: 0;

        $librarian_deduction_query = "SELECT SUM(deduction_amount) FROM librarian_payroll WHERE school_id = ? AND salary_year = ? AND salary_month = ?";
        $stmt = $conn->prepare($librarian_deduction_query);
        $stmt->execute([$school_id, $year, $month]);
        $librarian_deductions = $stmt->fetchColumn() ?: 0;

        $principal_deduction_query = "SELECT SUM(deduction_amount) FROM principal_payroll WHERE school_id = ? AND salary_year = ? AND salary_month = ?";
        $stmt = $conn->prepare($principal_deduction_query);
        $stmt->execute([$school_id, $year, $month]);
        $principal_deductions = $stmt->fetchColumn() ?: 0;
        
        $total_deductions = $incentive_deductions + $teacher_deductions + $librarian_deductions + $principal_deductions;

        // 3. Detailed Payroll Data for Table
        $detailed_query = "
            SELECT t.teacher_name as name, 'Teacher' as role, tp.base_salary, tp.total_incentives, tp.deduction_amount, tp.net_salary_paid
            FROM teacher_payroll tp JOIN teacher t ON tp.teacher_id = t.id
            WHERE tp.school_id = ? AND tp.salary_year = ? AND tp.salary_month = ?
            UNION ALL
            SELECT l.librarian_name as name, 'Librarian' as role, lp.base_salary, lp.total_incentives, lp.deduction_amount, lp.net_salary_paid
            FROM librarian_payroll lp JOIN librarian l ON lp.librarian_id = l.id
            WHERE lp.school_id = ? AND lp.salary_year = ? AND lp.salary_month = ?
            UNION ALL
            SELECT p.principal_name as name, 'Principal' as role, pp.base_salary, pp.total_incentives, pp.deduction_amount, pp.net_salary_paid
            FROM principal_payroll pp JOIN principal p ON pp.principal_id = p.id
            WHERE pp.school_id = ? AND pp.salary_year = ? AND pp.salary_month = ?
        ";
        $stmt = $conn->prepare($detailed_query);
        $stmt->execute([$school_id, $year, $month, $school_id, $year, $month, $school_id, $year, $month]);
        $detailed_payroll_data = $stmt->fetchAll(PDO::FETCH_ASSOC);


    } catch (Exception $e) {
        $errorMessage = "An error occurred: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Financial & Payroll Summary</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Financial & Payroll Summary</h1>
                        <button id="download-full-report-btn" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
                        </button>
                    </div>

                    <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo $errorMessage; ?></div><?php endif; ?>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <form method="GET" class="form-inline">
                                <label for="report_month" class="mr-2">Report for Month:</label>
                                <input type="month" id="report_month" name="report_month" value="<?php echo $selected_month; ?>" class="form-control mr-2" onchange="this.form.submit()">
                            </form>
                        </div>
                    </div>

                    <div id="report-content">
                        <!-- Summary Cards -->
                        <div class="row">
                            <div class="col-xl-4 col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Salary Disbursed</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($total_salary_disbursed, 2); ?></div>
                                            </div>
                                            <div class="col-auto"><i class="fas fa-rupee-sign fa-2x text-gray-300"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6 mb-4">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Incentives</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($total_incentives, 2); ?></div>
                                            </div>
                                            <div class="col-auto"><i class="fas fa-gift fa-2x text-gray-300"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6 mb-4">
                                <div class="card border-left-danger shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Deductions</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($total_deductions, 2); ?></div>
                                            </div>
                                            <div class="col-auto"><i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Salary Distribution Chart -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Salary Distribution by Role</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie pt-4" style="height: 300px;">
                                    <canvas id="salaryDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Incentive and Deduction Details -->
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success">Incentives Breakdown</h6></div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead><tr><th>Incentive Name</th><th>Total Amount</th></tr></thead>
                                                <tbody>
                                                    <?php foreach($incentive_details as $item): if($item['type'] == 'Addition'): ?>
                                                    <tr><td><?php echo htmlspecialchars($item['incentive_name']); ?></td><td>₹<?php echo number_format($item['total_amount'], 2); ?></td></tr>
                                                    <?php endif; endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-danger">Deductions Breakdown</h6></div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead><tr><th>Deduction Name</th><th>Total Amount</th></tr></thead>
                                                <tbody>
                                                    <?php foreach($incentive_details as $item): if($item['type'] == 'Subtraction'): ?>
                                                    <tr><td><?php echo htmlspecialchars($item['incentive_name']); ?></td><td>₹<?php echo number_format($item['total_amount'], 2); ?></td></tr>
                                                    <?php endif; endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Payroll List -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Detailed Payroll for <?php echo date("F Y", strtotime($selected_month)); ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="detailedPayrollTable">
                                        <thead>
                                            <tr>
                                                <th>Staff Name</th>
                                                <th>Role</th>
                                                <th>Base Salary</th>
                                                <th>Incentives</th>
                                                <th>Deductions</th>
                                                <th>Net Salary Paid</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($detailed_payroll_data as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['role']); ?></td>
                                                <td>₹<?php echo number_format($row['base_salary'], 2); ?></td>
                                                <td>₹<?php echo number_format($row['total_incentives'], 2); ?></td>
                                                <td>₹<?php echo number_format($row['deduction_amount'], 2); ?></td>
                                                <td>₹<?php echo number_format($row['net_salary_paid'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
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
    $(document).ready(function() {
        $('#detailedPayrollTable').DataTable();
    });

    // Salary Distribution Donut Chart
    var ctxSalary = document.getElementById("salaryDistributionChart");
    var salaryChart = new Chart(ctxSalary, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($salary_by_role)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($salary_by_role)); ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
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
            },
            cutoutPercentage: 80,
        },
    });

    // PDF Download Logic
    document.getElementById('download-full-report-btn').addEventListener('click', function() {
        const chartImage = salaryChart.toBase64Image();
        const reportHtml = document.getElementById('report-content').innerHTML;

        // Clean up the HTML for PDF
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = reportHtml;
        // Remove the canvas element to replace it with an image
        const canvas = tempDiv.querySelector('canvas');
        if (canvas) {
            const img = document.createElement('img');
            img.src = chartImage;
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            canvas.parentNode.replaceChild(img, canvas);
        }

        const pdfHtml = `
            <!DOCTYPE html><html><head><title>Payroll Report</title><style>
                body{font-family:sans-serif} .header{text-align:center;margin-bottom:20px} h1,h2,h3{margin:0} h2{font-size:1.2em;font-weight:normal} .card{border:1px solid #ddd; margin-bottom:15px;} .card-header{background-color:#f2f2f2;padding:8px;font-weight:bold;} .card-body{padding:15px;} .row{width:100%;display:table;margin-bottom:15px;} .col-xl-4{width:32%;display:table-cell;padding:5px;} .text-xs{font-size:0.7rem;} .font-weight-bold{font-weight:bold;} .text-primary{color:#4e73df;} .text-success{color:#1cc88a;} .text-danger{color:#e74a3b;} .text-uppercase{text-transform:uppercase;} .mb-1{margin-bottom:0.25rem;} .h5{font-size:1.25rem;} .text-gray-800{color:#5a5c69;} img{max-width:100%;}
            </style></head><body>
                <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>Payroll Summary for <?php echo date("F Y", strtotime($selected_month)); ?></h2></div>
                ${tempDiv.innerHTML}
            </body></html>`;
        
        const form = document.createElement('form');
        form.method = 'POST'; form.action = '';
        const hiddenInputHtml = document.createElement('input');
        hiddenInputHtml.type = 'hidden'; hiddenInputHtml.name = 'pdf_html'; hiddenInputHtml.value = pdfHtml;
        form.appendChild(hiddenInputHtml);
        const hiddenInputFlag = document.createElement('input');
        hiddenInputFlag.type = 'hidden'; hiddenInputFlag.name = 'download_pdf'; hiddenInputFlag.value = '1';
        form.appendChild(hiddenInputFlag);
        document.body.appendChild(form);
        form.submit();
    });
    </script>
</body>
</html>
