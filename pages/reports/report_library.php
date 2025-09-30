<?php
// --- FILE INCLUDES ---
require_once '../../includes/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// --- DYNAMIC REPORT DATA FETCHER ---
function get_full_report_data($conn, $school_id) {
    $data = [];
    $params = [];
    $where_clause_books = '';
    $where_clause_payroll = '';
    if ($school_id) {
        $where_clause_books = 'AND b.school_id = ?';
        $where_clause_payroll = 'WHERE school_id = ?';
        $params[] = $school_id;
    }

    // 1. Overdue Books and Fines
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
                      WHERE br.return_date IS NULL AND br.due_date < CURRENT_DATE $where_clause_books";
    $overdue_stmt = $conn->prepare($overdue_query);
    $overdue_stmt->execute($params);
    $data['overdue_books'] = $overdue_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $data['most_borrowed_books'] = $most_borrowed_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Total Salary Disbursed per Month
    $salary_query = "SELECT TO_CHAR(TO_DATE(salary_year || '-' || salary_month, 'YYYY-MM'), 'YYYY-MM') AS month, SUM(net_salary_paid) as total_disbursed
                     FROM (
                        SELECT salary_year, salary_month, net_salary_paid, school_id FROM teacher_payroll
                        UNION ALL
                        SELECT salary_year, salary_month, net_salary_paid, school_id FROM librarian_payroll
                        UNION ALL
                        SELECT salary_year, salary_month, net_salary_paid, school_id FROM principal_payroll
                     ) as all_salaries
                     $where_clause_payroll
                     GROUP BY month
                     ORDER BY month DESC
                     LIMIT 12";
    $salary_stmt = $conn->prepare($salary_query);
    $salary_stmt->execute($params);
    $data['salary_by_month'] = array_reverse($salary_stmt->fetchAll(PDO::FETCH_ASSOC));

    // 4. Summary of Incentives and Deductions
    $finance_summary_query = "SELECT SUM(total_incentives) as total_incentives, SUM(deduction_amount) as total_deductions
                              FROM (
                                SELECT total_incentives, deduction_amount, school_id FROM teacher_payroll
                                UNION ALL
                                SELECT total_incentives, deduction_amount, school_id FROM librarian_payroll
                                UNION ALL
                                SELECT total_incentives, deduction_amount, school_id FROM principal_payroll
                              ) as all_finances
                              $where_clause_payroll";
    $finance_stmt = $conn->prepare($finance_summary_query);
    $finance_stmt->execute($params);
    $data['incentive_summary'] = $finance_stmt->fetch(PDO::FETCH_ASSOC);

    return $data;
}

// --- Helper function to get data for a specific section (for CSV/Excel downloads) ---
function get_section_data($conn, $section_id, $school_id) {
    $params = [];
    $where_clause = '';
    $and_clause = '';
    if ($school_id) {
        $where_clause = 'WHERE school_id = ?';
        $and_clause = 'AND school_id = ?';
        $params[] = $school_id;
    }

    switch ($section_id) {
        case 'overdue-books':
            $query = "SELECT 
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
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            return [
                'title' => 'Overdue Books & Fines',
                'labels' => ['Book Title', 'Borrower', 'Due Date', 'Days Overdue', 'Fine Due'],
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        case 'most-borrowed':
            $query = "SELECT b.title, COUNT(br.record_id) as borrow_count 
                           FROM borrowing_records br
                           JOIN books b ON br.book_id = b.book_id
                           " . ($school_id ? "WHERE b.school_id = ?" : "") . "
                           GROUP BY b.book_id, b.title
                           ORDER BY borrow_count DESC
                           LIMIT 10";
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            return [
                'title' => 'Top 10 Most Borrowed Books',
                'labels' => ['Book Title', 'Times Borrowed'],
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        case 'salary-summary':
            $query = "SELECT TO_CHAR(TO_DATE(salary_year || '-' || salary_month, 'YYYY-MM'), 'YYYY-MM') AS month, SUM(net_salary_paid) as total_disbursed
                     FROM (
                        SELECT salary_year, salary_month, net_salary_paid, school_id FROM teacher_payroll
                        UNION ALL
                        SELECT salary_year, salary_month, net_salary_paid, school_id FROM librarian_payroll
                        UNION ALL
                        SELECT salary_year, salary_month, net_salary_paid, school_id FROM principal_payroll
                     ) as all_salaries
                     $where_clause
                     GROUP BY month
                     ORDER BY month DESC
                     LIMIT 12";
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            return [
                'title' => 'Total Salary Disbursed per Month',
                'labels' => ['Month', 'Total Disbursed (₹)'],
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        case 'financial-summary':
            $query = "SELECT SUM(total_incentives) as total_incentives, SUM(deduction_amount) as total_deductions
                              FROM (
                                SELECT total_incentives, deduction_amount, school_id FROM teacher_payroll
                                UNION ALL
                                SELECT total_incentives, deduction_amount, school_id FROM librarian_payroll
                                UNION ALL
                                SELECT total_incentives, deduction_amount, school_id FROM principal_payroll
                              ) as all_finances
                              $where_clause";
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'title' => 'Financial Summary',
                'labels' => ['Category', 'Amount (₹)'],
                'data' => [
                    ['Total Incentives', $summary['total_incentives']],
                    ['Total Deductions', $summary['total_deductions']]
                ]
            ];
        default:
            return null;
    }
}

// --- PHP GENERATION LOGIC ---
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if (!in_array($role, ['principal', 'superadmin'])) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

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

// --- CSV/EXCEL GENERATION LOGIC for INDIVIDUAL SECTIONS ---
if (isset($_POST['download_section_csv']) || isset($_POST['download_section_excel'])) {
    $file_type = isset($_POST['download_section_csv']) ? 'csv' : 'xls';
    $section_id = $_POST['section_id'];

    $role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
    $userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
    $school_id_download = ($role === 'principal') ? get_school_id_by_user_id($conn, $role, $userId) : (isset($_GET['school_id']) ? (int)$_GET['school_id'] : null);
    
    $section_data = get_section_data($conn, $section_id, $school_id_download);

    if ($file_type === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $section_data['title']) . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, $section_data['labels']);
        foreach ($section_data['data'] as $row) {
            fputcsv($output, is_array($row) ? $row : array_values($row));
        }
        fclose($output);
    } else { // Excel
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
if (isset($_POST['download_full_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Full_Library_Report.csv"');

    $role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
    $userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
    $school_id_download = ($role === 'principal') ? get_school_id_by_user_id($conn, $role, $userId) : (isset($_GET['school_id']) ? (int)$_GET['school_id'] : null);

    $report_data = get_full_report_data($conn, $school_id_download);
    
    $output = fopen('php://output', 'w');

    // Section 1: Overdue Books & Fines
    fputcsv($output, ['Overdue Books & Fines']);
    fputcsv($output, ['Book Title', 'Borrower', 'Due Date', 'Days Overdue', 'Fine Due']);
    foreach ($report_data['overdue_books'] as $row) {
        fputcsv($output, $row);
    }
    fputcsv($output, ['']); // Blank line for separation

    // Section 2: Most Borrowed Books
    fputcsv($output, ['Top 10 Most Borrowed Books']);
    fputcsv($output, ['Book Title', 'Times Borrowed']);
    foreach ($report_data['most_borrowed_books'] as $row) {
        fputcsv($output, $row);
    }
    fputcsv($output, ['']); // Blank line for separation

    // Section 3: Salary Disbursed per Month
    fputcsv($output, ['Total Salary Disbursed per Month']);
    fputcsv($output, ['Month', 'Total Disbursed (₹)']);
    foreach ($report_data['salary_by_month'] as $row) {
        fputcsv($output, $row);
    }
    fputcsv($output, ['']); // Blank line for separation
    
    // Section 4: Financial Summary
    fputcsv($output, ['Financial Summary']);
    fputcsv($output, ['Category', 'Amount (₹)']);
    fputcsv($output, ['Total Incentives', $report_data['incentive_summary']['total_incentives']]);
    fputcsv($output, ['Total Deductions', $report_data['incentive_summary']['total_deductions']]);

    fclose($output);
    exit();
}

// --- EXCEL GENERATION LOGIC for FULL REPORT ---
if (isset($_POST['download_full_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Full_Library_Report.xls"');

    $role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
    $userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
    $school_id_download = ($role === 'principal') ? get_school_id_by_user_id($conn, $role, $userId) : (isset($_GET['school_id']) ? (int)$_GET['school_id'] : null);

    $report_data = get_full_report_data($conn, $school_id_download);

    echo '<html><body><table>';

    // Section 1: Overdue Books & Fines
    echo '<tr><th colspan="5">Overdue Books & Fines</th></tr>';
    echo '<tr><th>Book Title</th><th>Borrower</th><th>Due Date</th><th>Days Overdue</th><th>Fine Due</th></tr>';
    foreach ($report_data['overdue_books'] as $row) {
        echo '<tr><td>' . htmlspecialchars($row['title']) . '</td><td>' . htmlspecialchars($row['borrower_name']) . '</td><td>' . htmlspecialchars($row['due_date']) . '</td><td>' . htmlspecialchars($row['days_overdue']) . '</td><td>' . htmlspecialchars($row['fine_due']) . '</td></tr>';
    }
    echo '<tr><td colspan="5"></td></tr>';

    // Section 2: Most Borrowed Books
    echo '<tr><th colspan="2">Top 10 Most Borrowed Books</th></tr>';
    echo '<tr><th>Book Title</th><th>Times Borrowed</th></tr>';
    foreach ($report_data['most_borrowed_books'] as $row) {
        echo '<tr><td>' . htmlspecialchars($row['title']) . '</td><td>' . htmlspecialchars($row['borrow_count']) . '</td></tr>';
    }
    echo '<tr><td colspan="2"></td></tr>';

    // Section 3: Salary Disbursed per Month
    echo '<tr><th colspan="2">Total Salary Disbursed per Month</th></tr>';
    echo '<tr><th>Month</th><th>Total Disbursed (₹)</th></tr>';
    foreach ($report_data['salary_by_month'] as $row) {
        echo '<tr><td>' . htmlspecialchars($row['month']) . '</td><td>' . htmlspecialchars($row['total_disbursed']) . '</td></tr>';
    }
    echo '<tr><td colspan="2"></td></tr>';

    // Section 4: Financial Summary
    echo '<tr><th colspan="2">Financial Summary</th></tr>';
    echo '<tr><th>Category</th><th>Amount (₹)</th></tr>';
    echo '<tr><td>Total Incentives</td><td>' . htmlspecialchars($report_data['incentive_summary']['total_incentives']) . '</td></tr>';
    echo '<tr><td>Total Deductions</td><td>' . htmlspecialchars($report_data['incentive_summary']['total_deductions']) . '</td></tr>';

    echo '</table></body></html>';
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

    $pdf_filename = "Library_Usage_Report.pdf";
    if ($school_id) {
        $safe_school_name = preg_replace('/[^a-zA-Z0-9]+/', '_', $school_name);
        $pdf_filename = trim($safe_school_name, '_') . "_Library_Report.pdf";
    } else if ($role === 'superadmin' && !$school_id) {
        $pdf_filename = "All_School_Library_Report.pdf";
    }

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
    <link rel="stylesheet" href="../../assets/css/responsive.css" />

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
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Total Salary Disbursed per Month (Last 12 Months)</h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-download fa-sm"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                        <a class="dropdown-item download-section-pdf-btn" href="#" data-section="salary-summary">PDF</a>
                                        <a class="dropdown-item download-section-csv-btn" href="#" data-section="salary-summary">CSV</a>
                                        <a class="dropdown-item download-section-excel-btn" href="#" data-section="salary-summary">Excel</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body"><div class="chart-area" style="height: 320px;"><canvas id="salaryChart"></canvas></div></div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card shadow mb-4" id="most-borrowed-section">
                                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                        <h6 class="m-0 font-weight-bold text-primary">Top 10 Most Borrowed Books</h6>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-download fa-sm"></i></button>
                                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                                <a class="dropdown-item download-section-pdf-btn" href="#" data-section="most-borrowed">PDF</a>
                                                <a class="dropdown-item download-section-csv-btn" href="#" data-section="most-borrowed">CSV</a>
                                                <a class="dropdown-item download-section-excel-btn" href="#" data-section="most-borrowed">Excel</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body"><div class="chart-bar" style="height: 400px;"><canvas id="mostBorrowedChart"></canvas></div></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card shadow mb-4" id="overdue-section">
                                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                        <h6 class="m-0 font-weight-bold text-primary">Overdue Books & Fines</h6>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-download fa-sm"></i></button>
                                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                                <a class="dropdown-item download-section-pdf-btn" href="#" data-section="overdue-books">PDF</a>
                                                <a class="dropdown-item download-section-csv-btn" href="#" data-section="overdue-books">CSV</a>
                                                <a class="dropdown-item download-section-excel-btn" href="#" data-section="overdue-books">Excel</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body" style="height: 484px; overflow-y: auto;"><div class="table-responsive"><table class="table table-bordered" id="overdueTable" width="100%" cellspacing="0"><thead><tr><th>Book Title</th><th>Borrower</th><th>Days Overdue</th><th>Fine Due</th></tr></thead><tbody><?php if(empty($overdue_books)): ?><tr><td colspan="4" class="text-center">No overdue books found.</td></tr><?php else: foreach ($overdue_books as $book): ?><tr><td><?php echo htmlspecialchars($book['title']); ?></td><td><?php echo htmlspecialchars($book['borrower_name']); ?></td><td><?php echo $book['days_overdue']; ?></td><td>₹<?php echo number_format($book['fine_due'], 2); ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>

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

    // --- Full Report Download Logic ---
    document.getElementById('download-full-pdf').addEventListener('click', function() {
        const salaryChartImg = salaryChart.toBase64Image();
        const borrowedChartImg = mostBorrowedChart.toBase64Image();
        const overdueTableHtml = document.getElementById('overdueTable').parentElement.innerHTML.replace(/ id="overdueTable"/g, '').replace(/<input.*?>/g, '');

        const pdfHtml = `
            <!DOCTYPE html><html><head><title>Library Usage Report</title><style>
                body{font-family:sans-serif} .header{text-align:center;margin-bottom:20px} h1,h2,h3{margin:0} h2{font-size:1.2em;font-weight:normal} table{width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12px} th,td{border:1px solid #ddd;padding:6px} th{background-color:#f2f2f2} .chart-container{text-align:center;margin-top:20px;page-break-inside:avoid} .chart-container img{max-width:95%;height:auto}
            </style></head><body>
                <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>Library & Financial Summary</h2></div>
                <h3>Financial Summary</h3>
                <table class="table table-bordered">
                    <tr><th>Total Incentives (All Time)</th><td>₹<?php echo number_format($incentive_summary['total_incentives'] ?? 0, 2); ?></td></tr>
                    <tr><th>Total Deductions (All Time)</th><td>₹<?php echo number_format($incentive_summary['total_deductions'] ?? 0, 2); ?></td></tr>
                </table>
                <h3>Salary Disbursed per Month</h3><div class="chart-container"><img src="${salaryChartImg}"></div>
                <h3>Most Borrowed Books</h3><div class="chart-container"><img src="${borrowedChartImg}"></div>
                <h3>Overdue Books & Fines</h3>${overdueTableHtml}
            </body></html>`;
        generateAndSubmitPdf(pdfHtml, mainPdfFilename);
    });

    document.getElementById('download-full-csv').addEventListener('click', function(e) {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?';
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'download_full_csv';
        hiddenInput.value = '1';
        form.appendChild(hiddenInput);
        document.body.appendChild(form);
        form.submit();
    });

    document.getElementById('download-full-excel').addEventListener('click', function(e) {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?';
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'download_full_excel';
        hiddenInput.value = '1';
        form.appendChild(hiddenInput);
        document.body.appendChild(form);
        form.submit();
    });
    
    // --- Individual Section Download Logic ---
    document.querySelectorAll('.download-section-pdf-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.dataset.section;
            const sectionElement = document.getElementById(sectionId);
            const title = sectionElement.querySelector('.card-header h6').textContent;
            let chartImage = '';
            let tableHtml = '';
            
            if (sectionId === 'salary-summary') chartImage = salaryChart.toBase64Image();
            if (sectionId === 'most-borrowed') chartImage = mostBorrowedChart.toBase64Image();
            if (sectionId === 'overdue-books') tableHtml = document.getElementById('overdueTable').parentElement.innerHTML.replace(/ id="overdueTable"/g, '').replace(/<input.*?>/g, '');
            
            const contentHtml = chartImage ? `<div class="chart-container"><img src="${chartImage}"></div>` : tableHtml;
            const sectionFilename = title.replace(/[^a-zA-Z0-9]+/g, '_') + '_Report.pdf';

            const pdfHtml = `
            <!DOCTYPE html><html><head><title>${title}</title><style>
                body { font-family: sans-serif; } .header { text-align: center; margin-bottom: 20px; }
                h1, h2 { margin: 0; } h2 { font-size: 1.2em; font-weight: normal; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
                th, td { border: 1px solid #ddd; padding: 6px; } th { background-color: #f2f2f2; }
                .chart-container { text-align: center; margin-top: 20px; page-break-inside: avoid; }
                .chart-container img { max-width: 100%; height: auto; }
            </style></head><body>
                <div class="header"><h1><?php echo htmlspecialchars($school_name); ?></h1><h2>${title}</h2></div>
                ${contentHtml}
            </body></html>`;
            
            generateAndSubmitPdf(pdfHtml, sectionFilename);
        });
    });

    document.querySelectorAll('.download-section-csv-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.dataset.section;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?';
            const hiddenInputType = document.createElement('input');
            hiddenInputType.type = 'hidden';
            hiddenInputType.name = 'download_section_csv';
            hiddenInputType.value = '1';
            form.appendChild(hiddenInputType);
            const hiddenInputId = document.createElement('input');
            hiddenInputId.type = 'hidden';
            hiddenInputId.name = 'section_id';
            hiddenInputId.value = sectionId;
            form.appendChild(hiddenInputId);
            document.body.appendChild(form);
            form.submit();
        });
    });

    document.querySelectorAll('.download-section-excel-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.dataset.section;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?';
            const hiddenInputType = document.createElement('input');
            hiddenInputType.type = 'hidden';
            hiddenInputType.name = 'download_section_excel';
            hiddenInputType.value = '1';
            form.appendChild(hiddenInputType);
            const hiddenInputId = document.createElement('input');
            hiddenInputId.type = 'hidden';
            hiddenInputId.name = 'section_id';
            hiddenInputId.value = sectionId;
            form.appendChild(hiddenInputId);
            document.body.appendChild(form);
            form.submit();
        });
    });
    </script>
</body>
</html>