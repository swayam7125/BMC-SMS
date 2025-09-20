<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

// Allow principal and hr to edit student profiles
if ($role !== 'principal' && $role !== 'hr') {
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

// Define BASE_WEB_PATH for consistent path handling
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

try {
    // Check if the user is authorized to edit this student
    $query_access = "SELECT school_id FROM student WHERE id = ?";
    $stmt_access = $conn->prepare($query_access);
    $stmt_access->execute([$student_id]);
    $target_school_id = $stmt_access->fetchColumn();

    $user_school_id = null;
    if ($role === 'principal') {
        $stmt_user_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
        $stmt_user_school->execute([$current_user_id]);
        $user_school_id = $stmt_user_school->fetchColumn();
    } elseif ($role === 'hr') {
        $stmt_user_school = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
        $stmt_user_school->execute([$current_user_id]);
        $user_school_id = $stmt_user_school->fetchColumn();
    }

    if ($target_school_id != $user_school_id) {
        header("Location: student_list.php?error=Unauthorized access");
        exit;
    }

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
        $dob = $_POST['dob'] ?: null;
        $gender = $_POST['gender'];
        $blood_group = $_POST['blood_group'];
        $std = trim($_POST['std']);
        $academic_year = trim($_POST['academic_year']);
        $address = trim($_POST['address']);
        $father_name = trim($_POST['father_name']);
        $father_phone = trim($_POST['father_phone']);
        $mother_name = trim($_POST['mother_name']);
        $mother_phone = trim($_POST['mother_phone']);
        
        $transport_mode = $_POST['transport_mode'] ?? 'Self Transport';
        $stop_id = ($transport_mode === 'School Transport' && !empty($_POST['stop_id'])) ? (int)$_POST['stop_id'] : null;
        
        //Retrieve self-transport fields
        $self_transport_mode = ($transport_mode === 'Self Transport' && !empty($_POST['self_transport_mode'])) ? $_POST['self_transport_mode'] : null;
        $vehicle_number = null;
        $license_number = null;

        if ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') {
            $vehicle_number = trim($_POST['vehicle_number'] ?? '');
            $license_number = trim($_POST['license_number'] ?? '');
        }

        $image_path_for_db = $original_image_path;

        // --- Validation ---
        if (empty($student_name)) $errors[] = "Student name is required.";
        if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
        if (empty($rollno)) $errors[] = "Roll Number is required.";
        if ($transport_mode === 'Self Transport' && empty($self_transport_mode)) $errors[] = "Please specify the mode of self-transport.";
        if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($vehicle_number)) $errors[] = "Vehicle number is required.";
        if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($license_number)) $errors[] = "License number is required.";
        
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
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/student/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_filename = 'student_' . uniqid('', true) . '.' . $file_ext;
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $image_path_for_db = '/BMC-SMS/pages/student/uploads/' . $new_filename;

                    $old_path_for_delete = $_SERVER['DOCUMENT_ROOT'] . $original_image_path;
                    if (!empty($original_image_path) && file_exists($old_path_for_delete) && is_file($old_path_for_delete)) {
                        @unlink($old_path_for_delete);
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

            if ($new_email !== $original_email) {
                $stmt_users = $conn->prepare("UPDATE users SET email = ? WHERE id = ? AND role = 'student'");
                $stmt_users->execute([$new_email, $student_id]);
            }

            $update_student_sql = "UPDATE student SET
                                  student_image = ?, student_name = ?, rollno = ?, std = ?, email = ?, academic_year = ?,
                                  school_id = ?, dob = ?, gender = ?, blood_group = ?, address = ?,
                                  father_name = ?, father_phone = ?, mother_name = ?, mother_phone = ?,
                                  stop_id = ?, transport_mode = ?, self_transport_mode = ?, vehicle_number = ?, license_number = ?
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
                $stop_id,
                $transport_mode,
                $self_transport_mode,
                $vehicle_number,
                $license_number,
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

    $schools_result = $conn->query("SELECT id, school_name FROM school ORDER BY school_name");
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $errors[] = "Database update failed: " . $e->getMessage();
    error_log("Edit student error: " . $e->getMessage());
    $schools_result = $conn->query("SELECT id, school_name FROM school ORDER BY school_name");
}

// Prepare the image path for display in the HTML
$display_image_path = $student['student_image'] ?? '';
$default_image_path = BASE_WEB_PATH . 'assets/images/undraw_profile.svg';

$full_path = $_SERVER['DOCUMENT_ROOT'] . $display_image_path;

if (!empty($display_image_path) && file_exists($full_path)) {
    $image_src = $display_image_path;
} else {
    $image_src = $default_image_path;
}

try {
    $school_to_check = $student['school_id'];
    $stmt_routes = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM routes r JOIN stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
    $stmt_routes->execute([$school_to_check]);
    $transport_stops = $stmt_routes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $transport_stops = [];
    error_log("Could not fetch transport stops: " . $e->getMessage());
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
                                        <img src="<?php echo htmlspecialchars($image_src); ?>" alt="Student Photo" id="imagePreview" class="img-thumbnail mb-2 mt-3 h-50 w-50" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group mt-3"><label for="student_image" class="small btn btn-sm btn-primary"><i class="fas fa-upload fa-sm"></i> Change Photo</label><input type="file" class="d-none" id="student_image" name="student_image" onchange="document.getElementById('imagePreview').src = window.URL.createObjectURL(this.files[0])"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-6 form-group"><label for="student_name">Student Name *</label><input type="text" class="form-control" id="student_name" name="student_name" value="<?php echo htmlspecialchars($student['student_name'] ?? ''); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($student['dob'] ?? ''); ?>"></div>
                                            <div class="col-md-6 form-group">
                                                <label for="gender">Gender</label>
                                                <select class="form-control" id="gender" name="gender">
                                                    <option value="Male" <?php echo (isset($student['gender']) && $student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo (isset($student['gender']) && $student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                                    <option value="Others" <?php echo (isset($student['gender']) && $student['gender'] === 'Others') ? 'selected' : ''; ?>>Others</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 form-group"><label for="blood_group">Blood Group</label><select class="form-control" id="blood_group" name="blood_group"><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                foreach ($bg_options as $bg) {
                                                    $selected = (($student['blood_group'] ?? '') == $bg) ? 'selected' : '';
                                                    echo "<option value='{$bg}' {$selected}>" . strtoupper($bg) . "</option>";
                                                } ?></select>
                                            </div>
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
                                        } ?></select>
                                    </div>
                                    <div class="col-md-6 form-group"><label for="rollno">Roll Number *</label><input type="text" class="form-control" id="rollno" name="rollno" value="<?php echo htmlspecialchars($student['rollno'] ?? ''); ?>" required></div>
                                    <div class="col-md-6 form-group"><label for="std">Class (Standard) *</label><input type="text" class="form-control" id="std" name="std" value="<?php echo htmlspecialchars($student['std'] ?? ''); ?>" required></div>
                                    <div class="col-md-6 form-group"><label for="academic_year">Academic Year *</label><input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($student['academic_year'] ?? ''); ?>" required></div>
                                </div>

                                <hr>
                                <h6 class="text-primary font-weight-bold">Transport Details</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="transport_mode">Mode of Transport *</label>
                                        <select class="form-control" id="transport_mode" name="transport_mode" required>
                                            <option value="Self Transport" <?php echo (isset($student['transport_mode']) && $student['transport_mode'] == 'Self Transport') ? 'selected' : ''; ?>>Self (Own Vehicle/Walking)</option>
                                            <option value="School Transport" <?php echo (isset($student['transport_mode']) && $student['transport_mode'] == 'School Transport') ? 'selected' : ''; ?>>School Transport (Bus/Van)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group" id="self-transport-div" style="display: <?php echo (isset($student['transport_mode']) && $student['transport_mode'] == 'Self Transport') ? 'block' : 'none'; ?>;">
                                        <label for="self_transport_mode">Self Transport Mode *</label>
                                        <select class="form-control" id="self_transport_mode" name="self_transport_mode">
                                            <option value="">-- Select Mode --</option>
                                            <option value="Walking" <?php echo (isset($student['self_transport_mode']) && $student['self_transport_mode'] == 'Walking') ? 'selected' : ''; ?>>Walking</option>
                                            <option value="Parents" <?php echo (isset($student['self_transport_mode']) && $student['self_transport_mode'] == 'Parents') ? 'selected' : ''; ?>>Parents</option>
                                            <option value="Bike" <?php echo (isset($student['self_transport_mode']) && $student['self_transport_mode'] == 'Bike') ? 'selected' : ''; ?>>Bike</option>
                                            <option value="Car" <?php echo (isset($student['self_transport_mode']) && $student['self_transport_mode'] == 'Car') ? 'selected' : ''; ?>>Car</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group" id="transport-stop-div" style="display: <?php echo (isset($student['transport_mode']) && $student['transport_mode'] == 'School Transport') ? 'block' : 'none'; ?>;">
                                        <label for="stop_id">Assign School Transport Stop</label>
                                        <select class="form-control" id="stop_id" name="stop_id">
                                            <option value="">-- No Stop Selected --</option>
                                            <?php
                                            if ($student['school_id']) {
                                                $stmt_routes = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM routes r JOIN stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
                                                $stmt_routes->execute([$student['school_id']]);
                                                $current_route = '';
                                                while ($row = $stmt_routes->fetch(PDO::FETCH_ASSOC)) {
                                                    if ($row['route_name'] !== $current_route) {
                                                        if ($current_route !== '') echo '</optgroup>';
                                                        $current_route = $row['route_name'];
                                                        echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                    }
                                                    $selected = (isset($student['stop_id']) && $student['stop_id'] == $row['stop_id']) ? 'selected' : '';
                                                    echo "<option value='" . $row['stop_id'] . "' {$selected}>" . htmlspecialchars($row['stop_name']) . "</option>";
                                                }
                                                if ($current_route !== '') echo '</optgroup>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3" id="vehicle-details-div" style="display: <?php echo (isset($student['self_transport_mode']) && ($student['self_transport_mode'] == 'Bike' || $student['self_transport_mode'] == 'Car')) ? 'flex' : 'none'; ?>;">
                                    <div class="col-md-6 form-group">
                                        <label for="vehicle_number">Vehicle Number *</label>
                                        <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" value="<?php echo htmlspecialchars($student['vehicle_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="license_number">License Number *</label>
                                        <input type="text" class="form-control" id="license_number" name="license_number" value="<?php echo htmlspecialchars($student['license_number'] ?? ''); ?>">
                                    </div>
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
                document.getElementById('imagePreview').src = window.URL.createObjectURL(file)
            }
        };

        const transportModeSelect = document.getElementById('transport_mode');
        const selfTransportSelect = document.getElementById('self_transport_mode');
        const schoolTransportDiv = document.getElementById('transport-stop-div');
        const selfTransportDiv = document.getElementById('self-transport-div');
        const vehicleDetailsDiv = document.getElementById('vehicle-details-div');

        function fetchTransportStops(schoolId, selectedStopId) {
            if (!schoolId) {
                $('#stop_id').html('<option value="">-- No Transport --</option>');
                return;
            }
            
            $('#stop_id').html('<option value="">-- Loading stops --</option>');
            
            fetch('../teacher/get_transport_stops.php?school_id=' + schoolId)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">-- No Stop Selected --</option>';
                    data.forEach(stop => {
                        const isSelected = stop.stop_id == selectedStopId ? 'selected' : '';
                        options += `<option value="${stop.stop_id}" ${isSelected}>${stop.stop_name} (Route: ${stop.route_name})</option>`;
                    });
                    $('#stop_id').html(options);
                })
                .catch(error => {
                    console.error('Error fetching transport stops:', error);
                    $('#stop_id').html('<option value="">-- Error loading stops --</option>');
                });
        }

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
                
                const schoolId = document.getElementById('school_id').value;
                const selectedStopId = <?php echo json_encode($student['stop_id'] ?? null); ?>;
                if (schoolId) {
                    fetchTransportStops(schoolId, selectedStopId);
                }
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
        document.addEventListener('DOMContentLoaded', function() {
            toggleTransportFields();
        });

        // Add event listeners
        transportModeSelect.addEventListener('change', toggleTransportFields);
        selfTransportSelect.addEventListener('change', toggleSelfTransportFields);
        document.getElementById('school_id').addEventListener('change', function() {
            if (transportModeSelect.value === 'School Transport') {
                fetchTransportStops(this.value, null);
            }
        });
    </script>
</body>

</html>