<?php
// Includes and session start
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";
// include_once "../../includes/ajax_navigation.php"; // Removed as it's not needed here
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if this is an AJAX request
if ($is_ajax_request) {
    // Start output buffering to capture the HTML
    ob_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/BMC-SMS/');
}

function getWebAccessibleImagePath($db_image_path, $base_web_path, $default_sub_folder = '')
{
    if (empty($db_image_path)) {
        return null;
    }

    // 1. Check the exact path stored in the DB, handling if it's already a full path
    if (strpos($db_image_path, '/') === 0) {
        $full_web_path = $db_image_path;
    } else {
        $full_web_path = $base_web_path . ltrim($db_image_path, '/');
    }

    $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path;
    if (@file_exists($filesystem_path) && @is_file($filesystem_path)) {
        return $full_web_path;
    }

    // 2. Fallback check for other possible locations
    $possible_locations = [
        "pages/{$default_sub_folder}/uploads/",
        "uploads/{$default_sub_folder}s/",
        "uploads/"
    ];
    foreach ($possible_locations as $location) {
        $test_path = $base_web_path . $location . basename($db_image_path);
        $test_filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $test_path;
        if (@file_exists($test_filesystem_path) && @is_file($test_filesystem_path)) {
            return $test_path;
        }
    }

    return null; // Return null if no image is found
}

$user_data = null;
$error_message = '';
$user_role = '';
$timings = [];

$success_message_from_url = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error_message_from_url = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

if (isset($_COOKIE['encrypted_user_id']) && isset($_COOKIE['encrypted_user_role'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
    $user_role = decrypt_id($_COOKIE['encrypted_user_role']);

    $table_name = '';
    $image_field = '';
    $name_field = '';
    $sub_folder = ''; // Added sub_folder variable

    switch ($user_role) {
        case 'teacher':
            $table_name = 'teacher';
            $image_field = 'teacher_image';
            $name_field = 'teacher_name';
            $sub_folder = 'teacher';
            break;
        case 'student':
            $table_name = 'student';
            $image_field = 'student_image';
            $name_field = 'student_name';
            $sub_folder = 'student';
            break;
        case 'principal':
            $table_name = 'principal';
            $image_field = 'principal_image';
            $name_field = 'principal_name';
            $sub_folder = 'principal';
            break;
        case 'librarian':
            $table_name = 'librarian';
            $image_field = 'librarian_image';
            $name_field = 'librarian_name';
            $sub_folder = 'librarian';
            break;
        case 'hr':
            $table_name = 'hr';
            $image_field = 'hr_image';
            $name_field = 'hr_name';
            $sub_folder = 'hr';
            break;
        default:
            $error_message = "Invalid user role.";
            break;
    }

    if ($table_name) {
        try {
            // MODIFIED: Updated the queries to include transportation details for all relevant roles.
            if ($user_role === 'student' || $user_role === 'teacher' || $user_role === 'librarian' || $user_role === 'principal' || $user_role === 'hr') {
                $query = "SELECT t.*, s.school_name, s.school_opening, s.address AS school_address, s.email AS school_email, s.phone AS school_phone,
                          st.stop_name, r.route_name, v.vehicle_number as school_vehicle_number
                          FROM {$table_name} t
                          LEFT JOIN school s ON t.school_id = s.id
                          LEFT JOIN stops st ON t.stop_id = st.id
                          LEFT JOIN routes r ON st.route_id = r.id
                          LEFT JOIN vehicles v ON r.vehicle_id = v.id
                          WHERE t.id = ?";
            } else {
                $query = "SELECT t.*, s.school_name, s.school_opening, s.address AS school_address, s.email AS school_email, s.phone AS school_phone
                          FROM {$table_name} t
                          LEFT JOIN school s ON t.school_id = s.id
                          WHERE t.id = ?";
            }

            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id]);

            if ($stmt->rowCount() > 0) {
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user_role === 'teacher') {
                    $query_timings = "SELECT * FROM teacher_timings WHERE teacher_id = ?";
                    $stmt_timings = $conn->prepare($query_timings);
                    $stmt_timings->execute([$user_id]);
                    while ($row = $stmt_timings->fetch(PDO::FETCH_ASSOC)) {
                        $timings[$row['day_of_week']] = $row;
                    }
                }

                if ($user_role === 'hr') {
                    $query_timings = "SELECT * FROM hr_timings WHERE hr_id = ?";
                    $stmt_timings = $conn->prepare($query_timings);
                    $stmt_timings->execute([$user_id]);
                    while ($row = $stmt_timings->fetch(PDO::FETCH_ASSOC)) {
                        $timings[$row['day_of_week']] = $row;
                    }
                }

                if ($user_role === 'librarian') {
                    $query_timings = "SELECT * FROM librarian_timings WHERE librarian_id = ?";
                    $stmt_timings = $conn->prepare($query_timings);
                    $stmt_timings->execute([$user_id]);
                    while ($row = $stmt_timings->fetch(PDO::FETCH_ASSOC)) {
                        $timings[$row['day_of_week']] = $row;
                    }
                }

                if ($user_role === 'principal') {
                    $query_timings = "SELECT * FROM principal_timings WHERE principal_id = ?";
                    $stmt_timings = $conn->prepare($query_timings);
                    $stmt_timings->execute([$user_id]);
                    while ($row = $stmt_timings->fetch(PDO::FETCH_ASSOC)) {
                        $timings[$row['day_of_week']] = $row;
                    }
                }
            } else {
                $error_message = "User not found in the database.";
            }
        } catch (PDOException $e) {
            $error_message = "Database query failed: " . $e->getMessage();
            error_log("Profile fetch error: " . $e->getMessage());
        }
    }
} else {
    // For a non-AJAX request, redirect to login. For AJAX, the front-end will handle it.
    if (!is_ajax_request()) {
        header("Location: ../../login.php");
        exit;
    }
}

if (!is_ajax_request()):
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <title>User Profile - School Management System</title>
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
        <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
        <link rel="stylesheet" href="../../assets/css/sidebar.css">
        <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
        <link rel="stylesheet" href="../../assets/css/profile.css">
        <style>
            .table-timings th {
                width: 30%;
            }

            .table-timings td {
                width: 70%;
            }
        </style>
    </head>

    <body id="page-top">
        <div id="wrapper">
            <?php
            if (!$is_ajax_request) {
                include '../../includes/sidebar.php';
            } ?>
            <div id="content-wrapper" class="d-flex flex-column">
                <div id="content">
                    <?php
                    if (!$is_ajax_request) {
                        include '../../includes/header.php';
                    } ?>
                <?php endif; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">User Profile</h1>
                        <div>
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#changePasswordModal"><i class="fas fa-lock fa-sm"></i> Change Password</button>
                            <a href="edit_profile.php" class="btn btn-primary btn-sm"><i class="fas fa-edit fa-sm"></i> Edit Profile</a>
                        </div>
                    </div>

                    <?php if ($success_message_from_url): ?><div class="alert alert-success"><?php echo $success_message_from_url; ?></div><?php endif; ?>
                    <?php if ($error_message_from_url): ?><div class="alert alert-danger"><?php echo $error_message_from_url; ?></div><?php endif; ?>

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
                                        // MODIFIED: Use the new $sub_folder variable for image path lookup
                                        $path_role = ($user_role === 'principal' || $user_role === 'librarian') ? $user_role : $user_role;
                                        $defaultImagePath = BASE_URL . 'assets/images/unisex.png';
                                        $imagePathFromDB = $user_data[$image_field] ?? '';
                                        $profileImagePath = getWebAccessibleImagePath($imagePathFromDB, BASE_URL, $sub_folder) ?? $defaultImagePath;
                                        ?>
                                        <img src="<?php echo htmlspecialchars($profileImagePath); ?>" class="profile-photo mb-4 mt-3 h-50 w-50" alt="Profile Photo" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($defaultImagePath); ?>';">
                                        <h4 class="font-weight-bold text-gray-800"><?php echo htmlspecialchars($user_data[$name_field] ?? 'N/A'); ?></h4>
                                        <p class="text-muted text-capitalize"><?php echo htmlspecialchars($user_role); ?> Staff</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-8 mb-4">
                                <div class="card shadow h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i> Basic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row info-row">
                                            <div class="col-sm-4 info-label">Full Name:</div>
                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data[$name_field] ?? 'N/A'); ?></div>
                                        </div>
                                        <hr>
                                        <div class="row info-row">
                                            <div class="col-sm-4 info-label">Email:</div>
                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></div>
                                        </div>
                                        <hr>
                                        <?php if ($user_role !== 'student'): ?>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Phone:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['phone'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                        <?php endif; ?>
                                        <?php if ($user_role === 'student'): ?>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Father's Name:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['father_name'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Mother's Name:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['mother_name'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                        <?php endif; ?>
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

                            <?php if ($user_role === 'student'): ?>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-lg-6 mb-4">
                                            <div class="card shadow h-100">
                                                <div class="card-header py-3">
                                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-briefcase mr-2"></i>Academic Information</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="row info-row">
                                                                <div class="col-sm-5 info-label">School Name:</div>
                                                                <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['school_name'] ?? 'N/A'); ?></div>
                                                            </div>
                                                            <hr>
                                                            <div class="row info-row">
                                                                <div class="col-sm-5 info-label">Date of Joining:</div>
                                                                <div class="col-sm-7 info-value"><?php echo !empty($user_data['date_of_joining']) ? htmlspecialchars(date('F j, Y', strtotime($user_data['date_of_joining']))) : 'N/A'; ?></div>
                                                            </div>
                                                            <hr>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <div class="row info-row">
                                                                <div class="col-sm-5 info-label">Standard:</div>
                                                                <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['std'] ?? 'N/A'); ?></div>
                                                            </div>
                                                            <hr>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <div class="row info-row">
                                                                <div class="col-sm-5 info-label">Roll No:</div>
                                                                <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['rollno'] ?? 'N/A'); ?></div>
                                                            </div>
                                                            <hr>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <div class="row info-row">
                                                                <div class="col-sm-5 info-label">Academic Year:</div>
                                                                <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['academic_year'] ?? 'N/A'); ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 mb-4">
                                            <div class="card shadow h-100">
                                                <div class="card-header py-3">
                                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-school"></i> School Information</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">School Name:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_name'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Date of Opening:</div>
                                                        <div class="col-sm-8 info-value"><?php echo !empty($user_data['school_opening']) ? htmlspecialchars(date('F j, Y', strtotime($user_data['school_opening']))) : 'N/A'; ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">School Address:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_address'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">School Email:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_email'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">School Phone:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_phone'] ?? 'N/A'); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 mb-4">
                                            <div class="card shadow h-100">
                                                <div class="card-header py-3">
                                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-users"></i> Parent Information</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Father's Name:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['father_name'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Father's Phone:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['father_phone'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Mother's Name:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['mother_name'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Mother's Phone:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['mother_phone'] ?? 'N/A'); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 mb-4">
                                            <div class="card shadow h-100">
                                                <div class="card-header py-3">
                                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bus"></i> Transport Information</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="row info-row">
                                                                <div class="col-sm-6 info-label">Mode of Transport:</div>
                                                                <div class="col-sm-6 info-value"><?php echo htmlspecialchars($user_data['transport_mode'] ?? 'N/A'); ?></div>
                                                            </div>
                                                            <?php if (isset($user_data['transport_mode']) && $user_data['transport_mode'] === 'School Transport'): ?>
                                                                <hr>
                                                                <div class="row info-row">
                                                                    <div class="col-sm-6 info-label">Route:</div>
                                                                    <div class="col-sm-6 info-value"><?php echo htmlspecialchars($user_data['route_name'] ?? 'N/A'); ?></div>
                                                                </div>
                                                                <hr>
                                                                <div class="row info-row">
                                                                    <div class="col-sm-6 info-label">Stop:</div>
                                                                    <div class="col-sm-6 info-value"><?php echo htmlspecialchars($user_data['stop_name'] ?? 'N/A'); ?></div>
                                                                </div>
                                                            <?php elseif (isset($user_data['transport_mode']) && $user_data['transport_mode'] === 'Self Transport'): ?>
                                                                <hr>
                                                                <div class="row info-row">
                                                                    <div class="col-sm-6 info-label">Self Transport Mode:</div>
                                                                    <div class="col-sm-6 info-value"><?php echo htmlspecialchars($user_data['self_transport_mode'] ?? 'N/A'); ?></div>
                                                                </div>
                                                                <?php if (isset($user_data['self_transport_mode']) && ($user_data['self_transport_mode'] === 'Bike' || $user_data['self_transport_mode'] === 'Car')): ?>
                                                                    <hr>
                                                                    <div class="row info-row">
                                                                        <div class="col-sm-6 info-label">Vehicle Number:</div>
                                                                        <div class="col-sm-6 info-value"><?php echo htmlspecialchars($user_data['vehicle_number'] ?? 'N/A'); ?></div>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="row info-row">
                                                                        <div class="col-sm-6 info-label">License Number:</div>
                                                                        <div class="col-sm-6 info-value"><?php echo htmlspecialchars($user_data['license_number'] ?? 'N/A'); ?></div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($user_role === 'teacher'): ?>
                                <div class="row">
                                    <div class="col-12 mb-4">
                                        <div class="card shadow">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-briefcase mr-2"></i>Professional Information</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">School Name:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['school_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Date of Joining:</div>
                                                            <div class="col-sm-7 info-value"><?php echo !empty($user_data['date_of_joining']) ? htmlspecialchars(date('F j, Y', strtotime($user_data['date_of_joining']))) : 'N/A'; ?></div>
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Qualification:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['qualification'] ?? 'N/A'); ?></div>
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Subject:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['subject'] ?? 'N/A'); ?></div>
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Languages Known:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['language_known'] ?? 'N/A'); ?></div>
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Experience:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars($user_data['experience'] ?? '0'); ?> years</div>
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Assigned Standards:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars(str_replace(['{', '}'], '', $user_data['std'] ?? 'N/A')); ?></div>
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Is Class Teacher:</div>
                                                            <div class="col-sm-7 info-value"><?php if ($user_data['class_teacher']): ?><span class="badge badge-success">Yes</span><small class="text-muted"> (Std: <?php echo htmlspecialchars($user_data['class_teacher_std']); ?>)</small><?php else: ?><span class="badge badge-secondary">No</span><?php endif; ?></div>
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Salary:</div>
                                                            <div class="col-sm-7 info-value salary-display">₹<?php echo number_format($user_data['salary'] ?? 0, 2); ?></div>
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Assigned Batch:</div>
                                                            <div class="col-sm-7 info-value">
                                                                <span class="badge badge-<?php echo ($user_data['batch'] ?? '') === 'Morning' ? 'primary' : 'warning'; ?> p-2"><?php echo htmlspecialchars($user_data['batch'] ?? 'N/A'); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-4">
                                        <div class="card shadow">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bus"></i> Transport Information</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="row info-row">
                                                            <div class="col-sm-4 info-label">Mode of Transport:</div>
                                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['transport_mode'] ?? 'N/A'); ?></div>
                                                        </div>
                                                        <?php if (isset($user_data['transport_mode']) && $user_data['transport_mode'] === 'School Transport'): ?>
                                                            <hr>
                                                            <div class="row info-row">
                                                                <div class="col-sm-4 info-label">Route:</div>
                                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['route_name'] ?? 'N/A'); ?></div>
                                                            </div>
                                                            <hr>
                                                            <div class="row info-row">
                                                                <div class="col-sm-4 info-label">Stop:</div>
                                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['stop_name'] ?? 'N/A'); ?></div>
                                                            </div>
                                                        <?php elseif (isset($user_data['transport_mode']) && $user_data['transport_mode'] === 'Self Transport'): ?>
                                                            <hr>
                                                            <div class="row info-row">
                                                                <div class="col-sm-4 info-label">Self Transport Mode:</div>
                                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['self_transport_mode'] ?? 'N/A'); ?></div>
                                                            </div>
                                                            <?php if (isset($user_data['self_transport_mode']) && ($user_data['self_transport_mode'] === 'Bike' || $user_data['self_transport_mode'] === 'Car')): ?>
                                                                <hr>
                                                                <div class="row info-row">
                                                                    <div class="col-sm-4 info-label">Vehicle Number:</div>
                                                                    <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['vehicle_number'] ?? 'N/A'); ?></div>
                                                                </div>
                                                                <hr>
                                                                <div class="row info-row">
                                                                    <div class="col-sm-4 info-label">License Number:</div>
                                                                    <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['license_number'] ?? 'N/A'); ?></div>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($user_role === 'hr'): ?>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-lg-6 mb-4">
                                            <div class="card shadow h-100">
                                                <div class="card-header py-3">
                                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-briefcase"></i> Professional Information</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">School Name:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_name'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Date of Joining:</div>
                                                        <div class="col-sm-8 info-value"><?php echo !empty($user_data['date_of_joining']) ? htmlspecialchars(date('F j, Y', strtotime($user_data['date_of_joining']))) : 'N/A'; ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Qualification:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['qualification'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Experience:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['experience'] ?? '0'); ?> years</div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Languages Known:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['language_known'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Salary:</div>
                                                        <div class="col-sm-8 info-value salary-display">₹<?php echo number_format($user_data['salary'] ?? 0, 2); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 mb-4">
                                            <div class="card shadow h-100">
                                                <div class="card-header py-3">
                                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-clock"></i> Batch & Timings</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Assigned Batch:</div>
                                                        <div class="col-sm-8 info-value">
                                                            <span class="badge badge-<?php echo ($user_data['batch'] ?? '') === 'Morning' ? 'primary' : 'warning'; ?> p-2"><?php echo htmlspecialchars($user_data['batch'] ?? 'N/A'); ?></span>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <h6 class="info-label mb-2">Weekly Schedule:</h6>
                                                    <?php if (!empty($timings)): ?>
                                                        <table class="table table-sm table-bordered table-striped table-timings">
                                                            <tbody>
                                                                <?php
                                                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                                foreach ($days as $day):
                                                                    $day_timing = $timings[$day] ?? null;
                                                                ?>
                                                                    <tr>
                                                                        <th><?php echo $day; ?></th>
                                                                        <td>
                                                                            <?php if ($day_timing && !empty($day_timing['is_closed'])): ?>
                                                                                <span class="badge badge-secondary">Closed</span>
                                                                            <?php elseif ($day_timing && !empty($day_timing['opens_at'])): ?>
                                                                                <?php echo date("g:i A", strtotime($day_timing['opens_at'])); ?> - <?php echo date("g:i A", strtotime($day_timing['closes_at'])); ?>
                                                                            <?php else: ?>
                                                                                <span class="text-muted">Not Set</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning small">No weekly schedule has been set for this HR user.</div>
                                                    <?php endif; ?>
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
                                                        <div class="col-md-12">
                                                            <div class="row info-row">
                                                                <div class="col-sm-4 info-label">Mode of Transport:</div>
                                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['transport_mode'] ?? 'N/A'); ?></div>
                                                            </div>
                                                            <?php if (isset($user_data['transport_mode']) && $user_data['transport_mode'] === 'School Transport'): ?>
                                                                <hr>
                                                                <div class="row info-row">
                                                                    <div class="col-sm-4 info-label">Route:</div>
                                                                    <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['route_name'] ?? 'N/A'); ?></div>
                                                                </div>
                                                                <hr>
                                                                <div class="row info-row">
                                                                    <div class="col-sm-4 info-label">Stop:</div>
                                                                    <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['stop_name'] ?? 'N/A'); ?></div>
                                                                </div>
                                                            <?php elseif (isset($user_data['transport_mode']) && $user_data['transport_mode'] === 'Self Transport'): ?>
                                                                <hr>
                                                                <div class="row info-row">
                                                                    <div class="col-sm-4 info-label">Self Transport Mode:</div>
                                                                    <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['self_transport_mode'] ?? 'N/A'); ?></div>
                                                                </div>
                                                                <?php if (isset($user_data['self_transport_mode']) && ($user_data['self_transport_mode'] === 'Bike' || $user_data['self_transport_mode'] === 'Car')): ?>
                                                                    <hr>
                                                                    <div class="row info-row">
                                                                        <div class="col-sm-4 info-label">Vehicle Number:</div>
                                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['vehicle_number'] ?? 'N/A'); ?></div>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="row info-row">
                                                                        <div class="col-sm-4 info-label">License Number:</div>
                                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['license_number'] ?? 'N/A'); ?></div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($user_role === 'librarian'): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="card shadow h-100">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-briefcase"></i> Professional Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">School Name:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_name'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Date of Joining:</div>
                                                <div class="col-sm-8 info-value"><?php echo !empty($user_data['date_of_joining']) ? htmlspecialchars(date('F j, Y', strtotime($user_data['date_of_joining']))) : 'N/A'; ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">School Email:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_email'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">School Phone:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_phone'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Qualification:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['qualification'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Salary:</div>
                                                <div class="col-sm-8 info-value salary-display">₹<?php echo number_format($user_data['salary'] ?? 0, 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card shadow h-100">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-clock"></i> Batch & Timings</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Assigned Batch:</div>
                                                <div class="col-sm-8 info-value">
                                                    <span class="badge badge-<?php echo ($user_data['batch'] ?? '') === 'Morning' ? 'primary' : 'warning'; ?> p-2"><?php echo htmlspecialchars($user_data['batch'] ?? 'N/A'); ?></span>
                                                </div>
                                            </div>
                                            <hr>
                                            <h6 class="info-label mb-2">Weekly Schedule:</h6>
                                            <?php if (!empty($timings)): ?>
                                                <table class="table table-sm table-bordered table-striped table-timings">
                                                    <tbody>
                                                        <?php
                                                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                        foreach ($days as $day):
                                                            $day_timing = $timings[$day] ?? null;
                                                        ?>
                                                            <tr>
                                                                <th><?php echo $day; ?></th>
                                                                <td>
                                                                    <?php if ($day_timing && !empty($day_timing['is_closed'])): ?>
                                                                        <span class="badge badge-secondary">Closed</span>
                                                                    <?php elseif ($day_timing && !empty($day_timing['opens_at'])): ?>
                                                                        <?php echo date("g:i A", strtotime($day_timing['opens_at'])); ?> - <?php echo date("g:i A", strtotime($day_timing['closes_at'])); ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Not Set</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                <div class="alert alert-warning small">No weekly schedule has been set for this principal.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-4 ">
                                    <div class="card shadow">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bus"></i> Transport Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Mode of Transport:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['transport_mode'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <?php if (isset($user_data['transport_mode']) && $user_data['transport_mode'] === 'School Transport'): ?>
                                                        <hr>
                                                        <div class="row info-row">
                                                            <div class="col-sm-4 info-label">Route:</div>
                                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['route_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                        <hr>
                                                        <div class="row info-row">
                                                            <div class="col-sm-4 info-label">Stop:</div>
                                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['stop_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    <?php elseif (isset($user_data['transport_mode']) && $user_data['transport_mode'] === 'Self Transport'): ?>
                                                        <hr>
                                                        <div class="row info-row">
                                                            <div class="col-sm-4 info-label">Self Transport Mode:</div>
                                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['self_transport_mode'] ?? 'N/A'); ?></div>
                                                        </div>
                                                        <?php if (isset($user_data['self_transport_mode']) && ($user_data['self_transport_mode'] === 'Bike' || $user_data['self_transport_mode'] === 'Car')): ?>
                                                            <hr>
                                                            <div class="row info-row">
                                                                <div class="col-sm-4 info-label">Vehicle Number:</div>
                                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['vehicle_number'] ?? 'N/A'); ?></div>
                                                            </div>
                                                            <hr>
                                                            <div class="row info-row">
                                                                <div class="col-sm-4 info-label">License Number:</div>
                                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['license_number'] ?? 'N/A'); ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($user_role === 'principal'): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="card shadow h-100">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-briefcase"></i> Professional Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">School Name:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_name'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Date of Joining:</div>
                                                <div class="col-sm-8 info-value"><?php echo !empty($user_data['date_of_joining']) ? htmlspecialchars(date('F j, Y', strtotime($user_data['date_of_joining']))) : 'N/A'; ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Qualification:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['qualification'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Salary:</div>
                                                <div class="col-sm-8 info-value salary-display">₹<?php echo number_format($user_data['salary'] ?? 0, 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card shadow h-100">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-school"></i> School Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">School Name:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_name'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Date of Opening:</div>
                                                <div class="col-sm-8 info-value"><?php echo !empty($user_data['school_opening']) ? htmlspecialchars(date('F j, Y', strtotime($user_data['school_opening']))) : 'N/A'; ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">School Email:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_email'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">School Phone:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_phone'] ?? 'N/A'); ?></div>
                                            </div>
                                            <hr>
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">School Address:</div>
                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['school_address'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card shadow h-100">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-clock"></i> Batch & Timings</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row info-row">
                                                <div class="col-sm-4 info-label">Assigned Batch:</div>
                                                <div class="col-sm-8 info-value">
                                                    <span class="badge badge-<?php echo ($user_data['batch'] ?? '') === 'Morning' ? 'primary' : 'warning'; ?> p-2"><?php echo htmlspecialchars($user_data['batch'] ?? 'N/A'); ?></span>
                                                </div>
                                            </div>
                                            <hr>
                                            <h6 class="info-label mb-2">Weekly Schedule:</h6>
                                            <?php if (!empty($timings)): ?>
                                                <table class="table table-sm table-bordered table-striped table-timings">
                                                    <tbody>
                                                        <?php
                                                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                        foreach ($days as $day):
                                                            $day_timing = $timings[$day] ?? null;
                                                        ?>
                                                            <tr>
                                                                <th><?php echo $day; ?></th>
                                                                <td>
                                                                    <?php if ($day_timing && !empty($day_timing['is_closed'])): ?>
                                                                        <span class="badge badge-secondary">Closed</span>
                                                                    <?php elseif ($day_timing && !empty($day_timing['opens_at'])): ?>
                                                                        <?php echo date("g:i A", strtotime($day_timing['opens_at'])); ?> - <?php echo date("g:i A", strtotime($day_timing['closes_at'])); ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Not Set</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                <div class="alert alert-warning small">No weekly schedule has been set for this principal.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card shadow h-100">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bus"></i> Transport Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Mode of Transport:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['transport_mode'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <?php if (isset($user_data['transport_mode']) && $user_data['transport_mode'] === 'School Transport'): ?>
                                                        <hr>
                                                        <div class="row info-row">
                                                            <div class="col-sm-4 info-label">Route:</div>
                                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['route_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                        <hr>
                                                        <div class="row info-row">
                                                            <div class="col-sm-4 info-label">Stop:</div>
                                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['stop_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    <?php elseif (isset($user_data['transport_mode']) && $user_data['transport_mode'] === 'Self Transport'): ?>
                                                        <hr>
                                                        <div class="row info-row">
                                                            <div class="col-sm-4 info-label">Self Transport Mode:</div>
                                                            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['self_transport_mode'] ?? 'N/A'); ?></div>
                                                        </div>
                                                        <?php if (isset($user_data['self_transport_mode']) && ($user_data['self_transport_mode'] === 'Bike' || $user_data['self_transport_mode'] === 'Car')): ?>
                                                            <hr>
                                                            <div class="row info-row">
                                                                <div class="col-sm-4 info-label">Vehicle Number:</div>
                                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['vehicle_number'] ?? 'N/A'); ?></div>
                                                            </div>
                                                            <hr>
                                                            <div class="row info-row">
                                                                <div class="col-sm-4 info-label">License Number:</div>
                                                                <div class="col-sm-8 info-value"><?php echo htmlspecialchars($user_data['license_number'] ?? 'N/A'); ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
                // If it's NOT an AJAX request, print the full HTML footer.
                if (!is_ajax_request()):
                    include_once '../../includes/footer.php';
                ?>

                </div>
            </div>
        </div>
        <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
        <?php include_once "../../includes/logout_modal.php" ?>
        <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <form id="changePasswordForm" action="process_password_change.php" method="POST">
                            <div class="form-group"><label for="current_password">Current Password</label><input type="password" class="form-control" id="current_password" name="current_password" required></div>
                            <div class="form-group"><label for="new_password">New Password</label><input type="password" class="form-control" id="new_password" name="new_password" required></div>
                            <div class="form-group"><label for="confirm_password">Confirm New Password</label><input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div id="password_match_error" class="text-danger mt-2" style="display: none;">Passwords do not match.</div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save changes</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>
        <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#changePasswordForm').on('submit', function(e) {
                    if ($('#new_password').val() !== $('#confirm_password').val()) {
                        e.preventDefault();
                        $('#password_match_error').show();
                    } else {
                        $('#password_match_error').hide();
                    }
                });
            });
        </script>
    </body>
    <?php
                    // Add this block at the very end of the file
                    if (is_ajax_request()) {
                        // Get the captured HTML
                        $content = ob_get_clean();

                        // Extract just the main content area for the AJAX response
                        if (preg_match('/<div class="container-fluid".*?>(.*?)<\/div>/s', $content, $matches)) {
                            echo '<div class="container-fluid">' . $matches[1] . '</div>';
                        } else {
                            // Fallback if the main container isn't found
                            echo $content;
                        }
                        // Stop the script for AJAX requests
                        exit;
                    }
    ?>

    </html>
<?php endif; ?>