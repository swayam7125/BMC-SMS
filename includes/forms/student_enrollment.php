<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";
// NEW: Include the logging system
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
// Fetch the enrolling user's name for logging context
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
    $stmt = $conn->prepare('SELECT s."id", s."school_name" FROM "principal" p JOIN "school" s ON p."school_id" = s."id" WHERE p."id" = ?');
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

try {
    $stmt_schools = $conn->query('SELECT "id", "school_name" FROM "school" ORDER BY "school_name"');
    $schools = $stmt_schools->fetchAll(PDO::FETCH_ASSOC);

    // Fetch standards based on school category with custom sorting
    if ($admin_school_id) {
    $stmt_standards = $conn->prepare('
        SELECT DISTINCT scm.standard_name,
        CASE
            WHEN scm.standard_name = \'Nursery\' THEN 1
            WHEN scm.standard_name = \'Junior\' THEN 2
            WHEN scm.standard_name = \'Senior\' THEN 3
            ELSE 4
        END AS custom_order_group,

        CASE
            WHEN scm.standard_name ~ \'^[0-9]+$\' THEN CAST(scm.standard_name AS INTEGER)
            ELSE 999
        END AS custom_order_numeric
        FROM "school" s
        JOIN "standard_categories_mapping" scm ON scm.category_name = ANY(s.school_category)
        WHERE s.id = ?
        ORDER BY
            custom_order_group,
            custom_order_numeric,
            scm.standard_name

    ');

    $stmt_standards->execute([$admin_school_id]);
    $standards = $stmt_standards->fetchAll(PDO::FETCH_ASSOC);
    
} else {
    // Fallback for non-principal roles or if no school ID is found
    $stmt_standards = $conn->query('SELECT DISTINCT standard_name FROM "standard_categories_mapping" ORDER BY standard_name');
    $standards = $stmt_standards->fetchAll(PDO::FETCH_ASSOC);
}

    // Fetch transport stops for the current school if the user is a principal
    $transport_stops = [];
    if ($role === 'principal' && $admin_school_id) {
        $stmt_routes = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM routes r JOIN stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
        $stmt_routes->execute([$admin_school_id]);
        $transport_stops = $stmt_routes->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

if (!is_ajax_request()) {
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
                            <form id="enrollmentForm" method="POST" action="../includes/forms/process_student_enrollment.php" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="../../assets/images/unisex.png" alt="Student Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="student_photo" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label>
                                            <input type="file" class="d-none" id="student_photo" name="photo" accept="image/*">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="name">Student Name *</label><input type="text" class="form-control" id="name" name="name" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" required></div>
                                            <div class="form-group col-md-6"><label for="password">Password *</label><input type="password" class="form-control" id="password" name="password" required></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                <h6 class="text-primary">Academic Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6">
                                        <label for="school_id">School *</label>
                                        <?php if ($role === 'principal'): ?>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_school_name); ?>" disabled>
                                            <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($admin_school_id); ?>">
                                        <?php else: ?>
                                            <select class="form-control" id="school_id" name="school_id" required>
                                                <option value="">-- Select School --</option>
                                                <?php foreach ($schools as $school): ?>
                                                    <option value="<?php echo htmlspecialchars($school['id']); ?>"><?php echo htmlspecialchars($school['school_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="academic_year">Academic Year *</label>
                                        <select class="form-control" id="academic_year" name="academic_year" required>
                                            <option value="">-- Select Year --</option>
                                            <?php
                                            $currentYear = date('Y');
                                            for ($i = -2; $i <= 2; $i++):
                                                $year = $currentYear + $i;
                                                $academicYear = $year . '-' . ($year + 1);
                                                echo "<option value='" . $academicYear . "'>" . $academicYear . "</option>";
                                            endfor;
                                            ?>
                                        </select>
                                    </div>
                                    </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="class">Class / Standard *</label>
                                        <select class="form-control" id="class" name="class" required>
                                            <option value="">-- Select Class --</option>
                                            <?php foreach ($standards as $standard): ?>
                                                <option value="<?php echo htmlspecialchars($standard['standard_name']); ?>"><?php echo htmlspecialchars($standard['standard_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="roll_number">Roll Number *</label>
                                        <input type="text" class="form-control" id="roll_number" name="roll_number" required>
                                    </div>
                                
                                </div>

                                <hr>
                                <h6 class="text-primary">Personal Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6"><label for="dob">Date of Birth *</label><input type="date" class="form-control" id="dob" name="dob" required></div>
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Others">Others</option>
                                        </select></div>
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required>
                                            <option value="">-- Select Blood Group --</option>
                                            <option value="A+">A+</option><option value="A-">A-</option><option value="B+">B+</option><option value="B-">B-</option><option value="AB+">AB+</option><option value="AB-">AB-</option><option value="O+">O+</option><option value="O-">O-</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="address">Residential Address</label><textarea class="form-control" id="address" name="address" rows="1"></textarea></div>
                                </div>
                                
                                <hr>
                                <h6 class="text-primary">Parent/Guardian Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6"><label for="father_name">Father's Name *</label><input type="text" class="form-control" id="father_name" name="father_name" required></div>
                                    <div class="form-group col-md-6"><label for="father_phone">Father's Contact Number *</label><input type="tel" class="form-control" id="father_phone" name="father_phone" maxlength="10" required></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="mother_name">Mother's Name *</label><input type="text" class="form-control" id="mother_name" name="mother_name" required></div>
                                    <div class="form-group col-md-6"><label for="mother_phone">Mother's Contact Number *</label><input type="tel" class="form-control" id="mother_phone" name="mother_phone" maxlength="10" required></div>
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
                                     <div class="form-group col-md-6" id="self-transport-div" style="display: block;">
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
                                            <?php
                                            $current_route = '';
                                            foreach ($transport_stops as $row) {
                                                if ($row['route_name'] !== $current_route) {
                                                    if ($current_route !== '') echo '</optgroup>';
                                                    $current_route = $row['route_name'];
                                                    echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                }
                                                echo "<option value='" . $row['stop_id'] . "'>" . htmlspecialchars($row['stop_name']) . "</option>";
                                            }
                                            if ($current_route !== '') echo '</optgroup>';
                                            ?>
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

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll Student</button>
                                    <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Reset Form</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/js/ajax-forms.js"></script>
    <script>
        $(document).ready(function() {
            // Image Preview
            $('#student_photo').on('change', function(event) {
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

            toggleTransportFields();

            transportModeSelect.addEventListener('change', toggleTransportFields);
            selfTransportSelect.addEventListener('change', toggleSelfTransportFields);
        });
    </script>
</body>
</html>
<?php
}
?>