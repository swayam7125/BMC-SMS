<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: principal_list.php?error=Invalid ID provided");
    exit;
}

$principal_id = intval($_GET['id']);
$errors = [];

// Fetch current principal data to get original image path
$query = "SELECT * FROM principal WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $principal_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: principal_list.php?error=Principal not found");
    exit;
}
$principal = mysqli_fetch_assoc($result);
$original_image_path = $principal['principal_image'];
mysqli_stmt_close($stmt);


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve text data
    $principal_name = trim($_POST['principal_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $principal_dob = $_POST['principal_dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $salary = trim($_POST['salary']);
    $school_id = intval($_POST['school_id']);
    
    $image_path_for_db = $original_image_path; // Default to old path

    // --- Handle Photo Upload ---
    if (isset($_FILES['principal_image']) && $_FILES['principal_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['principal_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_exts)) {
            $target_dir = "../../uploads/principals/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $new_filename = uniqid('principal_', true) . '.' . $file_ext;
            $destination = $target_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $image_path_for_db = $destination;
                if (!empty($original_image_path) && file_exists($original_image_path)) {
                    unlink($original_image_path);
                }
            } else {
                $errors[] = "Failed to move uploaded file.";
            }
        } else {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    }

    // --- Validation --- (Excluding file)
    if (empty($principal_name)) $errors[] = "Principal name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone number is required";

    if (empty($errors)) {
        // --- Database Update ---
        $update_principal = "UPDATE principal SET 
                            principal_image = ?, principal_name = ?, email = ?, phone = ?, 
                            principal_dob = ?, gender = ?, blood_group = ?, address = ?, 
                            qualification = ?, salary = ?, school_id = ?
                            WHERE id = ?";

        $stmt_update = mysqli_prepare($conn, $update_principal);
        mysqli_stmt_bind_param(
            $stmt_update, "sssssssssdii",
            $image_path_for_db, $principal_name, $email, $phone, $principal_dob,
            $gender, $blood_group, $address, $qualification, $salary, $school_id, $principal_id
        );

        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            header("Location: principal_list.php?success=Principal updated successfully");
            exit;
        } else {
            $errors[] = "Error updating principal: " . mysqli_stmt_error($stmt_update);
        }
    }
}

// Fetch schools for dropdown
$schools_query = "SELECT id, school_name FROM school ORDER BY school_name";
$schools_result = mysqli_query($conn, $schools_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Principal - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include_once '../../includes/sidebar/BMC_sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header/BMC_header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Principal</h1>
                        <a href="principal_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Principal Information</h6></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="<?php echo htmlspecialchars(!empty($principal['principal_image']) && file_exists($principal['principal_image']) ? $principal['principal_image'] : '../../assets/img/default-user.jpg'); ?>" 
                                             alt="Principal Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="principal_image" class="small">Change Photo</label>
                                            <input type="file" class="form-control-file" id="principal_image" name="principal_image">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="principal_name">Principal Name *</label><input type="text" class="form-control" id="principal_name" name="principal_name" value="<?php echo htmlspecialchars($principal['principal_name']); ?>" required></div>
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($principal['email']); ?>" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="phone">Phone *</label><input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($principal['phone']); ?>" maxlength="10" required></div>
                                            <div class="form-group col-md-6"><label for="principal_dob">Date of Birth</label><input type="date" class="form-control" id="principal_dob" name="principal_dob" value="<?php echo htmlspecialchars($principal['principal_dob']); ?>"></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="school_id">School *</label><select class="form-control" id="school_id" name="school_id" required><?php mysqli_data_seek($schools_result, 0); while ($school = mysqli_fetch_assoc($schools_result)) { $selected = ($school['id'] == $principal['school_id']) ? 'selected' : ''; echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>"; } ?></select></div>
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required><option value="Male" <?php echo ($principal['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option><option value="Female" <?php echo ($principal['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option><option value="Others" <?php echo ($principal['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option></select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']; foreach ($bg_options as $bg) { $selected = ($principal['blood_group'] == $bg) ? 'selected' : ''; echo "<option value='{$bg}' {$selected}>" . strtoupper($bg) . "</option>"; } ?></select></div>
                                    <div class="form-group col-md-6"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($principal['qualification']); ?>"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($principal['salary']); ?>" step="0.01" min="0"></div>
                                    <div class="form-group col-md-6"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($principal['address']); ?></textarea></div>
                                </div>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Principal</button>
                                    <a href="principal_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer/BMC_footer.php'; ?>
        </div>
    </div>
    
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        document.getElementById('principal_image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>