<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

$role = null;
$userId = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

$admin_school_id = null;
$admin_school_name = null;
if ($userId) {
    try {
        $stmt = $conn->prepare('SELECT s."id", s."school_name" FROM "principal" p JOIN "school" s ON p."school_id" = s."id" WHERE p."id" = ?');
        $stmt->execute([$userId]);
        $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin_data) {
            $admin_school_id = $admin_data['id'];
            $admin_school_name = $admin_data['school_name'];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch principal's school info: " . $e->getMessage());
        die("Could not retrieve school information.");
    }
}

$errors = [];
$schools = [];

try {
    $stmt_schools = $conn->query('SELECT "id", "school_name" FROM "school" ORDER BY "school_name"');
    $schools = $stmt_schools->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- FORM DATA RETRIEVAL ---
    $librarian_name = trim($_POST['librarian_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $salary = trim($_POST['salary']);
    $timings = $_POST['timings'] ?? [];
    $batch = $_POST['batch'] ?? '';
    $school_id = $admin_school_id;
    $image_path_for_db = null;
    $date_of_joining = $_POST['date_of_joining'] ?? null;

    // NEW: Retrieve transport-related fields
    $transport_mode = $_POST['transport_mode'] ?? 'Self Transport';
    $stop_id = ($transport_mode === 'School Transport' && !empty($_POST['stop_id'])) ? (int)$_POST['stop_id'] : null;
    $self_transport_mode = ($transport_mode === 'Self Transport' && !empty($_POST['self_transport_mode'])) ? $_POST['self_transport_mode'] : null;
    $vehicle_number = null;
    $license_number = null;
    if ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') {
        $vehicle_number = trim($_POST['vehicle_number'] ?? '');
        $license_number = trim($_POST['license_number'] ?? '');
    }

    // --- FILE UPLOAD LOGIC ---
    if (isset($_FILES['librarian_image']) && $_FILES['librarian_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['librarian_image'];
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/librarian/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = 'librarian_' . uniqid() . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $image_path_for_db = '/BMC-SMS/pages/librarian/uploads/' . $fileName;
        } else {
            $errors[] = "Failed to upload image.";
        }
    }

    // --- VALIDATION ---
    if (empty($librarian_name)) $errors[] = "Librarian name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (empty($school_id)) $errors[] = "School association is missing.";
    if (empty($batch)) $errors[] = "Batch is required.";
    // NEW: Validation for transport fields
    if ($transport_mode === 'Self Transport' && empty($self_transport_mode)) $errors[] = "Please specify the mode of self-transport.";
    if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($vehicle_number)) $errors[] = "Vehicle number is required.";
    if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($license_number)) $errors[] = "License number is required.";

    // --- DATABASE INSERTION ---
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_role = 'librarian';

            $stmt_user = $conn->prepare('INSERT INTO "users" ("role", "email", "password") VALUES (?, ?, ?)');
            $stmt_user->execute([$user_role, $email, $hashed_password]);
            $new_user_id = $conn->lastInsertId();

            // MODIFIED: Added new columns to the INSERT statement
            $stmt_librarian = $conn->prepare('INSERT INTO "librarian" (id, librarian_image, librarian_name, school_id, email, password, phone, dob, gender, blood_group, address, qualification, salary, batch, date_of_joining, transport_mode, self_transport_mode, vehicle_number, license_number, stop_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt_librarian->execute([$new_user_id, $image_path_for_db, $librarian_name, $school_id, $email, $hashed_password, $phone, $dob, $gender, $blood_group, $address, $qualification, $salary, $batch, $date_of_joining, $transport_mode, $self_transport_mode, $vehicle_number, $license_number, $stop_id]);

            $stmt_timing = $conn->prepare('INSERT INTO "librarian_timings" (librarian_id, day_of_week, opens_at, closes_at, is_closed) VALUES (?, ?, ?, ?, ?)');
            foreach ($timings as $day => $details) {
                $is_closed = isset($details['is_closed']) ? 1 : 0;
                
                // --- UPDATE: Convert 12-hour AM/PM time to 24-hour format for DB ---
                $opens_at = null;
                if (!$is_closed && !empty($details['opens_at']) && !empty($details['opens_at_ampm'])) {
                    $opens_at = date("H:i:s", strtotime($details['opens_at'] . ' ' . $details['opens_at_ampm']));
                }

                $closes_at = null;
                if (!$is_closed && !empty($details['closes_at']) && !empty($details['closes_at_ampm'])) {
                    $closes_at = date("H:i:s", strtotime($details['closes_at'] . ' ' . $details['closes_at_ampm']));
                }

                $stmt_timing->execute([$new_user_id, $day, $opens_at, $closes_at, $is_closed]);
            }

            $conn->commit();
            header("Location: ../../pages/librarian/librarian_list.php?success=Librarian enrolled successfully");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            if ($e->getCode() == 23505) {
                if (strpos($e->getMessage(), 'unique_librarian_school_batch') !== false) {
                    $errors[] = "A librarian is already assigned to this school for the selected batch.";
                } else {
                    $errors[] = "A user with this email or phone number already exists.";
                }
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
    <title>Enroll Librarian - School Management System</title>
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
<?php
}
?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Enroll New Librarian</h1>
                        <a href="../../pages/librarian/librarian_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Librarian Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="../../assets/images/unisex.png" alt="Librarian Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="librarian_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label>
                                            <input type="file" class="d-none" id="librarian_image" name="librarian_image" accept="image/*">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="librarian_name">Librarian Name *</label><input type="text" class="form-control" id="librarian_name" name="librarian_name" value="<?php echo htmlspecialchars($_POST['librarian_name'] ?? ''); ?>" required></div>
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
                                        <label>Assigned School</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_school_name); ?>" disabled>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="batch">Batch *</label>
                                        <select class="form-control" id="batch" name="batch" required>
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning" <?= (($_POST['batch'] ?? '') == 'Morning') ? 'selected' : '' ?>>Morning</option>
                                            <option value="Evening" <?= (($_POST['batch'] ?? '') == 'Evening') ? 'selected' : '' ?>>Evening</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="date_of_joining">Date of Joining</label>
                                        <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" value="<?php echo htmlspecialchars($_POST['date_of_joining'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group col-md-6"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>"></div>
                                </div>
                                <div class="form-row">
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
                                            $school_to_check = ($role === 'principal') ? $admin_school_id : ($_POST['school_id'] ?? null);
                                            if ($school_to_check) {
                                                $stmt_routes = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM routes r JOIN stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
                                                $stmt_routes->execute([$school_to_check]);
                                                $current_route = '';
                                                while($row = $stmt_routes->fetch(PDO::FETCH_ASSOC)) {
                                                    if ($row['route_name'] !== $current_route) {
                                                        if ($current_route !== '') echo '</optgroup>';
                                                        $current_route = $row['route_name'];
                                                        echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                    }
                                                    $selected = (isset($_POST['stop_id']) && $_POST['stop_id'] == $row['stop_id']) ? 'selected' : '';
                                                    echo "<option value='" . $row['stop_id'] . "' {$selected}>" . htmlspecialchars($row['stop_name']) . "</option>";
                                                }
                                                if ($current_route !== '') echo '</optgroup>';
                                            }
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
                                <h6 class="font-weight-bold text-primary mb-3">Weekly Timings</h6>
                                <div id="timings-schedule">
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day):
                                        $posted_day = $_POST['timings'][$day] ?? [];
                                        $is_closed = isset($posted_day['is_closed']);
                                        $opens_at = $posted_day['opens_at'] ?? '09:00';
                                        $opens_at_ampm = $posted_day['opens_at_ampm'] ?? 'AM';
                                        $closes_at = $posted_day['closes_at'] ?? '05:00';
                                        $closes_at_ampm = $posted_day['closes_at_ampm'] ?? 'PM';
                                    ?>
                                        <div class="form-row align-items-center mb-2 timing-row" data-day="<?php echo $day; ?>">
                                            <div class="col-md-2"><label class="mb-0"><?php echo $day; ?></label></div>
                                            <div class="col-md-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input closed-checkbox" id="closed_<?php echo $day; ?>" name="timings[<?php echo $day; ?>][is_closed]" <?php if ($is_closed) echo 'checked'; ?>>
                                                    <label class="custom-control-label" for="closed_<?php echo $day; ?>">Closed</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text small">Opens at</span></div>
                                                    <input type="text" class="form-control time-input" name="timings[<?php echo $day; ?>][opens_at]" value="<?php echo htmlspecialchars($opens_at); ?>" placeholder="HH:MM" <?php if ($is_closed) echo 'disabled'; ?>>
                                                    <div class="input-group-append">
                                                        <select class="form-control ampm-select" name="timings[<?php echo $day; ?>][opens_at_ampm]" <?php if ($is_closed) echo 'disabled'; ?>>
                                                            <option value="AM" <?php if ($opens_at_ampm == 'AM') echo 'selected'; ?>>AM</option>
                                                            <option value="PM" <?php if ($opens_at_ampm == 'PM') echo 'selected'; ?>>PM</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text small">Closes at</span></div>
                                                    <input type="text" class="form-control time-input" name="timings[<?php echo $day; ?>][closes_at]" value="<?php echo htmlspecialchars($closes_at); ?>" placeholder="HH:MM" <?php if ($is_closed) echo 'disabled'; ?>>
                                                    <div class="input-group-append">
                                                        <select class="form-control ampm-select" name="timings[<?php echo $day; ?>][closes_at_ampm]" <?php if ($is_closed) echo 'disabled'; ?>>
                                                            <option value="AM" <?php if ($closes_at_ampm == 'AM') echo 'selected'; ?>>AM</option>
                                                            <option value="PM" <?php if ($closes_at_ampm == 'PM') echo 'selected'; ?>>PM</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <hr>
                                <h6 class="text-primary">Personal Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-4"><label for="phone">Phone *</label><input type="tel" class="form-control" id="phone" name="phone" maxlength="10" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required></div>
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
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll Librarian</button>
                                    <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Reset Form</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
<?php
if (!is_ajax_request()) {
?>
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
            $('#librarian_image').on('change', function(event) {
                if (event.target.files && event.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imagePreview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(event.target.files[0]);
                }
            });

            // Timings schedule logic
            $('.closed-checkbox').on('change', function() {
                const row = $(this).closest('.timing-row');
                const timeInputs = row.find('.time-input, .ampm-select');
                timeInputs.prop('disabled', $(this).is(':checked'));
            });

            // Trigger on page load to set initial state
            $('.closed-checkbox').trigger('change');
            
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

            // NEW: JavaScript for transport fields
            const transportModeSelect = document.getElementById('transport_mode');
            const selfTransportSelect = document.getElementById('self_transport_mode');
            const schoolTransportDiv = document.getElementById('transport-stop-div');
            const selfTransportDiv = document.getElementById('self-transport-div');
            const vehicleDetailsDiv = document.getElementById('vehicle-details-div');
            const schoolSelect = document.getElementById('school_id'); // Assuming there's a school_id select for non-principal roles
            
            // Function to fetch transport stops for a school
            function fetchTransportStops(schoolId, selectedStopId = null) {
                if (!schoolId) {
                    $('#stop_id').html('<option value="">-- No Transport --</option>');
                    return;
                }
                
                $('#stop_id').html('<option value="">-- Loading stops --</option>');
                
                fetch('../../pages/student/get_transport_stops.php?school_id=' + schoolId)
                    .then(response => response.json())
                    .then(data => {
                        let options = '<option value="">-- No Transport --</option>';
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

                    const schoolId = $('#school_id').val();
                    if (schoolId) {
                        fetchTransportStops(schoolId);
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

            // Trigger initial state
            toggleTransportFields();

            // Add event listeners
            transportModeSelect.addEventListener('change', toggleTransportFields);
            selfTransportSelect.addEventListener('change', toggleSelfTransportFields);

            // Re-fetch stops if school changes
            if (schoolSelect) {
                schoolSelect.addEventListener('change', function() {
                    if (transportModeSelect.value === 'School Transport') {
                        fetchTransportStops(this.value);
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php
}
?>