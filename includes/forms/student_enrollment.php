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
        $stmt_user_info = $conn->prepare('SELECT email FROM "users" WHERE "id" = ?');
        $stmt_user_info->execute([$userId]);
        $user_info = $stmt_user_info->fetch(PDO::FETCH_ASSOC);
        if ($user_info) {
            $enrolling_user_name = $user_info['email'];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch enrolling user info for logging in student_enrollment.php: " . $e->getMessage());
        $enrolling_user_name = 'Unknown';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax_request()) {
    $errors = [];
    
    // --- Form data retrieval and validation for student ---
    $school_id = $_POST['school_id'] ?? null;
    $student_name = trim($_POST['student_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rollno = trim($_POST['rollno'] ?? '');
    $std = trim($_POST['std'] ?? '');

    if (empty($school_id)) $errors[] = "A school must be selected.";
    if (empty($student_name)) $errors[] = "Student's name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (empty($rollno)) $errors[] = "Roll number is required.";
    if (empty($std)) $errors[] = "Standard/Class is required.";
    if ($password !== $_POST['confirm_password']) $errors[] = "Passwords do not match.";

    if (!empty($errors)) {
        Response::send(['success' => false, 'message' => implode('<br>', $errors)]);
        exit;
    }

    $image_path_for_db = null;
    if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['student_image'];
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/student/uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'student_' . uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $image_path_for_db = '/BMC-SMS/pages/student/uploads/' . $new_filename;
        } else {
            Response::send(['success' => false, 'message' => 'Failed to move uploaded file.']);
            exit;
        }
    }

    try {
        $conn->beginTransaction();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_role = 'student';

        $stmt_user = $conn->prepare('INSERT INTO "users" ("role", "email", "password") VALUES (?, ?, ?)');
        $stmt_user->execute([$user_role, $email, $hashed_password]);
        $new_user_id = $conn->lastInsertId();
        
        $academic_year = trim($_POST['academic_year'] ?? '');
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender = $_POST['gender'] ?? '';
        $blood_group = $_POST['blood_group'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $father_name = trim($_POST['father_name'] ?? '');
        $father_phone = trim($_POST['father_phone'] ?? '');
        $mother_name = trim($_POST['mother_name'] ?? '');
        $mother_phone = trim($_POST['mother_phone'] ?? '');
        $date_of_joining = !empty($_POST['date_of_joining']) ? $_POST['date_of_joining'] : null;
        $transport_mode = $_POST['transport_mode'] ?? 'Self';
        $self_transport_mode = ($transport_mode === 'Self Transport' && !empty($_POST['self_transport_mode'])) ? $_POST['self_transport_mode'] : null;
        $vehicle_number = ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') ? trim($_POST['vehicle_number'] ?? '') : null;
        $license_number = ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') ? trim($_POST['license_number'] ?? '') : null;
        $stop_id = ($transport_mode === 'School Transport' && !empty($_POST['stop_id'])) ? (int)$_POST['stop_id'] : null;

        $sql = 'INSERT INTO student (id, student_image, student_name, email, password, rollno, std, academic_year, school_id, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, date_of_joining, transport_mode, self_transport_mode, vehicle_number, license_number, stop_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt_student = $conn->prepare($sql);
        
        $stmt_student->execute([$new_user_id, $image_path_for_db, $student_name, $email, $hashed_password, $rollno, $std, $academic_year, $school_id, $dob, $gender, $blood_group, $address, $father_name, $father_phone, $mother_name, $mother_phone, $date_of_joining, $transport_mode, $self_transport_mode, $vehicle_number, $license_number, $stop_id]);

        $conn->commit();

        log_interaction($role, $userId, "ENROLLMENT SUCCESS: Enrolled new Student: {$student_name} (ID: {$new_user_id})", $enrolling_user_name);
        
        Response::send([
            'success' => true, 
            'message' => 'Student enrolled successfully!',
            'redirect' => '../../pages/student/student_list.php'
        ]);

    } catch (PDOException $e) {
        $conn->rollBack();
        $message = "Database error: " . $e->getMessage();
        if ($e->getCode() == 23505) { // Unique constraint violation
            if (strpos($e->getMessage(), 'student_email_key') !== false || strpos($e->getMessage(), 'users_email_key') !== false) {
                 $message = "A student with this email already exists.";
            } else if (strpos($e->getMessage(), 'uq_student_rollno_std_year') !== false) {
                 $message = "A student with this Roll Number and Standard already exists for the selected school and academic year.";
            } else {
                 $message = "A record with this information already exists. Please check email and roll number.";
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
    $stmt = $conn->prepare('SELECT s."id", s."school_name" FROM "principal" p JOIN "school" s ON p."school_id" = s."id" WHERE p."id" = ?');
    $stmt->execute([$userId]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin_data) {
        $admin_school_id = $admin_data['id'];
        $admin_school_name = $admin_data['school_name'];
    }
}

$schools = [];
$stmt_schools = $conn->query('SELECT "id", "school_name" FROM "school" ORDER BY "school_name"');
$schools = $stmt_schools->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enroll Student - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                        <h1 class="h3 mb-0 text-gray-800">Enroll New Student</h1>
                        <a href="../../pages/student/student_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    <div id="enrollment-alert-placeholder"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                        </div>
                        <div class="card-body">
                             <form id="studentEnrollmentForm" method="POST" action="" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="../../assets/images/unisex.png" alt="Student Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="student_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label>
                                            <input type="file" class="d-none" id="student_image" name="student_image" accept="image/*">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="student_name">Student Name *</label><input type="text" class="form-control" id="student_name" name="student_name" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" required></div>
                                            <div class="form-group col-md-6"><label for="password">Password *</label><input type="password" class="form-control" id="password" name="password" required></div>
                                        </div>
                                        <div class="form-row">
                                             <div class="form-group col-md-6"><label for="confirm_password">Confirm Password *</label><input type="password" class="form-control" id="confirm_password" name="confirm_password" required></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                <h6 class="text-primary">Academic Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6">
                                        <label for="school_id">Assign to School *</label>
                                        <?php if ($role === 'principal'): ?>
                                            <select class="form-control" disabled>
                                                <option value="<?php echo $admin_school_id; ?>" selected><?php echo htmlspecialchars($admin_school_name); ?></option>
                                            </select>
                                            <input type="hidden" name="school_id" value="<?php echo $admin_school_id; ?>">
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
                                        <label for="std">Standard/Class *</label>
                                        <input type="text" class="form-control" id="std" name="std" required>
                                    </div>
                                    </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="rollno">Roll Number *</label>
                                        <input type="text" class="form-control" id="rollno" name="rollno" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="academic_year">Academic Year</label>
                                        <input type="text" class="form-control" id="academic_year" name="academic_year" placeholder="e.g., 2024-2025">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-12">
                                        <label for="date_of_joining">Date of Joining</label>
                                        <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>

                                <hr>
                                <h6 class="text-primary">Personal Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob"></div>
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
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

                                <hr>
                                <h6 class="text-primary">Parental Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6"><label for="father_name">Father's Name</label><input type="text" class="form-control" id="father_name" name="father_name"></div>
                                    <div class="form-group col-md-6"><label for="father_phone">Father's Phone</label><input type="tel" class="form-control" id="father_phone" name="father_phone" maxlength="10"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="mother_name">Mother's Name</label><input type="text" class="form-control" id="mother_name" name="mother_name"></div>
                                    <div class="form-group col-md-6"><label for="mother_phone">Mother's Phone</label><input type="tel" class="form-control" id="mother_phone" name="mother_phone" maxlength="10"></div>
                                </div>


                                <hr>
                                <h6 class="text-primary">Transport Details</h6>
                                 <div class="form-row mt-3">
                                     <div class="form-group col-md-6">
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
                                    <div class="form-group col-md-6">
                                        <label for="vehicle_number">Vehicle Number *</label>
                                        <input type="text" class="form-control" id="vehicle_number" name="vehicle_number">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="license_number">License Number *</label>
                                        <input type="text" class="form-control" id="license_number" name="license_number">
                                    </div>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll Student</button>
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
    
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/js/ajax-forms.js"></script>
    <script src="../../assets/js/student_enrollment.js"></script>
    <script src="../../assets/js/custom.js"></script>
</body>

</html>