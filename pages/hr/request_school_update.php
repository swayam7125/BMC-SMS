<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/log_system.php";

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Define the base web path for your project
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// --- Authorization Check (ensure only HR can access) ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$hr_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'HR User';

if ($role !== 'hr' || !$hr_id) {
    header("Location: " . BASE_WEB_PATH . "login.php?error=Unauthorized");
    exit;
}

$school = null;
$schools_list = [];
$errors = [];

// --- A. Fetch HR's assigned school ID ---
try {
    $stmt_hr_school = $conn->prepare('SELECT school_id FROM "hr" WHERE id = ?');
    $stmt_hr_school->execute([$hr_id]);
    $hr_data = $stmt_hr_school->fetch(PDO::FETCH_ASSOC);
    $hr_school_id = $hr_data['school_id'] ?? null;
} catch (PDOException $e) {
    $errors[] = "Database error fetching HR school ID: " . $e->getMessage();
}

// --- B. Handle Form Submission (Save as Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax_request()) {
    $school_id_to_edit = filter_var($_POST['school_id_to_edit'] ?? null, FILTER_SANITIZE_NUMBER_INT);
    $reason = trim($_POST['reason'] ?? '');
    
    // Check if the HR user is allowed to modify this school
    if (empty($school_id_to_edit) || $school_id_to_edit != $hr_school_id) {
         Response::send(['success' => false, 'message' => 'Invalid school ID or not authorized for this school.']);
         exit;
    }

    // Capture the rest of the form data for JSONB storage (excluding files/reason/id)
    $request_data = $_POST;
    unset($request_data['school_id_to_edit']);
    unset($request_data['reason']);
    
    if (empty($reason) || strlen($reason) < 10) {
        $errors[] = "A detailed reason (at least 10 characters) for the change is mandatory.";
    }

    if (!empty($errors)) {
        Response::send(['success' => false, 'message' => implode('<br>', $errors)]);
        exit;
    }
    
    // Clean up arrays for JSON storage (if coming from multiple selects)
    foreach ($request_data as $key => $value) {
        if (is_array($value)) {
            // For array inputs, convert them to a simple comma-separated string if possible
            $request_data[$key] = implode(',', $value);
        }
    }
    
    // --- DATABASE INSERTION (REQUEST) ---
    try {
        $conn->beginTransaction();

        $sql_insert = "INSERT INTO school_update_requests 
                       (school_id, hr_id, request_data, reason, status)
                       VALUES (?, ?, ?::jsonb, ?, 'Pending')";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->execute([
            $school_id_to_edit, 
            $hr_id, 
            json_encode($request_data),
            $reason
        ]);
        $new_request_id = $conn->lastInsertId();

        // 3. Send Notification to Super Admin (ID 8)
        $notif_message = "New School Update Request (ID: {$new_request_id}) from HR.";
        $notif_link = "/BMC-SMS/pages/bmc/manage_school_requests.php"; // Assuming a new SA management page
        
        $sa_users_stmt = $conn->query("SELECT id FROM users WHERE role = 'superadmin'");
        $sa_users = $sa_users_stmt->fetchAll(PDO::FETCH_COLUMN);

        $notifications_to_insert = [];
        foreach ($sa_users as $sa_id) {
            $notifications_to_insert[] = [
                'user_id' => $sa_id,
                'message' => $notif_message,
                'link' => $notif_link,
                'type' => 'school_update_request'
            ];
        }

        if (!empty($notifications_to_insert)) {
            $insert_notif_sql = "INSERT INTO notifications (user_id, message, link, type) VALUES ";
            $insert_notif_values = [];
            $insert_notif_placeholders = [];
            foreach ($notifications_to_insert as $notif) {
                $insert_notif_placeholders[] = "(?, ?, ?, ?)";
                $insert_notif_values[] = $notif['user_id'];
                $insert_notif_values[] = $notif['message'];
                $insert_notif_values[] = $notif['link'];
                $insert_notif_values[] = $notif['type'];
            }
            $insert_notif_sql .= implode(', ', $insert_notif_placeholders);
            $stmt_notif = $conn->prepare($insert_notif_sql);
            $stmt_notif->execute($insert_notif_values);
        }
        
        $conn->commit();
        log_interaction($role, $hr_id, "REQUEST SUCCESS: Submitted school update request (ID: {$new_request_id}) for School ID: {$school_id_to_edit}", $acting_user_name);

        Response::send([
            'success' => true, 
            'message' => 'Update request submitted successfully! Waiting for Super Admin approval.',
            'redirect' => BASE_WEB_PATH . 'dashboard.php'
        ]);

    } catch (PDOException $e) {
        $conn->rollBack();
        $message = "Database error: " . $e->getMessage();
        log_interaction($role, $hr_id, "REQUEST FAILED: " . $message, $acting_user_name);
        Response::send(['success' => false, 'message' => $message]);
    }
    exit;
}

// --- C. Fetch current school data for HR's form display ---
if ($hr_school_id) {
    try {
        $stmt_fetch = $conn->prepare('SELECT * FROM "school" WHERE "id" = ?');
        $stmt_fetch->execute([$hr_school_id]);
        $school = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if ($school) {
            $selected_boards = $school['education_board'] ? explode(',', trim($school['education_board'], '{}')) : [];
            $selected_mediums = $school['school_medium'] ? explode(',', trim($school['school_medium'], '{}')) : [];
            $school_category_string = trim($school['school_category'], '{}');
            $categories_raw = preg_split('/,(?=(?:(?:[^"]*"){2})*[^"]*$)/', $school_category_string);
            $selected_categories = array_map(function($category) {
                return trim($category, ' "');
            }, $categories_raw);
        } else {
            $errors[] = "Assigned school details not found.";
        }
    } catch (PDOException $e) {
        $errors[] = "Database error fetching school details: " . $e->getMessage();
    }
} else {
    $errors[] = "You are not assigned to a school. Please contact Super Admin.";
}

if (!$school) {
     $school = []; // Ensure $school is an array even if data fetch failed
}

// Helper function to safely get data
function get_school_value($school_array, $key, $default = '') {
    return htmlspecialchars($school_array[$key] ?? $default);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Request School Update - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css" />
    <style>
        .select2-container .select2-selection--multiple { min-height: 38px; border-color: #d1d3e2 !important; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice { background-color: #4e73df; border-color: #4e73df; color: white; }
    </style>
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
                include_once '../../includes/header.php'; 
            }
            ?>
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Request School Profile Update</h1>
                    <a href="<?php echo BASE_WEB_PATH; ?>dashboard.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Dashboard
                    </a>
                </div>
                
                <div id="enrollment-alert-placeholder">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($hr_school_id && $school): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Current Details for: <?php echo get_school_value($school, 'school_name'); ?> (ID: <?php echo $hr_school_id; ?>)</h6>
                    </div>
                    <div class="card-body">
                        <form id="schoolUpdateRequestForm" method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="school_id_to_edit" value="<?php echo $hr_school_id; ?>">
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="school_name">School Name *</label>
                                    <input type="text" class="form-control" id="school_name" name="school_name" value="<?php echo get_school_value($school, 'school_name'); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="email">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo get_school_value($school, 'email'); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="phone">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo get_school_value($school, 'phone'); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="school_opening">Date of Opening *</label>
                                    <input type="date" class="form-control" id="school_opening" name="school_opening" value="<?php echo get_school_value($school, 'school_opening'); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo get_school_value($school, 'address'); ?></textarea>
                            </div>
                            
                            <hr>
                            <h6 class="m-0 font-weight-bold text-primary mb-3">Social Links (Optional)</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="facebook_url"><i class="fab fa-facebook"></i> Facebook URL</label>
                                    <input type="url" class="form-control" id="facebook_url" name="facebook_url" value="<?php echo get_school_value($school, 'facebook_url'); ?>" placeholder="e.g., https://facebook.com/myschool">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="twitter_url"><i class="fab fa-twitter"></i> Twitter URL</label>
                                    <input type="url" class="form-control" id="twitter_url" name="twitter_url" value="<?php echo get_school_value($school, 'twitter_url'); ?>" placeholder="e.g., https://twitter.com/myschool">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="instagram_url"><i class="fab fa-instagram"></i> Instagram URL</label>
                                    <input type="url" class="form-control" id="instagram_url" name="instagram_url" value="<?php echo get_school_value($school, 'instagram_url'); ?>" placeholder="e.g., https://instagram.com/myschool">
                                </div>
                            </div>
                            <hr>

                            <h6 class="m-0 font-weight-bold text-primary mb-3">Location (Optional)</h6>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="latitude">School Latitude</label>
                                    <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo get_school_value($school, 'latitude'); ?>" placeholder="e.g., 21.21060270">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="longitude">School Longitude</label>
                                    <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo get_school_value($school, 'longitude'); ?>" placeholder="e.g., 72.76795460">
                                </div>
                            </div>

                            <hr>
                            <h6 class="m-0 font-weight-bold text-primary mb-3">Academic Details</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="school_type">School Type *</label>
                                    <select class="form-control" id="school_type" name="school_type" required>
                                        <option value="Private" <?php if (get_school_value($school, 'school_type') == 'Private') echo 'selected'; ?>>Private</option>
                                        <option value="Government" <?php if (get_school_value($school, 'school_type') == 'Government') echo 'selected'; ?>>Government</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="education_board">Education Board *</label>
                                    <select class="form-control multi-select" id="education_board" name="education_board[]" multiple="multiple" required>
                                        <?php $boards = ['CBSE', 'State', 'IGCSE']; foreach ($boards as $board): ?>
                                            <option value="<?php echo $board; ?>" <?php if (in_array($board, $selected_boards)) echo 'selected'; ?>><?php echo $board; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="school_medium">School Medium *</label>
                                    <select class="form-control multi-select" id="school_medium" name="school_medium[]" multiple="multiple" required>
                                         <?php $mediums = ['English', 'Hindi', 'Regional Language']; foreach ($mediums as $medium): ?>
                                            <option value="<?php echo $medium; ?>" <?php if (in_array($medium, $selected_mediums)) echo 'selected'; ?>><?php echo $medium; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label for="school_category">School Category *</label>
                                    <select class="form-control multi-select" id="school_category" name="school_category[]" multiple="multiple" required>
                                        <?php $categories = ['Pre-Primary', 'Primary', 'Upper Primary', 'Secondary', 'Higher Secondary']; foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat; ?>" <?php if (in_array($cat, $selected_categories)) echo 'selected'; ?>><?php echo $cat; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <hr>
                            <h6 class="m-0 font-weight-bold text-danger mb-3">Reason for Update *</h6>
                            <div class="form-group">
                                <label for="reason">Please specify why these changes are required (min 10 characters)</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required minlength="10"></textarea>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane"></i> Submit Update Request to Super Admin</button>
                                <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Reset Form</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php
        if (!$is_ajax_request) { 
            include '../../includes/footer.php'; 
        }
        ?>
    </div>
</div>

<?php include_once "../../includes/logout_modal.php"?>

<script src="../../assets/vendor/jquery/jquery.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../../assets/js/responsive-tables.js"></script>

<script>
$(document).ready(function() {
    $('.multi-select').select2({
        placeholder: "Select options",
        allowClear: true
    });

    $('#schoolUpdateRequestForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalButtonText = submitButton.html();
        submitButton.html('<i class="fas fa-spinner fa-spin"></i> Submitting...').prop('disabled', true);

        const formData = new FormData(this);

        // Remove the file input field, as HR cannot upload the logo for approval (needs to be done by SA)
        formData.delete('school_logo');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                let alertClass = response.success ? 'alert-success' : 'alert-danger';
                let alertMessage = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                                        ${response.message}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                                    </div>`;
                $('#enrollment-alert-placeholder').html(alertMessage);

                if (response.success) {
                    // Redirect after successful submission
                    setTimeout(() => { window.location.href = response.redirect; }, 1500);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('#enrollment-alert-placeholder').html(`<div class="alert alert-danger">An unexpected error occurred: ${errorThrown}</div>`);
            },
            complete: function() {
                submitButton.html(originalButtonText).prop('disabled', false);
                $('html, body').animate({ scrollTop: 0 }, 'slow');
            }
        });
    });

    $('button[type="reset"]').on('click', function() {
         $('.multi-select').val(null).trigger('change');
    });
});
</script>
</body>
</html>