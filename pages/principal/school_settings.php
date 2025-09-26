<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if (!$role || $role !== 'principal') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
$school_settings = [];

try {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $principalDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $school_id = $principalDetails['school_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $passing_percentage = $_POST['passing_percentage'];
        $minimum_attendance_percentage = $_POST['minimum_attendance_percentage']; // New field

        // Modified UPDATE query to include the new attendance setting
        $update_stmt = $conn->prepare("UPDATE school SET passing_percentage = ?, minimum_attendance_percentage = ? WHERE id = ?");

        if ($update_stmt->execute([$passing_percentage, $minimum_attendance_percentage, $school_id])) {
            $successMessage = "School settings have been updated successfully!";
        } else {
            $errorMessage = "Failed to update settings. Please try again.";
        }
    }

    $settings_stmt = $conn->prepare("SELECT * FROM school WHERE id = ?");
    $settings_stmt->execute([$school_id]);
    $school_settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "A database error occurred: " . $e->getMessage();
    error_log("School Settings Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>School Settings - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php
        if (!$is_ajax_request) {
            include '../../includes/sidebar.php';
        }
        ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
                if (!$is_ajax_request) {
                    include '../../includes/header.php';
                }
                ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">School Settings</h1>
                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $successMessage; ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                    <?php endif; ?>
                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Result Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group row">
                                    <label for="passing_percentage" class="col-sm-4 col-form-label">Minimum Passing Percentage (%)</label>
                                    <div class="col-sm-8">
                                        <input type="number" class="form-control" id="passing_percentage" name="passing_percentage" value="<?php echo htmlspecialchars($school_settings['passing_percentage'] ?? '33.00'); ?>" step="0.01" min="0" max="100" required>
                                        <small class="form-text text-muted">e.g., 33.00. Used to determine pass/fail status.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Attendance Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group row">
                                    <label for="minimum_attendance_percentage" class="col-sm-4 col-form-label">Minimum Attendance Percentage (%)</label>
                                    <div class="col-sm-8">
                                        <input type="number" class="form-control" id="minimum_attendance_percentage" name="minimum_attendance_percentage" value="<?php echo htmlspecialchars($school_settings['minimum_attendance_percentage'] ?? '75.00'); ?>" step="0.01" min="0" max="100" required>
                                        <small class="form-text text-muted">e.g., 75.00. Used for student eligibility for exams.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-12"><button type="submit" class="btn btn-primary">Save All Settings</button></div>
                        </div>
                    </form>
                </div>
            </div>
           <?php
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?> 
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
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