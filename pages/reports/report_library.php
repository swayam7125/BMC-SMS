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
    $filename = $_POST['pdf_filename'] ?? 'Library_Usage_Report.pdf';
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

// --- INITIALIZE VARIABLES ---
$report_title = "Library Usage Report";
$school_name = "All Schools";
$errorMessage = '';
$overdue_books = [];
$most_borrowed_books = [];
$salary_by_month = [];
$incentive_summary = ['total_incentives' => 0, 'total_deductions' => 0];

// --- DYNAMIC WHERE CLAUSE ---
$where_clause_school = '';
$and_clause_school = '';
$params = [];
if ($school_id) {
    // Note: We often filter by the table alias, which will be specified in the query.
    $where_clause_school = 'WHERE school_id = ?';
    $and_clause_school = 'AND school_id = ?';
    $params[] = $school_id;
}

try {
    if ($school_id) {
        $school_stmt = $conn->prepare("SELECT school_name FROM school WHERE id = ?");
        $school_stmt->execute([$school_id]);
        $school_name = $school_stmt->fetchColumn();
        $report_title = "Library Report for " . htmlspecialchars($school_name);
    } else {
        $report_title = "Library Report (All Schools)";
    }

    // Determine the PDF filename
    $pdf_filename = "Library_Usage_Report.pdf";
    if ($school_id) {
        $safe_school_name = preg_replace('/[^a-zA-Z0-9]+/', '_', $school_name);
        $pdf_filename = trim($safe_school_name, '_') . "_Library_Report.pdf";
    } else if ($role === 'superadmin' && !$school_id) {
        $pdf_filename = "All_School_Library_Report.pdf";
    }

    // 1. Overdue Books and Fines (Using 'borrowing_records')
    $overdue_query = "SELECT 
                        b.title, 
                        COALESCE(s.student_name, t.teacher_name) AS borrower_name,
                        br.due_date,
                        (CURRENT_DATE - br.due_date) AS days_overdue,
                        br.fine_amount AS fine_due
                      FROM borrowing_records br
                      JOIN books b ON br.book_id = b.book_id
                      LEFT JOIN student s ON br.borrower_id = s.id AND br.borrower_role = 'student'
                      LEFT JOIN teacher t ON br.borrower_id = t.id AND br.borrower_role = 'teacher'
                      WHERE br.return_date IS NULL AND br.due_date < CURRENT_DATE " . ($school_id ? "AND b.school_id = ?" : "");
    $overdue_stmt = $conn->prepare($overdue_query);
    $overdue_stmt->execute($params);
    $overdue_books = $overdue_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Most Borrowed Books
    $most_borrowed_query = "SELECT b.title, COUNT(br.record_id) as borrow_count 
                           FROM borrowing_records br
                           JOIN books b ON br.book_id = b.book_id
                           " . ($school_id ? "WHERE b.school_id = ?" : "") . "
                           GROUP BY b.book_id, b.title
                           ORDER BY borrow_count DESC
                           LIMIT 10";
    $most_borrowed_stmt = $conn->prepare($most_borrowed_query);
    $most_borrowed_stmt->execute($params);
    $most_borrowed_books = $most_borrowed_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Total Salary Disbursed per Month (Using payroll tables)
    $salary_query = "SELECT month, SUM(net_salary_paid) as total_disbursed
                     FROM (
                        SELECT TO_CHAR(TO_DATE(salary_year || '-' || salary_month, 'YYYY-MM'), 'YYYY-MM') AS month, net_salary_paid, school_id FROM teacher_payroll
                        UNION ALL
                        SELECT TO_CHAR(TO_DATE(salary_year || '-' || salary_month, 'YYYY-MM'), 'YYYY-MM') AS month, net_salary_paid, school_id FROM librarian_payroll
                        UNION ALL
                        SELECT TO_CHAR(TO_DATE(salary_year || '-' || salary_month, 'YYYY-MM'), 'YYYY-MM') AS month, net_salary_paid, school_id FROM principal_payroll
                     ) as all_salaries
                     $where_clause_school
                     GROUP BY month
                     ORDER BY month DESC
                     LIMIT 12";
    $salary_stmt = $conn->prepare($salary_query);
    $salary_stmt->execute($params);
    $salary_by_month = array_reverse($salary_stmt->fetchAll(PDO::FETCH_ASSOC));

    // 4. Summary of Incentives and Deductions
    $finance_summary_query = "SELECT SUM(total_incentives) as total_incentives, SUM(deduction_amount) as total_deductions
                              FROM (
                                SELECT total_incentives, deduction_amount, school_id FROM teacher_payroll
                                UNION ALL
                                SELECT total_incentives, deduction_amount, school_id FROM librarian_payroll
                                UNION ALL
                                SELECT total_incentives, deduction_amount, school_id FROM principal_payroll
                              ) as all_finances
                              $where_clause_school";
    $finance_stmt = $conn->prepare($finance_summary_query);
    $finance_stmt->execute($params);
    $incentive_summary = $finance_stmt->fetch(PDO::FETCH_ASSOC);


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

                    <?php if ($role === 'superadmin'): ?>
                    <div class="card shadow mb-4">
                        <div class="card-body"><form method="GET" action="" class="form-inline"><div class="form-group mr-3"><label for="school_id" class="mr-2"><strong>Filter by School:</strong></label><select name="school_id" id="school_id" class="form-control" onchange="this.form.submit()"><option value="">-- All Schools --</option><?php foreach($schools as $school): ?><option value="<?php echo $school['id']; ?>" <?php if ($school['id'] == $school_id) echo 'selected'; ?>><?php echo htmlspecialchars($school['school_name']); ?></option><?php endforeach; ?></select></div></form></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo $errorMessage; ?></div><?php endif; ?>
                    
                    <div id="report-content">
                        <div class="row">
                            <div class="col-xl-6 col-md-6 mb-4">
                                <div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Incentives (All Time)</div><div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($incentive_summary['total_incentives'] ?? 0, 2); ?></div></div><div class="col-auto"><i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i></div></div></div></div>
                            </div>
                            <div class="col-xl-6 col-md-6 mb-4">
                                <div class="card border-left-danger shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Deductions (All Time)</div><div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($incentive_summary['total_deductions'] ?? 0, 2); ?></div></div><div class="col-auto"><i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i></div></div></div></div>
                            </div>
                        </div>

                        <div class="card shadow mb-4" id="salary-section">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Total Salary Disbursed per Month (Last 12 Months)</h6></div>
                            <div class="card-body"><div class="chart-area" style="height: 320px;"><canvas id="salaryChart"></canvas></div></div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card shadow mb-4" id="most-borrowed-section">
                                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Top 10 Most Borrowed Books</h6></div>
                                    <div class="card-body"><div class="chart-bar" style="height: 400px;"><canvas id="mostBorrowedChart"></canvas></div></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card shadow mb-4" id="overdue-section">
                                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Overdue Books & Fines</h6></div>
                                    <div class="card-body" style="height: 484px; overflow-y: auto;"><div class="table-responsive"><table class="table table-bordered" id="overdueTable" width="100%" cellspacing="0"><thead><tr><th>Book Title</th><th>Borrower</th><th>Days Overdue</th><th>Fine Due</th></tr></thead><tbody><?php if(empty($overdue_books)): ?><tr><td colspan="4" class="text-center">No overdue books found.</td></tr><?php else: foreach ($overdue_books as $book): ?><tr><td><?php echo htmlspecialchars($book['title']); ?></td><td><?php echo htmlspecialchars($book['borrower_name']); ?></td><td><?php echo $book['days_overdue']; ?></td><td>₹<?php echo number_format($book['fine_due'], 2); ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div>
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
    $(document).ready(function() { $('#overdueTable').DataTable({"order": [[2, "desc"]]}); });

    var salaryChart = new Chart(document.getElementById("salaryChart"), {type:'line', data:{labels:<?php echo json_encode(array_column($salary_by_month, 'month'));?>, datasets:[{label:"Salary Disbursed", data:<?php echo json_encode(array_column($salary_by_month, 'total_disbursed'));?>, lineTension:0.3, backgroundColor:"rgba(78, 115, 223, 0.05)", borderColor:"#4e73df", pointRadius:3}]}, options:{maintainAspectRatio:false, scales:{y:{ticks:{callback:function(value){return'₹'+value;}}}}, plugins:{legend:{display:false}}}});
    var mostBorrowedChart = new Chart(document.getElementById("mostBorrowedChart"), {type:'bar', data:{labels:<?php echo json_encode(array_column($most_borrowed_books, 'title'));?>, datasets:[{label:"Times Borrowed", data:<?php echo json_encode(array_column($most_borrowed_books, 'borrow_count'));?>, backgroundColor:"#4e73df"}]}, options:{maintainAspectRatio:false, scales:{y:{beginAtZero:true, ticks: {stepSize: 1}}}, plugins:{legend:{display:false}}}});

    function generateAndSubmitPdf(htmlContent, filename) {
        const form = document.createElement('form'); form.method = 'POST'; form.action = '?';
        const hiddenInputHtml = document.createElement('input'); hiddenInputHtml.type = 'hidden'; hiddenInputHtml.name = 'pdf_html'; hiddenInputHtml.value = htmlContent; form.appendChild(hiddenInputHtml);
        const hiddenInputFilename = document.createElement('input'); hiddenInputFilename.type = 'hidden'; hiddenInputFilename.name = 'pdf_filename'; hiddenInputFilename.value = filename; form.appendChild(hiddenInputFilename);
        const hiddenInputFlag = document.createElement('input'); hiddenInputFlag.type = 'hidden'; hiddenInputFlag.name = 'download_pdf'; hiddenInputFlag.value = '1'; form.appendChild(hiddenInputFlag);
        document.body.appendChild(form); form.submit();
    }

    const mainPdfFilename = '<?php echo $pdf_filename; ?>';

    document.getElementById('download-full-report-btn').addEventListener('click', function() {
        const salaryChartImg = salaryChart.toBase64Image();
        const borrowedChartImg = mostBorrowedChart.toBase64Image();
        const overdueTableHtml = document.getElementById('overdueTable').parentElement.innerHTML.replace(/ id="overdueTable"/g, '').replace(/<input.*?>/g, '');

        const pdfHtml = `
            <!DOCTYPE html><html><head><title>Library Usage Report</title><style>
                body{font-family:sans-serif} .header{text-align:center;margin-bottom:20px} h1,h2,h3{margin:0} h2{font-size:1.2em;font-weight:normal} table{width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12px} th,td{border:1px solid #ddd;padding:6px} th{background-color:#f2f2f2} .chart-container{text-align:center;margin-top:20px;page-break-inside:avoid} .chart-container img{max-width:95%;height:auto}
            </style></head><body>
                <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>Library & Financial Summary</h2></div>
                <h3>Salary Disbursed per Month</h3><div class="chart-container"><img src="${salaryChartImg}"></div>
                <h3>Most Borrowed Books</h3><div class="chart-container"><img src="${borrowedChartImg}"></div>
                <h3>Overdue Books & Fines</h3>${overdueTableHtml}
                <h3>Financial Summary</h3>
                <table class="table table-bordered">
                    <tr><th>Total Incentives (All Time)</th><td>₹<?php echo number_format($incentive_summary['total_incentives'] ?? 0, 2); ?></td></tr>
                    <tr><th>Total Deductions (All Time)</th><td>₹<?php echo number_format($incentive_summary['total_deductions'] ?? 0, 2); ?></td></tr>
                </table>
            </body></html>`;
        generateAndSubmitPdf(pdfHtml, mainPdfFilename);
    });
    </script>
</body>
</html>