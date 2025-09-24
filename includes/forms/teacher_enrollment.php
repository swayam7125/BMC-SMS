<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";
require_once "../../includes/log_system.php";

$role = null;
$userId = null;
$enrolling_user_name = null; // ADDED for logging context
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$admin_school_id = null;
$admin_school_name = null;
if ($role === 'principal' && $userId) {
    // --- CORRECTED: Fetching principal's name for logging context ---
    $stmt = $conn->prepare('SELECT s."id", s."school_name", p."principal_name" AS enrolling_user_name FROM "principal" p JOIN "school" s ON p."school_id" = s."id" WHERE p."id" = ?');
    $stmt->execute([$userId]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin_data) {
        $admin_school_id = $admin_data['id'];
        $admin_school_name = $admin_data['school_name'];
        $enrolling_user_name = $admin_data['enrolling_user_name']; // Captured principal's name
    }
} else {
     // Fallback for Superadmin (who is the only other user enrolling a teacher if this is run)
    $enrolling_user_name = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'Unknown Admin';
}

$errors = [];
$schools = [];
$routes_stops = [];

// Fetch schools and routes/stops based on role
try {
    if ($role === 'principal' && $admin_school_id) {
        // Principal is restricted to their school's stops
        $stmt_routes = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM routes r JOIN stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
        $stmt_routes->execute([$admin_school_id]);
        $routes_stops = $stmt_routes->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fetch all schools for superadmin
        $stmt_schools = $conn->query('SELECT id, school_name FROM school ORDER BY school_name');
        $schools = $stmt_schools->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_name = trim($_POST['teacher_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $salary = trim($_POST['salary']);
    $date_of_joining = $_POST['date_of_joining'] ?? null;
    $school_id = ($role === 'principal') ? $admin_school_id : $_POST['school_id'];
    $image_path_for_db = null;
    
    // Transport details
    $transport_mode = $_POST['transport_mode'] ?? 'Self Transport';
    $stop_id = ($transport_mode === 'School Transport' && !empty($_POST['stop_id'])) ? (int)$_POST['stop_id'] : null;
    $self_transport_mode = ($transport_mode === 'Self Transport' && !empty($_POST['self_transport_mode'])) ? $_POST['self_transport_mode'] : null;
    $vehicle_number = null;
    $license_number = null;
    if ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') {
        $vehicle_number = trim($_POST['vehicle_number'] ?? '');
        $license_number = trim($_POST['license_number'] ?? '');
    }

    // File Upload Logic
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
            $errors[] = "Failed to upload image.";
        }
    }

    // Validation
    if (empty($teacher_name)) $errors[] = "Teacher name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (empty($school_id)) $errors[] = "School must be assigned.";
    if ($transport_mode === 'Self Transport' && empty($self_transport_mode)) $errors[] = "Please specify the mode of self-transport.";
    if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($vehicle_number)) $errors[] = "Vehicle number is required.";
    if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($license_number)) $errors[] = "License number is required.";

    // --- DATABASE INSERTION ---
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_role = 'teacher';

            // 1. Insert into users table
            $stmt_user = $conn->prepare('INSERT INTO "users" ("role", "email", "password") VALUES (?, ?, ?)');
            $stmt_user->execute([$user_role, $email, $hashed_password]);
            $new_user_id = $conn->lastInsertId();

            // 2. Insert into teacher table
            $stmt_teacher = $conn->prepare('
                INSERT INTO "teacher" (id, teacher_image, teacher_name, school_id, email, password, phone, dob, gender, blood_group, address, qualification, salary, date_of_joining, transport_mode, self_transport_mode, vehicle_number, license_number, stop_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt_teacher->execute([$new_user_id, $image_path_for_db, $teacher_name, $school_id, $email, $hashed_password, $phone, $dob, $gender, $blood_group, $address, $qualification, $salary, $date_of_joining, $transport_mode, $self_transport_mode, $vehicle_number, $license_number, $stop_id]);

            $conn->commit();
            
            // ⭐ LOGGING: Log the successful teacher enrollment action
            log_interaction($role, $userId, "ENROLLMENT SUCCESS: Enrolled new teacher: {$teacher_name} (ID: {$new_user_id})", $enrolling_user_name);

            header("Location: ../../pages/teacher/teacher_list.php?success=Teacher enrolled successfully");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            if ($e->getCode() == 23505) {
                $errors[] = "A user with this email already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enroll Teacher - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                            <form method="POST" action="your-handler.php" data-ajax="true" data-validate="true" enctype="multipart/form-data">
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
                                            <div class="form-group col-md-12"><label for="teacher_name">Teacher Name *</label><input type="text" class="form-control" id="teacher_name" name="teacher_name" value="<?php echo htmlspecialchars($_POST['teacher_name'] ?? ''); ?>" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div>
                                            <div class="form-group col-md-6"><label for="password">Password *</label><input type="password" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" required></div>
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
                                            <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($admin_school_id); ?>">
                                        <?php else: ?>
                                            <select class="form-control" id="school_id" name="school_id" required>
                                                <option value="">-- Select School --</option>
                                                <?php foreach ($schools as $school): ?>
                                                    <option value="<?php echo htmlspecialchars($school['id']); ?>" <?php echo (($_POST['school_id'] ?? '') == $school['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($school['school_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="date_of_joining">Date of Joining</label>
                                        <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" value="<?php echo htmlspecialchars($_POST['date_of_joining'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>"></div>
                                    <div class="form-group col-md-6"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>"></div>
                                </div>
                                
                                <hr>
                                <h6 class="text-primary">Transport Details</h6>
                                 <div class="form-row mt-3">
                                     <div class="form-group col-md-6">
                                        <label for="transport_mode">Mode of Transport *</label>
                                        <select class="form-control" id="transport_mode" name="transport_mode" required>
                                            <option value="Self Transport" <?php echo (isset($_POST['transport_mode']) && $_POST['transport_mode'] == 'Self Transport') ? 'selected' : ''; ?>>Self Transport (Own Vehicle/Walking)</option>
                                            <option value="School Transport" <?php echo (isset($_POST['transport_mode']) && $_POST['transport_mode'] == 'School Transport') ? 'selected' : ''; ?>>School Transport (Bus/Van)</option>
                                        </select>
                                     </div>
                                     <div class="form-group col-md-6" id="self-transport-div" style="display: <?php echo (isset($_POST['transport_mode']) && $_POST['transport_mode'] == 'Self Transport') ? 'block' : 'none'; ?>;">
                                        <label for="self_transport_mode">Self Transport Mode *</label>
                                        <select class="form-control" id="self_transport_mode" name="self_transport_mode">
                                            <option value="">-- Select Mode --</option>
                                            <option value="Public Transport" <?php echo (isset($_POST['self_transport_mode']) && $_POST['self_transport_mode'] == 'Public Transport') ? 'selected' : ''; ?>>Public Transport</option>
                                            <option value="Walking" <?php echo (isset($_POST['self_transport_mode']) && $_POST['self_transport_mode'] == 'Walking') ? 'selected' : ''; ?>>Walking</option>
                                            <option value="Parents" <?php echo (isset($_POST['self_transport_mode']) && $_POST['self_transport_mode'] == 'Parents') ? 'selected' : ''; ?>>Parents</option>
                                            <option value="Bike" <?php echo (isset($_POST['self_transport_mode']) && $_POST['self_transport_mode'] == 'Bike') ? 'selected' : ''; ?>>Bike</option>
                                            <option value="Car" <?php echo (isset($_POST['self_transport_mode']) && $_POST['self_transport_mode'] == 'Car') ? 'selected' : ''; ?>>Car</option>
                                        </select>
                                    </div>
                                     <div class="form-group col-md-6" id="transport-stop-div" style="display: <?php echo (isset($_POST['transport_mode']) && $_POST['transport_mode'] == 'School Transport') ? 'block' : 'none'; ?>;">
                                        <label for="stop_id">Assign Transport Stop (Optional)</label>
                                        <select class="form-control" id="stop_id" name="stop_id">
                                            <option value="">-- No Transport --</option>
                                            <?php
                                            $stops_to_display = ($role === 'principal' && !empty($admin_school_id)) ? $routes_stops : [];
                                            $current_route = '';
                                            foreach($stops_to_display as $row) {
                                                if ($row['route_name'] !== $current_route) {
                                                    if ($current_route !== '') echo '</optgroup>';
                                                    $current_route = $row['route_name'];
                                                    echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                }
                                                $selected = (isset($_POST['stop_id']) && $_POST['stop_id'] == $row['stop_id']) ? 'selected' : '';
                                                echo "<option value='" . $row['stop_id'] . "' {$selected}>" . htmlspecialchars($row['stop_name']) . "</option>";
                                            }
                                            if ($current_route !== '') echo '</optgroup>';
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row mt-3" id="vehicle-details-div" style="display: <?php echo (isset($_POST['self_transport_mode']) && ($_POST['self_transport_mode'] == 'Bike' || $_POST['self_transport_mode'] == 'Car')) ? 'flex' : 'none'; ?>;">
                                    <div class="form-group col-md-6">
                                        <label for="vehicle_number">Vehicle Number *</label>
                                        <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" value="<?php echo htmlspecialchars($_POST['vehicle_number'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="license_number">License Number *</label>
                                        <input type="text" class="form-control" id="license_number" name="license_number" value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <hr>
                                <h6 class="text-primary">Personal Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-4"><label for="phone">Phone</label><input type="tel" class="form-control" id="phone" name="phone" maxlength="10" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"></div>
                                    <div class="form-group col-md-4"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>"></div>
                                    <div class="form-group col-md-4"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male" <?= (($_POST['gender'] ?? '') == 'Male') ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= (($_POST['gender'] ?? '') == 'Female') ? 'selected' : '' ?>>Female</option>
                                            <option value="Others" <?= (($_POST['gender'] ?? '') == 'Others') ? 'selected' : '' ?>>Others</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required>
                                            <option value="">-- Select Blood Group --</option>
                                            <?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                            foreach ($bg_options as $bg) {
                                                $selected = (($_POST['blood_group'] ?? '') == $bg) ? 'selected' : '';
                                                echo "<option value='{$bg}' {$selected}>" . $bg . "</option>";
                                            } ?>
                                        </select></div>
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
    
    <?php include_once "../../includes/logout_modal.php"?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Image Preview
            $('#teacher_image').on('change', function(event) {
                if (event.target.files && event.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imagePreview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(event.target.files[0]);
                }
            });

            $('button[type="reset"]').on('click', function() {
                $('#imagePreview').attr('src', '../../assets/images/unisex.png');
            });

            // Blur past dates for "Date of Joining"
            const dateInput = document.getElementById('date_of_joining');
            if (dateInput) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const formattedDate = `${year}-${month}-${day}`;
                dateInput.setAttribute('min', formattedDate);
            }

            // Transport Logic
            const transportModeSelect = document.getElementById('transport_mode');
            const selfTransportSelect = document.getElementById('self_transport_mode');
            const schoolTransportDiv = document.getElementById('transport-stop-div');
            const selfTransportDiv = document.getElementById('self-transport-div');
            const vehicleDetailsDiv = document.getElementById('vehicle-details-div');

            function toggleSelfTransportFields() {
                const selectedMode = selfTransportSelect.value;
                if (selectedMode === 'Bike' || selectedMode === 'Car') {
                    vehicleDetailsDiv.style.display = 'flex';
                } else {
                    vehicleDetailsDiv.style.display = 'none';
                    document.getElementById('vehicle_number').value = '';
                    document.getElementById('license_number').value = '';
                }
            }

            function toggleTransportFields() {
                const mainMode = transportModeSelect.value;
                if (mainMode === 'School Transport') {
                    schoolTransportDiv.style.display = 'block';
                    selfTransportDiv.style.display = 'none';
                    vehicleDetailsDiv.style.display = 'none';
                    document.getElementById('self_transport_mode').value = '';
                    document.getElementById('vehicle_number').value = '';
                    document.getElementById('license_number').value = '';
                } else if (mainMode === 'Self Transport') {
                    selfTransportDiv.style.display = 'block';
                    schoolTransportDiv.style.display = 'none';
                    document.getElementById('stop_id').value = '';
                    toggleSelfTransportFields(); 
                } else {
                    selfTransportDiv.style.display = 'none';
                    schoolTransportDiv.style.display = 'none';
                    vehicleDetailsDiv.style.display = 'none';
                    document.getElementById('self_transport_mode').value = '';
                    document.getElementById('stop_id').value = '';
                    document.getElementById('vehicle_number').value = '';
                    document.getElementById('license_number').value = '';
                }
            }

            // Initial check on page load to set the correct display state
            toggleTransportFields();

            // Add event listeners
            transportModeSelect.addEventListener('change', toggleTransportFields);
            selfTransportSelect.addEventListener('change', toggleSelfTransportFields);
        });
    </script>
</body>

</html>
<?php
}
?>