<?php

include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if not logged in
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// Get student ID from URL
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id <= 0) {
    header("Location: student_list.php?error=Invalid student ID");
    exit;
}

// Fetch student data with school information
$query = "SELECT s.*, sc.school_name, sc.address as school_address
          FROM student s 
          LEFT JOIN school sc ON s.school_id = sc.id
          WHERE s.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: student_list.php?error=Student not found");
    exit;
}

$student = mysqli_fetch_assoc($result);

// Function to get student photo path with multiple fallback options
function getStudentPhotoPath($student_image, $student_id = null)
{
    if (empty($student_image)) {
        return null;
    }

    // Possible photo directories to check
    $photo_directories = [
        "../../assets/images/",
        "../../uploads/students/",
        "../../uploads/",
        "../../assets/img/students/",
        "../../images/students/",
        "../../student_photos/"
    ];

    // If it's already a full path, check if it exists
    if (strpos($student_image, '../../') === 0 || strpos($student_image, '/') === 0) {
        if (file_exists($student_image) && is_file($student_image)) {
            return $student_image;
        }
    }

    // Try different directory combinations
    foreach ($photo_directories as $dir) {
        $full_path = $dir . $student_image;
        if (file_exists($full_path) && is_file($full_path)) {
            return $full_path;
        }

        // Also try with student ID prefix (common pattern)
        if ($student_id) {
            $id_prefixed = $dir . $student_id . "_" . $student_image;
            if (file_exists($id_prefixed) && is_file($id_prefixed)) {
                return $id_prefixed;
            }
        }
    }

    return null; // No photo found
}

// Function to get default photo path
function getDefaultPhotoPath()
{
    $default_paths = [
        "../../assets/images/default-student.jpg",
        "../../assets/img/default-student.jpg",
        "../../assets/images/no-photo.jpg",
        "../../assets/img/no-photo.jpg"
    ];

    foreach ($default_paths as $path) {
        if (file_exists($path) && is_file($path)) {
            return $path;
        }
    }

    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150' viewBox='0 0 150 150'%3E%3Crect width='150' height='150' fill='%23f8f9fc'/%3E%3Ctext x='75' y='75' text-anchor='middle' dy='0.35em' fill='%23858796' font-family='Arial' font-size='14'%3ENo Photo%3C/text%3E%3C/svg%3E";
}

// Get the actual photo path
$photo_path = getStudentPhotoPath($student['student_image'], $student['id']);
$default_photo = getDefaultPhotoPath();
$show_default = ($photo_path === null);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Student - <?php echo htmlspecialchars($student['student_name']); ?></title>

    <!-- Custom fonts -->
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles -->
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Custom styles for photo display -->
    <style>
    .student-photo {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 10px;
        border: 3px solid #e3e6f0;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .photo-placeholder {
        width: 150px;
        height: 150px;
        background-color: #f8f9fc;
        border: 2px dashed #d1d3e2;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #858796;
        font-size: 14px;
        text-align: center;
    }

    .photo-container {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include_once '../../includes/sidebar/BMC_sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include_once '../../includes/header/BMC_header.php'; ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Student Details</h1>
                        <div>
                            <a href="student_list.php" class="btn btn-secondary btn-sm mr-2">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit Student
                            </a>
                        </div>
                    </div>

                    <!-- Student Information Cards -->
                    <div class="row">

                        <!-- Student Photo Card -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-camera"></i> Student Photo
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="photo-container">
                                        <?php if (!$show_default): ?>
                                        <img src="<?php echo htmlspecialchars($photo_path); ?>"
                                            alt="<?php echo htmlspecialchars($student['student_name']); ?>"
                                            class="student-photo"
                                            onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_photo); ?>'; this.nextElementSibling.style.display='block';">
                                        <small class="text-muted mt-2" style="display: none;">Photo not
                                            available</small>
                                        <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($default_photo); ?>"
                                            alt="Default Student Avatar" class="student-photo" style="opacity: 0.7;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-center">
                                        <small class="text-muted">
                                            <?php echo !$show_default ? 'Student Photo' : 'Default Avatar'; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information Card -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-user"></i> Basic Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Student ID:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($student['id']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Name:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($student['student_name'] ?? 'N/A'); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Roll Number:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($student['rollno'] ?? 'N/A'); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Standard:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($student['std'] ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Email:</div>
                                        <div class="col-sm-8">
                                            <?php if ($student['email']): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>">
                                                <?php echo htmlspecialchars($student['email']); ?>
                                            </a>
                                            <?php else: ?>
                                            N/A
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Academic Year:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($student['academic_year'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information Card -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-id-card"></i> Personal Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Date of Birth:</div>
                                        <div class="col-sm-8">
                                            <?php
                                            if ($student['dob']) {
                                                echo date('F j, Y', strtotime($student['dob']));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Gender:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars(ucfirst($student['gender'] ?? 'N/A')); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Blood Group:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars(strtoupper($student['blood_group'] ?? 'N/A')); ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Address:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- School Information Card -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-school"></i> School Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">School Name:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($student['school_name'] ?? 'N/A'); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">School Address:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($student['school_address'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parent Information Card -->
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-users"></i> Parent Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="row">
                                                <div class="col-sm-4 font-weight-bold">Father's Name:</div>
                                                <div class="col-sm-8">
                                                    <?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-sm-4 font-weight-bold">Father's Phone:</div>
                                                <div class="col-sm-8">
                                                    <?php if ($student['father_phone']): ?>
                                                    <a
                                                        href="tel:<?php echo htmlspecialchars($student['father_phone']); ?>">
                                                        <?php echo htmlspecialchars($student['father_phone']); ?>
                                                    </a>
                                                    <?php else: ?>
                                                    N/A
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="row">
                                                <div class="col-sm-4 font-weight-bold">Mother's Name:</div>
                                                <div class="col-sm-8">
                                                    <?php echo htmlspecialchars($student['mother_name'] ?? 'N/A'); ?>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-sm-4 font-weight-bold">Mother's Phone:</div>
                                                <div class="col-sm-8">
                                                    <?php if ($student['mother_phone']): ?>
                                                    <a
                                                        href="tel:<?php echo htmlspecialchars($student['mother_phone']); ?>">
                                                        <?php echo htmlspecialchars($student['mother_phone']); ?>
                                                    </a>
                                                    <?php else: ?>
                                                    N/A
                                                    <?php endif; ?>
                                                </div>
                                            </div>
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
            <?php include_once '../../includes/footer/BMC_footer.php'; ?>
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
                    <a class="btn btn-primary" href="../../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../../assets/js/sb-admin-2.min.js"></script>

</body>

</html>