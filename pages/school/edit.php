<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Define the base web path for your project
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

function getWebAccessibleImagePath($db_path) {
    if (empty($db_path)) return null;

    // Case 1: The path is already a full web path (e.g., /BMC-SMS/uploads/...)
    $physical_path_full = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $db_path;
    if (strpos($db_path, BASE_WEB_PATH) === 0 && file_exists($physical_path_full)) {
        return htmlspecialchars($db_path);
    }

    // Case 2: The path is a relative path (e.g., uploads/...) - for old data
    $physical_path_relative = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_WEB_PATH . ltrim($db_path, '/');
    if (file_exists($physical_path_relative)) {
        return htmlspecialchars(BASE_WEB_PATH . ltrim($db_path, '/'));
    }

    return null;
}

// Authenticate user role from cookie. Redirect to login if not found.
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: school_list.php?error=Invalid ID provided");
    exit;
}
$school_id = intval($_GET['id']);
$errors = [];

try {
    // Fetch existing school data
    $stmt_fetch = $conn->prepare('SELECT * FROM "school" WHERE "id" = ?');
    $stmt_fetch->execute([$school_id]);
    $school = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$school) {
        header("Location: school_list.php?error=School not found");
        exit;
    }

    // Prepare arrays for multi-select dropdowns
    $selected_boards = $school['education_board'] ? explode(',', trim($school['education_board'], '{}')) : [];
    $selected_mediums = $school['school_medium'] ? explode(',', trim($school['school_medium'], '{}')) : [];
    
    // Correctly parse the PostgreSQL array for school_category, handling quoted values
    $school_category_string = trim($school['school_category'], '{}');
    $categories_raw = preg_split('/,(?=(?:(?:[^"]*"){2})*[^"]*$)/', $school_category_string);
    $selected_categories = array_map(function($category) {
        return trim($category, ' "');
    }, $categories_raw);

    $original_logo_path = $school['school_logo'];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $school_name = trim($_POST['school_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $school_opening = $_POST['school_opening'];
        $school_type = $_POST['school_type'];
        $education_board = isset($_POST['education_board']) ? $_POST['education_board'] : [];
        $school_medium = isset($_POST['school_medium']) ? $_POST['school_medium'] : [];
        $school_category = isset($_POST['school_category']) ? $_POST['school_category'] : [];
        $logo_path_for_db = $original_logo_path; // Assume original logo unless a new one is uploaded

        // --- FIX: REFINED FILE UPLOAD LOGIC ---
        if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
            $file_info = $_FILES['school_logo'];
            
            $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array(mime_content_type($file_info['tmp_name']), $allowed_mime_types)) {
                $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            } elseif ($file_info['size'] > 5 * 1024 * 1024) { // 5MB limit
                $errors[] = "File is too large. Maximum size is 5MB.";
            }

            if (empty($errors)) {
                $upload_dir_physical = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_WEB_PATH . 'uploads/school_logos/';
                if (!is_dir($upload_dir_physical)) {
                    mkdir($upload_dir_physical, 0777, true);
                }

                $file_extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
                $new_file_name = 'school_' . $school_id . '_' . time() . '.' . $file_extension;
                $destination_physical_path = $upload_dir_physical . $new_file_name;

                if (move_uploaded_file($file_info['tmp_name'], $destination_physical_path)) {
                    // Create the correct, web-accessible path to store in the DB.
                    $logo_path_for_db = BASE_WEB_PATH . 'uploads/school_logos/' . $new_file_name;
                } else {
                    $errors[] = "Failed to move the uploaded file.";
                }
            }
        }

        if (empty($errors)) {
            $update_query = 'UPDATE "school" SET "school_logo"=?, "school_name"=?, "email"=?, "phone"=?, "address"=?, "school_opening"=?, "school_type"=?, "education_board"=?, "school_medium"=?, "school_category"=? WHERE "id"=?';
            $stmt = $conn->prepare($update_query);

            $education_board_pg = '{' . implode(',', $education_board) . '}';
            $school_medium_pg = '{' . implode(',', $school_medium) . '}';
            
            // Re-quote values with spaces for PostgreSQL
            $school_category_quoted = array_map(function($cat) {
                return strpos($cat, ' ') !== false ? '"' . $cat . '"' : $cat;
            }, $school_category);
            $school_category_pg = '{' . implode(',', $school_category_quoted) . '}';

            if ($stmt->execute([$logo_path_for_db, $school_name, $email, $phone, $address, $school_opening, $school_type, $education_board_pg, $school_medium_pg, $school_category_pg, $school_id])) {
                header("Location: ../../pages/school/school_list.php?success=School updated successfully");
                exit;
            } else {
                $errors[] = "Failed to update school.";
            }
        }
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23505) {
        $errors[] = "A school with this email or phone number already exists.";
    } else {
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// Use the helper function to get the correct path, with a fallback to a default logo.
$default_logo = BASE_WEB_PATH . 'assets/images/default-school.png';
$logo_display_path = getWebAccessibleImagePath($school['school_logo']) ?? $default_logo;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit School - School Management System</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Edit School</h1>
                        <a href="school_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
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
                                        <img src="<?php echo $logo_display_path; ?>" alt="School Logo" id="logoPreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: contain;">
                                        <div class="form-group">
                                            <label for="school_logo" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Change Logo</label>
                                            <input type="file" class="d-none" id="school_logo" name="school_logo" onchange="document.getElementById('logoPreview').src = window.URL.createObjectURL(this.files[0])">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="school_name">School Name *</label><input type="text" class="form-control" name="school_name" value="<?php echo htmlspecialchars($school['school_name']); ?>" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email Address *</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($school['email']); ?>" required></div>
                                            <div class="form-group col-md-6"><label for="phone">Phone Number *</label><input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($school['phone']); ?>" maxlength="10" required></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="school_opening">School Opening Date *</label><input type="date" class="form-control" name="school_opening" value="<?php echo htmlspecialchars($school['school_opening']); ?>" required></div>
                                    <div class="form-group col-md-6"><label for="school_type">School Type *</label><select class="form-control" name="school_type" required>
                                            <option value="Government" <?php if ($school['school_type'] == 'Government') echo 'selected'; ?>>Government</option>
                                            <option value="Private" <?php if ($school['school_type'] == 'Private') echo 'selected'; ?>>Private</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="education_board">Education Board *</label><select class="form-control multi-select" name="education_board[]" multiple="multiple" required><?php $boards = ['CBSE', 'State', 'IGCSE']; foreach ($boards as $board): ?><option value="<?php echo $board; ?>" <?php if (in_array($board, $selected_boards)) echo 'selected'; ?>><?php echo $board; ?></option><?php endforeach; ?></select></div>
                                    <div class="form-group col-md-6"><label for="school_medium">School Medium *</label><select class="form-control multi-select" name="school_medium[]" multiple="multiple" required><?php $mediums = ['English', 'Hindi', 'Regional Language']; foreach ($mediums as $medium): ?><option value="<?php echo $medium; ?>" <?php if (in_array($medium, $selected_mediums)) echo 'selected'; ?>><?php echo $medium; ?></option><?php endforeach; ?></select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-12"><label for="school_category">School Category *</label><select class="form-control multi-select" name="school_category[]" multiple="multiple" required><?php $categories = ['Pre-Primary', 'Primary', 'Upper Primary', 'Secondary', 'Higher Secondary']; foreach ($categories as $cat): ?><option value="<?php echo $cat; ?>" <?php if (in_array($cat, $selected_categories)) echo 'selected'; ?>><?php echo $cat; ?></option><?php endforeach; ?></select></div>
                                </div>
                                <div class="form-group"><label for="address">Address *</label><textarea class="form-control" name="address" rows="3" required><?php echo htmlspecialchars($school['address']); ?></textarea></div>
                                <hr>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update School</button>
                                    <a href="school_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.multi-select').select2();
        });
    </script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
