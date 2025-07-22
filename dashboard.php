<?php
// Enable all error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "encryption.php";
include_once "./includes/connect.php"; // Include your database connection file

$role = null;
$userId = null;
$userEmail = ''; // Initialize userEmail for consistent fetching
$schoolId = null; // Initialize schoolId

// Retrieve and decrypt user role and ID from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Fetch user email from the 'users' table using userId
// This is crucial because other tables might use email for linking if IDs are inconsistent
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
    } else {
        error_log("SQL Error (fetch user email prepare): " . $conn->error);
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
$userName = '';
$userPhone = '';
$userImage = '';
$userAddress = '';
$userDob = '';
$userGender = '';
$userBloodGroup = '';
$userQualification = '';
$userSubject = '';
$userExperience = '';
$userBatch = '';
$userStd = '';
$userRollNo = '';
$userAcademicYear = '';
$userFatherName = '';
$userFatherPhone = '';
$userMotherName = '';
$userMotherPhone = '';
$schoolName = '';

// Fetch data based on user role
switch ($role) {
    case 'bmc':
        // BMC role sees all global counts
        $sql = "SELECT COUNT(*) AS total FROM school";
        $result = $conn->query($sql);
        if ($result === FALSE) {
            // Log or display error, but don't halt execution
            error_log("SQL Error (school count): " . $conn->error);
        }
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalSchools = $row['total'];
        }

        $sql = "SELECT COUNT(*) AS total FROM principal";
        $result = $conn->query($sql);
        if ($result === FALSE) {
            error_log("SQL Error (principal count): " . $conn->error);
        }
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalPrincipals = $row['total'];
        }

        $sql = "SELECT COUNT(*) AS total FROM teacher";
        $result = $conn->query($sql);
        if ($result === FALSE) {
            error_log("SQL Error (teacher count): " . $conn->error);
        }
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalTeachers = $row['total'];
        }

        $sql = "SELECT COUNT(*) AS total FROM student";
        $result = $conn->query($sql);
        if ($result === FALSE) {
            error_log("SQL Error (student count): " . $conn->error);
        }
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalStudents = $row['total'];
        }
        break;

    case 'schooladmin':
        // School Admin sees data related to their school
        // First, get the school_id for the logged-in principal using their email
        $stmt = $conn->prepare("SELECT school_id, principal_name, email, phone, principal_image, address, principal_dob, gender, blood_group, qualification, batch FROM principal WHERE email = ?");
        if ($stmt === FALSE) {
            error_log("SQL Error (principal profile prepare): " . $conn->error);
        } else {
            $stmt->bind_param("s", $userEmail); // Bind by email
            $stmt->execute();
            if ($stmt->error) {
                error_log("SQL Error (principal profile execute): " . $stmt->error);
            }
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $principalData = $result->fetch_assoc();
                $schoolId = $principalData['school_id'];
                $userName = $principalData['principal_name'];
                $userEmail = $principalData['email']; // Ensure userEmail is updated from specific table
                $userPhone = $principalData['phone'];
                $userImage = !empty($principalData['principal_image']) ? 'pages/principal/uploads/' . basename($principalData['principal_image']) : '/BMC-SMS/assets/images/undraw_profile.svg';
                $userAddress = $principalData['address'];
                $userDob = $principalData['principal_dob'];
                $userGender = $principalData['gender'];
                $userBloodGroup = $principalData['blood_group'];
                $userQualification = $principalData['qualification'];
                $userBatch = $principalData['batch'];

                // Get school name
                $schoolStmt = $conn->prepare("SELECT school_name FROM school WHERE id = ?");
                if ($schoolStmt === FALSE) {
                    error_log("SQL Error (school name prepare): " . $conn->error);
                } else {
                    $schoolStmt->bind_param("i", $schoolId);
                    $schoolStmt->execute();
                    if ($schoolStmt->error) {
                        error_log("SQL Error (school name execute): " . $schoolStmt->error);
                    }
                    $schoolResult = $schoolStmt->get_result();
                    if ($schoolResult && $schoolResult->num_rows > 0) {
                        $schoolData = $schoolResult->fetch_assoc();
                        $schoolName = $schoolData['school_name'];
                    }
                    $schoolStmt->close();
                }

                // Get total teachers in this school
                $teacherStmt = $conn->prepare("SELECT COUNT(*) AS total FROM teacher WHERE school_id = ?");
                if ($teacherStmt === FALSE) {
                    error_log("SQL Error (teacher count prepare): " . $conn->error);
                } else {
                    $teacherStmt->bind_param("i", $schoolId);
                    $teacherStmt->execute();
                    if ($teacherStmt->error) {
                        error_log("SQL Error (teacher count execute): " . $teacherStmt->error);
                    }
                    $teacherResult = $teacherStmt->get_result();
                    if ($teacherResult && $teacherResult->num_rows > 0) {
                        $teacherRow = $teacherResult->fetch_assoc();
                        $totalTeachers = $teacherRow['total'];
                    }
                    $teacherStmt->close();
                }

                // Get total students in this school
                $studentStmt = $conn->prepare("SELECT COUNT(*) AS total FROM student WHERE school_id = ?");
                if ($studentStmt === FALSE) {
                    error_log("SQL Error (student count prepare): " . $conn->error);
                } else {
                    $studentStmt->bind_param("i", $schoolId);
                    $studentStmt->execute();
                    if ($studentStmt->error) {
                        error_log("SQL Error (student count execute): " . $studentStmt->error);
                    }
                    $studentResult = $studentStmt->get_result();
                    if ($studentResult && $studentResult->num_rows > 0) {
                        $studentRow = $studentResult->fetch_assoc();
                        $totalStudents = $studentRow['total'];
                    }
                    $studentStmt->close();
                }
            } else {
                error_log("Debug Info: School Admin - No principal data found for User Email = " . $userEmail);
            }
            $stmt->close();
        }
        break;

    case 'teacher':
        // Teacher sees data related to their school and their own profile
        // First, get the school_id for the logged-in teacher using their email
        $stmt = $conn->prepare("SELECT school_id, teacher_name, email, phone, teacher_image, address, dob, gender, blood_group, qualification, subject, experience, batch, std FROM teacher WHERE email = ?");
        if ($stmt === FALSE) {
            error_log("SQL Error (teacher profile prepare): " . $conn->error);
        } else {
            $stmt->bind_param("s", $userEmail); // Bind by email
            $stmt->execute();
            if ($stmt->error) {
                error_log("SQL Error (teacher profile execute): " . $stmt->error);
            }
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $teacherData = $result->fetch_assoc();
                $schoolId = $teacherData['school_id'];
                $userName = $teacherData['teacher_name'];
                $userEmail = $teacherData['email']; // Ensure userEmail is updated from specific table
                $userPhone = $teacherData['phone'];
                $userImage = !empty($teacherData['teacher_image']) ? 'pages/teacher/uploads/' . basename($teacherData['teacher_image']) : '/BMC-SMS/assets/images/undraw_profile.svg';
                $userAddress = $teacherData['address'];
                $userDob = $teacherData['dob'];
                $userGender = $teacherData['gender'];
                $userBloodGroup = $teacherData['blood_group'];
                $userQualification = $teacherData['qualification'];
                $userSubject = $teacherData['subject'];
                $userExperience = $teacherData['experience'];
                $userBatch = $teacherData['batch'];
                $userStd = $teacherData['std'];

                // Get school name
                $schoolStmt = $conn->prepare("SELECT school_name FROM school WHERE id = ?");
                if ($schoolStmt === FALSE) {
                    error_log("SQL Error (school name prepare): " . $conn->error);
                } else {
                    $schoolStmt->bind_param("i", $schoolId);
                    $schoolStmt->execute();
                    if ($schoolStmt->error) {
                        error_log("SQL Error (school name execute): " . $schoolStmt->error);
                    }
                    $schoolResult = $schoolStmt->get_result();
                    if ($schoolResult && $schoolResult->num_rows > 0) {
                        $schoolData = $schoolResult->fetch_assoc();
                        $schoolName = $schoolData['school_name'];
                    }
                    $schoolStmt->close();
                }

                // Get total students in this school
                $studentStmt = $conn->prepare("SELECT COUNT(*) AS total FROM student WHERE school_id = ?");
                if ($studentStmt === FALSE) {
                    error_log("SQL Error (student count prepare): " . $conn->error);
                } else {
                    $studentStmt->bind_param("i", $schoolId);
                    $studentStmt->execute();
                    if ($studentStmt->error) {
                        error_log("SQL Error (student count execute): " . $studentStmt->error);
                    }
                    $studentResult = $studentStmt->get_result();
                    if ($studentResult && $studentResult->num_rows > 0) {
                        $studentRow = $studentResult->fetch_assoc();
                        $totalStudents = $studentRow['total'];
                    }
                    $studentStmt->close();
                }
            } else {
                error_log("Debug Info: Teacher - No teacher data found for User Email = " . $userEmail);
            }
            $stmt->close();
        }
        break;

    case 'student':
        // Student sees their own profile data using their email
        $stmt = $conn->prepare("SELECT student_name, rollno, std, email, student_image, academic_year, school_id, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone FROM student WHERE email = ?");
        if ($stmt === FALSE) {
            error_log("SQL Error (student profile prepare): " . $conn->error);
        } else {
            $stmt->bind_param("s", $userEmail); // Bind by email
            $stmt->execute();
            if ($stmt->error) {
                error_log("SQL Error (student profile execute): " . $stmt->error);
            }
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $studentData = $result->fetch_assoc();
                $schoolId = $studentData['school_id'];
                $userName = $studentData['student_name'];
                $userRollNo = $studentData['rollno'];
                $userStd = $studentData['std'];
                $userEmail = $studentData['email']; // Ensure userEmail is updated from specific table
                $userImage = !empty($studentData['student_image']) ? 'pages/student/uploads/' . basename($studentData['student_image']) : '/BMC-SMS/assets/images/undraw_profile.svg';
                $userAcademicYear = $studentData['academic_year'];
                $userDob = $studentData['dob'];
                $userGender = $studentData['gender'];
                $userBloodGroup = $studentData['blood_group'];
                $userAddress = $studentData['address'];
                $userFatherName = $studentData['father_name'];
                $userFatherPhone = $studentData['father_phone'];
                $userMotherName = $studentData['mother_name'];
                $userMotherPhone = $studentData['mother_phone'];

                // Get school name
                $schoolStmt = $conn->prepare("SELECT school_name FROM school WHERE id = ?");
                if ($schoolStmt === FALSE) {
                    error_log("SQL Error (school name prepare): " . $conn->error);
                } else {
                    $schoolStmt->bind_param("i", $schoolId);
                    $schoolStmt->execute();
                    if ($schoolStmt->error) {
                        error_log("SQL Error (school name execute): " . $schoolStmt->error);
                    }
                    $schoolResult = $schoolStmt->get_result();
                    if ($schoolResult && $schoolResult->num_rows > 0) {
                        $schoolData = $schoolResult->fetch_assoc();
                        $schoolName = $schoolData['school_name'];
                    }
                    $schoolStmt->close();
                }
            } else {
                error_log("Debug Info: Student - No student data found for User Email = " . $userEmail);
            }
            $stmt->close();
        }
        break;

    default:
        // Handle other roles or no specific data to display
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

    <!-- Bootstrap CSS for grid and basic styling -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom fonts for this template-->
    <link href="./assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="./assets/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Corrected Calendar styles link -->
    <link rel="stylesheet" href="./assets/css/calender.css">

    <!-- Corrected Font Awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <!-- Sidebar CSS -->
    <link rel="stylesheet" href="./assets/css/sidebar.css">

</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php
        include './includes/sidebar/BMC_sidebar.php';
        ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php
                include './includes/header.php';
                ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    </div>

                    <!-- Content Row - Cards -->
                    <div class="row">
                        <?php if ($role == 'bmc'): ?>
                            <!-- Clickable Schools Card (BMC only) -->
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

                            <!-- Clickable Principals Card (BMC only) -->
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
                        <?php endif; ?>

                        <?php if ($role == 'bmc' || $role == 'schooladmin'): ?>
                            <!-- Clickable Teachers Card (BMC and School Admin) -->
                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="./pages/teacher/teacher_list.php">
                                    <div class="card border-left-info shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                        TOTAL Teachers <?php echo ($role == 'schooladmin' ? 'in My School' : ''); ?></div>
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
                        <?php endif; ?>

                        <?php if ($role == 'bmc' || $role == 'schooladmin' || $role == 'teacher'): ?>
                            <!-- Clickable Students Card (BMC, School Admin, and Teacher) -->
                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="./pages/student/student_list.php">
                                    <div class="card border-left-warning shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                        TOTAL Students <?php echo (($role == 'schooladmin' || $role == 'teacher') ? 'in My School' : ''); ?></div>
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
                        <?php endif; ?>
                    </div>

                    <!-- Content Row - Charts and Calendar -->
                    <div class="row">
                        <?php if ($role == 'bmc' || $role == 'schooladmin' || $role == 'teacher' || $role == 'student'): ?>
                            <!-- Area Chart -->
                            <div class="col-xl-8 col-lg-7">
                                <div class="card shadow mb-4">
                                    <!-- Card Header - Dropdown -->
                                    <div
                                        class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                        <h6 class="m-0 font-weight-bold text-primary">Growth Overview</h6>
                                    </div>
                                    <!-- Card Body -->
                                    <div class="card-body">
                                        <div class="chart-area">
                                            <canvas id="myAreaChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pie Chart -->
                            <div class="col-xl-4 col-lg-5">
                                <div class="card shadow mb-4">
                                    <!-- Card Header - Dropdown -->
                                    <div
                                        class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                        <h6 class="m-0 font-weight-bold text-primary">Donut Chart</h6>
                                    </div>
                                    <!-- Card Body -->
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
                        <?php endif; ?>
                    </div>
                    <div class="row">
                        <!-- Calendar (Visible for all roles) -->
                        <div class="col-xl-6 col-lg-7">
                            <div class="card shadow mb-4">
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
                                            <!-- Calendar days will be generated by js -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php
            include './includes/footer.php';
            ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
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

    <!-- Bootstrap core JavaScript-->
    <script src="./assets/vendor/jquery/jquery.min.js"></script>
    <script src="./assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="./assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="./assets/js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="./assets/vendor/chart.js/Chart.min.js"></script>

    <!-- Page level custom scripts -->
    <?php if ($role == 'bmc' || $role == 'schooladmin' || $role == 'teacher' || $role == 'student'): ?>
        <script src="./assets/js/demo/chart-area-demo.js"></script>
        <script src="./assets/js/demo/chart-pie-demo.js"></script>
    <?php endif; ?>

    <!-- Calendar Script -->
    <script src="./assets/js/calender.js"></script>

</body>

</html>
