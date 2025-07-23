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

// --- Robust Photo/Logo Handling Logic ---
// This function assumes the image_path stored in the DB is relative to your project's web root (e.g., 'pages/student/uploads/photo.jpg')
function getWebAccessibleImagePath($db_image_path, $base_web_path, $default_sub_folder = '')
{
    if (empty($db_image_path)) {
        return null;
    }

    // Attempt to make it a full web-accessible path
    $full_web_path = $base_web_path . ltrim($db_image_path, '/');

    // Check if the file actually exists on the filesystem from the DOCUMENT_ROOT
    $filesystem_path = $_SERVER['DOCUMENT_ROOT'] . $full_web_path;

    if (file_exists($filesystem_path) && is_file($filesystem_path)) {
        return $full_web_path;
    }
    
    // Fallback: If DB path is just a filename, try common upload locations
    // This part is for backward compatibility or if your initial uploads were just filenames
    $possible_locations = [
        "pages/{$default_sub_folder}/uploads/",
        "uploads/{$default_sub_folder}s/",
        "uploads/",
    ];
    
    foreach ($possible_locations as $location) {
        // Construct full web path for testing
        $test_path = $base_web_path . $location . basename($db_image_path);
        // Construct full filesystem path to check existence
        $test_filesystem_path = $_SERVER['DOCUMENT_ROOT'] . $test_path;

        if (file_exists($test_filesystem_path) && is_file($test_filesystem_path)) {
            return $test_path; // Return the web-accessible path
        }
    }

    return null; // No photo found
}

function getDefaultImagePath($type = 'user', $base_web_path)
{
    // Define BASE_WEB_PATH if it's not already defined (e.g., if this script is accessed directly)
    if (!defined('BASE_WEB_PATH')) {
        define('BASE_WEB_PATH', '/BMC-SMS/'); // Adjust as per your actual project setup
    }

    $default_paths = [
        "assets/images/default-{$type}.jpg", // Try default-student.jpg
        "assets/img/default-{$type}.jpg",    // Try default-student.jpg
        "assets/images/default-user.jpg",    // Generic user default
        "assets/img/default-user.jpg",       // Generic user default
        "assets/images/no-photo.jpg",        // General no photo
        "assets/img/no-photo.jpg"            // General no photo
    ];

    foreach ($default_paths as $path) {
        $full_web_path = $base_web_path . $path;
        $filesystem_path = $_SERVER['DOCUMENT_ROOT'] . $full_web_path;
        if (file_exists($filesystem_path) && is_file($filesystem_path)) {
            return $full_web_path;
        }
    }

    // Fallback to a base64 encoded SVG if no file found
    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150' viewBox='0 0 150 150'%3E%3Crect width='150' height='150' fill='%23f8f9fc'/%3E%3Ctext x='75' y='75' text-anchor='middle' dy='0.35em' fill='%23858796' font-family='Arial' font-size='14'%3ENo Photo%3C/text%3E%3C/svg%3E";
}

// Ensure BASE_WEB_PATH is defined for this script (it should be in header.php, but define here for safety)
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/'); // Adjust as per your actual project setup
}

// Get the actual photo path
$photo_path = getWebAccessibleImagePath($student['student_image'], BASE_WEB_PATH, 'student');
$default_photo = getDefaultImagePath('student', BASE_WEB_PATH);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Student - <?php echo htmlspecialchars($student['student_name']); ?></title>

    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

        <!-- Corrected Font Awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <!-- Custom styles -->
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">

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
    <div id="wrapper">

        <?php include_once '../../includes/sidebar/BMC_sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">

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

                    <div class="row">

                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-camera"></i> Student Photo
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="photo-container">
                                        <?php if ($photo_path): // Check if a valid web-accessible path was found ?>
                                            <img src="<?php echo htmlspecialchars($photo_path); ?>"
                                                alt="<?php echo htmlspecialchars($student['student_name']); ?>"
                                                class="student-photo"
                                                onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_photo); ?>';">
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($default_photo); ?>"
                                                alt="Default Student Avatar" class="student-photo" style="opacity: 0.7;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-center">
                                        <small class="text-muted">
                                            <?php echo ($photo_path) ? 'Student Photo' : 'Default Avatar'; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                </div>
            <?php
            include '../../includes/footer.php';
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
                    <a class="btn btn-primary" href="../../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
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
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <script src="../../assets/js/sb-admin-2.min.js"></script>

</body>

</html>