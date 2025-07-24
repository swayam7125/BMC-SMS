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
    header("Location: student_list.php?error=Invalid ID provided");
    exit;
}

$student_id = intval($_GET['id']); // This student_id is now also the user_id
$errors = [];

// Fetch current student data to get original details
$query = "SELECT * FROM student WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: student_list.php?error=Student not found");
    exit;
}

$student = mysqli_fetch_assoc($result);
$original_email = $student['email'];
$original_image_path = $student['student_image'];
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Form Data Retrieval ---
    $student_name = trim($_POST['student_name']);
    $new_email = trim($_POST['email']);
    $rollno = trim($_POST['rollno']);
    $school_id = intval($_POST['school_id']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $std = trim($_POST['std']);
    $academic_year = trim($_POST['academic_year']);
    $address = trim($_POST['address']);
    $father_name = trim($_POST['father_name']);
    $father_phone = trim($_POST['father_phone']);
    $mother_name = trim($_POST['mother_name']);
    $mother_phone = trim($_POST['mother_phone']);

    $image_path_for_db = $original_image_path;

    // --- Validation ---
    if (empty($student_name)) $errors[] = "Student name is required.";
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($rollno)) $errors[] = "Roll Number is required.";

    // Check if new email already exists for another user (excluding current user)
    if ($new_email !== $original_email) {
        $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt_check = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($stmt_check, "si", $new_email, $student_id); // student_id is now users.id
        mysqli_stmt_execute($stmt_check);
        if (mysqli_stmt_get_result($stmt_check)->num_rows > 0) {
            $errors[] = "This email address is already in use by another account.";
        }
        mysqli_stmt_close($stmt_check);
    }

    // --- Handle Photo Upload ---
    if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['student_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_exts)) {
            $target_dir = "../../pages/student/uploads/"; // Corrected path to be consistent with principal/teacher
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

            $new_filename = uniqid('student_', true) . '.' . $file_ext; // Add prefix for clarity
            $destination = $target_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $image_path_for_db = $destination;
                if (!empty($original_image_path) && file_exists($original_image_path) && $original_image_path !== $destination) {
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
            // Update the 'users' table ONLY if the email changed.
            // Student ID is now directly the User ID.
            if ($new_email !== $original_email) {
                $update_users = "UPDATE users SET email = ? WHERE id = ? AND role = 'student'"; // Update by ID
                $stmt_users = mysqli_prepare($conn, $update_users);
                mysqli_stmt_bind_param($stmt_users, "si", $new_email, $student_id); // Use student_id as user ID
                if (!mysqli_stmt_execute($stmt_users)) {
                     // Check for duplicate email error from users table (though already checked above)
                    if (mysqli_errno($conn) == 1062) {
                        throw new Exception("Another user with this email already exists.");
                    } else {
                        throw new Exception("Failed to update users table: " . mysqli_stmt_error($stmt_users));
                    }
                }
                mysqli_stmt_close($stmt_users);
            }

            // Update the 'student' table
            // Removed 'password' from this update as it should only be in 'users' table
            $update_student = "UPDATE student SET
                              student_image = ?, student_name = ?, rollno = ?, std = ?, email = ?, academic_year = ?,
                              school_id = ?, dob = ?, gender = ?, blood_group = ?, address = ?,
                              father_name = ?, father_phone = ?, mother_name = ?, mother_phone = ?
                              WHERE id = ?"; // No password here

            $stmt_update = mysqli_prepare($conn, $update_student);
            mysqli_stmt_bind_param(
                $stmt_update,
                "ssssssissssssssi", // CORRECTED: 16 characters to match 16 variables
                $image_path_for_db,
                $student_name,
                $rollno,
                $std,
                $new_email, // Use new_email here
                $academic_year,
                $school_id,
                $dob,
                $gender,
                $blood_group,
                $address,
                $father_name,
                $father_phone,
                $mother_name,
                $mother_phone,
                $student_id
            );

            if (!mysqli_stmt_execute($stmt_update)) throw new Exception("Failed to update student table.");
            mysqli_stmt_close($stmt_update);

            mysqli_commit($conn);
            header("Location: student_list.php?success=Student updated successfully");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Database update failed: " . $e->getMessage();
        }
    }
    // Repopulate form fields in case of error
    $student = $_POST;
    $student['id'] = $student_id;
    $student['student_image'] = $image_path_for_db; // Sticky image path
}

$schools_query = "SELECT id, school_name FROM school ORDER BY school_name";
$schools_result = mysqli_query($conn, $schools_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit Student - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
    <?php include '../../includes/sidebar.php';?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Student</h1>
                        <a href="student_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img src="<?php echo htmlspecialchars(!empty($student['student_image']) && file_exists($student['student_image']) ? $student['student_image'] : '../../assets/img/default-user.jpg'); ?>"
                                            alt="Student Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="student_image" class="small">Change Photo</label>
                                            <input type="file" class="form-control-file" id="student_image" name="student_image">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-6 form-group"><label for="student_name">Student Name *</label><input type="text" class="form-control" id="student_name" name="student_name" value="<?php echo htmlspecialchars($student['student_name']); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($student['dob']); ?>"></div>
                                            <div class="col-md-6 form-group"><label for="gender">Gender</label><select class="form-control" id="gender" name="gender">
                                                    <option value="male" <?php echo (strtolower($student['gender']) == 'male') ? 'selected' : ''; ?>>Male</option>
                                                    <option value="female" <?php echo (strtolower($student['gender']) == 'female') ? 'selected' : ''; ?>>Female</option>
                                                    <option value="others" <?php echo (strtolower($student['gender']) == 'others') ? 'selected' : ''; ?>>Others</option>
                                                </select></div>
                                            <div class="col-md-6 form-group"><label for="blood_group">Blood Group</label><select class="form-control" id="blood_group" name="blood_group"><?php $bg_options = ['a+', 'a-', 'b+', 'b-', 'ab+', 'ab-', 'o+', 'o-'];
                                                                                                                                                                                            foreach ($bg_options as $bg) {
                                                                                                                                                                                                $selected = (strtolower($student['blood_group']) == $bg) ? 'selected' : '';
                                                                                                                                                                                                echo "<option value='{$bg}' {$selected}>" . strtoupper($bg) . "</option>";
                                                                                                                                                                                            } ?></select></div>
                                            <div class="col-md-6 form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($student['address']); ?></textarea></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-primary font-weight-bold">Academic Details</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group"><label for="school_id">School *</label><select class="form-control" id="school_id" name="school_id" required><?php mysqli_data_seek($schools_result, 0);
                                                                                                                                                                                    while ($school = mysqli_fetch_assoc($schools_result)) {
                                                                                                                                                                                        $selected = ($school['id'] == $student['school_id']) ? 'selected' : '';
                                                                                                                                                                                        echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>";
                                                                                                                                                                                    } ?></select></div>
                                    <div class="col-md-6 form-group"><label for="rollno">Roll Number *</label><input type="text" class="form-control" id="rollno" name="rollno" value="<?php echo htmlspecialchars($student['rollno']); ?>" required></div>
                                    <div class="col-md-6 form-group"><label for="std">Class (Standard) *</label><input type="text" class="form-control" id="std" name="std" value="<?php echo htmlspecialchars($student['std']); ?>" required></div>
                                    <div class="col-md-6 form-group"><label for="academic_year">Academic Year *</label><input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($student['academic_year']); ?>" required></div>
                                </div>
                                <hr>
                                <h6 class="text-primary font-weight-bold">Parent Details</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group"><label for="father_name">Father's Name *</label><input type="text" class="form-control" id="father_name" name="father_name" value="<?php echo htmlspecialchars($student['father_name']); ?>" required></div>
                                    <div class="col-md-6 form-group"><label for="father_phone">Father's Phone *</label><input type="text" class="form-control" id="father_phone" name="father_phone" value="<?php echo htmlspecialchars($student['father_phone']); ?>" maxlength="10" required></div>
                                    <div class="col-md-6 form-group"><label for="mother_name">Mother's Name</label><input type="text" class="form-control" id="mother_name" name="mother_name" value="<?php echo htmlspecialchars($student['mother_name']); ?>"></div>
                                    <div class="col-md-6 form-group"><label for="mother_phone">Mother's Phone</label><input type="text" class="form-control" id="mother_phone" name="mother_phone" value="<?php echo htmlspecialchars($student['mother_phone']); ?>" maxlength="10"></div>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Student</button>
                                    <a href="student_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            include '../../includes/footer.php';
            ?>
            </div>
    </div>
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

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        document.getElementById('student_image').addEventListener('change', function(event) {
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