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
    header("Location: teacher_list.php?error=Invalid ID provided");
    exit;
}

$teacher_id = intval($_GET['id']);
$errors = [];

// Fetch current teacher data to get original details
$query = "SELECT * FROM teacher WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: teacher_list.php?error=Teacher not found");
    exit;
}

$teacher = mysqli_fetch_assoc($result);
$original_email = $teacher['email'];
$original_image_path = $teacher['teacher_image'];
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Form Data Retrieval ---
    $teacher_name = trim($_POST['teacher_name']);
    $new_email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $school_id = intval($_POST['school_id']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $subject = trim($_POST['subject']);
    $language_known = trim($_POST['language_known']);
    $salary = trim($_POST['salary']);
    $std = trim($_POST['std']);
    $experience = trim($_POST['experience']);

    $image_path_for_db = $original_image_path;

    // --- Validation ---
    if (empty($teacher_name)) $errors[] = "Teacher name is required.";
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) $errors[] = "Phone must be 10 digits.";

    if ($new_email !== $original_email) {
        $check_email = "SELECT id FROM users WHERE email = ?";
        $stmt_check = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($stmt_check, "s", $new_email);
        mysqli_stmt_execute($stmt_check);
        if (mysqli_stmt_get_result($stmt_check)->num_rows > 0) {
            $errors[] = "This email address is already in use.";
        }
        mysqli_stmt_close($stmt_check);
    }
    
    // --- Handle Photo Upload ---
    if (isset($_FILES['teacher_image']) && $_FILES['teacher_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['teacher_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_exts)) {
            $target_dir = "../../pages/teacher/uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            
            $new_filename = uniqid('', true) . '.' . $file_ext;
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

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            if ($new_email !== $original_email) {
                $update_users = "UPDATE users SET email = ? WHERE email = ? AND role = 'teacher'";
                $stmt_users = mysqli_prepare($conn, $update_users);
                mysqli_stmt_bind_param($stmt_users, "ss", $new_email, $original_email);
                if (!mysqli_stmt_execute($stmt_users)) throw new Exception("Failed to update users table.");
                mysqli_stmt_close($stmt_users);
            }

            // Note: password is not updated here.
            $update_teacher = "UPDATE teacher SET 
                              teacher_image = ?, teacher_name = ?, phone = ?, school_id = ?, dob = ?, 
                              gender = ?, blood_group = ?, address = ?, email = ?, qualification = ?, 
                              subject = ?, language_known = ?, salary = ?, std = ?, experience = ?
                              WHERE id = ?";
            
            $stmt_update = mysqli_prepare($conn, $update_teacher);
            mysqli_stmt_bind_param(
                $stmt_update, "sssisssssssssssi",
                $image_path_for_db, $teacher_name, $phone, $school_id, $dob, $gender, 
                $blood_group, $address, $new_email, $qualification, $subject, 
                $language_known, $salary, $std, $experience, $teacher_id
            );

            if (!mysqli_stmt_execute($stmt_update)) throw new Exception("Failed to update teacher table.");
            mysqli_stmt_close($stmt_update);

            mysqli_commit($conn);
            header("Location: teacher_list.php?success=Teacher updated successfully");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Database update failed: " . $e->getMessage();
        }
    }
    $teacher = $_POST;
    $teacher['id'] = $teacher_id;
    $teacher['teacher_image'] = $original_image_path;
}

$schools_query = "SELECT id, school_name FROM school ORDER BY school_name";
$schools_result = mysqli_query($conn, $schools_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Teacher - School Management System</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Edit Teacher</h1>
                        <a href="teacher_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                    </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Teacher Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img src="<?php echo htmlspecialchars(!empty($teacher['teacher_image']) && file_exists($teacher['teacher_image']) ? $teacher['teacher_image'] : '../../assets/img/default-user.jpg'); ?>" 
                                             alt="Teacher Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="teacher_image" class="small">Change Photo</label>
                                            <input type="file" class="form-control-file" id="teacher_image" name="teacher_image">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-6 form-group"><label for="teacher_name">Teacher Name *</label><input type="text" class="form-control" id="teacher_name" name="teacher_name" value="<?php echo htmlspecialchars($teacher['teacher_name']); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="phone">Phone *</label><input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($teacher['phone']); ?>" maxlength="10" required></div>
                                            <div class="col-md-6 form-group"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($teacher['dob']); ?>"></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4 form-group"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required><option value="Male" <?php echo ($teacher['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option><option value="Female" <?php echo ($teacher['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option><option value="Others" <?php echo ($teacher['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option></select></div>
                                    <div class="col-md-4 form-group"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']; foreach ($bg_options as $bg) { $selected = ($teacher['blood_group'] == $bg) ? 'selected' : ''; echo "<option value='{$bg}' {$selected}>{$bg}</option>"; } ?></select></div>
                                    <div class="col-md-4 form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($teacher['address']); ?></textarea></div>
                                </div>
                                <hr>
                                <h6 class="text-primary font-weight-bold">Professional Details</h6>
                                <div class="row">
                                    <div class="col-md-4 form-group"><label for="school_id">School *</label><select class="form-control" id="school_id" name="school_id" required><?php mysqli_data_seek($schools_result, 0); while ($school = mysqli_fetch_assoc($schools_result)) { $selected = ($school['id'] == $teacher['school_id']) ? 'selected' : ''; echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>"; } ?></select></div>
                                    <div class="col-md-4 form-group"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($teacher['qualification']); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="subject">Subject Specialization</label><input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($teacher['subject']); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="language_known">Languages Known</label><input type="text" class="form-control" id="language_known" name="language_known" value="<?php echo htmlspecialchars($teacher['language_known']); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="experience">Years of Experience</label><input type="number" class="form-control" id="experience" name="experience" min="0" value="<?php echo htmlspecialchars($teacher['experience']); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="std">Teaching Standards (e.g., 5-8)</label><input type="text" class="form-control" id="std" name="std" value="<?php echo htmlspecialchars($teacher['std']); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($teacher['salary']); ?>" step="0.01" min="0"></div>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Teacher</button>
                                    <a href="teacher_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
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
        document.getElementById('teacher_image').addEventListener('change', function(event) {
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