<?php
// Includes and session start
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Only define the constant if it hasn't been defined already.
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

function getWebAccessibleImagePath($db_image_path, $base_web_path, $default_sub_folder = '') {
    if (empty($db_image_path)) {
        return null;
    }
    $full_web_path = $base_web_path . ltrim($db_image_path, '/');
    $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path;
    if (@file_exists($filesystem_path) && @is_file($filesystem_path)) {
        return $full_web_path;
    }
    $possible_locations = ["pages/{$default_sub_folder}/uploads/", "uploads/{$default_sub_folder}s/", "uploads/"];
    foreach ($possible_locations as $location) {
        $test_path = $base_web_path . $location . basename($db_image_path);
        $test_filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $test_path;
        if (@file_exists($test_filesystem_path) && @is_file($test_filesystem_path)) {
            return $test_path;
        }
    }
    return null;
}

// Set default values
$user_data = null;
$error_message = '';
$user_role = '';

// Check for success/error messages from URL
$success_message_from_url = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error_message_from_url = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// Check if user is logged in via cookie
if (isset($_COOKIE['encrypted_user_id']) && isset($_COOKIE['encrypted_user_role'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
    $user_role = decrypt_id($_COOKIE['encrypted_user_role']);

    // Determine the table, fields, and join based on the user's role
    $table_name = '';
    $image_field = '';
    $name_field = '';
    
    switch ($user_role) {
        case 'teacher':
            $table_name = 'teacher';
            $image_field = 'teacher_image';
            $name_field = 'teacher_name';
            break;
        case 'student':
            $table_name = 'student';
            $image_field = 'student_image';
            $name_field = 'student_name';
            break;
        case 'principal':
            $table_name = 'principal';
            $image_field = 'principal_image';
            $name_field = 'principal_name';
            break;
        default:
            $error_message = "Invalid user role.";
            break;
    }

    if ($table_name) {
        try {
            $query = "SELECT t.*, s.school_name 
                      FROM {$table_name} t
                      LEFT JOIN school s ON t.school_id = s.id
                      WHERE t.id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);
            } else {
                $error_message = "User not found in the database.";
            }
            mysqli_stmt_close($stmt);
        } catch (Exception $e) {
            $error_message = "Database query failed: " . $e->getMessage();
        }
    }
} else {
    header("Location: ../../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>User Profile - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        .profile-photo { width: 150px; height: 150px; object-fit: cover; border-radius: 10px; border: 3px solid #e3e6f0; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); }
        .info-row { margin-bottom: 1rem; }
        .info-label { font-weight: bold; color: #5a5c69; }
        .info-value { color: #858796; }
        .salary-display { font-size: 1.2em; font-weight: bold; color: #1cc88a; }
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
                        <h1 class="h3 mb-0 text-gray-800">User Profile</h1>
                        <!-- UPDATE: Link to the new edit_profile.php page -->
                        <a href="edit_profile.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit fa-sm"></i> Edit Profile
                        </a>
                    </div>

                    <!-- Display Success/Error Messages -->
                    <?php if ($success_message_from_url): ?>
                        <div class="alert alert-success"><?php echo $success_message_from_url; ?></div>
                    <?php endif; ?>
                    <?php if ($error_message_from_url): ?>
                        <div class="alert alert-danger"><?php echo $error_message_from_url; ?></div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php elseif ($user_data): ?>
                        <div class="row">
                            <div class="col-lg-4 mb-4">
                                <div class="card shadow h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-camera mr-2"></i>Profile Photo</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php 
                                            $defaultImagePath = BASE_WEB_PATH . 'assets/img/default-user.jpg';
                                            $imagePathFromDB = $user_data[$image_field] ?? '';
                                            $profileImagePath = getWebAccessibleImagePath($imagePathFromDB, BASE_WEB_PATH, $user_role) ?? $defaultImagePath;
                                        ?>
                                        <img src="<?php echo htmlspecialchars($profileImagePath); ?>" 
                                             class="profile-photo mb-3" 
                                             alt="Profile Photo"
                                             onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($defaultImagePath); ?>';">
                                        <h4 class="text-primary font-weight-bold"><?php echo htmlspecialchars($user_data[$name_field] ?? 'N/A'); ?></h4>
                                        <p class="text-muted text-capitalize"><?php echo htmlspecialchars($user_role); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-8 mb-4">
                                <div class="card shadow h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-tie mr-2"></i>Basic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row info-row">
                                            <div class="col-sm-4 info-label">Email:</div>
                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></div>
                                        </div>
                                        <hr>
                                        <div class="row info-row">
                                            <div class="col-sm-4 info-label">Phone:</div>
                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['phone'] ?? 'N/A'); ?></div>
                                        </div>
                                        <hr>
                                        <div class="row info-row">
                                            <div class="col-sm-4 info-label">Date of Birth:</div>
                                            <div class="col-sm-8 info-value"><?php echo !empty($user_data['dob']) ? htmlspecialchars(date('F j, Y', strtotime($user_data['dob']))) : 'N/A'; ?></div>
                                        </div>
                                        <hr>
                                        <div class="row info-row">
                                            <div class="col-sm-4 info-label">Gender:</div>
                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['gender'] ?? 'N/A'); ?></div>
                                        </div>
                                        <hr>
                                        <div class="row info-row">
                                            <div class="col-sm-4 info-label">Blood Group:</div>
                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['blood_group'] ?? 'N/A'); ?></div>
                                        </div>
                                        <hr>
                                        <div class="row info-row">
                                            <div class="col-sm-4 info-label">Address:</div>
                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['address'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-4">
                                <div class="card shadow">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-briefcase mr-2"></i>Professional Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="row info-row">
                                                    <div class="col-sm-5 info-label">School:</div>
                                                    <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['school_name'] ?? 'N/A'); ?></div>
                                                </div>
                                            </div>
                                            <?php if ($user_role === 'teacher' || $user_role === 'principal'): ?>
                                                <div class="col-md-6">
                                                    <div class="row info-row">
                                                        <div class="col-sm-5 info-label">Qualification:</div>
                                                        <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['qualification'] ?? 'N/A'); ?></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="row info-row">
                                                        <div class="col-sm-5 info-label">Batch:</div>
                                                        <div class="col-sm-7 info-value"><span class="badge badge-<?php echo ($user_data['batch'] ?? '') === 'Morning' ? 'info' : 'warning'; ?>"><?php echo htmlspecialchars($user_data['batch'] ?? 'N/A'); ?></span></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="row info-row">
                                                        <div class="col-sm-5 info-label">Salary:</div>
                                                        <div class="col-sm-7 info-value salary-display">₹<?php echo number_format($user_data['salary'] ?? 0, 2); ?></div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($user_role === 'teacher'): ?>
                                                <div class="col-md-6">
                                                    <div class="row info-row">
                                                        <div class="col-sm-5 info-label">Subject:</div>
                                                        <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['subject'] ?? 'N/A'); ?></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="row info-row">
                                                        <div class="col-sm-5 info-label">Experience:</div>
                                                        <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['experience'] ?? '0'); ?> years</div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
