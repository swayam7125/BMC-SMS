<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if ($role !== 'principal') {
    header("Location: ../../login.php?error=Unauthorized");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: student_list.php?error=Invalid ID provided");
    exit;
}

$student_id = intval($_GET['id']);
$errors = [];
$student = null;
$original_email = null;
$original_image_path = null;

try {
    // PDO Change: Fetch current student data
    $stmt = $conn->prepare("SELECT * FROM student WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        header("Location: student_list.php?error=Student not found");
        exit;
    }

    $original_email = $student['email'];
    $original_image_path = $student['student_image'];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // --- Form Data Retrieval ---
        $student_name = trim($_POST['student_name']);
        $new_email = trim($_POST['email']);
        $rollno = trim($_POST['rollno']);
        $school_id = intval($_POST['school_id']);
        $dob = $_POST['dob'] ?: null; // Handle empty date
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

        // Check if new email already exists for another user
        if ($new_email !== $original_email) {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check->execute([$new_email, $student_id]);
            if ($stmt_check->rowCount() > 0) {
                $errors[] = "This email address is already in use by another account.";
            }
        }

        // --- Handle Photo Upload ---
        if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['student_image'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_exts)) {
                $target_dir = "../../pages/student/uploads/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

                $new_filename = 'student_' . uniqid('', true) . '.' . $file_ext;
                $destination = $target_dir . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Use a relative path for the database
                    $image_path_for_db = "pages/student/uploads/" . $new_filename;
                    // Delete old photo if it exists and is different
                    if (!empty($original_image_path) && file_exists("../../" . $original_image_path) && $original_image_path !== $image_path_for_db) {
                        unlink("../../" . $original_image_path);
                    }
                } else {
                    $errors[] = "Failed to move uploaded file.";
                }
            } else {
                $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            }
        }

        if (empty($errors)) {
            $conn->beginTransaction();

            // Update the 'users' table if email changed
            if ($new_email !== $original_email) {
                $stmt_users = $conn->prepare("UPDATE users SET email = ? WHERE id = ? AND role = 'student'");
                $stmt_users->execute([$new_email, $student_id]);
            }

            // Update the 'student' table
            $update_student_sql = "UPDATE student SET
                                  student_image = ?, student_name = ?, rollno = ?, std = ?, email = ?, academic_year = ?,
                                  school_id = ?, dob = ?, gender = ?, blood_group = ?, address = ?,
                                  father_name = ?, father_phone = ?, mother_name = ?, mother_phone = ?
                                  WHERE id = ?";

            $stmt_update = $conn->prepare($update_student_sql);
            $stmt_update->execute([
                $image_path_for_db,
                $student_name,
                $rollno,
                $std,
                $new_email,
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
            ]);

            $conn->commit();
            header("Location: student_list.php?success=Student updated successfully");
            exit;
        }
        // Repopulate form fields in case of error
        $student = $_POST;
        $student['id'] = $student_id;
        $student['student_image'] = $image_path_for_db;
    }

    // Fetch schools for dropdown
    $schools_result = $conn->query("SELECT id, school_name FROM school ORDER BY school_name");
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $errors[] = "Database update failed: " . $e->getMessage();
    error_log("Edit student error: " . $e->getMessage());
    // Fetch schools again for dropdown if transaction failed
    $schools_result = $conn->query("SELECT id, school_name FROM school ORDER BY school_name");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit Student - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                        <h1 class="h3 mb-0 text-gray-800">Edit Student</h1>
                        <a href="student_list.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
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
                                        <img src="<?php echo htmlspecialchars(!empty($student['student_image']) && file_exists('../../' . $student['student_image']) ? '../../' . $student['student_image'] : '../../assets/img/default-user.jpg'); ?>"
                                            alt="Student Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="student_image" class="small">Change Photo</label>
                                            <input type="file" class="form-control-file" id="student_image" name="student_image">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-6 form-group"><label for="student_name">Student Name *</label><input type="text" class="form-control" id="student_name" name="student_name" value="<?php echo htmlspecialchars($student['student_name'] ?? ''); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($student['dob'] ?? ''); ?>"></div>
                                            <div class="col-md-6 form-group"><label for="gender">Gender</label><select class="form-control" id="gender" name="gender">
                                                    <option value="male" <?php echo (strtolower($student['gender'] ?? '') == 'male') ? 'selected' : ''; ?>>Male</option>
                                                    <option value="female" <?php echo (strtolower($student['gender'] ?? '') == 'female') ? 'selected' : ''; ?>>Female</option>
                                                    <option value="others" <?php echo (strtolower($student['gender'] ?? '') == 'others') ? 'selected' : ''; ?>>Others</option>
                                                </select></div>
                                            <div class="col-md-6 form-group"><label for="blood_group">Blood Group</label><select class="form-control" id="blood_group" name="blood_group"><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                                                                                                                                                            foreach ($bg_options as $bg) {
                                                                                                                                                                                                $selected = (strtolower($student['blood_group'] ?? '') == $bg) ? 'selected' : '';
                                                                                                                                                                                                echo "<option value='{$bg}' {$selected}>" . strtoupper($bg) . "</option>";
                                                                                                                                                                                            } ?></select></div>
                                            <div class="col-md-6 form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-primary font-weight-bold">Academic Details</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group"><label for="school_id">School *</label><select class="form-control" id="school_id" name="school_id" required><?php
                                                                                                                                                                                    if (isset($schools_result)) {
                                                                                                                                                                                        foreach ($schools_result as $school) {
                                                                                                                                                                                            $selected = ($school['id'] == ($student['school_id'] ?? '')) ? 'selected' : '';
                                                                                                                                                                                            echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>";
                                                                                                                                                                                        }
                                                                                                                                                                                    } ?></select></div>
                                    <div class="col-md-6 form-group"><label for="rollno">Roll Number *</label><input type="text" class="form-control" id="rollno" name="rollno" value="<?php echo htmlspecialchars($student['rollno'] ?? ''); ?>" required></div>
                                    <div class="col-md-6 form-group"><label for="std">Class (Standard) *</label><input type="text" class="form-control" id="std" name="std" value="<?php echo htmlspecialchars($student['std'] ?? ''); ?>" required></div>
                                    <div class="col-md-6 form-group"><label for="academic_year">Academic Year *</label><input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($student['academic_year'] ?? ''); ?>" required></div>
                                </div>
                                <hr>
                                <h6 class="text-primary font-weight-bold">Parent Details</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group"><label for="father_name">Father's Name *</label><input type="text" class="form-control" id="father_name" name="father_name" value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>" required></div>
                                    <div class="col-md-6 form-group"><label for="father_phone">Father's Phone *</label><input type="text" class="form-control" id="father_phone" name="father_phone" value="<?php echo htmlspecialchars($student['father_phone'] ?? ''); ?>" maxlength="10" required></div>
                                    <div class="col-md-6 form-group"><label for="mother_name">Mother's Name</label><input type="text" class="form-control" id="mother_name" name="mother_name" value="<?php echo htmlspecialchars($student['mother_name'] ?? ''); ?>"></div>
                                    <div class="col-md-6 form-group"><label for="mother_phone">Mother's Phone</label><input type="text" class="form-control" id="mother_phone" name="mother_phone" value="<?php echo htmlspecialchars($student['mother_phone'] ?? ''); ?>" maxlength="10"></div>
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
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        document.getElementById('student_image').onchange = function(evt) {
            const [file] = this.files
            if (file) {
                document.getElementById('imagePreview').src = URL.createObjectURL(file)
            }
        }
    </script>
</body>

</html>