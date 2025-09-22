<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";
include_once "../../includes/log_system.php"; // Ensure log system is included

// Define the base web path for your project if not already defined.
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// Authenticate user role from cookie and get logging information.
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$enrolling_user_name = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'Unknown Admin';

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve all form data.
    $school_name = trim($_POST['school_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $school_opening = $_POST['school_opening'];
    $school_type = $_POST['school_type'];
    $education_board = isset($_POST['education_board']) ? $_POST['education_board'] : [];
    $school_medium = isset($_POST['school_medium']) ? $_POST['school_medium'] : [];
    $school_category = isset($_POST['school_category']) ? $_POST['school_category'] : [];
    
    // Principal and Location Info
    $principal_name = trim($_POST['principal_name'] ?? '');
    $principal_phone = trim($_POST['principal_phone'] ?? '');
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;

    // Logo upload
    $logo_path_for_db = null;
    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['school_logo'];
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/uploads/school_logos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = 'school_' . uniqid() . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $logo_path_for_db = BASE_WEB_PATH . 'uploads/school_logos/' . $fileName;
        } else {
            $errors[] = "Failed to upload school logo.";
        }
    }
    
    // Form validation
    if (empty($school_name)) $errors[] = "School name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid school email is required.";
    if (empty($school_opening)) $errors[] = "Date of opening is required.";
    if (empty($principal_name)) $errors[] = "Principal name is required.";
    if (empty($principal_phone)) $errors[] = "Principal phone number is required.";
    if (empty($education_board)) $errors[] = "At least one Education Board is required.";
    if (empty($school_medium)) $errors[] = "At least one School Medium is required.";

    // Database insertion
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Insert into school table
            $stmt_school = $conn->prepare('
                INSERT INTO "school" 
                (school_name, email, phone, address, date_of_opening, school_type, latitude, longitude, principal_name, principal_phone, school_logo, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            $stmt_school->execute([
                $school_name, $email, $phone, $address, $school_opening, $school_type,
                $latitude, $longitude, $principal_name, $principal_phone, $logo_path_for_db
            ]);
            $new_school_id = $conn->lastInsertId();

            // Insert education boards
            $stmt_boards = $conn->prepare('INSERT INTO "school_board" (school_id, board_name) VALUES (?, ?)');
            foreach ($education_board as $board) {
                $stmt_boards->execute([$new_school_id, $board]);
            }

            // Insert school mediums
            $stmt_mediums = $conn->prepare('INSERT INTO "school_medium" (school_id, medium_name) VALUES (?, ?)');
            foreach ($school_medium as $medium) {
                $stmt_mediums->execute([$new_school_id, $medium]);
            }
            
            // Insert school categories
            $stmt_categories = $conn->prepare('INSERT INTO "school_category" (school_id, category_name) VALUES (?, ?)');
            foreach ($school_category as $category) {
                $stmt_categories->execute([$new_school_id, $category]);
            }

            $conn->commit();
            
            // Logging
            log_interaction(
                $role,
                $userId,
                "ENROLLMENT SUCCESS: Enrolled new school: {$school_name} (ID: {$new_school_id})",
                $enrolling_user_name
            );

            header("Location: ../../pages/school/school_list.php?success=School enrolled successfully");
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            if ($e->getCode() == 23505) {
                $errors[] = "A school with this email address already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

if (!is_ajax_request()) {
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
        .select2-container .select2-selection--multiple {
            min-height: 38px;
            border-color: #d1d3e2 !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #4e73df;
            border-color: #4e73df;
            color: white;
        }
    </style>
</head>

<body id="page-top">
<div id="wrapper">
    <?php include '../../includes/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include_once '../../includes/header.php'; ?>
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Enroll New School</h1>
                    <a href="../../pages/school/school_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
                    </a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">School Details</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="school_name">School Name *</label>
                                    <input type="text" class="form-control" id="school_name" name="school_name"
                                           value="<?php echo htmlspecialchars($_POST['school_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="email">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="phone">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="school_opening">Date of Opening *</label>
                                    <input type="date" class="form-control" id="school_opening" name="school_opening"
                                           value="<?php echo htmlspecialchars($_POST['school_opening'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>

                            <hr>
                            <h6 class="m-0 font-weight-bold text-primary mb-3">Principal and Location</h6>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="principal_name">Principal's Full Name *</label>
                                    <input type="text" class="form-control" id="principal_name" name="principal_name"
                                           value="<?php echo htmlspecialchars($_POST['principal_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="principal_phone">Principal's Phone *</label>
                                    <input type="tel" class="form-control" id="principal_phone" name="principal_phone"
                                           value="<?php echo htmlspecialchars($_POST['principal_phone'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="latitude">School Latitude (Optional)</label>
                                    <input type="text" class="form-control" id="latitude" name="latitude"
                                           value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="longitude">School Longitude (Optional)</label>
                                    <input type="text" class="form-control" id="longitude" name="longitude"
                                           value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                                </div>
                            </div>

                            <hr>
                            <h6 class="m-0 font-weight-bold text-primary mb-3">Academic Details</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="school_type">School Type</label>
                                    <select class="form-control" id="school_type" name="school_type">
                                        <option value="Co-Ed" <?php echo (($_POST['school_type'] ?? 'Co-Ed') == 'Co-Ed') ? 'selected' : ''; ?>>Co-Ed</option>
                                        <option value="Boys" <?php echo (($_POST['school_type'] ?? '') == 'Boys') ? 'selected' : ''; ?>>Boys</option>
                                        <option value="Girls" <?php echo (($_POST['school_type'] ?? '') == 'Girls') ? 'selected' : ''; ?>>Girls</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="education_board">Education Board *</label>
                                    <select class="form-control multi-select" id="education_board" name="education_board[]" multiple="multiple" required>
                                        <?php 
                                        $boards = ['CBSE', 'ICSE', 'IB', 'State Board'];
                                        foreach ($boards as $board) {
                                            $selected = (isset($_POST['education_board']) && in_array($board, $_POST['education_board'])) ? 'selected' : '';
                                            echo "<option value='{$board}' {$selected}>{$board}</option>";
                                        } ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="school_medium">School Medium *</label>
                                    <select class="form-control multi-select" id="school_medium" name="school_medium[]" multiple="multiple" required>
                                        <?php 
                                        $mediums = ['English', 'Hindi', 'Gujarati', 'Marathi'];
                                        foreach ($mediums as $medium) {
                                            $selected = (isset($_POST['school_medium']) && in_array($medium, $_POST['school_medium'])) ? 'selected' : '';
                                            echo "<option value='{$medium}' {$selected}>{$medium}</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="school_category">School Category (Optional)</label>
                                    <select class="form-control multi-select" id="school_category" name="school_category[]" multiple="multiple">
                                        <?php 
                                        $categories = ['Primary', 'Secondary', 'Higher Secondary', 'Vocational'];
                                        foreach ($categories as $category) {
                                            $selected = (isset($_POST['school_category']) && in_array($category, $_POST['school_category'])) ? 'selected' : '';
                                            echo "<option value='{$category}' {$selected}>{$category}</option>";
                                        } ?>
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
        <?php include '../../includes/footer.php'; ?>
    </div>
</div>

<?php include_once "../../includes/logout_modal.php"?>

<script src="../../assets/vendor/jquery/jquery.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.multi-select').select2();
});

// Restrict "Date of Opening" to today or future
const dateInput = document.getElementById('school_opening');
if (dateInput) {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const formattedDate = `${year}-${month}-${day}`;
    dateInput.setAttribute('min', formattedDate);
}
</script>
</body>
</html>
<?php } ?>
