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

// Fetch student data with related information
// MODIFIED: Added self_transport_mode, vehicle_number, and license_number to the SELECT query.
$query = "SELECT s.*, sc.school_name, sc.address as school_address, sc.email as school_email, sc.phone as school_phone,
                st.stop_name, r.route_name, v.vehicle_number as school_vehicle_number
        FROM student s 
        LEFT JOIN school sc ON s.school_id = sc.id
        LEFT JOIN stops st ON s.stop_id = st.id
        LEFT JOIN routes r ON st.route_id = r.id
        LEFT JOIN vehicles v ON r.vehicle_id = v.id
        WHERE s.id = ?";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute([$student_id]);

    // PDO Change: Use rowCount() to check if a record was found
    if ($stmt->rowCount() == 0) {
        header("Location: student_list.php?error=Student not found");
        exit;
    }

    // PDO Change: Use fetch() to get the result
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in view.php: " . $e->getMessage());
    header("Location: student_list.php?error=An error occurred");
    exit;
}

// Define BASE_WEB_PATH if not already
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// --- Simplified Photo Handling Logic ---
$photo_path = $student['student_image'];
$default_photo = BASE_WEB_PATH . 'assets/images/unisex.png';

$full_filesystem_path = $_SERVER['DOCUMENT_ROOT'] . $photo_path;

if (!empty($photo_path) && file_exists($full_filesystem_path) && is_file($full_filesystem_path)) {
    $display_photo = $photo_path;
} else {
    $display_photo = $default_photo;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Student - <?php echo htmlspecialchars($student['student_name']); ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/profile.css">
    <link rel="stylesheet" href="../../assets/css/student_view.css">
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
                        <h1 class="h3 mb-0 text-gray-800">Student's Details</h1>
                        <div>
                            <a href="student_list.php" class="btn btn-secondary btn-sm mr-2">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <?php if ($role === 'principal'): ?>
                                <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit Student
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-camera"></i> Student Profile
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="photo-container">
                                        <img src="<?php echo htmlspecialchars($display_photo); ?>"
                                            alt="<?php echo htmlspecialchars($student['student_name']); ?>"
                                            class="profile-photo mb-3 mt-3 h-50 w-50"
                                            onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_photo); ?>';">
                                    </div>
                                    <div class="text-center">
                                        <h4 class="font-weight-bold text-gray-800 mt-2"><?php echo htmlspecialchars($student['student_name']); ?></h4>
                                        <p class="text-muted">Roll No : <?php echo htmlspecialchars($student['rollno']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-info-circle"></i> Academic Information
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
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Date of Joining:</div>
                                        <div class="col-sm-8">
                                            <?php
                                            if (!empty($student['date_of_joining'])) {
                                                echo date('F j, Y', strtotime($student['date_of_joining']));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
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
                            <div class="card shadow h-100">
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
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">School Email:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($student['school_email'] ?? 'N/A'); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">School Phone:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($student['school_phone'] ?? 'N/A'); ?></div>
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

                        <div class="col-lg-12 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bus"></i> Transport Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="row">
                                                <div class="col-sm-5 font-weight-bold">Mode of Transport:</div>
                                                <div class="col-sm-7"><?php echo htmlspecialchars($student['transport_mode'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                        <?php if (isset($student['transport_mode']) && $student['transport_mode'] === 'School Transport'): ?>
                                            <div class="col-md-8">
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="row">
                                                            <div class="col-sm-5 font-weight-bold">Route:</div>
                                                            <div class="col-sm-7"><?php echo htmlspecialchars($student['route_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="row">
                                                            <div class="col-sm-5 font-weight-bold">Stop:</div>
                                                            <div class="col-sm-7"><?php echo htmlspecialchars($student['stop_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="row">
                                                            <div class="col-sm-5 font-weight-bold">Vehicle:</div>
                                                            <div class="col-sm-7"><?php echo htmlspecialchars($student['school_vehicle_number'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php elseif (isset($student['transport_mode']) && $student['transport_mode'] === 'Self Transport'): ?>
                                            <div class="col-md-8">
                                                <div class="row">
                                                    <div class="col-sm-4 font-weight-bold">Self Transport Mode:</div>
                                                    <div class="col-sm-8"><?php echo htmlspecialchars($student['self_transport_mode'] ?? 'N/A'); ?></div>
                                                </div>
                                                <?php if (isset($student['self_transport_mode']) && ($student['self_transport_mode'] === 'Bike' || $student['self_transport_mode'] === 'Car')): ?>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-sm-4 font-weight-bold">Vehicle Number:</div>
                                                        <div class="col-sm-8"><?php echo htmlspecialchars($student['vehicle_number'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-sm-4 font-weight-bold">License Number:</div>
                                                        <div class="col-sm-8"><?php echo htmlspecialchars($student['license_number'] ?? 'N/A'); ?></div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
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

    <?php include_once "../../includes/logout_modal.php" ?>

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