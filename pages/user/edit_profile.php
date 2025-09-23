<?php
// --- DEBUGGING: Display all PHP errors. Remove or comment these out for production. ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Includes and session start
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/log_system.php"; // ADDED: Log system dependency

if (!defined('BASE_URL')) {
    define('BASE_URL', '/BMC-SMS/');
}

/**
 * Generates a web-accessible path for an image, checking multiple possible locations.
 *
 * @param string|null $db_image_path The path stored in the database.
 * @param string $base_web_path The base URL of the application.
 * @param string $default_sub_folder The role-specific sub-folder (e.g., 'teacher', 'student').
 * @return string|null The full web-accessible URL or null if not found.
 */
function getWebAccessibleImagePath($db_image_path, $base_web_path, $default_sub_folder = '')
{
    if (empty($db_image_path)) {
        return null;
    }

    // 1. Check the exact path stored in the DB
    $full_web_path = $base_web_path . ltrim($db_image_path, '/');
    $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path;
    // The '@' suppresses warnings if the file doesn't exist, which is expected behavior here.
    if (@file_exists($filesystem_path) && @is_file($filesystem_path)) {
        return $full_web_path;
    }

    // 2. Check common alternative locations if the primary path fails
    $possible_locations = [
        "pages/{$default_sub_folder}/uploads/",
        "uploads/{$default_sub_folder}s/",
        "uploads/"
    ];
    foreach ($possible_locations as $location) {
        $test_path = $base_web_path . $location . basename($db_image_path);
        $test_filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $test_path;
        if (@file_exists($test_filesystem_path) && @is_file($test_filesystem_path)) {
            return $test_path;
        }
    }

    return null; // Return null if no image is found in any location
}

$user_data = null;
$errors = [];
$success_message = '';

if (isset($_COOKIE['encrypted_user_id']) && isset($_COOKIE['encrypted_user_role'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
    $user_role = decrypt_id($_COOKIE['encrypted_user_role']);
    // ADDED: Retrieve user name for logging
    $user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'N/A';
    
    // Define a path-safe role name for directory creation
    $path_role = $user_role;

    // Determine table and field names based on user role
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
        case 'librarian':
            $table_name = 'librarian';
            $image_field = 'librarian_image';
            $name_field = 'librarian_name';
            break;
        case 'hr': 
            $table_name = 'hr';
            $image_field = 'hr_image';
            $name_field = 'hr_name';
            break;
        default:
            // Redirect if the role is invalid
            header("Location: profile.php?error=Invalid user role for editing.");
            exit;
    }

    try {
        // Fetch current user data
        $stmt_fetch = $conn->prepare("SELECT * FROM {$table_name} WHERE id = ?");
        $stmt_fetch->execute([$user_id]);
        if ($stmt_fetch->rowCount() > 0) {
            $user_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
        } else {
            header("Location: profile.php?error=User not found.");
            exit;
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitize and retrieve POST data
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone'] ?? '');
            $dob = $_POST['dob'];
            $gender = $_POST['gender'];
            $blood_group = trim($_POST['blood_group']);
            $address = trim($_POST['address']);
            $current_image_path = $_POST['current_image_path'];
            $new_image_path = $current_image_path;

            // --- VALIDATION ---
            if ($email !== $user_data['email']) {
                $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt_check->execute([$email, $user_id]);
                if ($stmt_check->rowCount() > 0) {
                    $errors[] = "This email address is already in use by another account.";
                }
            }

            // --- FIX: Add length validation for fields that might cause 'value too long' error ---
            if (strlen($blood_group) > 10) {
                $errors[] = "The Blood Group value is too long. Please use a standard format (e.g., 'A+', 'O-').";
            }
            // MODIFIED: Added 'hr' to the array for phone validation
            if (in_array($user_role, ['teacher', 'principal', 'librarian', 'hr']) && strlen($phone) > 15) {
                $errors[] = "The Phone Number is too long. Please limit it to 15 characters.";
            }
            if ($user_role === 'student') {
                if (strlen(trim($_POST['father_phone'])) > 15) {
                    $errors[] = "The Father's Phone Number is too long. Please limit it to 15 characters.";
                }
                if (strlen(trim($_POST['mother_phone'])) > 15) {
                    $errors[] = "The Mother's Phone Number is too long. Please limit it to 15 characters.";
                }
            }


            // --- FILE UPLOAD HANDLING ---
            if (empty($errors) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_image'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($file_ext, $allowed_exts)) {
                    $target_dir = "pages/{$path_role}/uploads/";
                    $full_target_dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_URL . $target_dir;

                    if (!file_exists($full_target_dir)) {
                        if (!mkdir($full_target_dir, 0775, true)) {
                            $errors[] = "Failed to create upload directory. Please check server permissions for the 'pages/{$path_role}/' folder.";
                        }
                    }

                    if (is_dir($full_target_dir)) {
                        $new_filename = uniqid($path_role . '_', true) . '.' . $file_ext;
                        $destination = $full_target_dir . $new_filename;

                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            // <<< CHANGE HERE: Prepend BASE_URL to the path stored in the database
                            $new_image_path = BASE_URL . $target_dir . $new_filename;
                        } else {
                            $errors[] = "Failed to move uploaded file. Check server permissions.";
                        }
                    }
                } else {
                    $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
                }
            }

            // --- DATABASE UPDATE ---
            if (empty($errors)) {
                $conn->beginTransaction();

                $update_fields = [
                    "{$name_field} = ?",
                    "email = ?",
                    "dob = ?",
                    "gender = ?",
                    "blood_group = ?",
                    "address = ?",
                    "{$image_field} = ?"
                ];
                $params = [$name, $email, $dob, $gender, $blood_group, $address, $new_image_path];

                // MODIFIED: Added 'hr' to the condition for phone and image update
                if (in_array($user_role, ['teacher', 'principal', 'librarian', 'hr'])) {
                    $update_fields[] = "phone = ?";
                    $params[] = $phone;
                } elseif ($user_role === 'student') {
                    $update_fields[] = "father_phone = ?";
                    $params[] = trim($_POST['father_phone']);
                    $update_fields[] = "mother_phone = ?";
                    $params[] = trim($_POST['mother_phone']);
                }
                $params[] = $user_id;

                $update_query_role = "UPDATE {$table_name} SET " . implode(', ', $update_fields) . " WHERE id = ?";
                $stmt_role = $conn->prepare($update_query_role);
                $stmt_role->execute($params);

                $update_users_query = "UPDATE users SET email = ? WHERE id = ?";
                $stmt_users = $conn->prepare($update_users_query);
                $stmt_users->execute([$email, $user_id]);

                $conn->commit();
                
                // ⭐ LOGGING: Log the self-service update
                $log_message = "UPDATE: User profile ({$user_role}) updated successfully.";
                log_interaction($user_role, $user_id, $log_message, $user_name);
                
                header("Location: profile.php?success=Profile updated successfully!");
                exit();
            }
        }
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        $errors[] = "Database update failed: " . $e->getMessage();
    }
} else {
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
                        <a href="profile.php" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Profile</a>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <p class="mb-0"><strong>Error!</strong> Please fix the following issues:</p>
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
                                        $default_image_path = BASE_URL . 'assets/images/unisex.png';
                                        $imagePathFromDB = $user_data[$image_field] ?? '';
                                        $current_image_web_path = getWebAccessibleImagePath($imagePathFromDB, BASE_URL, $path_role) ?? $default_image_path;
                                        ?>
                                        <img src="<?php echo htmlspecialchars($current_image_web_path); ?>" alt="Profile Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;" onerror="this.src='<?php echo htmlspecialchars($default_image_path); ?>';">
                                        <div class="form-group">
                                            <label for="profile_image" class="btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Change Photo</label>
                                            <input type="file" class="d-none" id="profile_image" name="profile_image" onchange="previewImage(event)">
                                        </div>
                                        <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($user_data[$image_field] ?? ''); ?>">
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
                                        <?php if (in_array($user_role, ['teacher', 'principal', 'librarian', 'hr'])): ?>
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
                                    <a href="profile.php" class="btn btn-secondary ml-2">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        function previewImage(event) {
            const imagePreview = document.getElementById('imagePreview');
            const file = event.target.files[0];
            if (file) {
                imagePreview.src = URL.createObjectURL(file);
                // Optional: Revoke the object URL on load to free up memory
                imagePreview.onload = function() {
                    URL.revokeObjectURL(imagePreview.src)
                }
            }
        }
    </script>
</body>

</html>