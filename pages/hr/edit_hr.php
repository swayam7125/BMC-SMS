<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/log_system.php"; // ADDED: Log system dependency

// Check if user is logged in
$role = null;
$current_user_id = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}
$acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'System Admin'; // Added for logging

if ($role !== 'principal') {
    header("Location: ../../login.php?error=Unauthorized");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: hr_list.php?error=Invalid ID provided");
    exit;
}

$hr_id = intval($_GET['id']);
$errors = [];
$hr_user = null;
$original_email = null;
$original_image_path = null;

// Define BASE_WEB_PATH for consistent path handling
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

try {
    // Fetch current HR user data with all necessary fields, including transport
    $sql_hr = "SELECT h.*, u.email, st.stop_name, r.route_name, v.vehicle_number as school_vehicle_number FROM hr h
               LEFT JOIN users u ON h.id = u.id
               LEFT JOIN stops st ON h.stop_id = st.id
               LEFT JOIN routes r ON st.route_id = r.id
               LEFT JOIN vehicles v ON r.vehicle_id = v.id
               WHERE h.id = ?";
    $stmt_hr_fetch = $conn->prepare($sql_hr);
    $stmt_hr_fetch->execute([$hr_id]);
    $hr_user = $stmt_hr_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$hr_user) {
        header("Location: hr_list.php?error=HR user not found");
        exit;
    }

    $original_email = $hr_user['email'] ?? '';
    $original_image_path = $hr_user['hr_image'] ?? '';

    $sql_timings = "SELECT * FROM hr_timings WHERE hr_id = ?";
    $stmt_timings_fetch = $conn->prepare($sql_timings);
    $stmt_timings_fetch->execute([$hr_id]);
    $timings = [];
    while ($row = $stmt_timings_fetch->fetch(PDO::FETCH_ASSOC)) {
        $timings[$row['day_of_week']] = $row;
    }
    
} catch (PDOException $e) {
    die("Database error while fetching data: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Form Data Retrieval ---
    $hr_name = trim($_POST['hr_name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $school_id = (int)($_POST['school_id'] ?? 0);
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $blood_group = $_POST['blood_group'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $language_known = trim($_POST['language_known'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $batch = $_POST['batch'] ?? '';
    $posted_timings = $_POST['timings'] ?? [];
    
    // NEW: Retrieve transport fields from the form
    $transport_mode = $_POST['transport_mode'] ?? 'Self Transport';
    $stop_id = ($transport_mode === 'School Transport' && !empty($_POST['stop_id'])) ? (int)$_POST['stop_id'] : null;
    $self_transport_mode = ($transport_mode === 'Self Transport' && !empty($_POST['self_transport_mode'])) ? $_POST['self_transport_mode'] : null;
    $vehicle_number = null;
    $license_number = null;
    
    if ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') {
        $vehicle_number = trim($_POST['vehicle_number'] ?? '');
        $license_number = trim($_POST['license_number'] ?? '');
    }
    
    $image_path_for_db = $original_image_path;

    // --- Validation ---
    if (empty($hr_name)) $errors[] = "HR name is required.";
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($batch)) $errors[] = "Batch selection is required.";
    
    // NEW: Validation for transport details
    if ($transport_mode === 'Self Transport' && empty($self_transport_mode)) $errors[] = "Please specify the mode of self-transport.";
    if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($vehicle_number)) $errors[] = "Vehicle number is required.";
    if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($license_number)) $errors[] = "License number is required.";


    // --- Handle Photo Upload ---
    if (isset($_FILES['hr_image']) && $_FILES['hr_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['hr_image'];
        // Corrected upload path
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/hr/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'hr_' . $hr_id . '_' . uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Corrected image path for database
            $image_path_for_db = '/BMC-SMS/pages/hr/uploads/' . $new_filename;
            if (!empty($original_image_path) && file_exists($_SERVER['DOCUMENT_ROOT'] . $original_image_path)) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $original_image_path);
            }
        } else {
            $errors[] = "Failed to move uploaded file.";
        }
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            if ($new_email !== $original_email) {
                $sql_update_users = "UPDATE users SET email = ? WHERE id = ? AND role = 'hr'";
                $stmt_users = $conn->prepare($sql_update_users);
                $stmt_users->execute([$new_email, $hr_id]);
            }

            $sql_update_hr = "UPDATE hr SET 
                                  hr_image = ?, hr_name = ?, phone = ?, school_id = ?, dob = ?, gender = ?, blood_group = ?, address = ?, 
                                  email = ?, qualification = ?, language_known = ?, salary = ?, experience = ?, batch = ?, 
                                  transport_mode = ?, self_transport_mode = ?, vehicle_number = ?, license_number = ?, stop_id = ? 
                                  WHERE id = ?";

            $stmt_update = $conn->prepare($sql_update_hr);
            $stmt_update->execute([
                $image_path_for_db,
                $hr_name,
                $phone,
                $school_id,
                $dob,
                $gender,
                $blood_group,
                $address,
                $new_email,
                $qualification,
                $language_known,
                $salary,
                $experience,
                $batch,
                // New fields start here
                $transport_mode,
                $self_transport_mode,
                $vehicle_number,
                $license_number,
                $stop_id,
                // WHERE clause variable
                $hr_id
            ]);

            $sql_upsert_timing = "INSERT INTO hr_timings (hr_id, day_of_week, opens_at, closes_at, is_closed) 
                                  VALUES (?, ?, ?, ?, ?) 
                                  ON CONFLICT (hr_id, day_of_week) 
                                  DO UPDATE SET opens_at = EXCLUDED.opens_at, closes_at = EXCLUDED.closes_at, is_closed = EXCLUDED.is_closed";
            $stmt_timing_upsert = $conn->prepare($sql_upsert_timing);
            foreach ($posted_timings as $day => $details) {
                $is_closed = isset($details['is_closed']) ? 1 : 0;

                // --- UPDATE: Convert 24-hour DB time back to 12-hour and AM/PM for display ---
                $opens_at = null;
                if (!$is_closed && !empty($details['opens_at']) && !empty($details['opens_at_ampm'])) {
                    $opens_at = date("H:i:s", strtotime($details['opens_at'] . ' ' . $details['opens_at_ampm']));
                }
                $closes_at = null;
                if (!$is_closed && !empty($details['closes_at']) && !empty($details['closes_at_ampm'])) {
                    $closes_at = date("H:i:s", strtotime($details['closes_at'] . ' ' . $details['closes_at_ampm']));
                }
                $stmt_timing_upsert->execute([$hr_id, $day, $opens_at, $closes_at, $is_closed]);
            }

            $conn->commit();
            
            // ⭐ LOGGING: Log the HR user profile update
            $log_message = "UPDATE: HR user profile for '{$hr_name}' (ID: {$hr_id}) was successfully updated by {$role}.";
            log_interaction($role, $current_user_id, $log_message, $acting_user_name);
            
            header("Location: hr_list.php?success=HR user updated successfully");
            exit;
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            if ($e->getCode() == 23505) {
                if (strpos($e->getMessage(), 'hr_email_key') !== false) {
                    $errors[] = "This email is already in use.";
                } elseif (strpos($e->getMessage(), 'uq_hr_phone') !== false) {
                    $errors[] = "This phone number is already in use.";
                } else {
                    $errors[] = "A duplicate entry was found. Please check your data.";
                }
            } else {
                $errors[] = "Database update failed: " . $e->getMessage();
            }
        }
    }
    // Re-populate form fields in case of error
    $hr_user = array_merge($hr_user, $_POST);
    $timings = $posted_timings;
}

try {
    $schools_query = "SELECT id, school_name FROM school ORDER BY school_name";
    $schools_result = $conn->query($schools_query)->fetchAll(PDO::FETCH_ASSOC);

    $school_to_check = $hr_user['school_id'];
    $stmt_routes = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM routes r JOIN stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
    $stmt_routes->execute([$school_to_check]);
    $transport_stops = $stmt_routes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Could not fetch schools list or transport stops: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit HR User - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                        <h1 class="h3 mb-0 text-gray-800">Edit HR User</h1>
                        <a href="hr_list.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">HR Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <?php
                                        // Correctly handle the image path for display
                                        $image_path = $hr_user['hr_image'] ?? '';
                                        $full_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $image_path;
                                        if (!empty($image_path) && file_exists($full_path)) {
                                            $display_path = $image_path;
                                        } else {
                                            $display_path = '../../assets/images/unisex.png';
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($display_path); ?>" alt="HR Photo" id="imagePreview" class="img-thumbnail mb-2 mt-3 h-50 w-50" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group mt-3"><label for="hr_image" class="small btn btn-sm btn-primary"><i class="fas fa-upload fa-sm"></i> Change Photo</label><input type="file" class="d-none" id="hr_image" name="hr_image" onchange="document.getElementById('imagePreview').src = window.URL.createObjectURL(this.files[0])"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-6 form-group"><label for="hr_name">HR Name *</label><input type="text" class="form-control" id="hr_name" name="hr_name" value="<?php echo htmlspecialchars($hr_user['hr_name'] ?? ''); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($hr_user['email'] ?? ''); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="phone">Phone *</label><input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($hr_user['phone'] ?? ''); ?>" maxlength="10" required></div>
                                            <div class="col-md-6 form-group"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($hr_user['dob'] ?? ''); ?>"></div>
                                            <div class="col-md-6 form-group"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                                    <option value="Male" <?php echo (($hr_user['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo (($hr_user['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                    <option value="Others" <?php echo (($hr_user['gender'] ?? '') == 'Others') ? 'selected' : ''; ?>>Others</option>
                                                </select></div>
                                            <div class="col-md-6 form-group"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                                                                                                                                                                        foreach ($bg_options as $bg) {
                                                                                                                                                                                                            $selected = (($hr_user['blood_group'] ?? '') == $bg) ? 'selected' : '';
                                                                                                                                                                                                            echo "<option value='{$bg}' {$selected}>{$bg}</option>";
                                                                                                                                                                                                        } ?></select></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($hr_user['address'] ?? ''); ?></textarea></div>
                                <hr>
                                <h6 class="font-weight-bold text-primary">Professional Details</h6>
                                <div class="row mt-3">
                                    <div class="col-md-6 form-group"><label for="school_id">School *</label><select class="form-control" id="school_id" name="school_id" required><?php
                                                                                                                                                                                    foreach ($schools_result as $school) {
                                                                                                                                                                                        $selected = ($school['id'] == ($hr_user['school_id'] ?? '')) ? 'selected' : '';
                                                                                                                                                                                        echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>";
                                                                                                                                                                                    } ?></select></div>
                                    <div class="form-group col-md-6"><label for="batch">Batch *</label><select class="form-control" id="batch" name="batch" required>
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning" <?php echo (($hr_user['batch'] ?? '') == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                                            <option value="Evening" <?php echo (($hr_user['batch'] ?? '') == 'Evening') ? 'selected' : ''; ?>>Evening</option>
                                        </select></div>
                                    <div class="col-md-6 form-group"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($hr_user['qualification'] ?? ''); ?>"></div>
                                    <div class="col-md-6 form-group"><label for="language_known">Languages Known</label><input type="text" class="form-control" id="language_known" name="language_known" value="<?php echo htmlspecialchars($hr_user['language_known'] ?? ''); ?>"></div>
                                    <div class="col-md-6 form-group"><label for="experience">Years of Experience</label><input type="number" class="form-control" id="experience" name="experience" min="0" value="<?php echo htmlspecialchars($hr_user['experience'] ?? '0'); ?>"></div>
                                    <div class="col-md-6 form-group"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($hr_user['salary'] ?? '0.00'); ?>" step="0.01" min="0"></div>
                                </div>
                                
                                <hr>
                                <h6 class="font-weight-bold text-primary">Transportation Details</h6>
                                <div class="row mt-3">
                                    <div class="col-md-6 form-group">
                                        <label for="transport_mode">Mode of Transport</label>
                                        <select class="form-control" id="transport_mode" name="transport_mode">
                                            <option value="Self Transport" <?php echo (isset($hr_user['transport_mode']) && $hr_user['transport_mode'] == 'Self Transport') ? 'selected' : ''; ?>>Self Transport</option>
                                            <option value="School Transport" <?php echo (isset($hr_user['transport_mode']) && $hr_user['transport_mode'] == 'School Transport') ? 'selected' : ''; ?>>School Transport</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group" id="self-transport-div" style="display: <?php echo (isset($hr_user['transport_mode']) && $hr_user['transport_mode'] == 'Self Transport') ? 'block' : 'none'; ?>;">
                                        <label for="self_transport_mode">Self Transport Mode</label>
                                        <select class="form-control" id="self_transport_mode" name="self_transport_mode">
                                            <option value="">-- Select Mode --</option>
                                            <option value="Public Transport" <?php echo (isset($hr_user['self_transport_mode']) && $hr_user['self_transport_mode'] == 'Public Transport') ? 'selected' : ''; ?>>Public Transport</option>
                                            <option value="Walking" <?php echo (isset($hr_user['self_transport_mode']) && $hr_user['self_transport_mode'] == 'Walking') ? 'selected' : ''; ?>>Walking</option>
                                            <option value="Parents" <?php echo (isset($hr_user['self_transport_mode']) && $hr_user['self_transport_mode'] == 'Parents') ? 'selected' : ''; ?>>Parents</option>
                                            <option value="Bike" <?php echo (isset($hr_user['self_transport_mode']) && $hr_user['self_transport_mode'] == 'Bike') ? 'selected' : ''; ?>>Bike</option>
                                            <option value="Car" <?php echo (isset($hr_user['self_transport_mode']) && $hr_user['self_transport_mode'] == 'Car') ? 'selected' : ''; ?>>Car</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group" id="transport-stop-div" style="display: <?php echo (isset($hr_user['transport_mode']) && $hr_user['transport_mode'] == 'School Transport') ? 'block' : 'none'; ?>;">
                                        <label for="stop_id">Assign School Transport Stop</label>
                                        <select class="form-control" id="stop_id" name="stop_id">
                                            <option value="">-- No Stop Selected --</option>
                                            <?php
                                            foreach ($transport_stops as $stop) {
                                                $selected = ($stop['stop_id'] == ($hr_user['stop_id'] ?? '')) ? 'selected' : '';
                                                echo "<option value='{$stop['stop_id']}' {$selected}>" . htmlspecialchars($stop['stop_name']) . " (Route: " . htmlspecialchars($stop['route_name']) . ")</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3" id="vehicle-details-div" style="display: <?php echo (isset($hr_user['self_transport_mode']) && ($hr_user['self_transport_mode'] == 'Bike' || $hr_user['self_transport_mode'] == 'Car')) ? 'flex' : 'none'; ?>;">
                                    <div class="col-md-6 form-group">
                                        <label for="vehicle_number">Vehicle Number *</label>
                                        <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" value="<?php echo htmlspecialchars($hr_user['vehicle_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="license_number">License Number *</label>
                                        <input type="text" class="form-control" id="license_number" name="license_number" value="<?php echo htmlspecialchars($hr_user['license_number'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <hr>
                                <h6 class="font-weight-bold text-primary mb-3">Weekly Timings</h6>
                                <div id="timings-schedule">
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day):
                                        $day_timing = $timings[$day] ?? [];
                                        $is_closed = isset($day_timing['is_closed']) && $day_timing['is_closed'];
                                        // --- UPDATE: Convert 24-hour DB time back to 12-hour and AM/PM for display ---
                                        $opens_at = !empty($day_timing['opens_at']) ? date("h:i", strtotime($day_timing['opens_at'])) : '10:00';
                                        $opens_at_ampm = !empty($day_timing['opens_at']) ? date("A", strtotime($day_timing['opens_at'])) : 'AM';
                                        $closes_at = !empty($day_timing['closes_at']) ? date("h:i", strtotime($day_timing['closes_at'])) : '06:00';
                                        $closes_at_ampm = !empty($day_timing['closes_at']) ? date("A", strtotime($day_timing['closes_at'])) : 'PM';
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
                                                    <input type="text" class="form-control time-input" name="timings[<? echo $day; ?>][opens_at]" value="<?php echo htmlspecialchars($opens_at); ?>" placeholder="HH:MM" <?php if ($is_closed) echo 'disabled'; ?>>
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

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update HR User</button>
                                    <a href="hr_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Re-enables multi-select functionality if any is present, though we removed it from the form
            $('.multi-select').select2();

            // Image Preview
            $('#hr_image').on('change', function(event) {
                if (event.target.files[0]) {
                    $('#imagePreview').attr('src', URL.createObjectURL(event.target.files[0]));
                }
            });

            // Timings schedule logic
            $('.closed-checkbox').on('change', function() {
                var row = $(this).closest('.timing-row');
                var timeInputs = row.find('.time-input, .ampm-select');
                if ($(this).is(':checked')) {
                    timeInputs.prop('disabled', true);
                } else {
                    timeInputs.prop('disabled', false);
                }
            });
            // Trigger the change on page load to set the initial state correctly
            $('.closed-checkbox').trigger('change');

            // JavaScript for dynamic transport fields
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
                    // Clear values when hiding
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
                    // Clear values when hiding
                    selfTransportSelect.value = '';
                    document.getElementById('vehicle_number').value = '';
                    document.getElementById('license_number').value = '';
                } else if (mainMode === 'Self Transport') {
                    selfTransportDiv.style.display = 'block';
                    schoolTransportDiv.style.display = 'none';
                    // Clear values when hiding
                    document.getElementById('stop_id').value = '';
                    toggleSelfTransportFields(); 
                } else {
                    // Default state, hide all
                    selfTransportDiv.style.display = 'none';
                    schoolTransportDiv.style.display = 'none';
                    vehicleDetailsDiv.style.display = 'none';
                    // Clear all values
                    selfTransportSelect.value = '';
                    document.getElementById('stop_id').value = '';
                    document.getElementById('vehicle_number').value = '';
                    document.getElementById('license_number').value = '';
                }
            }
            // Initial check on page load
            toggleTransportFields();

            // Add event listeners for dynamic changes
            transportModeSelect.addEventListener('change', toggleTransportFields);
            selfTransportSelect.addEventListener('change', toggleSelfTransportFields);
        });
    </script>
</body>

</html>