<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
include_once '../../includes/log_system.php'; // Log system included

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

$principal_id = $userId;
$school_id = null;
$school_data = null;
$message = '';
$message_type = '';

try {
    // Get principal's school_id
    $stmt_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt_school->execute([$principal_id]);
    $school_id = $stmt_school->fetchColumn();

    if (!$school_id) {
        die("Could not determine your school.");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $passing_percentage = $_POST['passing_percentage'];
        $attendance_percentage = $_POST['minimum_attendance_percentage'];
        $facebook_url = trim($_POST['facebook_url']);
        $twitter_url = trim($_POST['twitter_url']);
        $instagram_url = trim($_POST['instagram_url']);

        // Validate URLs
        if (!empty($facebook_url) && !filter_var($facebook_url, FILTER_VALIDATE_URL)) {
            $message = "Invalid Facebook URL.";
            $message_type = 'danger';
        } elseif (!empty($twitter_url) && !filter_var($twitter_url, FILTER_VALIDATE_URL)) {
            $message = "Invalid Twitter URL.";
            $message_type = 'danger';
        } elseif (!empty($instagram_url) && !filter_var($instagram_url, FILTER_VALIDATE_URL)) {
            $message = "Invalid Instagram URL.";
            $message_type = 'danger';
        } else {
            $stmt_update = $conn->prepare("
                UPDATE school 
                SET 
                    passing_percentage = ?, 
                    minimum_attendance_percentage = ?,
                    facebook_url = ?,
                    twitter_url = ?,
                    instagram_url = ?
                WHERE id = ?
            ");
            if ($stmt_update->execute([$passing_percentage, $attendance_percentage, $facebook_url, $twitter_url, $instagram_url, $school_id])) {
                $message = "School settings updated successfully!";
                $message_type = 'success';
                // Log the successful action
                log_interaction($role, $userId, "SETTINGS: Updated school settings.", $userName);
            } else {
                $message = "Failed to update settings.";
                $message_type = 'danger';
                log_interaction($role, $userId, "SETTINGS ERROR: Failed to update school settings.", $userName);
            }
        }
    }

    // Fetch current school settings
    $stmt_data = $conn->prepare("SELECT * FROM school WHERE id = ?");
    $stmt_data->execute([$school_id]);
    $school_data = $stmt_data->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
    $message_type = 'danger';
    // Log the database error
    log_interaction($role, $userId, "SETTINGS ERROR: A database error occurred on the school settings page. " . $e->getMessage(), $userName);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Settings</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
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
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">School Settings</h1>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Academic & Social Settings</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($school_data): ?>
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="passing_percentage">Passing Percentage (%)</label>
                                        <input type="number" class="form-control" id="passing_percentage" name="passing_percentage" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($school_data['passing_percentage']); ?>" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="minimum_attendance_percentage">Minimum Attendance Percentage (%)</label>
                                        <input type="number" class="form-control" id="minimum_attendance_percentage" name="minimum_attendance_percentage" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($school_data['minimum_attendance_percentage']); ?>" required>
                                    </div>
                                </div>
                                <hr>
                                <h5 class="mb-3">Social Media Links</h5>
                                <div class="form-group">
                                    <label for="facebook_url">Facebook URL</label>
                                    <input type="url" class="form-control" id="facebook_url" name="facebook_url" placeholder="https://facebook.com/yourschool" value="<?php echo htmlspecialchars($school_data['facebook_url'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="twitter_url">Twitter (X) URL</label>
                                    <input type="url" class="form-control" id="twitter_url" name="twitter_url" placeholder="https://twitter.com/yourschool" value="<?php echo htmlspecialchars($school_data['twitter_url'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="instagram_url">Instagram URL</label>
                                    <input type="url" class="form-control" id="instagram_url" name="instagram_url" placeholder="https://instagram.com/yourschool" value="<?php echo htmlspecialchars($school_data['instagram_url'] ?? ''); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </form>
                            <?php else: ?>
                                <div class="alert alert-warning">Could not load school data.</div>
                            <?php endif; ?>
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
    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>