<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php'; // Include for fetch_transport_stops

// Check if user is logged in
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if ($role !== 'principal') {
    header("Location: ../../login.php?error=Unauthorized");
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: librarian_list.php?error=Invalid ID provided");
    exit;
}

$librarian_id = (int)$_GET['id'];
$errors = [];
$librarian = [];
$timings = [];

// Define BASE_WEB_PATH for consistent path handling
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

try {
    // --- FETCH EXISTING LIBRARIAN DATA with transportation details ---
    $sql_librarian = "SELECT l.*, st.stop_name, r.route_name, v.vehicle_number as school_vehicle_number FROM librarian l
                    LEFT JOIN stops st ON l.stop_id = st.id
                    LEFT JOIN routes r ON st.route_id = r.id
                    LEFT JOIN vehicles v ON r.vehicle_id = v.id
                    WHERE l.id = ?";
    $stmt_librarian_fetch = $conn->prepare($sql_librarian);
    $stmt_librarian_fetch->execute([$librarian_id]);
    $librarian = $stmt_librarian_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$librarian) {
        header("Location: librarian_list.php?error=Librarian not found");
        exit;
    }
    // Store original values before any POST modifications
    $original_email = $librarian['email'];
    $original_image_path = $librarian['librarian_image'] ?? null;

    // Fetch timings
    $sql_timings = "SELECT * FROM librarian_timings WHERE librarian_id = ?";
    $stmt_timings_fetch = $conn->prepare($sql_timings);
    $stmt_timings_fetch->execute([$librarian_id]);
    while ($row = $stmt_timings_fetch->fetch(PDO::FETCH_ASSOC)) {
        $timings[$row['day_of_week']] = $row;
    }
} catch (PDOException $e) {
    die("Database error while fetching data: " . $e->getMessage());
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form Data Retrieval
    $librarian_name = trim($_POST['librarian_name']);
    $new_email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $salary = trim($_POST['salary']);
    $posted_timings = $_POST['timings'] ?? [];
    $batch = trim($_POST['batch']);
    
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
                $sql_update_users = "UPDATE users SET email = ? WHERE id = ? AND role = 'librarian'";
                $stmt_users = $conn->prepare($sql_update_users);
                $stmt_users->execute([$new_email, $librarian_id]);
            }

            // MODIFIED: Added transport-related fields to the UPDATE query
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
                // New fields start here
                $transport_mode,
                $self_transport_mode,
                $vehicle_number,
                $license_number,
                $stop_id,
                // WHERE clause variable
                $librarian_id
            ]);

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
            header("Location: view.php?id=" . $librarian_id . "&success=1");
            exit;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            if ($e->getCode() == 23505 && strpos($e->getMessage(), 'unique_librarian_school_batch') !== false) {
                $errors[] = "A librarian is already assigned to this school for the selected batch.";
            } else {
                $errors[] = "Database update failed: " . $e->getMessage();
            }
        }
    }
    // Repopulate librarian array with POST data on error
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit Librarian - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
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
                        <h1 class="h3 mb-0 text-gray-800">Edit Librarian</h1>
                        <a href="librarian_list.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img src="<?php echo htmlspecialchars(!empty($librarian['librarian_image'] ?? null) && file_exists($_SERVER['DOCUMENT_ROOT'] . $librarian['librarian_image']) ? $librarian['librarian_image'] : '../../assets/images/unisex.png'); ?>" alt="Librarian Photo" id="imagePreview" class="img-thumbnail mb-2 mt-3 h-50 w-50" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group mt-3"><label for="librarian_image" class="small btn btn-sm btn-primary"><i class="fas fa-upload fa-sm"></i> Change Photo</label><input type="file" class="d-none" id="librarian_image" name="librarian_image" onchange="document.getElementById('imagePreview').src = window.URL.createObjectURL(this.files[0])"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-6 form-group"><label for="librarian_name">Librarian Name *</label><input type="text" class="form-control" id="librarian_name" name="librarian_name" value="<?php echo htmlspecialchars($librarian['librarian_name'] ?? ''); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($librarian['email'] ?? ''); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="phone">Phone *</label><input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($librarian['phone'] ?? ''); ?>" maxlength="10" required></div>
                                            <div class="col-md-6 form-group"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($librarian['dob'] ?? ''); ?>"></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-primary font-weight-bold">Personal & Professional Details</h6>
                                <div class="row mt-3">
                                    <div class="col-md-4 form-group"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="Male" <?php echo (($librarian['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo (($librarian['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Others" <?php echo (($librarian['gender'] ?? '') == 'Others') ? 'selected' : ''; ?>>Others</option>
                                        </select></div>
                                    <div class="col-md-4 form-group"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                                                                                                                                                                foreach ($bg_options as $bg) {
                                                                                                                                                                                                    $selected = (($librarian['blood_group'] ?? '') == $bg) ? 'selected' : '';
                                                                                                                                                                                                    echo "<option value='{$bg}' {$selected}>{$bg}</option>";
                                                                                                                                                                                                } ?></select></div>
                                    <div class="col-md-4 form-group">
                                        <label for="batch">Batch *</label>
                                        <select class="form-control" id="batch" name="batch" required>
                                            <option value="Morning" <?php echo (($librarian['batch'] ?? '') == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                                            <option value="Evening" <?php echo (($librarian['batch'] ?? '') == 'Evening') ? 'selected' : ''; ?>>Evening</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 form-group"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($librarian['qualification'] ?? ''); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($librarian['salary'] ?? '0.00'); ?>" step="0.01" min="0"></div>
                                    <div class="col-md-12 form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($librarian['address'] ?? ''); ?></textarea></div>
                                </div>
                                <hr>
                                <h6 class="font-weight-bold text-primary">Transportation Details</h6>
                                <div class="row mt-3">
                                    <div class="col-md-6 form-group">
                                        <label for="transport_mode">Mode of Transport</label>
                                        <select class="form-control" id="transport_mode" name="transport_mode">
                                            <option value="Self Transport" <?php echo (isset($librarian['transport_mode']) && $librarian['transport_mode'] == 'Self Transport') ? 'selected' : ''; ?>>Self Transport</option>
                                            <option value="School Transport" <?php echo (isset($librarian['transport_mode']) && $librarian['transport_mode'] == 'School Transport') ? 'selected' : ''; ?>>School Transport</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group" id="self-transport-div" style="display: <?php echo (isset($librarian['transport_mode']) && $librarian['transport_mode'] == 'Self Transport') ? 'block' : 'none'; ?>;">
                                        <label for="self_transport_mode">Self Transport Mode</label>
                                        <select class="form-control" id="self_transport_mode" name="self_transport_mode">
                                            <option value="">-- Select Mode --</option>
                                            <option value="Public Transport" <?php echo (isset($librarian['self_transport_mode']) && $librarian['self_transport_mode'] == 'Public Transport') ? 'selected' : ''; ?>>Public Transport</option>
                                            <option value="Walking" <?php echo (isset($librarian['self_transport_mode']) && $librarian['self_transport_mode'] == 'Walking') ? 'selected' : ''; ?>>Walking</option>
                                            <option value="Parents" <?php echo (isset($librarian['self_transport_mode']) && $librarian['self_transport_mode'] == 'Parents') ? 'selected' : ''; ?>>Parents</option>
                                            <option value="Bike" <?php echo (isset($librarian['self_transport_mode']) && $librarian['self_transport_mode'] == 'Bike') ? 'selected' : ''; ?>>Bike</option>
                                            <option value="Car" <?php echo (isset($librarian['self_transport_mode']) && $librarian['self_transport_mode'] == 'Car') ? 'selected' : ''; ?>>Car</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group" id="transport-stop-div" style="display: <?php echo (isset($librarian['transport_mode']) && $librarian['transport_mode'] == 'School Transport') ? 'block' : 'none'; ?>;">
                                        <label for="stop_id">Assign School Transport Stop</label>
                                        <select class="form-control" id="stop_id" name="stop_id">
                                            <option value="">-- No Stop Selected --</option>
                                            <?php
                                            if ($librarian['school_id']) {
                                                $stmt_routes = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM routes r JOIN stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
                                                $stmt_routes->execute([$librarian['school_id']]);
                                                $current_route = '';
                                                while ($row = $stmt_routes->fetch(PDO::FETCH_ASSOC)) {
                                                    if ($row['route_name'] !== $current_route) {
                                                        if ($current_route !== '') echo '</optgroup>';
                                                        $current_route = $row['route_name'];
                                                        echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                    }
                                                    $selected = ($row['stop_id'] == ($librarian['stop_id'] ?? '')) ? 'selected' : '';
                                                    echo "<option value='{$row['stop_id']}' {$selected}>" . htmlspecialchars($row['stop_name']) . " (Route: " . htmlspecialchars($row['route_name']) . ")</option>";
                                                }
                                                if ($current_route !== '') echo '</optgroup>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3" id="vehicle-details-div" style="display: <?php echo (isset($librarian['self_transport_mode']) && ($librarian['self_transport_mode'] == 'Bike' || $librarian['self_transport_mode'] == 'Car')) ? 'flex' : 'none'; ?>;">
                                    <div class="col-md-6 form-group">
                                        <label for="vehicle_number">Vehicle Number *</label>
                                        <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" value="<?php echo htmlspecialchars($librarian['vehicle_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="license_number">License Number *</label>
                                        <input type="text" class="form-control" id="license_number" name="license_number" value="<?php echo htmlspecialchars($librarian['license_number'] ?? ''); ?>">
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
                                        $opens_at = !empty($day_timing['opens_at']) ? date("h:i", strtotime($day_timing['opens_at'])) : '09:00';
                                        $opens_at_ampm = !empty($day_timing['opens_at']) ? date("A", strtotime($day_timing['opens_at'])) : 'AM';
                                        $closes_at = !empty($day_timing['closes_at']) ? date("h:i", strtotime($day_timing['closes_at'])) : '05:00';
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

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Librarian</button>
                                    <a href="librarian_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
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
        $(document).ready(function() {
            $('.closed-checkbox').on('change', function() {
                var row = $(this).closest('.timing-row');
                var timeInputs = row.find('.time-input, .ampm-select');
                if ($(this).is(':checked')) {
                    timeInputs.prop('disabled', true);
                } else {
                    timeInputs.prop('disabled', false);
                }
            });
            // Trigger change on page load to set initial state
            $('.closed-checkbox').trigger('change');

            // NEW: JavaScript for dynamic transport fields
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
                
                fetch('get_transport_stops.php?school_id=' + schoolId)
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
                    
                    const schoolId = <?php echo json_encode($librarian['school_id']); ?>;
                    const selectedStopId = <?php echo json_encode($librarian['stop_id'] ?? null); ?>;
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
        });
    </script>
</body>

</html>