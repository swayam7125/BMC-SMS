<?php
// FILE: student_enrollment.php

//======================================================================
// PART 1: FORM PROCESSING LOGIC
//======================================================================

include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";
require_once "../../includes/log_system.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the request is a POST request to process the form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax_request()) {
    
    // Check authentication and role from session
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        Response::send([
            'success' => false,
            'message' => 'Unauthorized access. Please log in again.',
            'redirect' => '../../login.php'
        ], 403);
        exit;
    }

    // Identify enrolling user from session for logging
    $enrolling_user_id = $_SESSION['user_id'];
    $enrolling_role = $_SESSION['user_role'];
    $enrolling_user_name = $_SESSION['user_name'] ?? 'Session User';

    $target_path = null; // Initialize for potential file cleanup on error

    try {
        // Start transaction
        $conn->beginTransaction();

        // Validate required fields
        $required_fields = ['name', 'roll_number', 'class', 'school_id', 'email', 'password', 'dob', 'gender', 'blood_group', 'father_name', 'father_phone', 'mother_name', 'mother_phone', 'academic_year', 'transport_mode'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields. Missing: " . str_replace('_', ' ', ucfirst($field)));
            }
        }
        
        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Retrieve and prepare data
        $school_id = (int)$_POST['school_id'];
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user_role = 'student';
        $student_name = trim($_POST['name']);

        // Handle file upload for student photo
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/student/uploads/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Failed to create upload directory.");
                }
            }
            $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $new_file_name = "student_" . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                $photo_path = '/BMC-SMS/pages/student/uploads/' . $new_file_name;
            } else {
                throw new Exception("Failed to upload student photo.");
            }
        }

        // 1. Insert into the main 'users' table to create the login account
        $stmt_user = $conn->prepare('INSERT INTO public.users (role, email, password, account_status) VALUES (?, ?, ?, ?)');
        $stmt_user->execute([$user_role, $_POST['email'], $hashed_password, 'active']);
        $user_id = $conn->lastInsertId('public.users_id_seq');

        // 2. Insert the detailed student record into the 'student' table
        $stmt_student = $conn->prepare("
            INSERT INTO public.student (
                id, student_name, rollno, std, academic_year, school_id, dob, gender, blood_group, 
                address, father_name, father_phone, mother_name, mother_phone, 
                transport_mode, self_transport_mode, stop_id, vehicle_number, license_number, student_image, email, password
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        $stmt_student->execute([
            $user_id,
            $student_name,
            $_POST['roll_number'],
            $_POST['class'],
            $_POST['academic_year'],
            $school_id,
            $_POST['dob'],
            $_POST['gender'],
            $_POST['blood_group'],
            $_POST['address'] ?? null,
            $_POST['father_name'],
            $_POST['father_phone'],
            $_POST['mother_name'],
            $_POST['mother_phone'],
            $_POST['transport_mode'],
            $_POST['self_transport_mode'] ?? null,
            empty($_POST['stop_id']) ? null : (int)$_POST['stop_id'],
            $_POST['vehicle_number'] ?? null,
            $_POST['license_number'] ?? null,
            $photo_path,
            $_POST['email'], // Storing email and password hash in student table too
            $hashed_password
        ]);

        // Commit the transaction
        $conn->commit();
        
        // Log the successful enrollment
        log_interaction($enrolling_role, $enrolling_user_id, "ENROLLMENT SUCCESS: Enrolled new student: {$student_name} (Roll: {$_POST['roll_number']}, ID: {$user_id})", $enrolling_user_name);

        // Send a success response back to the form
        Response::send([
            'success' => true,
            'message' => "Student '{$student_name}' enrolled successfully!",
            'redirect' => '../student/student_list.php' // Redirect after success
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        // If a file was uploaded, try to delete it to avoid orphaned files
        if ($target_path && file_exists($target_path)) {
            unlink($target_path);
        }
        
        // Handle specific database constraint errors
        $error_message = $e->getMessage();
        if (strpos($error_message, 'duplicate key') !== false) {
            if (strpos($error_message, 'student_email_key') !== false) {
                $error_message = "A student with this email already exists.";
            } elseif (strpos($error_message, 'uq_student_rollno_std_year') !== false) {
                $error_message = "A student with this roll number already exists in the selected class and academic year.";
            } else {
                 $error_message = "A user with these details already exists.";
            }
        } else {
            error_log("Student Enrollment Error: " . $error_message); // Log the full error for the admin
            $error_message = "A system error occurred. Please check the required fields and try again.";
        }

        // Send an error response
        Response::send(['success' => false, 'message' => $error_message], 500);
    }
    
    exit; // Stop execution after handling the POST request
}

//======================================================================
// PART 2: HTML FORM DISPLAY
//======================================================================

$role = null;
$userId = null;
$enrolling_user_name = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (isset($_COOKIE['encrypted_user_name'])) {
    $enrolling_user_name = decrypt_id($_COOKIE['encrypted_user_name']);
}

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$admin_school_id = null;
$admin_school_name = null;
if ($role === 'principal' && $userId) {
    $stmt = $conn->prepare('SELECT s.id, s.school_name FROM public.principal p JOIN public.school s ON p.school_id = s.id WHERE p.id = ?');
    $stmt->execute([$userId]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin_data) {
        $admin_school_id = $admin_data['id'];
        $admin_school_name = $admin_data['school_name'];
    }
}

$errors = [];
$schools = [];
$standards = [];
$transport_stops = [];

try {
    // Fetch schools for non-principals
    if ($role !== 'principal') {
        $stmt_schools = $conn->query('SELECT id, school_name FROM public.school ORDER BY school_name');
        $schools = $stmt_schools->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch standards based on the principal's school
    if ($admin_school_id) {
        // THIS IS THE CORRECTED QUERY
        $stmt_standards = $conn->prepare('
            SELECT DISTINCT 
                scm.standard_name,
                CASE WHEN scm.standard_name ~ \'^[0-9]+$\' THEN lpad(scm.standard_name, 2, \'0\') ELSE scm.standard_name END AS sort_order
            FROM public.school s
            JOIN public.standard_categories_mapping scm ON scm.category_name = ANY(s.school_category)
            WHERE s.id = ?
            ORDER BY sort_order
        ');
        $stmt_standards->execute([$admin_school_id]);
        $standards = $stmt_standards->fetchAll(PDO::FETCH_ASSOC);

        // Fetch transport stops for the principal's school
        $stmt_routes = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM public.routes r JOIN public.stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
        $stmt_routes->execute([$admin_school_id]);
        $transport_stops = $stmt_routes->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    // Display the error directly on the page for debugging
    $errors[] = "Database error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enroll Student - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
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
                        <h1 class="h3 mb-0 text-gray-800">Enroll New Student</h1>
                        <a href="../../pages/student/student_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    
                    <div id="enrollment-alert-placeholder"></div>
                    
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
                            <form id="enrollmentForm" method="POST" action="" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img src="../../assets/images/unisex.png" alt="Student Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="student_photo" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label>
                                            <input type="file" class="d-none" id="student_photo" name="photo" accept="image/*">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row"><div class="form-group col-md-12"><label for="name">Student Name *</label><input type="text" class="form-control" id="name" name="name" required></div></div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" required></div>
                                            <div class="form-group col-md-6"><label for="password">Set Initial Password *</label><input type="password" class="form-control" id="password" name="password" required></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>

                                <h6 class="text-primary">Academic Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6">
                                        <label for="school_id">School *</label>
                                        <?php if ($role === 'principal' && $admin_school_id): ?>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_school_name); ?>" disabled>
                                            <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($admin_school_id); ?>">
                                        <?php else: ?>
                                            <select class="form-control" id="school_id" name="school_id" required>
                                                <option value="">-- Select School --</option>
                                                <?php foreach ($schools as $school): ?><option value="<?php echo htmlspecialchars($school['id']); ?>"><?php echo htmlspecialchars($school['school_name']); ?></option><?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="academic_year">Academic Year *</label>
                                        <select class="form-control" id="academic_year" name="academic_year" required>
                                            <option value="">-- Select Year --</option>
                                            <?php $currentYear = date('Y'); for ($i = -2; $i <= 2; $i++): $year = $currentYear + $i; $academicYear = $year . '-' . ($year + 1); echo "<option value='" . $academicYear . "'>" . $academicYear . "</option>"; endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="class">Class / Standard *</label>
                                        <select class="form-control" id="class" name="class" required>
                                            <option value="">-- Select Class --</option>
                                            <?php foreach ($standards as $standard): ?><option value="<?php echo htmlspecialchars($standard['standard_name']); ?>"><?php echo htmlspecialchars($standard['standard_name']); ?></option><?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6"><label for="roll_number">Roll Number *</label><input type="text" class="form-control" id="roll_number" name="roll_number" required></div>
                                </div>
                                <hr>

                                <h6 class="text-primary">Personal Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-4"><label for="dob">Date of Birth *</label><input type="date" class="form-control" id="dob" name="dob" required></div>
                                    <div class="form-group col-md-4"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required><option value="">-- Select --</option><option value="Male">Male</option><option value="Female">Female</option><option value="Others">Others</option></select></div>
                                    <div class="form-group col-md-4"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><option value="">-- Select --</option><option value="A+">A+</option><option value="A-">A-</option><option value="B+">B+</option><option value="B-">B-</option><option value="AB+">AB+</option><option value="AB-">AB-</option><option value="O+">O+</option><option value="O-">O-</option></select></div>
                                </div>
                                <div class="form-group"><label for="address">Residential Address</label><textarea class="form-control" id="address" name="address" rows="2"></textarea></div>
                                <hr>

                                <h6 class="text-primary">Parent/Guardian Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6"><label for="father_name">Father's Name *</label><input type="text" class="form-control" id="father_name" name="father_name" required></div>
                                    <div class="form-group col-md-6"><label for="father_phone">Father's Contact (10 digits) *</label><input type="tel" class="form-control" id="father_phone" name="father_phone" pattern="[0-9]{10}" title="Please enter a 10-digit phone number" required></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="mother_name">Mother's Name *</label><input type="text" class="form-control" id="mother_name" name="mother_name" required></div>
                                    <div class="form-group col-md-6"><label for="mother_phone">Mother's Contact (10 digits) *</label><input type="tel" class="form-control" id="mother_phone" name="mother_phone" pattern="[0-9]{10}" title="Please enter a 10-digit phone number" required></div>
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
                                        <label for="self_transport_mode">Self Transport Mode</label>
                                        <select class="form-control" id="self_transport_mode" name="self_transport_mode"><option value="">-- Select Mode --</option><option value="Public Transport">Public Transport</option><option value="Walking">Walking</option><option value="Parents">Parents</option><option value="Bike">Bike</option><option value="Car">Car</option></select>
                                    </div>
                                    <div class="form-group col-md-6" id="transport-stop-div" style="display: none;">
                                        <label for="stop_id">Assign Transport Stop</label>
                                        <select class="form-control" id="stop_id" name="stop_id">
                                            <option value="">-- No Stop Assigned --</option>
                                            <?php $current_route = ''; foreach ($transport_stops as $row) { if ($row['route_name'] !== $current_route) { if ($current_route !== '') echo '</optgroup>'; $current_route = $row['route_name']; echo '<optgroup label="' . htmlspecialchars($current_route) . '">'; } echo "<option value='" . $row['stop_id'] . "'>" . htmlspecialchars($row['stop_name']) . "</option>"; } if ($current_route !== '') echo '</optgroup>'; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row mt-3" id="vehicle-details-div" style="display: none;">
                                    <div class="form-group col-md-6"><label for="vehicle_number">Vehicle Number</label><input type="text" class="form-control" id="vehicle_number" name="vehicle_number"></div>
                                    <div class="form-group col-md-6"><label for="license_number">License Number</label><input type="text" class="form-control" id="license_number" name="license_number"></div>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll Student</button>
                                    <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    
    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
    // Handles the AJAX form submission
    function handleFormSubmit(formId, alertPlaceholderId) {
        $(formId).on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitButton = form.find('button[type="submit"]');
            const originalButtonText = submitButton.html();
            submitButton.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

            const formData = new FormData(this);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    let alertClass = response.success ? 'alert-success' : 'alert-danger';
                    let alertMessage = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                                            ${response.message}
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>`;
                    $(alertPlaceholderId).html(alertMessage);

                    if (response.success) {
                        form[0].reset();
                        $('#imagePreview').attr('src', '../../assets/images/unisex.png');
                        if (response.redirect) {
                            setTimeout(function() { window.location.href = response.redirect; }, 1500);
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMessage = 'An unexpected error occurred. Please try again.';
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                    }
                    let alertMessage = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            ${errorMessage}
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>`;
                    $(alertPlaceholderId).html(alertMessage);
                },
                complete: function() {
                    submitButton.html(originalButtonText).prop('disabled', false);
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                }
            });
        });
    }

    $(document).ready(function() {
        // Initialize the form handler
        handleFormSubmit('#enrollmentForm', '#enrollment-alert-placeholder');
        
        // Image Preview Logic
        $('#student_photo').on('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) { $('#imagePreview').attr('src', e.target.result); }
                reader.readAsDataURL(event.target.files[0]);
            }
        });
        
        $('button[type="reset"]').on('click', function() {
            $('#imagePreview').attr('src', '../../assets/images/unisex.png');
            toggleTransportFields(); // Reset transport fields to default state
        });
        
        // Transport UI Logic
        const transportModeSelect = document.getElementById('transport_mode');
        const selfTransportSelect = document.getElementById('self_transport_mode');
        const schoolTransportDiv = document.getElementById('transport-stop-div');
        const selfTransportDiv = document.getElementById('self-transport-div');
        const vehicleDetailsDiv = document.getElementById('vehicle-details-div');

        function toggleSelfTransportFields() {
            const selectedMode = selfTransportSelect.value;
            vehicleDetailsDiv.style.display = (selectedMode === 'Bike' || selectedMode === 'Car') ? 'flex' : 'none';
        }

        function toggleTransportFields() {
            const mainMode = transportModeSelect.value;
            if (mainMode === 'School Transport') {
                schoolTransportDiv.style.display = 'block';
                selfTransportDiv.style.display = 'none';
                vehicleDetailsDiv.style.display = 'none';
                selfTransportSelect.value = '';
            } else { // Self Transport
                selfTransportDiv.style.display = 'block';
                schoolTransportDiv.style.display = 'none';
                document.getElementById('stop_id').value = '';
                toggleSelfTransportFields(); 
            }
        }

        toggleTransportFields(); // Initial check
        transportModeSelect.addEventListener('change', toggleTransportFields);
        selfTransportSelect.addEventListener('change', toggleSelfTransportFields);
    });
    </script>
</body>
</html>