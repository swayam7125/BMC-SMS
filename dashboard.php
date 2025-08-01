<?php
// Enable all error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "encryption.php";
include_once "./includes/connect.php"; // This path is likely correct for a PHP include

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
    $stmt_email = $conn->prepare("SELECT email FROM users WHERE id = ?");
    if ($stmt_email) {
        $stmt_email->bind_param("i", $userId);
        $stmt_email->execute();
        $result_email = $stmt_email->get_result();
        if ($result_email && $result_email->num_rows > 0) {
            $user_data = $result_email->fetch_assoc();
            $userEmail = $user_data['email'];
        }
        $stmt_email->close();
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


// Fetch data based on user role
switch ($role) {
    case 'bmc':
        // BMC role sees all global counts
        $sql = "SELECT COUNT(*) AS total FROM school";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalSchools = $row['total'];
        }

        $sql = "SELECT COUNT(*) AS total FROM principal";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalPrincipals = $row['total'];
        }

        $sql = "SELECT COUNT(*) AS total FROM teacher";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalTeachers = $row['total'];
        }

        $sql = "SELECT COUNT(*) AS total FROM student";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalStudents = $row['total'];
        }
        break;

    case 'schooladmin':
        // School Admin sees data related to their school
        $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $principalData = $result->fetch_assoc();
                $schoolId = $principalData['school_id'];

                // Get total teachers in this school
                $teacherStmt = $conn->prepare("SELECT COUNT(*) AS total FROM teacher WHERE school_id = ?");
                if ($teacherStmt) {
                    $teacherStmt->bind_param("i", $schoolId);
                    $teacherStmt->execute();
                    $teacherResult = $teacherStmt->get_result();
                    if ($teacherResult && $teacherResult->num_rows > 0) {
                        $teacherRow = $teacherResult->fetch_assoc();
                        $totalTeachers = $teacherRow['total'];
                    }
                    $teacherStmt->close();
                }

                // Get total current students in this school
                $studentStmt = $conn->prepare("SELECT COUNT(*) AS total FROM student WHERE school_id = ?");
                if ($studentStmt) {
                    $studentStmt->bind_param("i", $schoolId);
                    $studentStmt->execute();
                    $studentResult = $studentStmt->get_result();
                    if ($studentResult && $studentResult->num_rows > 0) {
                        $studentRow = $studentResult->fetch_assoc();
                        $totalStudents = $studentRow['total'];
                    }
                    $studentStmt->close();
                }

                // Get total students who have left from this school
                $studentLeftStmt = $conn->prepare("SELECT COUNT(*) AS total FROM deleted_students WHERE school_id = ?");
                if ($studentLeftStmt) {
                    $studentLeftStmt->bind_param("i", $schoolId);
                    $studentLeftStmt->execute();
                    $studentLeftResult = $studentLeftStmt->get_result();
                    if ($studentLeftResult && $studentLeftResult->num_rows > 0) {
                        $studentLeftRow = $studentLeftResult->fetch_assoc();
                        $totalStudentsLeft = $studentLeftRow['total'];
                    }
                    $studentLeftStmt->close();
                }

                // Calculate total admissions (current students + students who left)
                $totalAdmissions = $totalStudents + $totalStudentsLeft;
            }
            $stmt->close();
        }
        break;

    case 'teacher':
        // Teacher sees data related to their school and personal stats
        $stmt = $conn->prepare("SELECT school_id, salary FROM teacher WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $teacherData = $result->fetch_assoc();
                $schoolId = $teacherData['school_id'];
                $salary = $teacherData['salary'] ?? 0; // Fetch salary

                // Get total students in the teacher's school
                $studentStmt = $conn->prepare("SELECT COUNT(*) AS total FROM student WHERE school_id = ?");
                if ($studentStmt) {
                    $studentStmt->bind_param("i", $schoolId);
                    $studentStmt->execute();
                    $studentResult = $studentStmt->get_result();
                    if ($studentResult && $studentResult->num_rows > 0) {
                        $studentRow = $studentResult->fetch_assoc();
                        $totalStudents = $studentRow['total'];
                    }
                    $studentStmt->close();
                }

                // Get total approved leaves for this teacher
                $leavesStmt = $conn->prepare("SELECT COUNT(*) AS total FROM leave_applications WHERE teacher_id = ? AND status = 'Approved'");
                if ($leavesStmt) {
                    $leavesStmt->bind_param("i", $userId);
                    $leavesStmt->execute();
                    $leavesResult = $leavesStmt->get_result();
                    if ($leavesResult && $leavesResult->num_rows > 0) {
                        $leavesRow = $leavesResult->fetch_assoc();
                        $totalLeaves = $leavesRow['total'];
                    }
                    $leavesStmt->close();
                }

                // **CORRECTED LOGIC**: Get the teacher's own overall present days (counting each day once)
                $presentStmt = $conn->prepare("SELECT COUNT(DISTINCT attendance_date) AS total FROM teacher_attendance WHERE teacher_id = ? AND status = 'Present'");
                if ($presentStmt) {
                    $presentStmt->bind_param("i", $userId);
                    $presentStmt->execute();
                    $presentResult = $presentStmt->get_result();
                    if ($presentResult && $presentResult->num_rows > 0) {
                        $presentRow = $presentResult->fetch_assoc();
                        $totalPresent = $presentRow['total'];
                    }
                    $presentStmt->close();
                }
            }
            $stmt->close();
        }
        break;

    case 'student':
        // Student sees data related to their school and personal attendance

        // Get total unique days the student was present
        $presentStmt = $conn->prepare("SELECT COUNT(DISTINCT attendance_date) AS total FROM attendance WHERE student_id = ? AND status = 'Present'");
        if ($presentStmt) {
            $presentStmt->bind_param("i", $userId);
            $presentStmt->execute();
            $presentResult = $presentStmt->get_result();
            if ($presentResult && $presentResult->num_rows > 0) {
                $presentRow = $presentResult->fetch_assoc();
                $totalPresent = $presentRow['total'];
            }
            $presentStmt->close();
        }

        // Get total absent lectures for the logged-in student
        $absentStmt = $conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE student_id = ? AND status = 'Absent'");
        if ($absentStmt) {
            $absentStmt->bind_param("i", $userId);
            $absentStmt->execute();
            $absentResult = $absentStmt->get_result();
            if ($absentResult && $absentResult->num_rows > 0) {
                $absentRow = $absentResult->fetch_assoc();
                $totalAbsent = $absentRow['total'];
            }
            $absentStmt->close();
        }

        // NOTE: Total Leaves is set to 0 as there is no student leave application system.
        $totalLeaves = 0;
        break;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <?php
    $pageTitle = 'Dashboard'; // Default title
    if ($role == 'bmc') {
        $pageTitle = 'BMC - Dashboard';
    } elseif ($role == 'teacher') {
        $pageTitle = 'Teacher - Dashboard';
    } elseif ($role == 'student') {
        $pageTitle = 'Student - Dashboard';
    } elseif ($role == 'schooladmin') {
        $pageTitle = 'School Admin - Dashboard';
    }
    ?>
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <!-- Corrected Asset Paths -->

    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <!-- ** REMOVED CALENDAR CSS, ADDED NOTIFICATION CSS ** -->
    <link rel="stylesheet" href="/BMC-SMS/assets/css/notification_window.css">
    <link rel="stylesheet" href="/BMC-SMS/assets/css/sidebar.css">
    <link rel="stylesheet" href="/BMC-SMS/assets/css/scrollbar_hidden.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />


</head>

<body id="page-top">
    <div id="wrapper">

        <?php
        include './includes/sidebar.php';
        ?>
        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                <?php
                include './includes/header.php';
                ?>
                <div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    </div>

                    <div class="row">
                        <?php if ($role == 'bmc'): ?>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <a class="card-link" href="./pages/school/school_list.php">
                                    <div class="card border-left-primary shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        TOTAL Schools</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalSchools; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-school fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                        TOTAL Principals</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPrincipals; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                        TOTAL Teachers</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalTeachers; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-person-chalkboard fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                        TOTAL Students</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudents; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-children fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php elseif ($role == 'schooladmin'): ?>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <a class="card-link" href="./pages/teacher/teacher_list.php">
                                    <div class="card border-left-primary shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        TOTAL Teachers in School</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalTeachers; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-person-chalkboard fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                        TOTAL Students in School</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudents; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-children fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                        TOTAL Admissions in School</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalAdmissions; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                        TOTAL Students Left from School</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudentsLeft; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-right-from-bracket fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        TOTAL Students</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                        <?php echo $totalStudents; ?>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-children fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                        Salary</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($salary); ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-indian-rupee-sign fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                        Total Present Days</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPresent; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                        TOTAL Leaves</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalLeaves; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-envelope-circle-check fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        My Current Standard
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                        <?php
                                                        // Fetch student's standard
                                                        $student_std = 'N/A';
                                                        $stmt_std = $conn->prepare("SELECT std FROM student WHERE id = ?");
                                                        if ($stmt_std) {
                                                            $stmt_std->bind_param("i", $userId);
                                                            $stmt_std->execute();
                                                            $result_std = $stmt_std->get_result();
                                                            if ($result_std && $result_std->num_rows > 0) {
                                                                $std_data = $result_std->fetch_assoc();
                                                                $student_std = htmlspecialchars($std_data['std']);
                                                            }
                                                            $stmt_std->close();
                                                        }
                                                        echo $student_std;
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-book-open fa-2x text-gray-300"></i>
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
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                        TOTAL Present Days</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPresent; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                        TOTAL Leaves</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalLeaves; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-envelope-circle-check fa-2x text-gray-300"></i>
                                                </div>
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
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                        TOTAL Absent Lectures</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalAbsent; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-calendar-xmark fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Unified Chart Area -->
                    <div class="row">
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4 h-100">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 id="chart-title" class="m-0 font-weight-bold text-primary">Overview</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area">
                                        <!-- Pass PHP variables to JS via data attributes -->
                                        <canvas id="myAreaChart"
                                            data-role="<?php echo htmlspecialchars($role); ?>"
                                            data-user-id="<?php echo htmlspecialchars($userId); ?>"
                                            data-base-url="/BMC-SMS/">
                                        </canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ** START: NOTIFICATION WINDOW REPLACEMENT ** -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4 h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Notifications</h6>
                                </div>
                                <!-- ** EDIT: Simplified card-body for better flexbox control ** -->
                                <div id="notification-window-wrapper" class="card-body d-flex flex-column">
                                    <div class="notification-tabs">
                                        <!-- Tabs will be dynamically generated by JS -->
                                    </div>
                                    <div class="notification-body">
                                        <!-- Notification items will be dynamically generated by JS -->
                                        <div class="notification-empty-state">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Loading...</span>
                                            </div>
                                            <p class="mt-2">Loading notifications...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ** END: NOTIFICATION WINDOW REPLACEMENT ** -->
                    </div>
                </div>
            </div>
            <?php
            include './includes/footer.php';
            ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Corrected Script Paths -->
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/chart.js/Chart.min.js"></script>
    <!-- ** REMOVED CALENDAR JS, ADDED NOTIFICATION JS ** -->
    <script src="/BMC-SMS/assets/js/notification_window.js"></script>
    <script src="/BMC-SMS/assets/js/dynamic_chart.js"></script>

</body>

</html>
<?php
// Close the database connection at the very end of the script.
$conn->close();
?>