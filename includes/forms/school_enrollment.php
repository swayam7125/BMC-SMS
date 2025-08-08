<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Define the base web path for your project if not already defined.
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// Authenticate user role from cookie. Redirect to login if not found.
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
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
    $logo_path_for_db = null;

    if (empty($school_name)) $errors[] = "School name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone is required";
    // Add other validation as needed...

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Insert initial school data with a null logo.
            $insert_query = 'INSERT INTO "school" (school_name, email, phone, school_opening, school_type, education_board, school_medium, school_category, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $conn->prepare($insert_query);

            $education_board_pg = '{' . implode(',', $education_board) . '}';
            $school_medium_pg = '{' . implode(',', $school_medium) . '}';
            $school_category_pg = '{' . implode(',', $school_category) . '}';

            $stmt->execute([$school_name, $email, $phone, $school_opening, $school_type, $education_board_pg, $school_medium_pg, $school_category_pg, $address]);

            // Get the ID of the newly inserted school.
            $last_school_id = $conn->lastInsertId();

            // --- FIX: ADDED COMPLETE FILE UPLOAD LOGIC ---
            if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
                $file_info = $_FILES['school_logo'];
                
                // Server-side validation
                $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array(mime_content_type($file_info['tmp_name']), $allowed_mime_types)) {
                    $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
                } elseif ($file_info['size'] > 5 * 1024 * 1024) { // 5MB limit
                    $errors[] = "File is too large. Maximum size is 5MB.";
                } else {
                    // Define the physical upload directory.
                    $upload_dir_physical = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_WEB_PATH . 'uploads/school_logos/';
                    if (!is_dir($upload_dir_physical)) {
                        mkdir($upload_dir_physical, 0777, true);
                    }
                    
                    // Create a new unique filename.
                    $file_extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
                    $new_file_name = 'school_' . $last_school_id . '_' . time() . '.' . $file_extension;
                    $destination_physical_path = $upload_dir_physical . $new_file_name;

                    if (move_uploaded_file($file_info['tmp_name'], $destination_physical_path)) {
                        // Create the correct, web-accessible path to store in the DB.
                        $logo_path_for_db = BASE_WEB_PATH . 'uploads/school_logos/' . $new_file_name;
                        
                        // Update the new school record with the logo path.
                        $update_logo_stmt = $conn->prepare('UPDATE "school" SET "school_logo" = ? WHERE "id" = ?');
                        $update_logo_stmt->execute([$logo_path_for_db, $last_school_id]);
                    } else {
                        $errors[] = "Failed to move the uploaded logo.";
                    }
                }
            }

            if (empty($errors)) {
                $conn->commit();
                header("Location: ../../pages/school/school_list.php?success=School enrolled successfully");
                exit;
            } else {
                // If there were upload errors, roll back the initial insert.
                $conn->rollBack();
            }

        } catch (PDOException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            if ($e->getCode() == 23505) {
                $errors[] = "A school with this email or phone number already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enroll School - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                        <h1 class="h3 mb-0 text-gray-800">Enroll New School</h1>
                        <a href="../../pages/school/school_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">School Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>School Logo</label><br>
                                        <img src="../../assets/img/default-school.png" alt="School Logo Preview" id="logoPreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: contain;">
                                        <div class="form-group">
                                            <label for="school_logo" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Logo</label>
                                            <input type="file" class="d-none" id="school_logo" name="school_logo" onchange="document.getElementById('logoPreview').src = window.URL.createObjectURL(this.files[0])">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="school_name">School Name *</label><input type="text" class="form-control" name="school_name" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email Address *</label><input type="email" class="form-control" name="email" required></div>
                                            <div class="form-group col-md-6"><label for="phone">Phone Number *</label><input type="tel" class="form-control" name="phone" maxlength="10" required></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="school_opening">School Opening Date *</label><input type="date" class="form-control" name="school_opening" required></div>
                                    <div class="form-group col-md-6"><label for="school_type">School Type *</label><select class="form-control" name="school_type" required>
                                            <option value="">-- Select Type --</option>
                                            <option value="Government">Government</option>
                                            <option value="Private">Private</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="education_board">Education Board *</label><select class="form-control multi-select" name="education_board[]" multiple="multiple" required><?php $boards = ['CBSE', 'State', 'IGCSE']; foreach ($boards as $board): ?><option value="<?php echo $board; ?>"><?php echo $board; ?></option><?php endforeach; ?></select></div>
                                    <div class="form-group col-md-6"><label for="school_medium">School Medium *</label><select class="form-control multi-select" name="school_medium[]" multiple="multiple" required><?php $mediums = ['English', 'Hindi', 'Regional Language']; foreach ($mediums as $medium): ?><option value="<?php echo $medium; ?>"><?php echo $medium; ?></option><?php endforeach; ?></select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-12"><label for="school_category">School Category *</label><select class="form-control multi-select" name="school_category[]" multiple="multiple" required><?php $categories = ['Pre-Primary', 'Primary', 'Upper Primary', 'Secondary', 'Higher Secondary']; foreach ($categories as $cat): ?><option value="<?php echo $cat; ?>"><?php echo $cat; ?></option><?php endforeach; ?></select></div>
                                </div>
                                <div class="form-group"><label for="address">Address *</label><textarea class="form-control" name="address" rows="3" required></textarea></div>
                                <hr>
                                <div class="form-group mt-4">
                                    <button type="submit" name="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll School</button>
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
    </script>
</body>
</html>