<?php
include_once "../connect.php";
include_once "../../encryption.php";
include_once "../ajax_helpers.php";
include_once "../log_system.php";

$role = null;
$userId = null;
$enrolling_user_name = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Fetch enrolling user's name for logging purposes
if ($userId) {
    try {
        $stmt_user_info = $conn->prepare('SELECT email FROM "users" WHERE "id" = ?');
        $stmt_user_info->execute([$userId]);
        $user_info = $stmt_user_info->fetch(PDO::FETCH_ASSOC);
        $enrolling_user_name = $user_info ? $user_info['email'] : 'Unknown';
    } catch (PDOException $e) {
        error_log("Failed to fetch enrolling user info: " . $e->getMessage());
        $enrolling_user_name = 'Unknown';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax_request()) {
    $errors = [];

    // --- FORM DATA RETRIEVAL AND VALIDATION ---
    $school_id = $_POST['school_id'] ?? null;
    $principal_name = trim($_POST['principal_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $batch = $_POST['batch'] ?? '';

    if (empty($school_id)) $errors[] = "A school must be selected.";
    if (empty($principal_name)) $errors[] = "Principal's name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (empty($batch)) $errors[] = "Batch is required.";
    
    if (!empty($errors)) {
        Response::send(['success' => false, 'message' => implode('<br>', $errors)]);
        exit;
    }

    $image_path_for_db = null;
    if (isset($_FILES['principal_image']) && $_FILES['principal_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['principal_image'];
        // The physical path needs to be constructed from the server root
        $upload_dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/BMC-SMS/pages/principal/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'principal_' . uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // The DB path should be relative to the web root
            $image_path_for_db = '/BMC-SMS/pages/principal/uploads/' . $new_filename;
        } else {
            Response::send(['success' => false, 'message' => 'Failed to move uploaded file.']);
            exit;
        }
    }

    try {
        $conn->beginTransaction();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_role = 'principal';

        $stmt_user = $conn->prepare('INSERT INTO "users" ("role", "email", "password") VALUES (?, ?, ?)');
        $stmt_user->execute([$user_role, $email, $hashed_password]);
        $new_user_id = $conn->lastInsertId();

        $phone = trim($_POST['phone'] ?? '');
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender = $_POST['gender'] ?? null;
        $blood_group = $_POST['blood_group'] ?? null;
        $address = trim($_POST['address'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $salary = !empty($_POST['salary']) ? trim($_POST['salary']) : null;
        $date_of_joining = !empty($_POST['date_of_joining']) ? $_POST['date_of_joining'] : null;
        
        $transport_mode = $_POST['transport_mode'] ?? 'Self Transport';
        $self_transport_mode = ($transport_mode === 'Self Transport') ? ($_POST['self_transport_mode'] ?? null) : null;
        $vehicle_number = ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') ? trim($_POST['vehicle_number']) : null;
        $license_number = ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') ? trim($_POST['license_number']) : null;
        $stop_id = ($transport_mode === 'School Transport' && !empty($_POST['stop_id'])) ? (int)$_POST['stop_id'] : null;

        $sql = 'INSERT INTO principal (id, principal_image, school_id, principal_name, email, password, phone, dob, gender, blood_group, address, qualification, salary, batch, date_of_joining, transport_mode, self_transport_mode, vehicle_number, license_number, stop_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt_principal = $conn->prepare($sql);
        $stmt_principal->execute([$new_user_id, $image_path_for_db, $school_id, $principal_name, $email, $hashed_password, $phone, $dob, $gender, $blood_group, $address, $qualification, $salary, $batch, $date_of_joining, $transport_mode, $self_transport_mode, $vehicle_number, $license_number, $stop_id]);

        $timings = $_POST['timings'] ?? [];
        $stmt_timing = $conn->prepare('INSERT INTO principal_timings (principal_id, day_of_week, opens_at, closes_at, is_closed) VALUES (?, ?, ?, ?, ?)');
        foreach ($timings as $day => $details) {
            $is_closed = isset($details['is_closed']) ? 1 : 0;
            $opens_at = !$is_closed && !empty($details['opens_at']) && !empty($details['opens_at_ampm']) ? date("H:i:s", strtotime($details['opens_at'] . ' ' . $details['opens_at_ampm'])) : null;
            $closes_at = !$is_closed && !empty($details['closes_at']) && !empty($details['closes_at_ampm']) ? date("H:i:s", strtotime($details['closes_at'] . ' ' . $details['closes_at_ampm'])) : null;
            $stmt_timing->execute([$new_user_id, $day, $opens_at, $closes_at, $is_closed]);
        }

        $conn->commit();
        
        log_interaction($role, $userId, "ENROLLMENT SUCCESS: Enrolled new Principal: {$principal_name} (School ID: {$school_id}, Batch: {$batch})", $enrolling_user_name);
        
        Response::send([
            'success' => true, 
            'message' => 'Principal enrolled successfully!',
            'redirect' => '../../pages/principal/principal_list.php'
        ]);

    } catch (PDOException $e) {
        $conn->rollBack();
        $message = "Database error: " . $e->getMessage();
        if ($e->getCode() == 23505) {
            $message = "A user with this email or a principal for this school and batch already exists.";
        }
        log_interaction($role, $userId, "ENROLLMENT FAILED: " . $message, $enrolling_user_name);
        Response::send(['success' => false, 'message' => $message]);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enroll Principal - School Management System</title>
    <!-- Paths are relative to the root, so they don't need to change -->
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../sidebar.php'; // Adjusted path ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../header.php';  // Adjusted path ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Enroll New Principal</h1>
                        <a href="../../pages/principal/principal_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    
                    <div id="enrollment-alert-placeholder"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Principal Information</h6>
                        </div>
                        <div class="card-body">
                            <form id="principalEnrollmentForm" method="POST" action="" enctype="multipart/form-data">
                                <!-- Form content remains the same -->
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="../../assets/images/unisex.png" alt="Principal Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group"><label for="principal_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label><input type="file" class="d-none" id="principal_image" name="principal_image" accept="image/*"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="principal_name">Principal Name *</label><input type="text" class="form-control" id="principal_name" name="principal_name" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" required></div>
                                            <div class="form-group col-md-6"><label for="password">Password *</label><input type="password" class="form-control" id="password" name="password" required></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-primary">Professional Information</h6>
                                <div class="form-row">
                                    <div class="form-group col-md-6 mt-3">
                                        <label for="batch">Batch *</label>
                                        <select class="form-control" id="batch" name="batch" required>
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning">Morning</option>
                                            <option value="Evening">Evening</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6 mt-3">
                                        <label for="school_id">School *</label>
                                        <select class="form-control" id="school_id" name="school_id" required>
                                            <option value="">-- Select a Batch First --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="date_of_joining">Date of Joining</label>
                                        <input type="date" class="form-control" id="date_of_joining" name="date_of_joining">
                                    </div>
                                    <div class="form-group col-md-6"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" step="0.01" min="0"></div>
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
                                <hr>
                                <h6 class="font-weight-bold text-primary mb-3">Weekly Timings</h6>
                                <div id="timings-schedule">
                                    <?php 
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day) : 
                                    ?>
                                        <div class="form-row align-items-center mb-2 timing-row" data-day="<?php echo $day; ?>">
                                            <div class="col-md-2"><label class="mb-0"><?php echo $day; ?></label></div>
                                            <div class="col-md-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input closed-checkbox" id="closed_<?php echo $day; ?>" name="timings[<?php echo $day; ?>][is_closed]"><label class="custom-control-label" for="closed_<?php echo $day; ?>">Closed</label></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text small">Opens at</span></div>
                                                    <input type="text" class="form-control time-input" name="timings[<?php echo $day; ?>][opens_at]" value="10:00" placeholder="HH:MM">
                                                    <div class="input-group-append">
                                                        <select class="form-control ampm-select" name="timings[<?php echo $day; ?>][opens_at_ampm]">
                                                            <option value="AM" selected>AM</option>
                                                            <option value="PM">PM</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text small">Closes at</span></div>
                                                    <input type="text" class="form-control time-input" name="timings[<?php echo $day; ?>][closes_at]" value="06:00" placeholder="HH:MM">
                                                    <div class="input-group-append">
                                                        <select class="form-control ampm-select" name="timings[<?php echo $day; ?>][closes_at_ampm]">
                                                            <option value="AM">AM</option>
                                                            <option value="PM" selected>PM</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <hr>
                                <h6 class="text-primary">Personal Information</h6>
                                <div class="form-row">
                                    <div class="form-group col-md-6 mt-3"><label for="phone">Phone</label><input type="tel" class="form-control" id="phone" name="phone" maxlength="10"></div>
                                    <div class="form-group col-md-6 mt-3"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Others">Others</option>
                                        </select></div>
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group</label><select class="form-control" id="blood_group" name="blood_group">
                                            <option value="">-- Select Blood Group --</option>
                                            <?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                foreach ($bg_options as $bg) echo "<option value='{$bg}'>{$bg}</option>";
                                            ?>
                                        </select></div>
                                </div>
                                <div class="form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="2"></textarea></div>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll Principal</button>
                                    <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Reset Form</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../footer.php'; // Adjusted path ?>
        </div>
    </div>
    <?php include_once "../logout_modal.php" // Adjusted path ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#principalEnrollmentForm').on('submit', function(e) {
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
                        $('#enrollment-alert-placeholder').html(alertMessage);

                        if (response.success) {
                            form[0].reset();
                            $('#imagePreview').attr('src', '../../assets/images/unisex.png');
                            $('.closed-checkbox').prop('checked', false).trigger('change');
                            toggleTransportFields();
                            $('#school_id').html('<option value="">-- Select a Batch First --</option>'); // Reset school dropdown
                            if (response.redirect) {
                                setTimeout(() => { window.location.href = response.redirect; }, 1500);
                            }
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $('#enrollment-alert-placeholder').html(`<div class="alert alert-danger">An unexpected error occurred: ${errorThrown}</div>`);
                    },
                    complete: function() {
                        submitButton.html(originalButtonText).prop('disabled', false);
                        $('html, body').animate({ scrollTop: 0 }, 'slow');
                    }
                });
            });

            $('#principal_image').on('change', function(event) {
                if (event.target.files && event.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = e => $('#imagePreview').attr('src', e.target.result);
                    reader.readAsDataURL(event.target.files[0]);
                }
            });

            $('.closed-checkbox').on('change', function() {
                const row = $(this).closest('.timing-row');
                row.find('.time-input, .ampm-select').prop('disabled', this.checked);
            }).trigger('change');

            const transportModeSelect = $('#transport_mode');
            const selfTransportSelect = $('#self_transport_mode');
            const schoolTransportDiv = $('#transport-stop-div');
            const selfTransportDiv = $('#self-transport-div');
            const vehicleDetailsDiv = $('#vehicle-details-div');
            const schoolSelect = $('#school_id');
            const batchSelect = $('#batch');

            function fetchAvailableSchools(batch) {
                if (!batch) {
                    schoolSelect.html('<option value="">-- Select a Batch First --</option>');
                    return;
                }
                $.ajax({
                    url: '../get_principal_form_data.php', // Adjusted path
                    type: 'GET',
                    data: { action: 'get_schools', batch: batch },
                    dataType: 'json',
                    success: function(schools) {
                        let options = '<option value="">-- Select School --</option>';
                        if (schools.length > 0) {
                            schools.forEach(school => {
                                options += `<option value="${school.id}">${school.school_name}</option>`;
                            });
                        } else {
                            options = '<option value="" disabled>No available schools for this batch</option>';
                        }
                        schoolSelect.html(options);
                    },
                    error: () => schoolSelect.html('<option value="">-- Error loading schools --</option>')
                });
            }

            function fetchTransportStops(schoolId) {
                if (!schoolId) {
                    $('#stop_id').html('<option value="">-- Select School First --</option>');
                    return;
                }
                $.ajax({
                    url: '../get_principal_form_data.php', // Adjusted path
                    type: 'GET',
                    data: { action: 'get_stops', school_id: schoolId },
                    dataType: 'json',
                    success: function(stops) {
                        let options = '<option value="">-- No Transport --</option>';
                        let currentRoute = '';
                        stops.forEach(stop => {
                            if (stop.route_name !== currentRoute) {
                                if (currentRoute !== '') options += '</optgroup>';
                                currentRoute = stop.route_name;
                                options += `<optgroup label="${currentRoute}">`;
                            }
                            options += `<option value="${stop.stop_id}">${stop.stop_name}</option>`;
                        });
                        if (currentRoute !== '') options += '</optgroup>';
                        $('#stop_id').html(options);
                    },
                    error: () => $('#stop_id').html('<option value="">-- Error loading stops --</option>')
                });
            }

            function toggleSelfTransportFields() {
                const selectedMode = selfTransportSelect.val();
                vehicleDetailsDiv.css('display', (selectedMode === 'Bike' || selectedMode === 'Car') ? 'flex' : 'none');
            }

            function toggleTransportFields() {
                const mainMode = transportModeSelect.val();
                if (mainMode === 'School Transport') {
                    schoolTransportDiv.show();
                    selfTransportDiv.hide();
                    vehicleDetailsDiv.hide();
                    selfTransportSelect.val('');
                    fetchTransportStops(schoolSelect.val());
                } else { // Self Transport
                    selfTransportDiv.show();
                    schoolTransportDiv.hide();
                    $('#stop_id').val('');
                    toggleSelfTransportFields();
                }
            }
            
            batchSelect.on('change', () => fetchAvailableSchools(batchSelect.val()));
            schoolSelect.on('change', () => fetchTransportStops(schoolSelect.val()));
            transportModeSelect.on('change', toggleTransportFields);
            selfTransportSelect.on('change', toggleSelfTransportFields);
           
            toggleTransportFields();
        });
    </script>
</body>
</html>
