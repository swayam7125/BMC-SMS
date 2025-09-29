<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";
include_once "../../includes/log_system.php";

$role = null;
$userId = null;
$enrolling_user_name = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($userId) {
    try {
        $user_table = $role; 
        $name_column = $role . '_name';
        if($role === 'superadmin'){
            $enrolling_user_name = 'Super Admin';
        } else {
             $stmt_user_info = $conn->prepare("SELECT {$name_column} FROM {$user_table} WHERE id = ?");
             $stmt_user_info->execute([$userId]);
             $user_info = $stmt_user_info->fetch(PDO::FETCH_ASSOC);
             if ($user_info) {
                 $enrolling_user_name = $user_info[$name_column];
             }
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch enrolling user info for logging in teacher_enrollment.php: " . $e->getMessage());
        $enrolling_user_name = 'Unknown';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax_request()) {
    $errors = [];

    // --- FORM DATA RETRIEVAL ---
    $school_id = $_POST['school_id'] ?? null;
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    
    // --- VALIDATION ---
    if (empty($school_id)) $errors[] = "A school must be selected.";
    if (empty($teacher_name)) $errors[] = "Teacher's name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    
    // --- CORRECTED BOOLEAN HANDLING ---
    $is_class_teacher = isset($_POST['class_teacher']) ? 'true' : 'false';
    $class_teacher_std = ($is_class_teacher === 'true') ? trim($_POST['class_teacher_std'] ?? '') : null;

    if ($is_class_teacher === 'true' && empty($class_teacher_std)) {
        $errors[] = "Please specify the standard for the class teacher.";
    }

    if (!empty($errors)) {
        Response::send(['success' => false, 'message' => implode('<br>', $errors)]);
        exit;
    }

    $image_path_for_db = null;
    if (isset($_FILES['teacher_image']) && $_FILES['teacher_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['teacher_image'];
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/teacher/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = 'teacher_' . uniqid() . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $image_path_for_db = '/BMC-SMS/pages/teacher/uploads/' . $fileName;
        } else {
            Response::send(['success' => false, 'message' => 'Failed to move uploaded file.']);
            exit;
        }
    }

    try {
        $conn->beginTransaction();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_role = 'teacher';

        $stmt_user = $conn->prepare('INSERT INTO "users" ("role", "email", "password") VALUES (?, ?, ?)');
        $stmt_user->execute([$user_role, $email, $hashed_password]);
        $new_user_id = $conn->lastInsertId();
        
        // --- Retrieve all form fields ---
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender = $_POST['gender'] ?? null;
        $blood_group = $_POST['blood_group'] ?? null;
        $address = trim($_POST['address'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $language_known = trim($_POST['language_known'] ?? '');
        $salary = !empty($_POST['salary']) ? trim($_POST['salary']) : null;
        $experience = !empty($_POST['experience']) ? trim($_POST['experience']) : null;
        $batch = $_POST['batch'] ?? null;
        $date_of_joining = !empty($_POST['date_of_joining']) ? $_POST['date_of_joining'] : null;
        
        $std_array = $_POST['std'] ?? [];
        $sanitized_std = array_map('htmlspecialchars', $std_array);
        $std_for_db = '{' . implode(',', $sanitized_std) . '}';

        $transport_mode = $_POST['transport_mode'] ?? 'Self';
        $self_transport_mode = ($transport_mode === 'Self Transport') ? ($_POST['self_transport_mode'] ?? null) : null;
        $vehicle_number = ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') ? trim($_POST['vehicle_number']) : null;
        $license_number = ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') ? trim($_POST['license_number']) : null;
        $stop_id = ($transport_mode === 'School Transport' && !empty($_POST['stop_id'])) ? (int)$_POST['stop_id'] : null;

        $sql = 'INSERT INTO teacher (id, teacher_image, teacher_name, phone, school_id, dob, gender, blood_group, address, email, password, qualification, subject, language_known, salary, std, experience, batch, class_teacher, class_teacher_std, date_of_joining, transport_mode, self_transport_mode, vehicle_number, license_number, stop_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt_teacher = $conn->prepare($sql);
        
        $stmt_teacher->execute([$new_user_id, $image_path_for_db, $teacher_name, $phone, $school_id, $dob, $gender, $blood_group, $address, $email, $hashed_password, $qualification, $subject, $language_known, $salary, $std_for_db, $experience, $batch, $is_class_teacher, $class_teacher_std, $date_of_joining, $transport_mode, $self_transport_mode, $vehicle_number, $license_number, $stop_id]);

        $conn->commit();
        
        log_interaction($role, $userId, "ENROLLMENT SUCCESS: Enrolled new Teacher: {$teacher_name} (ID: {$new_user_id})", $enrolling_user_name);
        
        Response::send([
            'success' => true, 
            'message' => 'Teacher enrolled successfully!',
            'redirect' => '../../pages/teacher/teacher_list.php'
        ]);

    } catch (PDOException $e) {
        $conn->rollBack();
        $message = "Database error: " . $e->getMessage();
         if ($e->getCode() == 23505) { // Unique constraint violation
            if (strpos($e->getMessage(), 'teacher_email_key') !== false || strpos($e->getMessage(), 'users_email_key') !== false) {
                 $message = "A teacher with this email already exists.";
            } else if (strpos($e->getMessage(), 'uq_class_teacher_std_batch') !== false) {
                 $message = "A class teacher is already assigned to this Standard for the selected batch.";
            } else {
                 $message = "A record with this information already exists. Please check email, phone, and license number.";
            }
        }
        log_interaction($role, $userId, "ENROLLMENT FAILED: " . $message, $enrolling_user_name);
        Response::send(['success' => false, 'message' => $message]);
    }
    exit;
}

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$admin_school_id = null;
$admin_school_name = null;
if ($role === 'principal' && $userId) {
    $stmt = $conn->prepare('SELECT s."id", s."school_name" FROM "principal" p JOIN "school" s ON p."school_id" = s."id" WHERE p."id" = ?');
    $stmt->execute([$userId]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin_data) {
        $admin_school_id = $admin_data['id'];
        $admin_school_name = $admin_data['school_name'];
    }
}

$schools = [];
if($role === 'superadmin'){
    $stmt_schools = $conn->query('SELECT "id", "school_name" FROM "school" ORDER BY "school_name"');
    $schools = $stmt_schools->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enroll Teacher - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Enroll New Teacher</h1>
                        <a href="../../pages/teacher/teacher_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    
                    <div id="enrollment-alert-placeholder"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Teacher Information</h6>
                        </div>
                        <div class="card-body">
                            <form id="teacherEnrollmentForm" method="POST" action="" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="../../assets/images/unisex.png" alt="Teacher Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="teacher_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label>
                                            <input type="file" class="d-none" id="teacher_image" name="teacher_image" accept="image/*">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="teacher_name">Teacher Name *</label><input type="text" class="form-control" id="teacher_name" name="teacher_name" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" required></div>
                                            <div class="form-group col-md-6"><label for="password">Password *</label><input type="password" class="form-control" id="password" name="password" required></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                <h6 class="text-primary">Professional Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6">
                                        <label for="school_id">Assign to School *</label>
                                        <?php if ($role === 'principal'): ?>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_school_name); ?>" disabled>
                                            <input type="hidden" id="school_id" name="school_id" value="<?php echo $admin_school_id; ?>">
                                        <?php else: ?>
                                            <select class="form-control" id="school_id" name="school_id" required>
                                                <option value="">-- Select School --</option>
                                                <?php foreach ($schools as $school) {
                                                    echo "<option value='{$school['id']}'>" . htmlspecialchars($school['school_name']) . "</option>";
                                                } ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="batch">Batch *</label>
                                        <select class="form-control" id="batch" name="batch" required>
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning">Morning</option>
                                            <option value="Evening">Evening</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                     <div class="form-group col-md-6">
                                        <label for="std">Teaches Standard(s) *</label>
                                        <select class="form-control" id="std" name="std[]" multiple="multiple" required>
                                            <!-- Options will be loaded by JS based on school -->
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="subject">Subject</label>
                                        <input type="text" class="form-control" id="subject" name="subject">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="qualification">Qualification</label>
                                        <input type="text" class="form-control" id="qualification" name="qualification">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="language_known">Languages Known</label>
                                        <input type="text" class="form-control" id="language_known" name="language_known">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="experience">Experience (yrs)</label>
                                        <input type="number" class="form-control" id="experience" name="experience" min="0" max="50">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="salary">Salary</label>
                                        <input type="number" class="form-control" id="salary" name="salary" step="0.01" min="0">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="date_of_joining">Date of Joining</label>
                                        <input type="date" class="form-control" id="date_of_joining" name="date_of_joining">
                                    </div>
                                </div>
                                <div class="form-row align-items-center">
                                    <div class="form-group col-md-6">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="class_teacher" name="class_teacher">
                                            <label class="custom-control-label" for="class_teacher">Is Class Teacher?</label>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6" id="class-teacher-std-div" style="display: none;">
                                        <label for="class_teacher_std">Class Teacher of Standard *</label>
                                        <input type="text" class="form-control" id="class_teacher_std" name="class_teacher_std">
                                    </div>
                                </div>

                                <hr>
                                <h6 class="text-primary">Transport Details</h6>
                                 <div class="form-row mt-3">
                                     <div class="form-group col-md-6">
                                        <label for="transport_mode">Mode of Transport *</label>
                                        <select class="form-control" id="transport_mode" name="transport_mode" required>
                                            <option value="Self Transport">Self Transport (Own Vehicle/Walking)</option>
                                            <option value="School Transport">School Transport (Bus/Van)</option>
                                        </select>
                                     </div>
                                     <div class="form-group col-md-6" id="self-transport-div">
                                        <label for="self_transport_mode">Self Transport Mode *</label>
                                        <select class="form-control" id="self_transport_mode" name="self_transport_mode">
                                            <option value="">-- Select Mode --</option>
                                            <option value="Public Transport">Public Transport</option>
                                            <option value="Walking">Walking</option>
                                            <option value="Parents">Parents</option>
                                            <option value="Bike">Bike</option>
                                            <option value="Car">Car</option>
                                        </select>
                                    </div>
                                     <div class="form-group col-md-6" id="transport-stop-div" style="display: none;">
                                        <label for="stop_id">Assign Transport Stop (Optional)</label>
                                        <select class="form-control" id="stop_id" name="stop_id">
                                            <option value="">-- No Transport --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row mt-3" id="vehicle-details-div" style="display: none;">
                                    <div class="form-group col-md-6">
                                        <label for="vehicle_number">Vehicle Number *</label>
                                        <input type="text" class="form-control" id="vehicle_number" name="vehicle_number">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="license_number">License Number *</label>
                                        <input type="text" class="form-control" id="license_number" name="license_number">
                                    </div>
                                </div>
                                
                                <hr>
                                <h6 class="text-primary">Personal Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-4"><label for="phone">Phone *</label><input type="tel" class="form-control" id="phone" name="phone" maxlength="10" required></div>
                                    <div class="form-group col-md-4"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob"></div>
                                    <div class="form-group col-md-4"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Others">Others</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required>
                                            <option value="">-- Select Blood Group --</option>
                                            <?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                            foreach ($bg_options as $bg) echo "<option value='{$bg}'>{$bg}</option>";
                                            ?>
                                        </select></div>
                                    <div class="form-group col-md-6"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"></textarea></div>
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
    
    <?php include_once "../../includes/logout_modal.php"?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/js/teacher_enrollment.js"></script>
</body>
</html>

