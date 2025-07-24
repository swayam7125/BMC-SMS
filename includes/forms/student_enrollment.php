<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
$userId = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Redirect to login if not authenticated
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// --- NEW: Logic to get School Admin's school details ---
$admin_school_id = null;
$admin_school_name = null;
if ($role === 'schooladmin' && $userId) {
    $stmt = $conn->prepare("SELECT s.id, s.school_name FROM principal p JOIN school s ON p.school_id = s.id WHERE p.id = ?");
    if($stmt){
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($admin_data = $result->fetch_assoc()) {
            $admin_school_id = $admin_data['id'];
            $admin_school_name = $admin_data['school_name'];
        }
        $stmt->close();
    }
}
// --- End of New Logic ---

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Retrieve all form data ---
    $student_name = trim($_POST['student_name']);
    $rollno = trim($_POST['rollno']);
    $std = trim($_POST['std']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $academic_year = $_POST['academic_year'];

    // --- MODIFIED: Get school_id based on role ---
    $school_id = ($role === 'schooladmin') ? $admin_school_id : $_POST['school_id'];
    
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $father_name = trim($_POST['father_name']);
    $father_phone = trim($_POST['father_phone']);
    $mother_name = trim($_POST['mother_name']);
    $mother_phone = trim($_POST['mother_phone']);
    
    $image_path_for_db = null;

    // --- Handle Photo Upload ---
    if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['student_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_exts)) {
            $target_dir = "../../pages/student/uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $new_filename = uniqid('student_', true) . '.' . $file_ext;
            $destination = $target_dir . $new_filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $image_path_for_db = $destination;
            } else { $errors[] = "Failed to move uploaded file."; }
        } else { $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed."; }
    }

    // --- Validation ---
    if (empty($student_name)) $errors[] = "Student name is required.";
    if (empty($school_id)) $errors[] = "A school must be selected.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (empty($father_name)) $errors[] = "Father's name is required.";
    
    // If validation passes, proceed with database insertion
    if (empty($errors)) {
        mysqli_autocommit($conn, false);
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 1. Insert into 'users' table FIRST to get the user_id
            $user_role = 'student';
            $insert_user_query = "INSERT INTO users (role, email, password) VALUES (?, ?, ?)";
            $stmt_user = mysqli_prepare($conn, $insert_user_query);
            mysqli_stmt_bind_param($stmt_user, "sss", $user_role, $email, $hashed_password);
            if (!mysqli_stmt_execute($stmt_user)) {
                throw new Exception("User record creation failed: " . mysqli_stmt_error($stmt_user));
            }
            // Get the last inserted ID from the users table
            $new_user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_user);

            // 2. Insert into 'student' table using the new_user_id as its primary key
            $student_query = "INSERT INTO student (id, student_image, student_name, rollno, std, email, password, academic_year, school_id, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_student = mysqli_prepare($conn, $student_query);
            mysqli_stmt_bind_param($stmt_student, "isssssssissssssss",
                $new_user_id, $image_path_for_db, $student_name, $rollno, $std, $email, $hashed_password, $academic_year, $school_id, $dob, $gender, $blood_group, $address, $father_name, $father_phone, $mother_name, $mother_phone
            );
            if (!mysqli_stmt_execute($stmt_student)) {
                throw new Exception("Student record creation failed: " . mysqli_stmt_error($stmt_student));
            }
            mysqli_stmt_close($stmt_student);

            mysqli_commit($conn);
            header("Location: ../../pages/student/student_list.php?success=Student enrolled successfully");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            if(mysqli_errno($conn) == 1062){
                $errors[] = "A student with this email already exists.";
            } else { $errors[] = "Database error: " . $e->getMessage(); }
        }
    }
}

$school_query = "SELECT id, school_name FROM school ORDER BY school_name";
$school_result = mysqli_query($conn, $school_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Enroll Student - School Management System</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Enroll New Student</h1>
                        <a href="../../pages/student/student_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Student Information</h6></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="../../assets/img/default-user.jpg" alt="Student Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                             <label for="student_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label>
                                            <input type="file" class="d-none" id="student_image" name="student_image">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row"><div class="form-group col-md-12"><label for="student_name">Student Name *</label><input type="text" class="form-control" id="student_name" name="student_name" value="<?php echo htmlspecialchars($_POST['student_name'] ?? ''); ?>" required></div></div>
                                        <div class="form-row"><div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div><div class="form-group col-md-6"><label for="password">Password *</label><input type="password" class="form-control" id="password" name="password" required></div></div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-primary">Academic Details</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6">
                                        <label for="school_id">School *</label>
                                        
                                        <?php if ($role === 'schooladmin'): ?>
                                            <select class="form-control" name="school_id_disabled" disabled>
                                                <option value="<?php echo $admin_school_id; ?>" selected><?php echo htmlspecialchars($admin_school_name); ?></option>
                                            </select>
                                            <input type="hidden" name="school_id" value="<?php echo $admin_school_id; ?>">
                                        <?php else: ?>
                                            <select class="form-control" id="school_id" name="school_id" required>
                                                <option value="">-- Select School --</option>
                                                <?php 
                                                if($school_result) {
                                                    while ($school = mysqli_fetch_assoc($school_result)) {
                                                        $selected = (isset($_POST['school_id']) && $_POST['school_id'] == $school['id']) ? 'selected' : '';
                                                        echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        <?php endif; ?>

                                    </div>
                                    <div class="form-group col-md-6"><label for="academic_year">Academic Year *</label><select class="form-control" id="academic_year" name="academic_year" required><option value="">-- Select Year --</option><?php for ($i = -1; $i < 3; $i++) { $year = date("Y") + $i; $acad_year = $year . '-' . ($year + 1); $selected = (isset($_POST['academic_year']) && $_POST['academic_year'] == $acad_year) ? 'selected' : ''; echo "<option value='{$acad_year}' {$selected}>{$acad_year}</option>"; } ?></select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="std">Standard / Class *</label><input type="text" class="form-control" id="std" name="std" value="<?php echo htmlspecialchars($_POST['std'] ?? ''); ?>" required></div>
                                    <div class="form-group col-md-6"><label for="rollno">Roll Number *</label><input type="text" class="form-control" id="rollno" name="rollno" value="<?php echo htmlspecialchars($_POST['rollno'] ?? ''); ?>" required></div>
                                </div>
                                <hr>
                                <h6 class="text-primary">Personal Information</h6>
                                 <div class="form-row mt-3">
                                    <div class="form-group col-md-6"><label for="dob">Date of Birth *</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>" required></div>
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required><option value="">-- Select Gender --</option><option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option><option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option><option value="others" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'others') ? 'selected' : ''; ?>>Others</option></select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><option value="">-- Select Blood Group --</option><?php $bg_options = ['a+', 'a-', 'b+', 'b-', 'ab+', 'ab-', 'o+', 'o-']; foreach ($bg_options as $bg) { $selected = (isset($_POST['blood_group']) && $_POST['blood_group'] == $bg) ? 'selected' : ''; echo "<option value='{$bg}' {$selected}>" . strtoupper($bg) . "</option>"; } ?></select></div>
                                    <div class="form-group col-md-6"><label for="address">Residential Address *</label><textarea class="form-control" id="address" name="address" rows="1" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea></div>
                                </div>
                                <hr>
                                <h6 class="text-primary">Parent/Guardian Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6"><label for="father_name">Father's Name *</label><input type="text" class="form-control" id="father_name" name="father_name" value="<?php echo htmlspecialchars($_POST['father_name'] ?? ''); ?>" required></div>
                                    <div class="form-group col-md-6"><label for="father_phone">Father's Phone *</label><input type="tel" class="form-control" id="father_phone" name="father_phone" value="<?php echo htmlspecialchars($_POST['father_phone'] ?? ''); ?>" maxlength="10" required></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="mother_name">Mother's Name *</label><input type="text" class="form-control" id="mother_name" name="mother_name" value="<?php echo htmlspecialchars($_POST['mother_name'] ?? ''); ?>" required></div>
                                    <div class="form-group col-md-6"><label for="mother_phone">Mother's Phone *</label><input type="tel" class="form-control" id="mother_phone" name="mother_phone" value="<?php echo htmlspecialchars($_POST['mother_phone'] ?? ''); ?>" maxlength="10" required></div>
                                </div>
                                <div class="form-group mt-4">
                                    <button type="submit" name="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll Student</button>
                                    <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Reset Form</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5><button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button></div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a></div>
            </div>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        document.getElementById('student_image').addEventListener('change', function(event) {
            if (event.target.files[0]) {
                document.getElementById('imagePreview').src = URL.createObjectURL(event.target.files[0]);
            }
        });
    </script>
</body>
</html>