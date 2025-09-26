<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";
include_once "../../includes/log_system.php";

$role = null;
$userId = null;
$enrolling_user_name = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($userId) {
    try {
        $stmt_user_info = $conn->prepare('SELECT email FROM "users" WHERE "id" = ?');
        $stmt_user_info->execute([$userId]);
        $user_info = $stmt_user_info->fetch(PDO::FETCH_ASSOC);
        if ($user_info) {
            $enrolling_user_name = $user_info['email'];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch enrolling user info for logging in hr_enrollment.php: " . $e->getMessage());
        $enrolling_user_name = 'Unknown';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax_request()) {
    $errors = [];
    
    // --- Form data retrieval and validation ---
    $school_id = $_POST['school_id'] ?? null;
    $hr_name = trim($_POST['hr_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    // ... (rest of the validation remains the same)

    if (empty($school_id)) $errors[] = "A school must be selected.";
    if (empty($hr_name)) $errors[] = "HR user's name is required.";
    if (empty($password)) $errors[] = "Password is required.";


    if (!empty($errors)) {
        Response::send(['success' => false, 'message' => implode('<br>', $errors)]);
        exit;
    }

    $image_path_for_db = null;
    if (isset($_FILES['hr_image']) && $_FILES['hr_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['hr_image'];
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/hr/uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'hr_' . uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $image_path_for_db = '/BMC-SMS/pages/hr/uploads/' . $new_filename;
        } else {
            Response::send(['success' => false, 'message' => 'Failed to move uploaded file.']);
            exit;
        }
    }

    try {
        $conn->beginTransaction();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_role = 'hr';

        $stmt_user = $conn->prepare('INSERT INTO "users" ("role", "email", "password") VALUES (?, ?, ?)');
        $stmt_user->execute([$user_role, $email, $hashed_password]);
        $new_user_id = $conn->lastInsertId();
        
        $dob = $_POST['dob'] ?? null;
        $gender = $_POST['gender'] ?? '';
        $blood_group = $_POST['blood_group'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $language_known = trim($_POST['language_known'] ?? '');
        $salary = !empty($_POST['salary']) ? trim($_POST['salary']) : null;
        $experience = !empty($_POST['experience']) ? trim($_POST['experience']) : null;
        $batch = $_POST['batch'] ?? '';
        $date_of_joining = $_POST['date_of_joining'] ?? null;
        $transport_mode = $_POST['transport_mode'] ?? 'Self Transport';
        $self_transport_mode = ($transport_mode === 'Self Transport' && !empty($_POST['self_transport_mode'])) ? $_POST['self_transport_mode'] : null;
        $vehicle_number = ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') ? trim($_POST['vehicle_number'] ?? '') : null;
        $license_number = ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') ? trim($_POST['license_number'] ?? '') : null;
        $stop_id = ($transport_mode === 'School Transport' && !empty($_POST['stop_id'])) ? (int)$_POST['stop_id'] : null;

        // ⭐ **THE FIX**: Removed double quotes from table and column names
        $sql = 'INSERT INTO hr (id, hr_image, hr_name, email, phone, school_id, dob, gender, blood_group, address, salary, qualification, language_known, experience, batch, date_of_joining, transport_mode, self_transport_mode, vehicle_number, license_number, stop_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt_hr = $conn->prepare($sql);
        
        // This check helps in debugging if prepare fails again
        if ($stmt_hr === false) {
            throw new PDOException("Failed to prepare the SQL statement for the 'hr' table.");
        }

        $stmt_hr->execute([$new_user_id, $image_path_for_db, $hr_name, $email, $phone, $school_id, $dob, $gender, $blood_group, $address, $salary, $qualification, $language_known, $experience, $batch, $date_of_joining, $transport_mode, $self_transport_mode, $vehicle_number, $license_number, $stop_id]);

        $timings = $_POST['timings'] ?? [];
        $stmt_timing = $conn->prepare('INSERT INTO hr_timings (hr_id, day_of_week, opens_at, closes_at, is_closed) VALUES (?, ?, ?, ?, ?)');
        foreach ($timings as $day => $details) {
            $is_closed = isset($details['is_closed']) ? 1 : 0;
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

        log_interaction($role, $userId, "ENROLLMENT SUCCESS: Enrolled new HR: {$hr_name} (ID: {$new_user_id})", $enrolling_user_name);
        
        Response::send([
            'success' => true, 
            'message' => 'HR enrolled successfully!',
            'redirect' => '../../pages/hr/hr_list.php'
        ]);

    } catch (PDOException $e) {
        $conn->rollBack();
        $message = "Database error: " . $e->getMessage();
        if ($e->getCode() == 23505) {
            $message = "A user with this email or phone number already exists.";
        }
        Response::send(['success' => false, 'message' => $message]);
    }
    exit;
}

// The rest of the file (HTML and JavaScript) remains the same.
// ... (HTML form code from the previous correct answer)
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

$schools = [];
$stmt_schools = $conn->query('SELECT "id", "school_name" FROM "school" ORDER BY "school_name"');
$schools = $stmt_schools->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enroll HR - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                        <h1 class="h3 mb-0 text-gray-800">Enroll New HR</h1>
                        <a href="../../pages/hr/hr_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    
                    <div id="enrollment-alert-placeholder"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">HR Information</h6>
                        </div>
                        <div class="card-body">
                             <form id="hrEnrollmentForm" method="POST" action="" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="../../assets/images/undraw_profile.svg" alt="HR Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="hr_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label>
                                            <input type="file" class="d-none" id="hr_image" name="hr_image">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="hr_name">HR Name *</label><input type="text" class="form-control" id="hr_name" name="hr_name" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" required></div>
                                            <div class="form-group col-md-6"><label for="password">Password *</label><input type="password" class="form-control" id="password" name="password" required></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-primary">Professional Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6">
                                        <label for="school_id">Assign to School *</label>
                                        <?php if ($role === 'principal'): ?>
                                            <select class="form-control" disabled>
                                                <option value="<?php echo $admin_school_id; ?>" selected><?php echo htmlspecialchars($admin_school_name); ?></option>
                                            </select>
                                            <input type="hidden" name="school_id" value="<?php echo $admin_school_id; ?>">
                                        <?php else: ?>
                                            <select class="form-control" id="school_id" name="school_id" required>
                                                <option value="">-- Select School --</option>
                                                <?php foreach ($schools as $school) {
                                                    echo "<option value='{$school['id']}'>" . htmlspecialchars($school['school_name']) . "</option>";
                                                } ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="batch">Batch *</label>
                                        <select class="form-control" id="batch" name="batch" required>
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning">Morning</option>
                                            <option value="Evening">Evening</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="date_of_joining">Date of Joining</label>
                                        <input type="date" class="form-control" id="date_of_joining" name="date_of_joining">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="qualification">Qualification</label>
                                        <input type="text" class="form-control" id="qualification" name="qualification">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="language_known">Languages Known</label>
                                        <input type="text" class="form-control" id="language_known" name="language_known">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="experience">Experience (yrs)</label>
                                        <input type="number" class="form-control" id="experience" name="experience" min="0" max="50">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="salary">Salary</label>
                                        <input type="number" class="form-control" id="salary" name="salary" step="0.01" min="0">
                                    </div>
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
                                    foreach ($days as $day):
                                    ?>
                                        <div class="form-row align-items-center mb-2 timing-row" data-day="<?php echo $day; ?>">
                                            <div class="col-md-2"><label class="mb-0"><?php echo $day; ?></label></div>
                                            <div class="col-md-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input closed-checkbox" id="closed_<?php echo $day; ?>" name="timings[<?php echo $day; ?>][is_closed]">
                                                    <label class="custom-control-label" for="closed_<?php echo $day; ?>">Closed</label>
                                                </div>
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
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-4"><label for="phone">Phone *</label><input type="tel" class="form-control" id="phone" name="phone" maxlength="10" required></div>
                                    <div class="form-group col-md-4"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob"></div>
                                    <div class="form-group col-md-4"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Others">Others</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required>
                                            <option value="">-- Select Blood Group --</option>
                                            <?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                            foreach ($bg_options as $bg) echo "<option value='{$bg}'>{$bg}</option>";
                                            ?>
                                        </select></div>
                                    <div class="form-group col-md-6"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"></textarea></div>
                                </div>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll HR</button>
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
    
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#hrEnrollmentForm').on('submit', function(e) {
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
                             $('#imagePreview').attr('src', '../../assets/images/undraw_profile.svg');
                             $('.closed-checkbox').trigger('change');
                             toggleTransportFields();
                            if (response.redirect) {
                                setTimeout(function() { window.location.href = response.redirect; }, 1500);
                            }
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        let errorMessage = 'An unexpected error occurred. Please try again.';
                        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                            errorMessage = jqXHR.responseJSON.message;
                        } else {
                            errorMessage = 'A server error occurred. Please check the server logs.';
                        }
                        let alertMessage = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                ${errorMessage}
                                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            </div>`;
                        $('#enrollment-alert-placeholder').html(alertMessage);
                    },
                    complete: function() {
                        submitButton.html(originalButtonText).prop('disabled', false);
                        $('html, body').animate({ scrollTop: 0 }, 'slow');
                    }
                });
            });

            // --- The rest of the Javascript is for UI and remains the same ---
            
            $('.multi-select').select2();
            $('#hr_image').on('change', function(event) {
                if (event.target.files[0]) {
                    $('#imagePreview').attr('src', URL.createObjectURL(event.target.files[0]));
                }
            });
            $('.closed-checkbox').on('change', function() {
                const row = $(this).closest('.timing-row');
                const timeInputs = row.find('.time-input, .ampm-select');
                timeInputs.prop('disabled', $(this).is(':checked'));
            }).trigger('change');
            
            const dateInput = document.getElementById('date_of_joining');
            if (dateInput) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const formattedDate = `${year}-${month}-${day}`;
                dateInput.setAttribute('max', formattedDate);
            }
            
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
                } else if (mainMode === 'Self Transport') {
                    selfTransportDiv.style.display = 'block';
                    schoolTransportDiv.style.display = 'none';
                    document.getElementById('stop_id').value = '';
                    toggleSelfTransportFields(); 
                }
            }

            toggleTransportFields();
            transportModeSelect.addEventListener('change', toggleTransportFields);
            selfTransportSelect.addEventListener('change', toggleSelfTransportFields);
        });
    </script>
</body>

</html>