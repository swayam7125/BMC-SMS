<?php
// Enable all error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "encryption.php";
include_once "./includes/connect.php"; // Include your database connection file

$role = null;
$userId = null;
$userEmail = ''; // Initialize userEmail for consistent fetching (still useful for general info)
$schoolId = null; // Initialize schoolId

// Retrieve and decrypt user role and ID from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Fetch user email from the 'users' table using userId (Still useful for displaying, but not for ID-based lookups)
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

// Fetch data based on user role
switch ($role) {
    case 'bmc':
        // BMC role sees all global counts (no change needed here as it's not role-specific detail lookup)
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
        // MODIFIED: Use userId directly to get school_id from principal table
        $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?"); // Use ID, not email
        if ($stmt) {
            $stmt->bind_param("i", $userId); // Bind userId
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

                // Get total students in this school
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
            }
            $stmt->close();
        }
        break;

    case 'teacher':
        // Teacher sees data related to their school
        // MODIFIED: Use userId directly to get school_id from teacher table
        $stmt = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?"); // Use ID, not email
        if ($stmt) {
            $stmt->bind_param("i", $userId); // Bind userId
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $teacherData = $result->fetch_assoc();
                $schoolId = $teacherData['school_id'];

                // Get total students in this school
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
            }
            $stmt->close();
        }
        break;

    case 'student':
        // MODIFIED: Student sees data related to their school
        $stmt = $conn->prepare("SELECT school_id FROM student WHERE id = ?"); // Use ID, not email
        if ($stmt) {
            $stmt->bind_param("i", $userId); // Bind userId
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $studentData = $result->fetch_assoc();
                $schoolId = $studentData['school_id'];
                // Students typically won't see counts of other students/teachers globally.
                // You might add specific queries here if a student's dashboard needs to show, e.g.,
                // counts of courses they are enrolled in, or specific notices for their class.
            }
            $stmt->close();
        }
        break;
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>BMC - Dashboard</title>

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <link href="./assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link href="./assets/css/sb-admin-2.min.css" rel="stylesheet">

    <link rel="stylesheet" href="./assets/css/calender.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <link rel="stylesheet" href="./assets/css/sidebar.css">

    <style>
        .notification-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e3e6f0;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-icon {
            font-size: 1.5rem;
            margin-right: 15px;
        }

        .notification-content {
            flex-grow: 1;
        }

        .notification-title {
            font-weight: 600;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #858796;
        }
    </style>

</head>

<body id="page-top">
    <div id="wrapper">

        <?php
        include './includes/sidebar/BMC_sidebar.php';
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
                                <a href="./pages/school/school_list.php">
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
                                <a href="./pages/principal/principal_list.php">
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
                                <a href="./pages/teacher/teacher_list.php">
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
                                <a href="./pages/student/student_list.php">
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
                                <a href="./pages/teacher/teacher_list.php">
                                    <div class="card border-left-info shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                        TOTAL Teachers in My School</div>
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
                                <a href="./pages/student/student_list.php">
                                    <div class="card border-left-warning shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                        TOTAL Students in My School</div>
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
                        <?php elseif ($role == 'teacher'): ?>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="./pages/student/student_list.php">
                                    <div class="card border-left-warning shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                        TOTAL Students in My School</div>
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
                        <?php elseif ($role == 'student'): ?>
                             <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    My Current Standard</div>
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
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <div
                                    class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Growth Overview</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area">
                                        <canvas id="myAreaChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div
                                    class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Donut Chart</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="myPieChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-primary"></i> Direct
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-success"></i> Social
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-info"></i> Referral
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-6 col-lg-7 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Calendar</h6>
                                </div>
                                <div class="card-body">
                                    <div class="calendar">
                                        <div class="calendar-header">
                                            <button class="btn btn-primary btn-sm" id="prev-month">&lt;</button>
                                            <h4 id="month-year"></h4>
                                            <button class="btn btn-primary btn-sm" id="next-month">&gt;</button>
                                        </div>
                                        <div class="days-of-week">
                                            <div>Sun</div>
                                            <div>Mon</div>
                                            <div>Tue</div>
                                            <div>Wed</div>
                                            <div>Thu</div>
                                            <div>Fri</div>
                                            <div>Sat</div>
                                        </div>
                                        <div class="calendar-grid" id="calendar-grid">
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-lg-5 mb-4">
                            <div class="card shadow h-100 overflow-y-auto">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Notifications</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        <div class="notification-item">
                                            <i class="fas fa-user-plus notification-icon text-primary"></i>
                                            <div class="notification-content">
                                                <div class="notification-title">New student enrolled</div>
                                                <p class="mb-0">A new student, John Doe, has been enrolled in Class 10.</p>
                                                <div class="notification-time">3 days ago</div>
                                            </div>
                                        </div>
                                        <div class="notification-item">
                                            <i class="fas fa-edit notification-icon text-success"></i>
                                            <div class="notification-content">
                                                <div class="notification-title">Teacher profile updated</div>
                                                <p class="mb-0">Jane Smith's profile has been updated.</p>
                                                <div class="notification-time">1 week ago</div>
                                            </div>
                                        </div>
                                        <div class="notification-item">
                                            <i class="fas fa-calendar-alt notification-icon text-warning"></i>
                                            <div class="notification-content">
                                                <div class="notification-title">School event reminder</div>
                                                <p class="mb-0">Annual sports day is scheduled for next month.</p>
                                                <div class="notification-time">2 weeks ago</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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

    <script src="./assets/vendor/jquery/jquery.min.js"></script>
    <script src="./assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script src="./assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <script src="./assets/js/sb-admin-2.min.js"></script>

    <script src="./assets/vendor/chart.js/Chart.min.js"></script>

    <?php if ($role == 'bmc' || $role == 'schooladmin' || $role == 'teacher' || $role == 'student'): ?>
        <script src="./assets/js/demo/chart-area-demo.js"></script>
        <script src="./assets/js/demo/chart-pie-demo.js"></script>
    <?php endif; ?>

    <script src="./assets/js/calender.js"></script>

</body>

</html>