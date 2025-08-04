<?php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Authorization check for 'principal' role
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
if (!$role || $role !== 'principal') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// Fetch principal's school_id from the principal table
$stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$principalDetails = $result->fetch_assoc();
$school_id = $principalDetails['school_id'];
$stmt->close();

// Handle form submission to UPDATE settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passing_percentage = $_POST['passing_percentage'];

    // Update the school table
    $update_stmt = $conn->prepare("UPDATE school SET passing_percentage = ? WHERE id = ?");
    
    // ## FIX: The type string was changed from "dddii" to "di" to match the 2 variables ##
    $update_stmt->bind_param("di", 
        $passing_percentage, 
        $school_id
    );
    
    if ($update_stmt->execute()) {
        $successMessage = "School settings have been updated successfully!";
    } else {
        $errorMessage = "Failed to update settings. Please try again.";
    }
    $update_stmt->close();
}

// Fetch all current school settings to display in the form
$settings_stmt = $conn->prepare("SELECT * FROM school WHERE id = ?");
$settings_stmt->bind_param("i", $school_id);
$settings_stmt->execute();
$school_settings = $settings_stmt->get_result()->fetch_assoc();
$settings_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>School Settings - School Management System</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <!-- <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css"> -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
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
                    <h1 class="h3 mb-4 text-gray-800">School Settings</h1>

                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $successMessage; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
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
                                        <input type="number" class="form-control" id="passing_percentage" name="passing_percentage" 
                                               value="<?php echo htmlspecialchars($school_settings['passing_percentage'] ?? '33.00'); ?>" 
                                               step="0.01" min="0" max="100" required>
                                        <small class="form-text text-muted">e.g., 33.00. Used to determine pass/fail status.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <button type="submit" class="btn btn-primary">Save All Settings</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
        <?php include_once "../../includes/logout_modal.php"?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#getLocationBtn').on('click', function() {
            var $btn = $(this);
            var $msgBox = $('#location-message');
            $msgBox.html(''); // Clear previous messages

            if (!navigator.geolocation) {
                $msgBox.html('<div class="alert alert-warning">Geolocation is not supported by your browser.</div>');
                return;
            }

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Getting Location...');

            navigator.geolocation.getCurrentPosition(function(position) {
                // Success
                var lat = position.coords.latitude;
                var lon = position.coords.longitude;

                $('#latitude').val(lat.toFixed(8));
                $('#longitude').val(lon.toFixed(8));

                $msgBox.html('<div class="alert alert-success">Location fetched successfully!</div>');
                $btn.prop('disabled', false).html('<i class="fas fa-map-marker-alt fa-sm"></i> Get Current Location');
            }, function() {
                // Error
                $msgBox.html('<div class="alert alert-danger">Unable to retrieve location. Please grant permission and try again.</div>');
                $btn.prop('disabled', false).html('<i class="fas fa-map-marker-alt fa-sm"></i> Get Current Location');
            });
        });
    });
    </script>
</body>
</html>