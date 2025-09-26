<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once '../../includes/ajax_helpers.php';

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if this is an AJAX request
if (is_ajax_request()) {
    // Start output buffering to capture the HTML
    ob_start();
}

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

// Read the filter value that was sent from the list page
$from_list_filter = isset($_GET['from_list_filter']) ? $_GET['from_list_filter'] : ''; 

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($teacher_id <= 0) {
    header("Location: teacher_list.php?error=Invalid teacher ID");
    exit;
}

$teacher = null;
$timings = [];

try {
    // Check access based on role and school_id
    $query_access = "SELECT school_id FROM teacher WHERE id = ?";
    $stmt_access = $conn->prepare($query_access);
    $stmt_access->execute([$teacher_id]);
    $target_school_id = $stmt_access->fetchColumn();

    $user_school_id = null;
    if ($role === 'principal') {
        $stmt_user_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
        $stmt_user_school->execute([$current_user_id]);
        $user_school_id = $stmt_user_school->fetchColumn();
    } elseif ($role === 'hr') {
        $stmt_user_school = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
        $stmt_user_school->execute([$current_user_id]);
        $user_school_id = $stmt_user_school->fetchColumn();
    }
    
    // Authorization check for principal and HR roles
    if (($role === 'principal' || $role === 'hr') && $target_school_id != $user_school_id) {
        $redirect_url = ($role === 'hr') ? 'hr_teacher_list.php' : 'teacher_list.php';
        header("Location: " . $redirect_url . "?error=Unauthorized access to this profile.");
        exit;
    }
    
    // Authorization check for teacher role (self-view only)
    if ($role === 'teacher' && $teacher_id != $current_user_id) {
        header("Location: ../../dashboard.php?error=Unauthorized access to this profile.");
        exit;
    }

    $query_teacher = "SELECT t.*, s.school_name, s.address as school_address, s.phone as school_phone, s.email as school_email,
                      st.stop_name, r.route_name, v.vehicle_number as school_vehicle_number
                      FROM teacher t
                      LEFT JOIN school s ON t.school_id = s.id
                      LEFT JOIN stops st ON t.stop_id = st.id
                      LEFT JOIN routes r ON st.route_id = r.id
                      LEFT JOIN vehicles v ON r.vehicle_id = v.id
                      WHERE t.id = ?";
    $stmt_teacher = $conn->prepare($query_teacher);
    $stmt_teacher->execute([$teacher_id]);
    $teacher = $stmt_teacher->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        header("Location: teacher_list.php?error=Teacher not found");
        exit;
    }

    $query_timings = "SELECT * FROM teacher_timings WHERE teacher_id = ?";
    $stmt_timings = $conn->prepare($query_timings);
    $stmt_timings->execute([$teacher_id]);
    while ($row = $stmt_timings->fetch(PDO::FETCH_ASSOC)) {
        $timings[$row['day_of_week']] = $row;
    }
} catch (PDOException $e) {
    error_log("View Teacher Error: " . $e->getMessage());
    die("A database error occurred.");
}

$photo_path = $teacher['teacher_image'];
$default_photo = "../../assets/images/unisex.png";
$final_photo_path = $default_photo;

// Correctly check if the file exists on the server based on the database path
if (!empty($photo_path)) {
    $full_filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $photo_path;
    if (file_exists($full_filesystem_path) && is_file($full_filesystem_path)) {
        $final_photo_path = $photo_path;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>View Teacher - <?php echo htmlspecialchars($teacher['teacher_name']); ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/profile.css">
    <link rel="stylesheet" href="../../assets/css/teacher_view.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
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
?>                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Teacher's Details</h1>
                        <div>
                            <?php if ($role === 'principal' || $role === 'hr'): ?>
                                <a href="teacher_list.php?std=<?php echo urlencode($from_list_filter); ?>" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                                <a href="edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit fa-sm"></i> Edit Teacher</a>
                            <?php else: ?>
                                <a href="../../dashboard.php" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left fa-sm"></i> Back to Dashboard</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-camera"></i> Teacher Photo</h6>
                                </div>
                                <div class="card-body text-center">
                                    <img src="<?php echo htmlspecialchars($final_photo_path); ?>" alt="<?php echo htmlspecialchars($teacher['teacher_name']); ?>" class="profile-photo mb-3 mt-3 h-50 w-50">
                                    <h4 class="font-weight-bold text-gray-800 mt-2"><?php echo htmlspecialchars($teacher['teacher_name']); ?></h4>
                                    <p class="text-muted"><?php echo htmlspecialchars($teacher['subject']); ?> Specialist</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i> Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Full Name:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['teacher_name'] ?? 'N/A'); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Email Address:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['email']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Phone Number:</div>
                                        <div class="sm-8 info-value"><?php echo htmlspecialchars($teacher['phone']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Date of Birth:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars(date("d F Y", strtotime($teacher['dob']))); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Gender:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['gender']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Blood Group:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['blood_group']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Address:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['address']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-briefcase"></i> Professional Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-5 info-label">School Name:</div>
                                        <div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['school_name']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-5 info-label">Qualification:</div>
                                        <div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['qualification']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-5 info-label">Date of Joining:</div>
                                        <div class="col-sm-7 info-value">
                                            <?php
                                            if (!empty($teacher['date_of_joining'])) {
                                                echo htmlspecialchars(date("d F Y", strtotime($teacher['date_of_joining'])));
                                            } else {
                                                echo "N/A";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-5 info-label">Subject:</div>
                                        <div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['subject']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-5 info-label">Teaching Standards:</div>
                                        <div class="col-sm-7 info-value"><?php echo htmlspecialchars(str_replace(['{', '}'], '', $teacher['std'])); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-5 info-label">Experience:</div>
                                        <div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['experience']); ?> Years</div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-5 info-label">Is Class Teacher:</div>
                                        <div class="col-sm-7 info-value"><?php if ($teacher['class_teacher']): ?><span class="badge badge-success">Yes</span><small class="text-muted"> (Std: <?php echo htmlspecialchars($teacher['class_teacher_std']); ?>)</small><?php else: ?><span class="badge badge-danger">No</span><?php endif; ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-5 info-label">Salary:</div>
                                        <div class="col-sm-7 info-value font-weight-bold text-success">₹<?php echo number_format($teacher['salary'], 2); ?></div>
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
                                    <div class="row mb-3">
                                        <div class="col-sm-4 info-label">Assigned Batch:</div>
                                        <div class="col-sm-8 info-value"><span class="col-sm-8 info-value<?php echo ($teacher['batch'] == 'Morning') ?>"><?php echo htmlspecialchars($teacher['batch'] ?? 'N/A'); ?></span></div>
                                    </div>
                                    <hr>
                                    <h6 class="info-label mb-2">Weekly Schedule:</h6>
                                    <?php if (!empty($timings)): ?>
                                        <table class="table table-sm table-bordered table-striped table-timings">
                                            <tbody>
                                                <?php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                foreach ($days as $day): $day_timing = $timings[$day] ?? null; ?>
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
                                        <div class="alert alert-warning small">No weekly schedule has been set for this teacher.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bus"></i> Transportation Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="row info-row">
                                                <div class="col-sm-5 info-label">Mode of Transport:</div>
                                                <div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['transport_mode'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                        <?php if (isset($teacher['transport_mode']) && $teacher['transport_mode'] === 'School Transport'): ?>
                                            <div class="col-md-8">
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Route:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['route_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Stop:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['stop_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="row info-row">
                                                            <div class="col-sm-5 info-label">Vehicle:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['school_vehicle_number'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php elseif (isset($teacher['transport_mode']) && $teacher['transport_mode'] === 'Self Transport'): ?>
                                            <div class="col-md-8">
                                                <div class="row info-row">
                                                    <div class="col-sm-4 info-label">Self Transport Mode:</div>
                                                    <div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['self_transport_mode'] ?? 'N/A'); ?></div>
                                                </div>
                                                <?php if (isset($teacher['self_transport_mode']) && ($teacher['self_transport_mode'] === 'Bike' || $teacher['self_transport_mode'] === 'Car')): ?>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">Vehicle Number:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['vehicle_number'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-4 info-label">License Number:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['license_number'] ?? 'N/A'); ?></div>
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
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?>        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
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