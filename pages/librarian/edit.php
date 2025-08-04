<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$role || $role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: librarian_list.php?error=Invalid ID provided");
    exit;
}

$librarian_id = intval($_GET['id']);
$errors = [];

$query_librarian = "SELECT * FROM librarian WHERE id = ?";
$stmt_librarian_fetch = mysqli_prepare($conn, $query_librarian);
mysqli_stmt_bind_param($stmt_librarian_fetch, "i", $librarian_id);
mysqli_stmt_execute($stmt_librarian_fetch);
$result_librarian = mysqli_stmt_get_result($stmt_librarian_fetch);

if (mysqli_num_rows($result_librarian) === 0) {
    header("Location: librarian_list.php?error=Librarian not found");
    exit;
}
$librarian = mysqli_fetch_assoc($result_librarian);
$original_email = $librarian['email'];
$original_image_path = $librarian['librarian_image'];
mysqli_stmt_close($stmt_librarian_fetch);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $librarian_name = trim($_POST['librarian_name']);
    $new_email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $salary = trim($_POST['salary']);
    
    $image_path_for_db = $original_image_path;

    if (empty($librarian_name)) $errors[] = "Librarian name is required.";
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";

    if (isset($_FILES['librarian_image']) && $_FILES['librarian_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['librarian_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_exts)) {
            
            $web_upload_path = '/BMC-SMS/pages/librarian/uploads/';
            $server_upload_dir = $_SERVER['DOCUMENT_ROOT'] . $web_upload_path;

            if (!file_exists($server_upload_dir)) {
                mkdir($server_upload_dir, 0777, true);
            }
            
            $new_filename = uniqid('librarian_', true) . '.' . $file_ext;
            $destination = $server_upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $image_path_for_db = $web_upload_path . $new_filename;
                // Delete old image if it exists
                if (!empty($original_image_path)) {
                    $old_image_server_path = $_SERVER['DOCUMENT_ROOT'] . $original_image_path;
                    if (file_exists($old_image_server_path)) {
                        @unlink($old_image_server_path);
                    }
                }
            } else {
                $errors[] = "Failed to move uploaded file.";
            }
        } else {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    }

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            if ($new_email !== $original_email) {
                $update_users = "UPDATE users SET email = ? WHERE id = ? AND role = 'librarian'";
                $stmt_users = mysqli_prepare($conn, $update_users);
                mysqli_stmt_bind_param($stmt_users, "si", $new_email, $librarian_id);
                if (!mysqli_stmt_execute($stmt_users)) {
                    throw new Exception("Failed to update users table: " . mysqli_stmt_error($stmt_users));
                }
                mysqli_stmt_close($stmt_users);
            }

            $update_librarian = "UPDATE librarian SET librarian_image = ?, librarian_name = ?, phone = ?, dob = ?, gender = ?, blood_group = ?, address = ?, email = ?, qualification = ?, salary = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $update_librarian);
            mysqli_stmt_bind_param($stmt_update, "sssssssssdi", $image_path_for_db, $librarian_name, $phone, $dob, $gender, $blood_group, $address, $new_email, $qualification, $salary, $librarian_id);
            if (!mysqli_stmt_execute($stmt_update)) {
                throw new Exception("Failed to update librarian table: " . mysqli_stmt_error($stmt_update));
            }
            mysqli_stmt_close($stmt_update);
            
            mysqli_commit($conn);
            header("Location: librarian_list.php?success=Librarian updated successfully");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Database update failed: " . $e->getMessage();
        }
    }
    $librarian = array_merge($librarian, $_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Librarian - School Management System</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Edit Librarian</h1>
                        <a href="librarian_list.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Librarian Information</h6></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img src="<?php echo htmlspecialchars(!empty($librarian['librarian_image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $librarian['librarian_image']) ? $librarian['librarian_image'] : '/BMC-SMS/assets/img/default-user.jpg'); ?>" alt="Librarian Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group"><label for="librarian_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Change Photo</label><input type="file" class="d-none" id="librarian_image" name="librarian_image"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-6 form-group"><label for="librarian_name">Librarian Name *</label><input type="text" class="form-control" id="librarian_name" name="librarian_name" value="<?php echo htmlspecialchars($librarian['librarian_name']); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($librarian['email']); ?>" required></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 form-group"><label for="phone">Phone *</label><input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($librarian['phone']); ?>" maxlength="10" required></div>
                                    <div class="col-md-4 form-group"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($librarian['dob']); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="Male" <?php echo ($librarian['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($librarian['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Others" <?php echo ($librarian['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                                        </select></div>
                                    <div class="col-md-4 form-group"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                        foreach ($bg_options as $bg) {
                                            $selected = ($librarian['blood_group'] == $bg) ? 'selected' : '';
                                            echo "<option value='{$bg}' {$selected}>{$bg}</option>";
                                        } ?></select></div>
                                    <div class="col-md-4 form-group"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($librarian['qualification']); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($librarian['salary']); ?>" step="0.01" min="0"></div>
                                </div>
                                <div class="form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($librarian['address']); ?></textarea></div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Librarian</button>
                                    <a href="librarian_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#librarian_image').on('change', function(event) {
            if (event.target.files[0]) {
                $('#imagePreview').attr('src', URL.createObjectURL(event.target.files[0]));
            }
        });
    });
    </script>
</body>
</html>