<?php
// Includes for database connection and encryption functions.
include_once "../../includes/connect.php";
include_once "../../encryption.php";

if (!defined('BASE_URL')) {
    define('BASE_URL', '/BMC-SMS/');
}

function getWebAccessibleImagePath($db_image_path)
{
    if (empty($db_image_path)) {
        return null;
    }

    // The path from the database is already the correct full web path.
    $full_web_path = $db_image_path;

    // Construct the physical path to check if the file actually exists.
    $physical_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path;

    if (file_exists($physical_path) && is_file($physical_path)) {
        return htmlspecialchars($full_web_path);
    }

    return null; // Return null if the file doesn't exist.
}

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: principal_list.php?error=Invalid ID provided");
    exit;
}

$principal_id = (int)$_GET['id'];
$errors = [];
$principal = [];
$timings = [];

// Define BASE_WEB_PATH for consistent path handling
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

try {
    // --- FETCH EXISTING PRINCIPAL DATA with transportation details ---
    $sql_principal = "SELECT p.*, st.stop_name, r.route_name, v.vehicle_number as school_vehicle_number FROM principal p
                    LEFT JOIN stops st ON p.stop_id = st.id
                    LEFT JOIN routes r ON st.route_id = r.id
                    LEFT JOIN vehicles v ON r.vehicle_id = v.id
                    WHERE p.id = ?";
    $stmt_principal_fetch = $conn->prepare($sql_principal);
    $stmt_principal_fetch->execute([$principal_id]);
    $principal = $stmt_principal_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$principal) {
        header("Location: principal_list.php?error=Principal not found");
        exit;
    }
    // Store original values before any POST modifications
    $original_email = $principal['email'];
    $original_image_path = $principal['principal_image'] ?? null;
    $original_batch = $principal['batch'];

    // Fetch timings
    $sql_timings = "SELECT * FROM principal_timings WHERE principal_id = ?";
    $stmt_timings_fetch = $conn->prepare($sql_timings);
    $stmt_timings_fetch->execute([$principal_id]);
    while ($row = $stmt_timings_fetch->fetch(PDO::FETCH_ASSOC)) {
        $timings[$row['day_of_week']] = $row;
    }
} catch (PDOException $e) {
    die("Database error while fetching data: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form Data Retrieval
    $principal_name = trim($_POST['principal_name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $gender = $_POST['gender'] ?? '';
    $blood_group = $_POST['blood_group'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $school_id = (int)($_POST['school_id'] ?? 0);
    $new_batch = $_POST['batch'] ?? '';
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
        $new_image_was_uploaded = false; // Flag to check if a new image was uploaded

    // --- Handle Photo Upload ---
    if (isset($_FILES['principal_image']) && $_FILES['principal_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['principal_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_exts)) {
            $target_dir_relative = "pages/principal/uploads/";
            $full_target_dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_URL . $target_dir_relative;
            if (!file_exists($full_target_dir)) mkdir($full_target_dir, 0777, true);
            $new_filename = 'principal_' . $principal_id . '_' . time() . '.' . $file_ext;
            $destination = $full_target_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // If the file moves successfully, update the path and set our flag
                $image_path_for_db = BASE_URL . $target_dir_relative . $new_filename;
                $new_image_was_uploaded = true;
                if (!empty($original_image_path) && file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $original_image_path)) {
                    @unlink(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $original_image_path);
                }
            } else {
                $errors[] = "Failed to move uploaded file.";
            }
        } else {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    }

    // --- Validation ---
    if (empty($principal_name)) $errors[] = "Principal name is required.";
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($new_batch)) $errors[] = "Batch selection is required.";
    
    // NEW: Validation for transport details
    if ($transport_mode === 'Self Transport' && empty($self_transport_mode)) $errors[] = "Please specify the mode of self-transport.";
    if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($vehicle_number)) $errors[] = "Vehicle number is required.";
    if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($license_number)) $errors[] = "License number is required.";

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            if ($new_batch !== $original_batch) {
                $stmt_swap_check = $conn->prepare("SELECT id FROM principal WHERE school_id = ? AND batch = ? AND id != ?");
                $stmt_swap_check->execute([$school_id, $new_batch, $principal_id]);
                if ($other_principal = $stmt_swap_check->fetch(PDO::FETCH_ASSOC)) {
                    $other_principal_id_to_swap = $other_principal['id'];
                    $stmt_swap = $conn->prepare("UPDATE principal SET batch = ? WHERE id = ?");
                    $stmt_swap->execute([$original_batch, $other_principal_id_to_swap]);
                }
            }

            if ($new_email !== $original_email) {
                $stmt_user = $conn->prepare("UPDATE users SET email=? WHERE id=? AND role='principal'");
                $stmt_user->execute([$new_email, $principal_id]);
            }

            // MODIFIED: Added transport-related fields to the UPDATE query
            $update_principal_query = "UPDATE principal SET 
                                      principal_image=?, principal_name=?, email=?, phone=?, dob=?, gender=?, blood_group=?, address=?, 
                                      qualification=?, salary=?, school_id=?, batch=?, 
                                      transport_mode = ?, self_transport_mode = ?, vehicle_number = ?, license_number = ?, stop_id = ? 
                                      WHERE id=?";
            $stmt_principal_update = $conn->prepare($update_principal_query);
            $stmt_principal_update->execute([
                $image_path_for_db, $principal_name, $new_email, $phone, $dob, $gender, $blood_group, $address,
                $qualification, $salary, $school_id, $new_batch,
                $transport_mode, $self_transport_mode, $vehicle_number, $license_number, $stop_id,
                $principal_id
            ]);

            $upsert_timing_query = "INSERT INTO principal_timings (principal_id, day_of_week, opens_at, closes_at, is_closed) 
                                  VALUES (?, ?, ?, ?, ?) ON CONFLICT (principal_id, day_of_week) 
                                  DO UPDATE SET opens_at = EXCLUDED.opens_at, closes_at = EXCLUDED.closes_at, is_closed = EXCLUDED.is_closed";
            $stmt_timing_upsert = $conn->prepare($upsert_timing_query);
            foreach ($days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
                $details = $posted_timings[$day] ?? [];
                $is_closed_bool = isset($details['is_closed']);
                $is_closed_for_db = $is_closed_bool ? 1 : 0;

                $opens_at = null;
                if (!$is_closed_bool && !empty($details['opens_at']) && !empty($details['opens_at_ampm'])) {
                    $opens_at = date("h:i A", strtotime($details['opens_at'] . ' ' . $details['opens_at_ampm']));
                }

                $closes_at = null;
                if (!$is_closed_bool && !empty($details['closes_at']) && !empty($details['closes_at_ampm'])) {
                    $closes_at = date("h:i A", strtotime($details['closes_at'] . ' ' . $details['closes_at_ampm']));
                }

                $stmt_timing_upsert->execute([$principal_id, $day, $opens_at, $closes_at, $is_closed_for_db]);
            }

            $conn->commit();
            
            if ($new_image_was_uploaded && $principal_id == decrypt_id($_COOKIE['encrypted_user_id'])) {
                $encrypted_image_path = encrypt_id($image_path_for_db);
                setcookie('encrypted_profile_image', $encrypted_image_path, time() + 86400, "/");
            }
            header("Location: principal_list.php?success=Principal updated successfully.");
            exit;
        } catch (Exception $e) {
            if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    // Repopulate principal array with POST data on error
    $principal = array_merge($principal, $_POST);
    $timings = $posted_timings;
}

try {
    $schools_query = "SELECT id, school_name FROM school ORDER BY school_name";
    $schools_result = $conn->query($schools_query)->fetchAll(PDO::FETCH_ASSOC);

    $school_to_check = $principal['school_id'];
    $stmt_routes = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM routes r JOIN stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
    $stmt_routes->execute([$school_to_check]);
    $transport_stops = $stmt_routes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Could not fetch schools list or transport stops: " . $e->getMessage());
}

$default_image_path = BASE_URL . 'assets/images/unisex.png';
$current_image_web_path = getWebAccessibleImagePath($principal['principal_image'] ?? '') ?? $default_image_path;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit Principal - School Management System</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Edit Principal</h1>
                        <a href="principal_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Principal Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img src="<?php echo htmlspecialchars($current_image_web_path); ?>" alt="Principal Photo" id="imagePreview" class="img-thumbnail mb-2 mt-3 h-50 w-50" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group mt-3"><label for="principal_image" class="small btn btn-sm btn-primary"><i class="fas fa-upload fa-sm"></i> Change Photo</label><input type="file" class="d-none" id="principal_image" name="principal_image" onchange="document.getElementById('imagePreview').src = window.URL.createObjectURL(this.files[0])"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="principal_name">Name *</label><input type="text" class="form-control" id="principal_name" name="principal_name" value="<?php echo htmlspecialchars($principal['principal_name'] ?? ''); ?>" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($principal['email'] ?? ''); ?>" required></div>
                                            <div class="form-group col-md-6"><label for="phone">Phone</label><input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($principal['phone'] ?? ''); ?>" maxlength="10"></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-primary font-weight-bold">Professional Details</h6>
                                <div class="row">
                                    <div class="form-group col-md-6 mt-3"><label for="school_id">School *</label><select class="form-control" id="school_id" name="school_id" required>
                                            <option value="">-- Select School --</option>
                                            <?php foreach ($schools_result as $school) {
                                                $selected = ($school['id'] == ($principal['school_id'] ?? '')) ? 'selected' : '';
                                                echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>";
                                            } ?>
                                        </select></div>
                                    <div class="form-group col-md-6 mt-3"><label for="batch">Batch *</label><select class="form-control" id="batch" name="batch" required>
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning" <?php echo (($principal['batch'] ?? '') == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                                            <option value="Evening" <?php echo (($principal['batch'] ?? '') == 'Evening') ? 'selected' : ''; ?>>Evening</option>
                                        </select></div>
                                    <div class="form-group col-md-6"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($principal['qualification'] ?? ''); ?>"></div>
                                    <div class="form-group col-md-6"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($principal['salary'] ?? '0.00'); ?>" step="0.01" min="0"></div>
                                </div>
                                <hr>
                                <h6 class="font-weight-bold text-primary">Transportation Details</h6>
                                <div class="row mt-3">
                                    <div class="col-md-6 form-group">
                                        <label for="transport_mode">Mode of Transport</label>
                                        <select class="form-control" id="transport_mode" name="transport_mode">
                                            <option value="Self Transport" <?php echo (isset($principal['transport_mode']) && $principal['transport_mode'] == 'Self Transport') ? 'selected' : ''; ?>>Self Transport</option>
                                            <option value="School Transport" <?php echo (isset($principal['transport_mode']) && $principal['transport_mode'] == 'School Transport') ? 'selected' : ''; ?>>School Transport</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group" id="self-transport-div" style="display: <?php echo (isset($principal['transport_mode']) && $principal['transport_mode'] == 'Self Transport') ? 'block' : 'none'; ?>;">
                                        <label for="self_transport_mode">Self Transport Mode</label>
                                        <select class="form-control" id="self_transport_mode" name="self_transport_mode">
                                            <option value="">-- Select Mode --</option>
                                            <option value="Public Transport" <?php echo (isset($principal['self_transport_mode']) && $principal['self_transport_mode'] == 'Public Transport') ? 'selected' : ''; ?>>Public Transport</option>
                                            <option value="Walking" <?php echo (isset($principal['self_transport_mode']) && $principal['self_transport_mode'] == 'Walking') ? 'selected' : ''; ?>>Walking</option>
                                            <option value="Parents" <?php echo (isset($principal['self_transport_mode']) && $principal['self_transport_mode'] == 'Parents') ? 'selected' : ''; ?>>Parents</option>
                                            <option value="Bike" <?php echo (isset($principal['self_transport_mode']) && $principal['self_transport_mode'] == 'Bike') ? 'selected' : ''; ?>>Bike</option>
                                            <option value="Car" <?php echo (isset($principal['self_transport_mode']) && $principal['self_transport_mode'] == 'Car') ? 'selected' : ''; ?>>Car</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group" id="transport-stop-div" style="display: <?php echo (isset($principal['transport_mode']) && $principal['transport_mode'] == 'School Transport') ? 'block' : 'none'; ?>;">
                                        <label for="stop_id">Assign School Transport Stop</label>
                                        <select class="form-control" id="stop_id" name="stop_id">
                                            <option value="">-- No Stop Selected --</option>
                                            <?php
                                            foreach ($transport_stops as $stop) {
                                                $selected = ($stop['stop_id'] == ($principal['stop_id'] ?? '')) ? 'selected' : '';
                                                echo "<option value='{$stop['stop_id']}' {$selected}>" . htmlspecialchars($stop['stop_name']) . " (Route: " . htmlspecialchars($stop['route_name']) . ")</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3" id="vehicle-details-div" style="display: <?php echo (isset($principal['self_transport_mode']) && ($principal['self_transport_mode'] == 'Bike' || $principal['self_transport_mode'] == 'Car')) ? 'flex' : 'none'; ?>;">
                                    <div class="col-md-6 form-group">
                                        <label for="vehicle_number">Vehicle Number *</label>
                                        <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" value="<?php echo htmlspecialchars($principal['vehicle_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="license_number">License Number *</label>
                                        <input type="text" class="form-control" id="license_number" name="license_number" value="<?php echo htmlspecialchars($principal['license_number'] ?? ''); ?>">
                                    </div>
                                </div>
                                <hr>
                                <h6 class="font-weight-bold text-primary mb-3">Weekly Timings</h6>
                                <div id="timings-schedule">
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day):
                                        $day_timing = $timings[$day] ?? [];
                                        $is_closed = !empty($day_timing['is_closed']);

                                        $opens_at_time = !empty($day_timing['opens_at']) ? date("h:i", strtotime($day_timing['opens_at'])) : '10:00';
                                        $opens_at_ampm = !empty($day_timing['opens_at']) ? date("A", strtotime($day_timing['opens_at'])) : 'AM';
                                        $closes_at_time = !empty($day_timing['closes_at']) ? date("h:i", strtotime($day_timing['closes_at'])) : '06:00';
                                        $closes_at_ampm = !empty($day_timing['closes_at']) ? date("A", strtotime($day_timing['closes_at'])) : 'PM';
                                    ?>
                                        <div class="form-row align-items-center mb-2 timing-row">
                                            <div class="col-md-2"><label class="mb-0"><?php echo $day; ?></label></div>
                                            <div class="col-md-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="closed_<?php echo $day; ?>" name="timings[<?php echo $day; ?>][is_closed]" <?php if ($is_closed) echo 'checked'; ?>><label class="custom-control-label" for="closed_<?php echo $day; ?>">Closed</label></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text small">Opens at</span></div>
                                                    <input type="text" class="form-control time-input" name="timings[<?php echo $day; ?>][opens_at]" value="<?php echo htmlspecialchars($opens_at_time); ?>" placeholder="HH:MM" <?php if ($is_closed) echo 'disabled'; ?>>
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
                                                    <input type="text" class="form-control time-input" name="timings[<?php echo $day; ?>][closes_at]" value="<?php echo htmlspecialchars($closes_at_time); ?>" placeholder="HH:MM" <?php if ($is_closed) echo 'disabled'; ?>>
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
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($principal['dob'] ?? ''); ?>"></div>
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male" <?php echo (($principal['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo (($principal['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Others" <?php echo (($principal['gender'] ?? '') == 'Others') ? 'selected' : ''; ?>>Others</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group</label><select class="form-control" id="blood_group" name="blood_group">
                                            <option value="">-- Select Blood Group --</option>
                                            <?php
                                            $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                            foreach ($bg_options as $bg) {
                                                $selected = (($principal['blood_group'] ?? '') == $bg) ? 'selected' : '';
                                                echo "<option value='{$bg}' {$selected}>" . strtoupper($bg) . "</option>";
                                            }
                                            ?>
                                        </select></div>
                                    <div class="form-group col-md-6"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($principal['qualification'] ?? ''); ?>"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($principal['salary'] ?? '0.00'); ?>" step="0.01" min="0"></div>
                                    <div class="form-group col-md-6"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($principal['address'] ?? ''); ?></textarea></div>
                                </div>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Principal</button>
                                    <a href="principal_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
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
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.timing-row .custom-control-input').forEach(function(checkbox) {
                const row = checkbox.closest('.timing-row');
                const timeInputs = row.querySelectorAll('.time-input, .ampm-select');
                function toggle() {
                    timeInputs.forEach(input => input.disabled = checkbox.checked);
                }
                checkbox.addEventListener('change', toggle);
                toggle();
            });

            // NEW: JavaScript for dynamic transport fields
            const transportModeSelect = document.getElementById('transport_mode');
            const selfTransportSelect = document.getElementById('self_transport_mode');
            const schoolTransportDiv = document.getElementById('transport-stop-div');
            const selfTransportDiv = document.getElementById('self-transport-div');
            const vehicleDetailsDiv = document.getElementById('vehicle-details-div');
            const schoolSelect = document.getElementById('school_id');
            
            function fetchTransportStops(schoolId, selectedStopId) {
                if (!schoolId) {
                    $('#stop_id').html('<option value="">-- No Transport --</option>');
                    return;
                }
                
                $('#stop_id').html('<option value="">-- Loading stops --</option>');
                
                fetch('../teacher/get_transport_stops.php?school_id=' + schoolId)
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
                    selfTransportSelect.value = '';
                    document.getElementById('vehicle_number').value = '';
                    document.getElementById('license_number').value = '';
                    
                    const schoolId = schoolSelect.value;
                    const selectedStopId = <?php echo json_encode($principal['stop_id'] ?? null); ?>;
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
            
            // Re-fetch stops if school changes
            if (schoolSelect) {
                schoolSelect.addEventListener('change', function() {
                    if (transportModeSelect.value === 'School Transport') {
                        fetchTransportStops(this.value, <?php echo json_encode($principal['stop_id'] ?? null); ?>);
                    }
                });
            }
        });
    </script>
</body>

</html>
