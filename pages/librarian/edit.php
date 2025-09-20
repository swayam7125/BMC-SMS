<?php
/*
 * Filename: edit.php
 * Description: Page for editing librarian details, including personal info, transport, and weekly timings.
 * Author: Your Name
 * Date: 2024-09-18
 */

// --- Includes & Setup ---
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php'; // Include for fetch_transport_stops

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'principal' && $role !== 'hr') {
    header("Location: ../../login.php?error=Unauthorized");
    exit;
}

// --- Input Validation ---
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: librarian_list.php?error=Invalid_ID");
    exit;
}
$librarian_id = (int)$_GET['id'];
$errors = [];
$librarian = [];
$timings = [];

// --- Initial Data Fetch ---
try {
    // Check if the user is authorized to edit this librarian
    $query_access = "SELECT school_id FROM librarian WHERE id = ?";
    $stmt_access = $conn->prepare($query_access);
    $stmt_access->execute([$librarian_id]);
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
        $redirect_url = ($role === 'hr') ? 'hr_librarian_list.php' : 'librarian_list.php';
        header("Location: " . $redirect_url . "?error=Unauthorized access");
        exit;
    }

    $sql_librarian = "SELECT l.*, st.stop_name, r.route_name, v.vehicle_number as school_vehicle_number FROM librarian l
                      LEFT JOIN stops st ON l.stop_id = st.id
                      LEFT JOIN routes r ON st.route_id = r.id
                      LEFT JOIN vehicles v ON r.vehicle_id = v.id
                      WHERE l.id = ?";
    $stmt_librarian_fetch = $conn->prepare($sql_librarian);
    $stmt_librarian_fetch->execute([$librarian_id]);
    $librarian = $stmt_librarian_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$librarian) {
        header("Location: librarian_list.php?error=Not_Found");
        exit;
    }

    // Store original values needed for comparison during update.
    $original_email = $librarian['email'];
    $original_image_path = $librarian['librarian_image'] ?? null;
    $original_batch = $librarian['batch'];

    // Fetch librarian's weekly timings.
    $sql_timings = "SELECT * FROM librarian_timings WHERE librarian_id = ?";
    $stmt_timings_fetch = $conn->prepare($sql_timings);
    $stmt_timings_fetch->execute([$librarian_id]);
    while ($row = $stmt_timings_fetch->fetch(PDO::FETCH_ASSOC)) {
        $timings[$row['day_of_week']] = $row;
    }
} catch (PDOException $e) {
    // A fatal error if initial data cannot be fetched.
    error_log("Database error in edit.php (initial fetch): " . $e->getMessage());
    die("A critical database error occurred while fetching librarian data.");
}

// --- FORM SUBMISSION HANDLING (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve all form data.
    $librarian_name = trim($_POST['librarian_name']);
    $new_email = trim($_POST['email']);
    // ... (rest of the form data retrieval as in your original file) ...
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $salary = trim($_POST['salary']);
    $posted_timings = $_POST['timings'] ?? [];
    $batch = trim($_POST['batch']);
    $transport_mode = $_POST['transport_mode'] ?? 'Self Transport';
    $stop_id = ($transport_mode === 'School Transport' && !empty($_POST['stop_id'])) ? (int)$_POST['stop_id'] : null;
    $self_transport_mode = ($transport_mode === 'Self Transport' && !empty($_POST['self_transport_mode'])) ? $_POST['self_transport_mode'] : null;
    $vehicle_number = ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') ? trim($_POST['vehicle_number'] ?? '') : null;
    $license_number = ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') ? trim($_POST['license_number'] ?? '') : null;

    $image_path_for_db = $original_image_path;
    $new_image_was_uploaded = false;
    
    // --- Handle Photo Upload ---
    if (isset($_FILES['librarian_image']) && $_FILES['librarian_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['librarian_image'];
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/librarian/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'librarian_' . $librarian_id . '_' . uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $image_path_for_db = '/BMC-SMS/pages/librarian/uploads/' . $new_filename;
            $new_image_was_uploaded = true;
            if (!empty($original_image_path) && file_exists($_SERVER['DOCUMENT_ROOT'] . $original_image_path)) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $original_image_path);
            }
        } else {
            $errors[] = "Failed to move uploaded file.";
        }
    }

    if (empty($librarian_name)) $errors[] = "Librarian name is required.";
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($batch)) $errors[] = "Batch selection is required.";
    if ($transport_mode === 'Self Transport' && empty($self_transport_mode)) $errors[] = "Please specify the mode of self-transport.";
    if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($vehicle_number)) $errors[] = "Vehicle number is required.";
    if (($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') && empty($license_number)) $errors[] = "License number is required.";
    

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // If email is changed, update the central 'users' table as well.
            if ($new_email !== $original_email) {
                $stmt_users = $conn->prepare("UPDATE users SET email = ? WHERE id = ? AND role = 'librarian'");
                $stmt_users->execute([$new_email, $librarian_id]);
            }

            $sql_update_librarian = "UPDATE librarian SET 
                                      librarian_image = ?, librarian_name = ?, phone = ?, dob = ?, gender = ?, blood_group = ?, address = ?, 
                                      email = ?, qualification = ?, salary = ?, batch = ?, 
                                      transport_mode = ?, self_transport_mode = ?, vehicle_number = ?, license_number = ?, stop_id = ? 
                                  WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update_librarian);
            $stmt_update->execute([
                $image_path_for_db,
                $librarian_name,
                $phone,
                $dob,
                $gender,
                $blood_group,
                $address,
                $new_email,
                $qualification,
                $salary,
                $batch,
                $transport_mode,
                $self_transport_mode,
                $vehicle_number,
                $license_number,
                $stop_id,
                $librarian_id
            ]);

            // Upsert (INSERT or UPDATE) the weekly timings.
            $sql_upsert_timing = "INSERT INTO librarian_timings (librarian_id, day_of_week, opens_at, closes_at, is_closed)
                                  VALUES (?, ?, ?, ?, ?)
                                  ON CONFLICT (librarian_id, day_of_week)
                                  DO UPDATE SET opens_at = EXCLUDED.opens_at, closes_at = EXCLUDED.closes_at, is_closed = EXCLUDED.is_closed";
            $stmt_timing_upsert = $conn->prepare($sql_upsert_timing);

            foreach ($posted_timings as $day => $details) {
                $is_closed_db = isset($details['is_closed']) ? 1 : 0;

                // --- UPDATE: Convert 12-hour AM/PM time to 24-hour format for DB ---
                $opens_at = null;
                if (!$is_closed_db && !empty($details['opens_at']) && !empty($details['opens_at_ampm'])) {
                    $opens_at = date("H:i:s", strtotime($details['opens_at'] . ' ' . $details['opens_at_ampm']));
                }
                $closes_at = null;
                if (!$is_closed_db && !empty($details['closes_at']) && !empty($details['closes_at_ampm'])) {
                    $closes_at = date("H:i:s", strtotime($details['closes_at'] . ' ' . $details['closes_at_ampm']));
                }
                $stmt_timing_upsert->execute([$librarian_id, $day, $opens_at, $closes_at, $is_closed_db]);
            }
            $conn->commit();
            header("Location: librarian_list.php?success=Librarian updated successfully");
            exit;
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            if ($e->getCode() == 23505) {
                if (strpos($e->getMessage(), 'unique_librarian_school_batch') !== false) {
                    $errors[] = "A librarian is already assigned to this school for the selected batch.";
                } else {
                    $errors[] = "Database update failed: " . $e->getMessage();
                }
            } else {
                $errors[] = "Database update failed: " . $e->getMessage();
            }
        }
    }

    // On error, repopulate form fields with submitted data to avoid data loss.
    $librarian = array_merge($librarian, $_POST);
    $timings = $posted_timings;
}

try {
    $schools_query = "SELECT id, school_name FROM school ORDER BY school_name";
    $schools_result = $conn->query($schools_query)->fetchAll(PDO::FETCH_ASSOC);

    $school_to_check = $librarian['school_id'];
    $stmt_routes = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM routes r JOIN stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
    $stmt_routes->execute([$school_to_check]);
    $transport_stops = $stmt_routes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Could not fetch schools list or transport stops: " . $e->getMessage());
}

$back_to_list_url = 'librarian_list.php';
$form_action_url = 'edit.php?id=' . $librarian_id;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Librarian - <?php echo htmlspecialchars($librarian['librarian_name'] ?? 'Librarian'); ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <style>
        /* Custom style for better image preview */
        #imagePreview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Librarian</h1>
                        <a href="librarian_list.php" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" action="<?php echo $form_action_url; ?>">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <?php
                                        $image_path = $librarian['librarian_image'] ?? '';
                                        $full_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $image_path;
                                        if (!empty($image_path) && file_exists($full_path)) {
                                            $display_path = $image_path;
                                        } else {
                                            $display_path = '../../assets/images/unisex.png';
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($display_path); ?>" alt="Librarian Photo" id="imagePreview" class="img-thumbnail mb-2 mt-3 h-50 w-50" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group mt-3"><label for="librarian_image" class="small btn btn-sm btn-primary"><i class="fas fa-upload fa-sm"></i> Change Photo</label><input type="file" class="d-none" id="librarian_image" name="librarian_image" onchange="document.getElementById('imagePreview').src = window.URL.createObjectURL(this.files[0])"></div>
                                    </div>
                                    <div class="col-lg-9 col-md-8">
                                        <div class="row">
                                            <div class="col-lg-6 form-group"><label for="librarian_name">Librarian Name *</label><input type="text" class="form-control" id="librarian_name" name="librarian_name" value="<?php echo htmlspecialchars($librarian['librarian_name'] ?? ''); ?>" required></div>
                                            <div class="col-lg-6 form-group"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($librarian['email'] ?? ''); ?>" required></div>
                                            <div class="col-lg-6 form-group"><label for="phone">Phone *</label><input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($librarian['phone'] ?? ''); ?>" maxlength="10" required></div>
                                            <div class="col-lg-6 form-group"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($librarian['dob'] ?? ''); ?>"></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>

                                <h6 class="text-primary font-weight-bold">Personal & Professional Details</h6>
                                <div class="row mt-3">
                                    <div class="col-lg-4 col-md-6 form-group"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="Male" <?php if (($librarian['gender'] ?? '') == 'Male') echo 'selected'; ?>>Male</option>
                                            <option value="Female" <?php if (($librarian['gender'] ?? '') == 'Female') echo 'selected'; ?>>Female</option>
                                            <option value="Others" <?php if (($librarian['gender'] ?? '') == 'Others') echo 'selected'; ?>>Others</option>
                                        </select></div>
                                    <div class="col-lg-4 col-md-6 form-group"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                                                                                                                                                                        foreach ($bg_options as $bg) {
                                                                                                                                                                                                            $selected = (($librarian['blood_group'] ?? '') == $bg) ? 'selected' : '';
                                                                                                                                                                                                            echo "<option value='{$bg}' {$selected}>{$bg}</option>";
                                                                                                                                                                                                        } ?></select></div>
                                    <div class="col-lg-4 col-md-6 form-group"><label for="batch">Batch *</label><select class="form-control" id="batch" name="batch" required>
                                            <option value="Morning" <?php if (($librarian['batch'] ?? '') == 'Morning') echo 'selected'; ?>>Morning</option>
                                            <option value="Evening" <?php if (($librarian['batch'] ?? '') == 'Evening') echo 'selected'; ?>>Evening</option>
                                        </select></div>
                                    <div class="col-lg-6 col-md-6 form-group"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($librarian['qualification'] ?? ''); ?>"></div>
                                    <div class="col-lg-6 col-md-6 form-group"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($librarian['salary'] ?? '0.00'); ?>" step="0.01" min="0"></div>
                                    <div class="col-12 form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($librarian['address'] ?? ''); ?></textarea></div>
                                </div>
                                <hr>

                                <h6 class="font-weight-bold text-primary">Transportation Details</h6>
                                <div class="row mt-3">
                                    <div class="col-lg-6 col-md-12 form-group"><label for="transport_mode">Mode of Transport</label><select class="form-control" id="transport_mode" name="transport_mode">
                                            <option value="Self Transport" <?php if (($librarian['transport_mode'] ?? 'Self Transport') == 'Self Transport') echo 'selected'; ?>>Self Transport</option>
                                            <option value="School Transport" <?php if (($librarian['transport_mode'] ?? '') == 'School Transport') echo 'selected'; ?>>School Transport</option>
                                        </select></div>
                                    <div class="col-lg-6 col-md-12 form-group" id="self-transport-div"><label for="self_transport_mode">Self Transport Mode</label><select class="form-control" id="self_transport_mode" name="self_transport_mode">
                                            <option value="">-- Select Mode --</option>
                                            <option value="Public Transport" <?php if (($librarian['self_transport_mode'] ?? '') == 'Public Transport') echo 'selected'; ?>>Public Transport</option>
                                            <option value="Walking" <?php if (($librarian['self_transport_mode'] ?? '') == 'Walking') echo 'selected'; ?>>Walking</option>
                                            <option value="Bike" <?php if (($librarian['self_transport_mode'] ?? '') == 'Bike') echo 'selected'; ?>>Bike</option>
                                            <option value="Car" <?php if (($librarian['self_transport_mode'] ?? '') == 'Car') echo 'selected'; ?>>Car</option>
                                        </select></div>
                                    <div class="col-lg-6 col-md-12 form-group" id="transport-stop-div"><label for="stop_id">Assign School Transport Stop</label><select class="form-control" id="stop_id" name="stop_id"><?php /* Options populated by JS */ ?></select></div>
                                </div>
                                <div class="row mt-3" id="vehicle-details-div">
                                    <div class="col-md-6 form-group"><label for="vehicle_number">Vehicle Number *</label><input type="text" class="form-control" id="vehicle_number" name="vehicle_number" value="<?php echo htmlspecialchars($librarian['vehicle_number'] ?? ''); ?>"></div>
                                    <div class="col-md-6 form-group"><label for="license_number">License Number *</label><input type="text" class="form-control" id="license_number" name="license_number" value="<?php echo htmlspecialchars($librarian['license_number'] ?? ''); ?>"></div>
                                </div>
                                <hr>

                                <h6 class="font-weight-bold text-primary mb-3">Weekly Timings</h6>
                                <div id="timings-schedule">
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day):
                                        $day_timing = $timings[$day] ?? [];
                                        $is_closed = !empty($day_timing['is_closed']);
                                        $opens_at = !empty($day_timing['opens_at']) ? date("h:i", strtotime($day_timing['opens_at'])) : '09:00';
                                        $opens_at_ampm = !empty($day_timing['opens_at']) ? date("A", strtotime($day_timing['opens_at'])) : 'AM';
                                        $closes_at = !empty($day_timing['closes_at']) ? date("h:i", strtotime($day_timing['closes_at'])) : '05:00';
                                        $closes_at_ampm = !empty($day_timing['closes_at']) ? date("A", strtotime($day_timing['closes_at'])) : 'PM';
                                    ?>
                                        <div class="form-row align-items-center mb-3 timing-row" data-day="<?php echo $day; ?>">
                                            <div class="col-lg-2 col-md-12 mb-2 mb-lg-0"><label class="mb-0 font-weight-bold"><?php echo $day; ?></label></div>
                                            <div class="col-lg-2 col-md-12 mb-2 mb-lg-0">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input closed-checkbox" id="closed_<?php echo $day; ?>" name="timings[<?php echo $day; ?>][is_closed]" <?php if ($is_closed) echo 'checked'; ?>><label class="custom-control-label" for="closed_<?php echo $day; ?>">Closed</label></div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text small">Opens</span></div><input type="text" class="form-control time-input" name="timings[<?php echo $day; ?>][opens_at]" value="<?php echo $opens_at; ?>" placeholder="HH:MM">
                                                    <div class="input-group-append"><select class="form-control ampm-select" name="timings[<?php echo $day; ?>][opens_at_ampm]">
                                                            <option value="AM" <?php if ($opens_at_ampm == 'AM') echo 'selected'; ?>>AM</option>
                                                            <option value="PM" <?php if ($opens_at_ampm == 'PM') echo 'selected'; ?>>PM</option>
                                                        </select></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text small">Closes</span></div><input type="text" class="form-control time-input" name="timings[<?php echo $day; ?>][closes_at]" value="<?php echo $closes_at; ?>" placeholder="HH:MM">
                                                    <div class="input-group-append"><select class="form-control ampm-select" name="timings[<?php echo $day; ?>][closes_at_ampm]">
                                                            <option value="AM" <?php if ($closes_at_ampm == 'AM') echo 'selected'; ?>>AM</option>
                                                            <option value="PM" <?php if ($closes_at_ampm == 'PM') echo 'selected'; ?>>PM</option>
                                                        </select></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="form-group mt-4"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Librarian</button><a href="librarian_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a></div>
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
        $(document).ready(function() {
            // --- Logic for Timings Section ---
            // Handles enabling/disabling time inputs when "Closed" is checked.
            $('.closed-checkbox').on('change', function() {
                var row = $(this).closest('.timing-row');
                var timeInputs = row.find('.time-input, .ampm-select');
                timeInputs.prop('disabled', $(this).is(':checked'));
            }).trigger('change'); // Trigger on page load to set initial state.

            // --- Logic for Dynamic Transportation Fields ---
            const transportModeSelect = document.getElementById('transport_mode');
            const selfTransportSelect = document.getElementById('self_transport_mode');
            const schoolTransportDiv = document.getElementById('transport-stop-div');
            const selfTransportDiv = document.getElementById('self-transport-div');
            const vehicleDetailsDiv = document.getElementById('vehicle-details-div');

            // Fetches school transport stops via AJAX for the selected school.
            function fetchTransportStops(schoolId, selectedStopId) {
                if (!schoolId) {
                    $('#stop_id').html('<option value="">-- No school assigned --</option>');
                    return;
                }
                
                $('#stop_id').html('<option value="">-- Loading stops --</option>');
                
                fetch('../teacher/get_transport_stops.php?school_id=' + schoolId)
                    .then(response => response.json())
                    .then(data => {
                        let options = '<option value="">-- No Stop Selected --</option>';
                        let currentRoute = '';
                        if (data && data.length > 0) {
                            data.forEach(stop => {
                                if (stop.route_name !== currentRoute) {
                                    if (currentRoute !== '') options += '</optgroup>';
                                    currentRoute = stop.route_name;
                                    options += `<optgroup label="${escapeHtml(currentRoute)}">`;
                                }
                                const isSelected = stop.stop_id == selectedStopId ? 'selected' : '';
                                options += `<option value="${stop.stop_id}" ${isSelected}>${escapeHtml(stop.stop_name)}</option>`;
                            });
                            if (currentRoute !== '') options += '</optgroup>';
                        }
                        $('#stop_id').html(options);
                    })
                    .catch(error => {
                        console.error('Error fetching transport stops:', error);
                        $('#stop_id').html('<option value="">-- Error loading stops --</option>');
                    });
            }

            // Shows/hides vehicle number and license fields.
            function toggleSelfTransportFields() {
                const selectedMode = selfTransportSelect.value;
                vehicleDetailsDiv.style.display = (selectedMode === 'Bike' || selectedMode === 'Car') ? 'flex' : 'none';
            }

            // Main function to orchestrate the visibility of transport sections.
            function toggleTransportSections() {
                const mainMode = transportModeSelect.value;
                schoolTransportDiv.style.display = (mainMode === 'School Transport') ? 'block' : 'none';
                selfTransportDiv.style.display = (mainMode === 'Self Transport') ? 'block' : 'none';

                if (mainMode === 'School Transport') {
                    toggleSelfTransportFields(); // Hide bike/car fields
                    const schoolId = <?php echo json_encode($librarian['school_id']); ?>;
                    const selectedStopId = <?php echo json_encode($librarian['stop_id'] ?? null); ?>;
                    fetchTransportStops(schoolId, selectedStopId);
                } else { // Self Transport
                    toggleSelfTransportFields();
                }
            }

            // Utility function to prevent XSS.
            function escapeHtml(text) {
                var map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, function(m) {
                    return map[m];
                });
            }

            // --- Initial Calls & Event Listeners ---
            toggleTransportSections(); // Set initial state on page load.
            transportModeSelect.addEventListener('change', toggleTransportSections);
            selfTransportSelect.addEventListener('change', toggleSelfTransportFields);
        });
    </script>
</body>

</html>
<?php
$conn = null;
?>