<?php
// Includes and session start
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Only define the constant if it hasn't been defined already.
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

/**
 * FIX: Added the robust image path function to correctly resolve the image URL.
 * Checks if an image path from the database is valid and returns a web-accessible URL.
 *
 * @param string|null $db_image_path The path stored in the database.
 * @param string $base_web_path The base URL of the project.
 * @param string $default_sub_folder A hint for the user type (e.g., 'teacher', 'student').
 * @return string|null A valid, web-accessible image path or null if not found.
 */
function getWebAccessibleImagePath($db_image_path, $base_web_path, $default_sub_folder = '')
{
    if (empty($db_image_path)) {
        return null;
    }

    $full_web_path = $base_web_path . ltrim($db_image_path, '/');
    $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path;
    if (@file_exists($filesystem_path) && @is_file($filesystem_path)) {
        return $full_web_path;
    }

    $possible_locations = [
        "pages/{$default_sub_folder}/uploads/",
        "uploads/{$default_sub_folder}s/",
        "uploads/",
    ];

    foreach ($possible_locations as $location) {
        $test_path = $base_web_path . $location . basename($db_image_path);
        $test_filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $test_path;

        if (@file_exists($test_filesystem_path) && @is_file($test_filesystem_path)) {
            return $test_path;
        }
    }

    return null;
}


// Set default values
$user_data = null;
$errors = [];
$success_message = '';

// Check if user is logged in via cookie
if (isset($_COOKIE['encrypted_user_id']) && isset($_COOKIE['encrypted_user_role'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
    $user_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $path_role = ($user_role === 'principal') ? 'principal' : $user_role;

    $table_name = '';
    $image_field = '';
    $name_field = '';

    switch ($user_role) {
        case 'teacher':
            $table_name = 'teacher';
            $image_field = 'teacher_image';
            $name_field = 'teacher_name';
            break;
        case 'student':
            $table_name = 'student';
            $image_field = 'student_image';
            $name_field = 'student_name';
            break;
        case 'principal':
            $table_name = 'principal';
            $image_field = 'principal_image';
            $name_field = 'principal_name';
            break;
        default:
            header("Location: profile.php?error=Invalid user role for editing.");
            exit;
    }

    // --- Fetch Current User Data First to get current email for comparison ---
    try {
        $query = "SELECT * FROM {$table_name} WHERE id = ?";
        $stmt_fetch = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
        mysqli_stmt_execute($stmt_fetch);
        $result = mysqli_stmt_get_result($stmt_fetch);
        if (mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
        } else {
            header("Location: profile.php?error=User not found.");
            exit;
        }
        mysqli_stmt_close($stmt_fetch);
    } catch (Exception $e) {
        $errors[] = "Database query failed: " . $e->getMessage();
    }


    // --- Handle Form Submission (POST Request) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve form data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone'] ?? '');
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $blood_group = $_POST['blood_group'];
        $address = trim($_POST['address']);
        $current_image_path = $_POST['current_image_path'];
        $new_image_path = $current_image_path;

        // Check if email has changed and if the new one is unique
        if ($email !== $user_data['email']) {
            $email_check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt_check = mysqli_prepare($conn, $email_check_query);
            mysqli_stmt_bind_param($stmt_check, "si", $email, $user_id);
            mysqli_stmt_execute($stmt_check);
            if (mysqli_stmt_get_result($stmt_check)->num_rows > 0) {
                $errors[] = "This email address is already in use by another account.";
            }
            mysqli_stmt_close($stmt_check);
        }

        // --- Handle Photo Upload ---
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_exts)) {
                $target_dir = "pages/{$path_role}/uploads/";
                $full_target_dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_WEB_PATH . $target_dir;
                if (!file_exists($full_target_dir)) {
                    mkdir($full_target_dir, 0777, true);
                }
                $new_filename = uniqid($path_role . '_', true) . '.' . $file_ext;
                $destination = $full_target_dir . $new_filename;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $new_image_path = $target_dir . $new_filename;
                } else {
                    $errors[] = "Failed to move uploaded file.";
                }
            } else {
                $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            }
        }

        // --- Update Database with Transaction ---
        if (empty($errors)) {
            mysqli_begin_transaction($conn);

            try {
                // 1. Update Role-Specific Table (e.g., student, teacher)
                $update_fields = [ "{$name_field} = ?", "email = ?", "dob = ?", "gender = ?", "blood_group = ?", "address = ?", "{$image_field} = ?" ];
                $params = [$name, $email, $dob, $gender, $blood_group, $address, $new_image_path];
                $param_types = "sssssss";

                if ($user_role === 'teacher' || $user_role === 'principal') {
                    $update_fields[] = "phone = ?";
                    $params[] = $phone;
                    $param_types .= "s";
                } elseif ($user_role === 'student') {
                    $update_fields[] = "father_phone = ?";
                    $params[] = trim($_POST['father_phone']);
                    $param_types .= "s";
                    $update_fields[] = "mother_phone = ?";
                    $params[] = trim($_POST['mother_phone']);
                    $param_types .= "s";
                }

                $params[] = $user_id;
                $param_types .= "i";

                $update_query_role = "UPDATE {$table_name} SET " . implode(', ', $update_fields) . " WHERE id = ?";
                $stmt_role = mysqli_prepare($conn, $update_query_role);
                mysqli_stmt_bind_param($stmt_role, $param_types, ...$params);

                if (!mysqli_stmt_execute($stmt_role)) {
                    throw new Exception("Failed to update profile details.");
                }
                mysqli_stmt_close($stmt_role);

                // 2. Update Users Table
                $update_users_query = "UPDATE users SET email = ? WHERE id = ?";
                $stmt_users = mysqli_prepare($conn, $update_users_query);
                mysqli_stmt_bind_param($stmt_users, "si", $email, $user_id);

                if (!mysqli_stmt_execute($stmt_users)) {
                    throw new Exception("Failed to update login details.");
                }
                mysqli_stmt_close($stmt_users);

                // If all good, commit the transaction
                mysqli_commit($conn);

                header("Location: profile.php?success=Profile updated successfully!");
                exit();

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors[] = "Database update failed: " . $e->getMessage();
            }
        }
    }
} else {
    // Redirect to login if cookies are not set
    header("Location: ../../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit Profile - School Management System</title>

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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Profile</h1>
                        <a href="profile.php" class="btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Profile
                        </a>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Update Your Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="edit_profile.php" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <?php
                                        $default_image_path = BASE_WEB_PATH . 'assets/img/default-user.jpg';
                                        $imagePathFromDB = $user_data[$image_field] ?? '';
                                        $current_image_web_path = getWebAccessibleImagePath($imagePathFromDB, BASE_WEB_PATH, $path_role) ?? $default_image_path;
                                        ?>
                                        <img src="<?php echo htmlspecialchars($current_image_web_path); ?>"
                                            alt="Profile Photo"
                                            id="imagePreview"
                                            class="img-thumbnail mb-2"
                                            style="width: 150px; height: 150px; object-fit: cover;"
                                            onerror="this.src='<?php echo htmlspecialchars($default_image_path); ?>';">
                                        <div class="form-group">
                                            <label for="profile_image" class="btn btn-sm btn-info">
                                                <i class="fas fa-upload fa-sm"></i> Change Photo
                                            </label>
                                            <input type="file" class="d-none" id="profile_image" name="profile_image">
                                            <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($user_data[$image_field] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="name">Full Name *</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user_data[$name_field] ?? ''); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                                        </div>
                                        <?php if ($user_role === 'teacher' || $user_role === 'principal'): ?>
                                        <div class="form-group">
                                            <label for="phone">Phone</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                                        </div>
                                        <?php elseif ($user_role === 'student'): ?>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="father_phone">Father's Phone</label>
                                                <input type="tel" class="form-control" id="father_phone" name="father_phone" value="<?php echo htmlspecialchars($user_data['father_phone'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="mother_phone">Mother's Phone</label>
                                                <input type="tel" class="form-control" id="mother_phone" name="mother_phone" value="<?php echo htmlspecialchars($user_data['mother_phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="dob">Date of Birth</label>
                                        <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($user_data['dob'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="gender">Gender *</label>
                                        <select class="form-control" id="gender" name="gender" required>
                                            <option value="Male" <?php echo ($user_data['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($user_data['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Others" <?php echo ($user_data['gender'] ?? '') === 'Others' ? 'selected' : ''; ?>>Others</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="blood_group">Blood Group</label>
                                        <input type="text" class="form-control" id="blood_group" name="blood_group" value="<?php echo htmlspecialchars($user_data['blood_group'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="address">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Save Changes</button>
                                    <a href="profile.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>

            <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                            <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/js/custom_user_script.js"></script>
    
</body>

</html>