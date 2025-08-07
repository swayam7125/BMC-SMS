<?php
// Enable all error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "encryption.php";
include_once "./includes/connect.php"; // Uses the new PDO $conn object

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
$totalSchools = 0; $totalPrincipals = 0; $totalTeachers = 0; $totalStudents = 0;
$totalAdmissions = 0; $totalStudentsLeft = 0; $salary = 0; $totalPresent = 0;
$totalLeaves = 0; $totalAbsent = 0; $totalBooks = 0; $issuedToday = 0;
$overdueBooks = 0; $totalLibraryMembers = 0;

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
                $salary = $teacherData['salary'] ?? 0;
                $studentStmt = $conn->prepare('SELECT COUNT(*) FROM "student" WHERE "school_id" = ?');
                $studentStmt->execute([$schoolId]);
                $totalStudents = $studentStmt->fetchColumn();
                $leavesStmt = $conn->prepare('SELECT COUNT(*) FROM "leave_applications" WHERE "teacher_id" = ? AND "status" = \'Approved\'');
                $leavesStmt->execute([$userId]);
                $totalLeaves = $leavesStmt->fetchColumn();
                $presentStmt = $conn->prepare('SELECT COUNT(DISTINCT "attendance_date") FROM "teacher_attendance" WHERE "teacher_id" = ? AND "status" = \'Present\'');
                $presentStmt->execute([$userId]);
                $totalPresent = $presentStmt->fetchColumn();
            }
            break;

        case 'librarian':
            $stmt = $conn->prepare('SELECT "school_id" FROM "librarian" WHERE "id" = ?');
            $stmt->execute([$userId]);
            $librarianData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($librarianData) {
                $schoolId = $librarianData['school_id'];
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
            $presentStmt = $conn->prepare("SELECT COUNT(DISTINCT \"attendance_date\") FROM \"attendance\" WHERE \"student_id\" = ? AND \"status\" = 'Present'");
            $presentStmt->execute([$userId]);
            $totalPresent = $presentStmt->fetchColumn();
            $absentStmt = $conn->prepare("SELECT COUNT(*) FROM \"attendance\" WHERE \"student_id\" = ? AND \"status\" = 'Absent'");
            $absentStmt->execute([$userId]);
            $totalAbsent = $absentStmt->fetchColumn();
            $totalLeaves = 0;
            break;
    }
} catch (PDOException $e) {
    error_log("Dashboard data fetch error: " . $e->getMessage());
}

$dashboard_notifications = [];
if ($userId && isset($conn)) {
    try {
        // This query now correctly fetches only UNREAD notifications
        $stmt_dash_notif = $conn->prepare('SELECT "id", "message", "link", "type", "created_at", "is_read" FROM "notifications" WHERE "user_id" = ? AND "is_read" = false ORDER BY "created_at" DESC LIMIT 6');
        $stmt_dash_notif->execute([$userId]);
        $dashboard_notifications = $stmt_dash_notif->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Dashboard notification fetch error: " . $e->getMessage());
    }
}
?>
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
        .notification-dashboard-list { max-height: 350px; overflow-y: auto; }
        .card-link { text-decoration: none; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    </div>
                    <div class="row">
                        <?php if ($role == 'superadmin'): ?>
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
                        <?php elseif ($role == 'principal'): ?>
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
                        <?php elseif ($role == 'teacher'): ?>
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
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Salary</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($salary); ?></div>
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
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Present Days</div>
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
                        <?php elseif ($role == 'librarian'): ?>
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
                                    <div class="card border-left-success shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Library Members</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalLibraryMembers; ?></div>
                                                </div>
                                                <div class="col-auto"><i class="fas fa-users-line fa-2x text-gray-300"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <a class="card-link" href="/BMC-SMS/pages/librarian/issue_return.php">
                                    <div class="card border-left-info shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Issued Today</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $issuedToday; ?></div>
                                                </div>
                                                <div class="col-auto"><i class="fas fa-right-from-bracket fa-2x text-gray-300"></i></div>
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
                        <?php elseif ($role == 'student'): ?>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <a class="card-link" href="dashboard.php">
                                    <div class="card border-left-primary shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">My Standard</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php
                                                        $student_std = 'N/A';
                                                        if ($userId && isset($conn)) {
                                                            $stmt_std = $conn->prepare('SELECT "std" FROM "student" WHERE "id" = ?');
                                                            $stmt_std->execute([$userId]);
                                                            if ($std_data = $stmt_std->fetch(PDO::FETCH_ASSOC)) {
                                                                $student_std = htmlspecialchars($std_data['std']);
                                                            }
                                                        }
                                                        echo $student_std;
                                                    ?></div>
                                                </div>
                                                <div class="col-auto"><i class="fas fa-book-open fa-2x text-gray-300"></i></div>
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
                                <div class="card-header py-3"><h6 id="chart-title" class="m-0 font-weight-bold text-primary">Overview</h6></div>
                                <div class="card-body"><div class="chart-area"><canvas id="myAreaChart" data-role="<?php echo htmlspecialchars($role ?? ''); ?>" data-user-id="<?php echo htmlspecialchars($userId ?? ''); ?>" data-base-url="/BMC-SMS/"></canvas></div></div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4 h-100">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Recent Unread Notifications</h6></div>
                                <div class="card-body d-flex flex-column">
                                    <div class="list-group list-group-flush notification-dashboard-list">
                                        <?php if (empty($dashboard_notifications)): ?>
                                            <div class="list-group-item d-flex align-items-center"><div class="mr-3"><div class="icon-circle bg-secondary"><i class="fas fa-info-circle text-white"></i></div></div><div><div class="small text-gray-500">All caught up!</div>No unread notifications.</div></div>
                                        <?php else: ?>
                                            <?php foreach ($dashboard_notifications as $notification):
                                                if (!function_exists('getNotificationIcon')) { function getNotificationIcon($type){
                                                    switch ($type) {
                                                        case 'borrow_status': return 'fas fa-book-reader text-white';
                                                        case 'leave_status': return 'fas fa-check-circle text-white';
                                                        case 'school_notice': return 'fas fa-chalkboard-teacher text-white';
                                                        case 'new_assignment': return 'fas fa-file-signature text-white';
                                                        default: return 'fas fa-bell text-white';
                                                    }
                                                }}
                                                $base_link = htmlspecialchars('/BMC-SMS/' . ltrim($notification['link'], '/'));
                                                $separator = (strpos($base_link, '?') === false) ? '?' : '&';
                                                $final_link = $base_link . $separator . 'notif_id=' . $notification['id'];
                                            ?>
                                                <a class="list-group-item list-group-item-action d-flex align-items-center" href="<?php echo $final_link; ?>">
                                                    <div class="mr-3"><div class="icon-circle bg-primary"><i class="<?php echo getNotificationIcon($notification['type']); ?>"></i></div></div>
                                                    <div>
                                                        <div class="small text-gray-500"><?php echo date('F j, Y', strtotime($notification['created_at'])); ?></div>
                                                        <span class="<?php echo !$notification['is_read'] ? 'font-weight-bold' : 'text-gray-800'; ?>"><?php echo htmlspecialchars($notification['message']); ?></span>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <a class="dropdown-item text-center small text-gray-500 mt-auto" href="/BMC-SMS/notification_history.php">Show All Notifications</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include './includes/footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "./includes/logout_modal.php" ?>
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/chart.js/Chart.min.js"></script>
    <script src="/BMC-SMS/assets/js/notification_window.js"></script>
    <script src="/BMC-SMS/assets/js/dynamic_chart.js"></script>
</body>
</html>
<?php $conn = null; ?>