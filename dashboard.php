<?php
require_once __DIR__ . "/includes/ajax_helpers.php";
require_once __DIR__ . "/includes/connect.php";
require_once __DIR__ . "/encryption.php";
require_once __DIR__ . '/includes/ajax_helpers.php'; 

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// This is the new check to see if the page is being requested by the AJAX script
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// All of your original PHP logic for fetching data remains here, unchanged.
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Formats a number into the Indian currency system (lakhs, crores).
 * @param float|int $number The number to format.
 * @return string The formatted number as a string with the Rupee symbol (e.g., ₹10,00,000).
 */
function formatIndianCurrency($number)
{
    $number = (string)round($number, 2);
    $parts = explode('.', $number);
    $integer_part = $parts[0];
    $decimal_part = isset($parts[1]) ? '.' . str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : '';

    $len = strlen($integer_part);
    if ($len <= 3) {
        return '₹' . $integer_part . $decimal_part;
    }

    $last_three = substr($integer_part, -3);
    $rest_units = substr($integer_part, 0, -3);

    // Format the rest of the number with commas after every two digits.
    $rest_formatted = strrev(implode(',', str_split(strrev($rest_units), 2)));

    return '₹' . $rest_formatted . ',' . $last_three . $decimal_part;
}

/**
 * MODIFIED: Calculates the number of effective working days, excluding holidays.
 * Assumes a 6-day work week (Monday to Saturday) and queries the database for holidays.
 * @param int $year The year (e.g., 2024).
 * @param int $month The month (1-12).
 * @param PDO $conn The database connection object.
 * @param int $schoolId The ID of the school to check for holidays.
 * @return int The total number of effective working days.
 */
function getWorkingDays($year, $month, $conn, $schoolId)
{
    // Step 1: Count total weekdays (Mon-Sat)
    $weekdays = 0;
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($i = 1; $i <= $daysInMonth; $i++) {
        // 'N' returns 1 for Monday through 7 for Sunday
        $dayOfWeek = date('N', strtotime("$year-$month-$i"));
        if ($dayOfWeek < 7) { // Counts Monday (1) to Saturday (6)
            $weekdays++;
        }
    }

    // Step 2: Count holidays that fall on a weekday
    $holiday_count = 0;
    if ($schoolId) {
        try {
            // In PostgreSQL, DOW is 0 for Sunday, 6 for Saturday. We exclude Sundays.
            $sql = "SELECT COUNT(*) FROM holidays 
                    WHERE school_id = ? 
                    AND EXTRACT(YEAR FROM holiday_date) = ? 
                    AND EXTRACT(MONTH FROM holiday_date) = ?
                    AND EXTRACT(DOW FROM holiday_date) != 0"; // Sunday is 0 in PostgreSQL DOW
            $stmt = $conn->prepare($sql);
            $stmt->execute([$schoolId, $year, $month]);
            $holiday_count = (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching holidays: " . $e->getMessage());
            return $weekdays; // Fallback to weekday count if query fails
        }
    }

    // Step 3: Calculate effective working days
    return $weekdays - $holiday_count;
}


$role = null;
$userId = null;
$userEmail = ''; // Initialize userEmail for consistent fetching
$schoolId = null; // Initialize schoolId

// Retrieve and decrypt user role and ID from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Fetch user email from the 'users' table using userId
if ($userId) {
    try {
        $stmt_email = $conn->prepare('SELECT "email" FROM "users" WHERE "id" = ?');
        $stmt_email->execute([$userId]);
        $user_data = $stmt_email->fetch(PDO::FETCH_ASSOC);
        if ($user_data) {
            $userEmail = $user_data['email'];
        }
    } catch (PDOException $e) {
        error_log("Dashboard user email fetch error: " . $e->getMessage());
    }
}

// Redirect to login if not logged in or role is not set
if (!$role) {
    header("Location: ./login.php");
    exit;
}

// Initialize variables for counts and user-specific data
$totalSchools = 0;
$totalPrincipals = 0;
$totalTeachers = 0;
$totalStudents = 0;
$totalAdmissions = 0;
$totalStudentsLeft = 0;
$salary = 0;
$totalPresent = 0;
$totalLeaves = 0;
$totalAbsent = 0;
$deduction_amount = 0;
$totalBooks = 0;
$issuedToday = 0;
$overdueBooks = 0;
$totalLibraryMembers = 0;
$monthly_present_days = 0;
$librarian_total_absent = 0;
$librarian_deduction_amount = 0;
$attendance_percentage = 0;
$minimum_attendance_percentage = 75.00; // Fallback for student role

// --- START OF PAYROLL ROLE ADDITION ---
$totalSalaryDisbursed = 0;
$totalPayrollTeachers = 0;
$totalPayrollLibrarians = 0;
$totalPayrollPrincipals = 0;
// --- END OF PAYROLL ROLE ADDITION ---


// Fetch data based on user role
try {
    switch ($role) {
        case 'superadmin':
            $totalSchools = $conn->query('SELECT COUNT(*) FROM "school"')->fetchColumn();
            $totalPrincipals = $conn->query('SELECT COUNT(*) FROM "principal"')->fetchColumn();
            $totalTeachers = $conn->query('SELECT COUNT(*) FROM "teacher"')->fetchColumn();
            $totalStudents = $conn->query('SELECT COUNT(*) FROM "student"')->fetchColumn();
            break;

        case 'principal':
            $stmt = $conn->prepare('SELECT "school_id" FROM "principal" WHERE "id" = ?');
            $stmt->execute([$userId]);
            $principalData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($principalData) {
                $schoolId = $principalData['school_id'];
                $teacherStmt = $conn->prepare('SELECT COUNT(*) FROM "teacher" WHERE "school_id" = ?');
                $teacherStmt->execute([$schoolId]);
                $totalTeachers = $teacherStmt->fetchColumn();
                $studentStmt = $conn->prepare('SELECT COUNT(*) FROM "student" WHERE "school_id" = ?');
                $studentStmt->execute([$schoolId]);
                $totalStudents = $studentStmt->fetchColumn();
                $studentLeftStmt = $conn->prepare('SELECT COUNT(*) FROM "deleted_students" WHERE "school_id" = ?');
                $studentLeftStmt->execute([$schoolId]);
                $totalStudentsLeft = $studentLeftStmt->fetchColumn();
                $totalAdmissions = $totalStudents + $totalStudentsLeft;
            }
            break;

        case 'teacher':
            $stmt = $conn->prepare('SELECT "school_id", "salary" FROM "teacher" WHERE "id" = ?');
            $stmt->execute([$userId]);
            $teacherData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($teacherData) {
                $schoolId = $teacherData['school_id'];
                $base_salary = $teacherData['salary'] ?? 0;

                $current_year = date('Y');
                $current_month = date('m');
                // MODIFIED: Call the new holiday-aware function
                $total_working_days = getWorkingDays($current_year, $current_month, $conn, $schoolId);

                // MODIFIED: Calculate payable days (Present = 1, Half Day = 0.5)
                $payableDaysStmt = $conn->prepare("
                    SELECT SUM(
                        CASE 
                            WHEN \"status\" = 'Present' THEN 1
                            WHEN \"status\" = 'Half Day' THEN 0.5
                            ELSE 0 
                        END
                    ) as payable_days 
                    FROM \"teacher_attendance\" 
                    WHERE \"teacher_id\" = ? 
                    AND EXTRACT(YEAR FROM \"attendance_date\") = ? 
                    AND EXTRACT(MONTH FROM \"attendance_date\") = ?
                ");
                $payableDaysStmt->execute([$userId, $current_year, $current_month]);
                $totalPresent = (float)$payableDaysStmt->fetchColumn();

                $absentStmt = $conn->prepare('SELECT COUNT(*) FROM "teacher_attendance" WHERE "teacher_id" = ? AND "status" = \'Absent\' AND EXTRACT(YEAR FROM "attendance_date") = ? AND EXTRACT(MONTH FROM "attendance_date") = ?');
                $absentStmt->execute([$userId, $current_year, $current_month]);
                $totalAbsent = $absentStmt->fetchColumn();

                $per_day_salary = 0;
                if ($total_working_days > 0 && $base_salary > 0) {
                    $per_day_salary = $base_salary / $total_working_days;
                }

                // MODIFIED: Salary is based on payable days
                $salary = $per_day_salary * $totalPresent;
                $deduction_amount = $per_day_salary * $totalAbsent;

                $studentStmt = $conn->prepare('SELECT COUNT(*) FROM "student" WHERE "school_id" = ?');
                $studentStmt->execute([$schoolId]);
                $totalStudents = $studentStmt->fetchColumn();
                $leavesStmt = $conn->prepare('SELECT COUNT(*) FROM "leave_applications" WHERE "teacher_id" = ? AND "status" = \'Approved\'');
                $leavesStmt->execute([$userId]);
                $totalLeaves = $leavesStmt->fetchColumn();
            }
            break;

        case 'hr':
            // First, get the school_id for the logged-in hr user
            $stmt = $conn->prepare('SELECT "school_id" FROM "hr" WHERE "id" = ?');
            $stmt->execute([$userId]);
            $payrollData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($payrollData && !empty($payrollData['school_id'])) {
                $schoolId = $payrollData['school_id'];
                $current_month = date('m');
                $current_year = date('Y');

                // Calculate total salary disbursed this month FOR THE ASSIGNED SCHOOL
                $salary_query = '
                    SELECT SUM(total_paid) as monthly_disbursement
                    FROM (
                        SELECT SUM(net_salary_paid) as total_paid FROM teacher_payroll WHERE salary_month = ? AND salary_year = ? AND school_id = ?
                        UNION ALL
                        SELECT SUM(net_salary_paid) as total_paid FROM librarian_payroll WHERE salary_month = ? AND salary_year = ? AND school_id = ?
                        UNION ALL
                        SELECT SUM(net_salary_paid) as total_paid FROM principal_payroll WHERE salary_month = ? AND salary_year = ? AND school_id = ?
                        UNION ALL
                        SELECT SUM(net_salary_paid) as total_paid FROM hr_payroll WHERE salary_month = ? AND salary_year = ? AND school_id = ?
                    ) as combined_payroll
                ';
                $salary_stmt = $conn->prepare($salary_query);
                $salary_stmt->execute([$current_month, $current_year, $schoolId, $current_month, $current_year, $schoolId, $current_month, $current_year, $schoolId, $current_month, $current_year, $schoolId]);
                $totalSalaryDisbursed = $salary_stmt->fetchColumn() ?: 0;

                // Get counts of staff FOR THE ASSIGNED SCHOOL
                $teacherStmt = $conn->prepare('SELECT COUNT(*) FROM "teacher" WHERE "school_id" = ?');
                $teacherStmt->execute([$schoolId]);
                $totalPayrollTeachers = $teacherStmt->fetchColumn();

                $librarianStmt = $conn->prepare('SELECT COUNT(*) FROM "librarian" WHERE "school_id" = ?');
                $librarianStmt->execute([$schoolId]);
                $totalPayrollLibrarians = $librarianStmt->fetchColumn();

                $principalStmt = $conn->prepare('SELECT COUNT(*) FROM "principal" WHERE "school_id" = ?');
                $principalStmt->execute([$schoolId]);
                $totalPayrollPrincipals = $principalStmt->fetchColumn();
            }
            break;

        case 'librarian':
            $stmt = $conn->prepare('SELECT "school_id", "salary" FROM "librarian" WHERE "id" = ?');
            $stmt->execute([$userId]);
            $librarianData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($librarianData) {
                $schoolId = $librarianData['school_id'];
                $base_salary = $librarianData['salary'] ?? 0;

                $current_year = date('Y');
                $current_month = date('m');
                // MODIFIED: Call the new holiday-aware function
                $total_working_days = getWorkingDays($current_year, $current_month, $conn, $schoolId);

                // MODIFIED: Calculate payable days for librarian (Present = 1, Half Day = 0.5)
                $payableDaysStmt = $conn->prepare("
                    SELECT SUM(
                        CASE 
                            WHEN \"status\" = 'Present' THEN 1
                            WHEN \"status\" = 'Half Day' THEN 0.5
                            ELSE 0 
                        END
                    ) as payable_days 
                    FROM \"librarian_attendance\" 
                    WHERE \"librarian_id\" = ? 
                    AND EXTRACT(YEAR FROM \"attendance_date\") = ? 
                    AND EXTRACT(MONTH FROM \"attendance_date\") = ?
                ");
                $payableDaysStmt->execute([$userId, $current_year, $current_month]);
                $monthly_present_days = (float)$payableDaysStmt->fetchColumn();

                $absentStmt = $conn->prepare('SELECT COUNT(*) FROM "librarian_attendance" WHERE "librarian_id" = ? AND "status" = \'Absent\' AND EXTRACT(YEAR FROM "attendance_date") = ? AND EXTRACT(MONTH FROM "attendance_date") = ?');
                $absentStmt->execute([$userId, $current_year, $current_month]);
                $librarian_total_absent = $absentStmt->fetchColumn();

                $per_day_salary = 0;
                if ($total_working_days > 0 && $base_salary > 0) {
                    $per_day_salary = $base_salary / $total_working_days;
                }

                // MODIFIED: Salary based on payable days
                $salary = $per_day_salary * $monthly_present_days;
                $librarian_deduction_amount = $per_day_salary * $librarian_total_absent;

                $booksStmt = $conn->prepare('SELECT COUNT(*) FROM "books" WHERE "school_id" = ?');
                $booksStmt->execute([$schoolId]);
                $totalBooks = $booksStmt->fetchColumn();
                $membersStmt = $conn->prepare('SELECT COUNT(DISTINCT br."borrower_id") FROM "borrowing_records" br JOIN "books" b ON br."book_id" = b."book_id" WHERE b."school_id" = ? AND br."borrower_role" IN (\'student\', \'teacher\')');
                $membersStmt->execute([$schoolId]);
                $totalLibraryMembers = $membersStmt->fetchColumn();
                $issuedTodayStmt = $conn->prepare('SELECT COUNT(*) FROM "borrowing_records" br JOIN "books" b ON br."book_id" = b."book_id" WHERE b."school_id" = ? AND br."checkout_date" = CURRENT_DATE');
                $issuedTodayStmt->execute([$schoolId]);
                $issuedToday = $issuedTodayStmt->fetchColumn();
                $overdueStmt = $conn->prepare('SELECT COUNT(*) FROM "borrowing_records" br JOIN "books" b ON br."book_id" = b."book_id" WHERE b."school_id" = ? AND br."due_date" < CURRENT_DATE AND br."is_returned" = false');
                $overdueStmt->execute([$schoolId]);
                $overdueBooks = $overdueStmt->fetchColumn();
            }
            break;

        case 'student':
            // Get student's school_id to fetch school settings
            $stmt_school = $conn->prepare('SELECT "school_id" FROM "student" WHERE "id" = ?');
            $stmt_school->execute([$userId]);
            $student_data = $stmt_school->fetch(PDO::FETCH_ASSOC);
            $schoolId = $student_data['school_id'] ?? null;

            // Fetch the minimum attendance percentage required by the school
            if ($schoolId) {
                $stmt_min_att = $conn->prepare('SELECT "minimum_attendance_percentage" FROM "school" WHERE "id" = ?');
                $stmt_min_att->execute([$schoolId]);
                $min_att_data = $stmt_min_att->fetch(PDO::FETCH_ASSOC);
                if ($min_att_data && isset($min_att_data['minimum_attendance_percentage'])) {
                    $minimum_attendance_percentage = (float)$min_att_data['minimum_attendance_percentage'];
                }
            }

            // Calculation for the "Attendance" card (Current Year)
            $current_year = date('Y');
            $attendance_percentage = 0;

            // Get total present days for the current year
            $present_yearly_stmt = $conn->prepare("SELECT COUNT(*) FROM \"attendance\" WHERE \"student_id\" = ? AND \"status\" = 'Present' AND EXTRACT(YEAR FROM \"attendance_date\") = ?");
            $present_yearly_stmt->execute([$userId, $current_year]);
            $yearly_present_count = (int)$present_yearly_stmt->fetchColumn();

            // Get total attendance days for the current year
            $total_yearly_stmt = $conn->prepare("SELECT COUNT(*) FROM \"attendance\" WHERE \"student_id\" = ? AND EXTRACT(YEAR FROM \"attendance_date\") = ?");
            $total_yearly_stmt->execute([$userId, $current_year]);
            $yearly_total_count = (int)$total_yearly_stmt->fetchColumn();

            if ($yearly_total_count > 0) {
                $attendance_percentage = round(($yearly_present_count / $yearly_total_count) * 100, 2);
            }

            // Calculations for existing cards (All time totals)
            $presentStmt = $conn->prepare("SELECT COUNT(DISTINCT \"attendance_date\") FROM \"attendance\" WHERE \"student_id\" = ? AND \"status\" = 'Present'");
            $presentStmt->execute([$userId]);
            $totalPresent = $presentStmt->fetchColumn();

            $absentStmt = $conn->prepare("SELECT COUNT(*) FROM \"attendance\" WHERE \"student_id\" = ? AND \"status\" = 'Absent'");
            $absentStmt->execute([$userId]);
            $totalAbsent = $absentStmt->fetchColumn();

            // FIX for Leaves card
            $leavesStmt = $conn->prepare("SELECT COUNT(*) FROM \"attendance\" WHERE \"student_id\" = ? AND \"status\" = 'Leave'");
            $leavesStmt->execute([$userId]);
            $totalLeaves = $leavesStmt->fetchColumn();
            break;
    }
} catch (PDOException $e) {
    error_log("Dashboard data fetch error: " . $e->getMessage());
}

$dashboard_notifications = [];
if ($userId && isset($conn)) {
    try {
        $stmt_dash_notif = $conn->prepare('SELECT "id", "message", "link", "type", "created_at", "is_read" FROM "notifications" WHERE "user_id" = ? ORDER BY "created_at" DESC LIMIT 6');
        $stmt_dash_notif->execute([$userId]);
        $dashboard_notifications = $stmt_dash_notif->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Dashboard notification fetch error: " . $e->getMessage());
    }
}

?>
<?php if (!$is_ajax_request): // If it's a normal page load, show the full HTML shell ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php
            $pageTitle = 'Dashboard';
            if ($role) $pageTitle = ucfirst($role) . ' - Dashboard';
            if ($role == 'superadmin') $pageTitle = 'Super Admin - Dashboard';
            echo htmlspecialchars($pageTitle);
            ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BMC-SMS/assets/css/notification_window.css">
    <link rel="stylesheet" href="/BMC-SMS/assets/css/sidebar.css">
    <link rel="stylesheet" href="/BMC-SMS/assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        .notification-dashboard-list {
            max-height: 350px;
            overflow-y: auto;
        }

        .card-link {
            text-decoration: none;
        }

        .absent-salary-cal {
            color: #dc3545;
            opacity: 0.8;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
<?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?>        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
<?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?>                <div id="main-content">
                    <div class="container-fluid">
<?php endif; ?>

                        <div class="d-sm-flex align-items-center justify-content-between mb-4">
                            <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                        </div>
                        <div class="row">
                            <?php if ($role == 'superadmin') : ?>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="./pages/school/school_list.php">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">TOTAL Schools</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalSchools; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-school fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="./pages/principal/principal_list.php">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">TOTAL Principals</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPrincipals; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-user-tie fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="./pages/teacher/teacher_list.php">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">TOTAL Teachers</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalTeachers; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-person-chalkboard fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="./pages/student/student_list.php">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">TOTAL Students</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudents; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-children fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php elseif ($role == 'principal') : ?>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="./pages/teacher/teacher_list.php">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">TOTAL Teachers</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalTeachers; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-person-chalkboard fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="./pages/student/student_list.php">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">TOTAL Students</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudents; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-children fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="./pages/student/student_list.php">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">TOTAL Admissions</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalAdmissions; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-user-plus fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="pages/past_record/past_student.php">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Students Left</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudentsLeft; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-right-from-bracket fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php elseif ($role == 'teacher') : ?>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="./pages/student/student_list.php">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">TOTAL Students</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudents; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-children fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="#">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Monthly Salary</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatIndianCurrency($salary); ?></div>
                                                        <?php if ($totalAbsent > 0) : ?>
                                                            <small class="d-block absent-salary-cal">
                                                                (-<?php echo formatIndianCurrency($deduction_amount); ?> for <?php echo $totalAbsent; ?> absent day/s)
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-indian-rupee-sign fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="./pages/teacher/view_my_attendance.php">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Payable Days (This Month)</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPresent; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-user-check fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="#">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">TOTAL Leaves</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalLeaves; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-envelope-circle-check fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                            <?php elseif ($role == 'hr') : ?>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-success shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">SALARY DISBURSED (THIS MONTH)</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatIndianCurrency($totalSalaryDisbursed); ?></div>
                                                </div>
                                                <div class="col-auto"><i class="fas fa-money-bill-wave fa-2x text-gray-300"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-primary shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">TOTAL PRINCIPALS</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPayrollPrincipals; ?></div>
                                                </div>
                                                <div class="col-auto"><i class="fas fa-user-tie fa-2x text-gray-300"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-info shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">TOTAL TEACHERS</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPayrollTeachers; ?></div>
                                                </div>
                                                <div class="col-auto"><i class="fas fa-person-chalkboard fa-2x text-gray-300"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-warning shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">TOTAL LIBRARIANS</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPayrollLibrarians; ?></div>
                                                </div>
                                                <div class="col-auto"><i class="fas fa-book-reader fa-2x text-gray-300"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($role == 'librarian') : ?>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="#">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Monthly Salary</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatIndianCurrency($salary); ?></div>

                                                        <?php if ($librarian_total_absent > 0) : ?>
                                                            <small class="d-block mt-1 absent-salary-cal">
                                                                (-<?php echo formatIndianCurrency($librarian_deduction_amount); ?> for <?php echo $librarian_total_absent; ?> absent day/s)
                                                            </small>
                                                        <?php endif; ?>

                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-indian-rupee-sign fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="#">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Payable Days (This Month)</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $monthly_present_days; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-user-check fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="/BMC-SMS/pages/librarian/book_list.php">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">TOTAL Books</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalBooks); ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-book-bookmark fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="/BMC-SMS/pages/librarian/issue_return.php">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Overdue Books</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overdueBooks; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-triangle-exclamation fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php elseif ($role == 'student') : ?>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="pages/student/view_lecture_attendance.php">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Attendance (This Year)</div>
                                                        <?php
                                                        // Determine the color class based on attendance percentage
                                                        $color_class = ($attendance_percentage < $minimum_attendance_percentage) ? 'text-danger' : 'text-gray-800';
                                                        ?>
                                                        <div class="h5 mb-0 font-weight-bold <?php echo $color_class; ?>">
                                                            <?php echo $attendance_percentage; ?>%
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="pages/student/view_lecture_attendance.php">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Present Days</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPresent; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-user-check fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="pages/student/view_lecture_attendance.php">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Leaves Taken</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalLeaves; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-envelope-circle-check fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <a class="card-link" href="pages/student/view_lecture_attendance.php">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Absent Lectures</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalAbsent; ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-calendar-xmark fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="row mb-4">
                            <div class="col-xl-8 col-lg-7">
                                <div class="card shadow mb-4 h-100">
                                    <div class="card-header py-3">
                                        <h6 id="chart-title" class="m-0 font-weight-bold text-primary">Overview</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-area"><canvas id="myAreaChart" data-role="<?php echo htmlspecialchars($role ?? ''); ?>" data-user-id="<?php echo htmlspecialchars($userId ?? ''); ?>" data-base-url="/BMC-SMS/"></canvas></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-5">
                                <div class="card shadow mb-4 h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Recent Notifications</h6>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="list-group list-group-flush notification-dashboard-list" id="dashboard-notifications-list">
                                        </div>
                                        <a class="dropdown-item text-center small text-gray-500 mt-auto" href="/BMC-SMS/notification_history.php">Show All Notifications</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if (!$is_ajax_request): // If it's a normal page load, close the HTML shell ?>
                    </div>
                </div>
<?php
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?>            </div>
        </div>
        <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
        <?php include_once "./includes/logout_modal.php" ?>
        <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
        <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
        <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
        <script src="/BMC-SMS/assets/vendor/chart.js/Chart.min.js"></script>
        <script src="/BMC-SMS/assets/js/dynamic_chart.js"></script>
        <script src="/BMC-SMS/assets/js/notification.js"></script>
        <script src="/BMC-SMS/assets/js/sidebar.js"></script>

        <script>
            // New script block to handle notification clicks on the dashboard
            document.addEventListener('DOMContentLoaded', function() {
                const base_path = '/BMC-SMS/';
                const notification_api_endpoint = base_path + 'includes/header.php'; // The API is now inside header.php

                // Use event delegation to handle clicks on links that are dynamically added
                document.getElementById('dashboard-notifications-list').addEventListener('click', function(event) {
                    // Find the clicked link
                    const link = event.target.closest('a.list-group-item');
                    if (!link) {
                        return; // Click was not on a notification link
                    }

                    const isUnread = link.classList.contains('unread');
                    const notifId = link.getAttribute('data-notif-id');

                    if (isUnread && notifId) {
                        event.preventDefault(); // Stop the default navigation
                        const targetUrl = link.getAttribute('href');

                        let formData = new FormData();
                        formData.append('action', 'mark_single_read');
                        formData.append('notif_id', notifId);

                        fetch(notification_api_endpoint, {
                                method: 'POST',
                                body: formData
                            })
                            .catch(error => console.error('Error marking dashboard notification as read:', error))
                            .finally(() => {
                                // Navigate after the API call is complete to ensure the state is updated
                                window.location.href = targetUrl;
                            });
                    }
                });
            });
        </script>

    </div> </body>
</html>
<?php endif; // End the check for normal page load ?>