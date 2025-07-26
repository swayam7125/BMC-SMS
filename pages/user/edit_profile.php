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

    // First, try to see if the path is already a full, valid web path
    $full_web_path = $base_web_path . ltrim($db_image_path, '/');
    $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path;
    if (@file_exists($filesystem_path) && @is_file($filesystem_path)) {
        return $full_web_path;
    }

    // Fallback: If the path is relative or just a filename, try common locations
    $possible_locations = [
        "pages/{$default_sub_folder}/uploads/",
        "uploads/{$default_sub_folder}s/",
        "uploads/",
    ];

    foreach ($possible_locations as $location) {
        $test_path = $base_web_path . $location . basename($db_image_path);
        $test_filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $test_path;

        if (@file_exists($test_filesystem_path) && @is_file($test_filesystem_path)) {
            return $test_path; // Return the web-accessible path
        }
    }

    return null; // No photo found
}


// Set default values
$user_data = null;
$errors = [];
$success_message = '';

// Check if user is logged in via cookie
if (isset($_COOKIE['encrypted_user_id']) && isset($_COOKIE['encrypted_user_role'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
    $user_role = decrypt_id($_COOKIE['encrypted_user_role']);

    // Determine the table and field names based on the user's role
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
            // Redirect if role is not editable
            header("Location: profile.php?error=Invalid user role for editing.");
            exit;
    }

    // --- Handle Form Submission (POST Request) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve form data
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $blood_group = $_POST['blood_group'];
        $address = trim($_POST['address']);
        $current_image_path = $_POST['current_image_path'];
        $new_image_path = $current_image_path; // Default to old path

        // --- Handle Photo Upload ---
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_exts)) {
                // The target directory should be relative to the project root for consistency
                $target_dir = "pages/{$user_role}/uploads/";
                $full_target_dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_WEB_PATH . $target_dir;

                if (!file_exists($full_target_dir)) {
                    mkdir($full_target_dir, 0777, true);
                }
                $new_filename = uniqid($user_role . '_', true) . '.' . $file_ext;
                $destination = $full_target_dir . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $new_image_path = $target_dir . $new_filename; // Store the relative path
                } else {
                    $errors[] = "Failed to move uploaded file.";
                }
            } else {
                $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            }
        }

        // --- Update Database ---
        if (empty($errors)) {
            try {
                $update_query = "UPDATE {$table_name} SET 
                                    {$name_field} = ?, 
                                    phone = ?, 
                                    dob = ?, 
                                    gender = ?, 
                                    blood_group = ?, 
                                    address = ?,
                                    {$image_field} = ?
                                 WHERE id = ?";

                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "sssssssi", $name, $phone, $dob, $gender, $blood_group, $address, $new_image_path, $user_id);

                if (mysqli_stmt_execute($stmt)) {
                    // Redirect to profile page with a success message
                    header("Location: profile.php?success=Profile updated successfully!");
                    exit();
                } else {
                    throw new Exception("Database update failed: " . mysqli_stmt_error($stmt));
                }
                mysqli_stmt_close($stmt);
            } catch (Exception $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }

    // --- Fetch Current User Data for Form (GET Request) ---
    try {
        $query = "SELECT * FROM {$table_name} WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
        } else {
            header("Location: profile.php?error=User not found.");
            exit;
        }
        mysqli_stmt_close($stmt);
    } catch (Exception $e) {
        $errors[] = "Database query failed: " . $e->getMessage();
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
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
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
                    <h1 class="h3 mb-4 text-gray-800">Edit Profile</h1>

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
                                    <!-- Photo Preview -->
                                    <div class="col-md-4 text-center">
                                        <?php
                                        // FIX: Use the robust function to get the correct path for the preview image.
                                        $default_image_path = BASE_WEB_PATH . 'assets/img/default-user.jpg';
                                        $imagePathFromDB = $user_data[$image_field] ?? '';
                                        $current_image_web_path = getWebAccessibleImagePath($imagePathFromDB, BASE_WEB_PATH, $user_role) ?? $default_image_path;
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
                                    <!-- User Details -->
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="name">Full Name *</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user_data[$name_field] ?? ''); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="phone">Phone</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                                        </div>
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
    <script>
        // Preview the new image when a file is selected
        document.getElementById('profile_image').addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                }
                reader.readAsDataURL(event.target.files[0]);
            }
        });
    </script>
</body>

</html>