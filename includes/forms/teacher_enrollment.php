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
    $teacher_name = trim($_POST['teacher_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $subject = trim($_POST['subject']);
    $language_known = trim($_POST['language_known']);
    $salary = trim($_POST['salary']);
    $std = implode(',', (isset($_POST['std']) ? $_POST['std'] : []));
    $experience = trim($_POST['experience']);
    $password = $_POST['password'];
    $batch = $_POST['batch']; 

    $class_teacher = isset($_POST['class_teacher']) ? 1 : 0;
    $class_teacher_std = $class_teacher ? ($_POST['class_teacher_std'] ?? null) : null;

    // --- MODIFIED: Get school_id based on role ---
    $school_id = ($role === 'schooladmin') ? $admin_school_id : $_POST['school_id'];
    
    $image_path_for_db = null;

    // --- Handle Photo Upload ---
    if (isset($_FILES['teacher_image']) && $_FILES['teacher_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['teacher_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_exts)) {
            $target_dir = "../../pages/teacher/uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $new_filename = uniqid('teacher_', true) . '.' . $file_ext;
            $destination = $target_dir . $new_filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $image_path_for_db = $destination;
            } else { $errors[] = "Failed to move uploaded file."; }
        } else { $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed."; }
    }

    // --- Validation ---
    if (empty($school_id)) $errors[] = "A school must be selected.";
    if (empty($teacher_name)) $errors[] = "Teacher name is required.";
    if (empty($batch)) $errors[] = "Batch selection is required.";
    if ($class_teacher && empty($class_teacher_std)) $errors[] = "Please select a standard for the class teacher.";

    if (empty($errors)) {
        mysqli_autocommit($conn, false);
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 1. Insert into users table
            $user_role = 'teacher';
            $insert_user_query = "INSERT INTO users (role, email, password) VALUES (?, ?, ?)";
            $stmt_user = mysqli_prepare($conn, $insert_user_query);
            mysqli_stmt_bind_param($stmt_user, "sss", $user_role, $email, $hashed_password);
            if (!mysqli_stmt_execute($stmt_user)) {
                throw new Exception("User record creation failed: " . mysqli_stmt_error($stmt_user));
            }
            $new_user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_user);

            // 2. Insert into 'teacher' table
            $insert_teacher_query = "INSERT INTO teacher (id, teacher_image, teacher_name, phone, school_id, dob, gender, blood_group, address, email, password, qualification, subject, language_known, salary, std, experience, batch, class_teacher, class_teacher_std) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_teacher = mysqli_prepare($conn, $insert_teacher_query);
            mysqli_stmt_bind_param($stmt_teacher, "isssisssssssssdsisis", 
                $new_user_id, $image_path_for_db, $teacher_name, $phone, $school_id, $dob, $gender, $blood_group, $address, $email, $hashed_password, $qualification, $subject, $language_known, $salary, $std, $experience, $batch, $class_teacher, $class_teacher_std
            );
            if (!mysqli_stmt_execute($stmt_teacher)) {
                throw new Exception("Teacher record creation failed: " . mysqli_stmt_error($stmt_teacher));
            }
            mysqli_stmt_close($stmt_teacher);

            mysqli_commit($conn);
            header("Location: ../../pages/teacher/teacher_list.php?success=Teacher enrolled successfully");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            if (mysqli_errno($conn) == 1062) {
                $errors[] = "A teacher with this email or phone number already exists.";
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
    <title>Enroll Teacher - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>

<body id="page-top">
    <div id="wrapper">
    <?php include '../../includes/sidebar.php';?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Enroll New Teacher</h1>
                        <a href="../../pages/teacher/teacher_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Teacher Information</h6></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="../../assets/img/default-user.jpg" alt="Teacher Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="teacher_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label>
                                            <input type="file" class="d-none" id="teacher_image" name="teacher_image">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row"><div class="form-group col-md-12"><label for="teacher_name">Teacher Name *</label><input type="text" class="form-control" id="teacher_name" name="teacher_name" value="<?php echo htmlspecialchars($_POST['teacher_name'] ?? ''); ?>" required></div></div>
                                        <div class="form-row"><div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div><div class="form-group col-md-6"><label for="password">Password *</label><input type="password" class="form-control" id="password" name="password" required></div></div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-primary">Professional Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-4">
                                        <label for="school_id">Assign to School *</label>
                                        
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
                                                    mysqli_data_seek($school_result, 0);
                                                    while ($school = mysqli_fetch_assoc($school_result)) {
                                                        $selected = (isset($_POST['school_id']) && $_POST['school_id'] == $school['id']) ? 'selected' : '';
                                                        echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="batch">Batch *</label>
                                        <select class="form-control" id="batch" name="batch" required>
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning" <?php echo (isset($_POST['batch']) && $_POST['batch'] == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                                            <option value="Evening" <?php echo (isset($_POST['batch']) && $_POST['batch'] == 'Evening') ? 'selected' : ''; ?>>Evening</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4"><label>Timings</label><div id="timingDetails" class="border p-2 rounded" style="min-height: 40px;"></div></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="subject">Subject Specialization *</label><input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required></div>
                                    <div class="form-group col-md-6"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4"><label for="std">Teaching Standards</label><select class="form-control multi-select" id="std" name="std[]" multiple="multiple"><?php $stds = ['Nursery', 'Junior', 'Senior', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']; foreach ($stds as $std_val): ?><option value="<?php echo $std_val; ?>"><?php echo $std_val; ?></option><?php endforeach; ?></select></div>
                                    <div class="form-group col-md-4"><label for="language_known">Languages Known</label><input type="text" class="form-control" id="language_known" name="language_known" value="<?php echo htmlspecialchars($_POST['language_known'] ?? ''); ?>"></div>
                                    <div class="form-group col-md-2"><label for="experience">Experience</label><input type="number" class="form-control" id="experience" name="experience" min="0" max="50" value="<?php echo htmlspecialchars($_POST['experience'] ?? ''); ?>"></div>
                                    <div class="form-group col-md-2"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>" step="0.01" min="0"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="class_teacher" name="class_teacher" <?php echo (isset($_POST['class_teacher']) && $_POST['class_teacher'] == '1') ? 'checked' : ''; ?>><label class="form-check-label" for="class_teacher">Is Class Teacher?</label></div></div>
                                    <div class="form-group col-md-6" id="classTeacherStdGroup" style="display: none;"><label for="class_teacher_std">Class Teacher for Standard *</label><select class="form-control" id="class_teacher_std" name="class_teacher_std"><option value="">-- Select Standard --</option><?php $stds_for_class_teacher = ['Nursery', 'Junior', 'Senior', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']; foreach ($stds_for_class_teacher as $std_val): ?><option value="<?php echo $std_val; ?>" <?php echo (isset($_POST['class_teacher_std']) && $_POST['class_teacher_std'] == $std_val) ? 'selected' : ''; ?>><?php echo $std_val; ?></option><?php endforeach; ?></select></div>
                                </div>
                                <hr>
                                <h6 class="text-primary">Personal Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-4"><label for="phone">Phone</label><input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" maxlength="10"></div>
                                    <div class="form-group col-md-4"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>"></div>
                                    <div class="form-group col-md-4"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required><option value="">-- Select Gender --</option><option value="Male">Male</option><option value="Female">Female</option><option value="Others">Others</option></select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><option value="">-- Select Blood Group --</option><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']; foreach ($bg_options as $bg) { echo "<option value='{$bg}'>{$bg}</option>"; } ?></select></div>
                                    <div class="form-group col-md-6"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea></div>
                                </div>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll Teacher</button>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.multi-select').select2();
            const timingDetails = $('#timingDetails');
            const batchSelect = $('#batch');
            const timings = { teacher: { Morning: `<div><strong>Mon-Fri:</strong> 7:00 AM - 2:00 PM</div><div><strong>Saturday:</strong> 7:00 AM - 12:00 PM</div><div><strong>Sunday:</strong> Holiday</div>`, Evening: `<div><strong>Mon-Fri:</strong> 11:00 AM - 6:00 PM</div><div><strong>Saturday:</strong> 11:00 AM - 4:00 PM</div><div><strong>Sunday:</strong> Holiday</div>` } };
            function updateTimings() { const selectedBatch = batchSelect.val(); if (selectedBatch && timings.teacher[selectedBatch]) { timingDetails.html(timings.teacher[selectedBatch]); } else { timingDetails.html(''); } }
            batchSelect.on('change', updateTimings);
            updateTimings();
            const isClassTeacherCheckbox = $('#class_teacher');
            const classTeacherStdGroup = $('#classTeacherStdGroup');
            const classTeacherStdSelect = $('#class_teacher_std');
            function toggleClassTeacherStd() { if (isClassTeacherCheckbox.is(':checked')) { classTeacherStdGroup.show(); classTeacherStdSelect.prop('required', true); } else { classTeacherStdGroup.hide(); classTeacherStdSelect.prop('required', false); classTeacherStdSelect.val(''); } }
            isClassTeacherCheckbox.on('change', toggleClassTeacherStd);
            toggleClassTeacherStd();
            <?php if (isset($_POST['class_teacher']) && $_POST['class_teacher'] == '1'): ?>
                isClassTeacherCheckbox.prop('checked', true);
                toggleClassTeacherStd();
            <?php endif; ?>
        });
        document.getElementById('teacher_image').addEventListener('change', function(event) { if (event.target.files[0]) { document.getElementById('imagePreview').src = URL.createObjectURL(event.target.files[0]); } });
    </script>
</body>
</html>