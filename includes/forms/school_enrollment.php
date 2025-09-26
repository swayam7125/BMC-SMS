<?php
include_once "../connect.php";
include_once "../../encryption.php";
include_once "../ajax_helpers.php";
include_once "../log_system.php"; 

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$enrolling_user_name = 'Unknown';

if ($userId) {
    try {
        $stmt_user_info = $conn->prepare('SELECT email FROM "users" WHERE "id" = ?');
        $stmt_user_info->execute([$userId]);
        $user_info = $stmt_user_info->fetch(PDO::FETCH_ASSOC);
        if ($user_info) {
            $enrolling_user_name = $user_info['email'];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch enrolling user info: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax_request()) {
    $errors = [];
    
    // --- FORM DATA RETRIEval & VALIDATION ---
    $school_name = trim($_POST['school_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $school_opening = $_POST['school_opening'] ?? null;
    $school_type = $_POST['school_type'] ?? null;
    $latitude = !empty($_POST['latitude']) ? trim($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? trim($_POST['longitude']) : null;
    
    // ⭐ NEW: Social Links
    $facebook_url = !empty($_POST['facebook_url']) ? trim($_POST['facebook_url']) : null;
    $twitter_url = !empty($_POST['twitter_url']) ? trim($_POST['twitter_url']) : null;
    $instagram_url = !empty($_POST['instagram_url']) ? trim($_POST['instagram_url']) : null;
    
    $education_board = $_POST['education_board'] ?? [];
    $school_medium = $_POST['school_medium'] ?? [];
    $school_category = $_POST['school_category'] ?? [];

    if (empty($school_name)) $errors[] = "School name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid school email is required.";
    if (empty($school_opening)) $errors[] = "Date of opening is required.";
    if (empty($education_board)) $errors[] = "At least one Education Board is required.";
    if (empty($school_medium)) $errors[] = "At least one School Medium is required.";

    if (!empty($errors)) {
        Response::send(['success' => false, 'message' => implode('<br>', $errors)]);
        exit;
    }

    // --- LOGO UPLOAD ---
    $logo_path_for_db = null;
    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['school_logo'];
        $uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/BMC-SMS/uploads/school_logos/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                 Response::send(['success' => false, 'message' => 'Failed to create upload directory.']);
                 exit;
            }
        }
        $fileName = 'school_' . uniqid() . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $logo_path_for_db = '/BMC-SMS/uploads/school_logos/' . $fileName;
        } else {
            Response::send(['success' => false, 'message' => 'Failed to move uploaded school logo.']);
            exit;
        }
    }
    
    // --- DATABASE INSERTION ---
    try {
        $conn->beginTransaction();

        // Convert PHP arrays to PostgreSQL array literal format, e.g., '{CBSE,State}'
        $board_pg_array = '{' . implode(',', $education_board) . '}';
        $medium_pg_array = '{' . implode(',', $school_medium) . '}';
        $category_pg_array = !empty($school_category) ? '{' . implode(',', $school_category) . '}' : null;

        $sql = 'INSERT INTO school 
                (school_logo, school_name, email, phone, school_opening, school_type, 
                 education_board, school_medium, school_category, address, latitude, longitude,
                 facebook_url, twitter_url, instagram_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $logo_path_for_db, $school_name, $email, $phone, $school_opening, $school_type, 
            $board_pg_array, $medium_pg_array, $category_pg_array, 
            $address, $latitude, $longitude,
            $facebook_url, $twitter_url, $instagram_url
        ]);
        $new_school_id = $conn->lastInsertId();

        $conn->commit();
        
        log_interaction($role, $userId, "ENROLLMENT SUCCESS: Enrolled new school: {$school_name} (ID: {$new_school_id})", $enrolling_user_name);
        
        Response::send([
            'success' => true, 
            'message' => 'School enrolled successfully!',
            'redirect' => '../../pages/school/school_list.php'
        ]);

    } catch (PDOException $e) {
        $conn->rollBack();
        $message = "Database error: " . $e->getMessage();
        if ($e->getCode() == 23505) {
            $message = "A school with this name or email already exists.";
        }
        log_interaction($role, $userId, "ENROLLMENT FAILED: " . $message, $enrolling_user_name);
        Response::send(['success' => false, 'message' => $message]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enroll School - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <style>
        .select2-container .select2-selection--multiple { min-height: 38px; border-color: #d1d3e2 !important; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice { background-color: #4e73df; border-color: #4e73df; color: white; }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <?php include '../sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include_once '../header.php'; ?>
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Enroll New School</h1>
                    <a href="../../pages/school/school_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
                    </a>
                </div>
                
                <div id="enrollment-alert-placeholder"></div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">School Details</h6>
                    </div>
                    <div class="card-body">
                        <form id="schoolEnrollmentForm" method="POST" action="" enctype="multipart/form-data">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="school_name">School Name *</label>
                                    <input type="text" class="form-control" id="school_name" name="school_name" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="email">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="phone">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="school_opening">Date of Opening *</label>
                                    <input type="date" class="form-control" id="school_opening" name="school_opening" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                            </div>

                            <hr>
                            <h6 class="m-0 font-weight-bold text-primary mb-3">Social Links (Optional)</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="facebook_url"><i class="fab fa-facebook"></i> Facebook URL</label>
                                    <input type="url" class="form-control" id="facebook_url" name="facebook_url" placeholder="e.g., https://facebook.com/myschool">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="twitter_url"><i class="fab fa-twitter"></i> Twitter URL</label>
                                    <input type="url" class="form-control" id="twitter_url" name="twitter_url" placeholder="e.g., https://twitter.com/myschool">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="instagram_url"><i class="fab fa-instagram"></i> Instagram URL</label>
                                    <input type="url" class="form-control" id="instagram_url" name="instagram_url" placeholder="e.g., https://instagram.com/myschool">
                                </div>
                            </div>
                            <hr>
                            
                            <h6 class="m-0 font-weight-bold text-primary mb-3">Location (Optional)</h6>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="latitude">School Latitude</label>
                                    <input type="text" class="form-control" id="latitude" name="latitude" placeholder="e.g., 21.21060270">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="longitude">School Longitude</label>
                                    <input type="text" class="form-control" id="longitude" name="longitude" placeholder="e.g., 72.76795460">
                                </div>
                            </div>

                            <hr>
                            <h6 class="m-0 font-weight-bold text-primary mb-3">Academic Details</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="school_type">School Type *</label>
                                    <select class="form-control" id="school_type" name="school_type" required>
                                        <option value="Private">Private</option>
                                        <option value="Government">Government</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="education_board">Education Board *</label>
                                    <select class="form-control multi-select" id="education_board" name="education_board[]" multiple="multiple" required>
                                        <option value="CBSE">CBSE</option>
                                        <option value="State">State</option>
                                        <option value="IGCSE">IGCSE</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="school_medium">School Medium *</label>
                                    <select class="form-control multi-select" id="school_medium" name="school_medium[]" multiple="multiple" required>
                                         <option value="English">English</option>
                                         <option value="Hindi">Hindi</option>
                                         <option value="Regional Language">Regional Language</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="school_category">School Category *</label>
                                    <select class="form-control multi-select" id="school_category" name="school_category[]" multiple="multiple" required>
                                        <option value="Pre-Primary">Pre-Primary</option>
                                        <option value="Primary">Primary</option>
                                        <option value="Upper Primary">Upper Primary</option>
                                        <option value="Secondary">Secondary</option>
                                        <option value="Higher Secondary">Higher Secondary</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="school_logo">School Logo (Optional)</label>
                                    <input type="file" class="form-control-file" id="school_logo" name="school_logo" accept="image/*">
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-school"></i> Enroll School</button>
                                <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Reset Form</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../footer.php'; ?>
    </div>
</div>

<?php include_once "../logout_modal.php"?>

<script src="../../assets/vendor/jquery/jquery.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.multi-select').select2({
        placeholder: "Select options",
        allowClear: true
    });

    $('#schoolEnrollmentForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalButtonText = submitButton.html();
        submitButton.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

        const formData = new FormData(this);

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
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>`;
                $('#enrollment-alert-placeholder').html(alertMessage);

                if (response.success) {
                    form[0].reset();
                    $('.multi-select').val(null).trigger('change');
                    if (response.redirect) {
                        setTimeout(() => { window.location.href = response.redirect; }, 1500);
                    }
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