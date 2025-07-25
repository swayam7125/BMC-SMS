<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if not authenticated
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$errors = [];
$batch = $_POST['batch'] ?? '';
$school_id_posted = $_POST['school_id'] ?? '';

// Handle form submission only when the main enroll button is clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_principal'])) {
    // --- Retrieve all form data ---
    $school_id = $_POST['school_id'];
    $principal_name = trim($_POST['principal_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $principal_dob = $_POST['principal_dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $salary = trim($_POST['salary']);
    $password = $_POST['password'];
    $timings = $_POST['timings'] ?? [];

    $image_path_for_db = null;

    // --- Handle Photo Upload ---
    if (isset($_FILES['principal_image']) && $_FILES['principal_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['principal_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_exts)) {
            $target_dir = "../../pages/principal/uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $new_filename = uniqid('principal_', true) . '.' . $file_ext;
            $destination = $target_dir . $new_filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $image_path_for_db = $destination;
            } else { $errors[] = "Failed to move uploaded file."; }
        } else { $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed."; }
    }

    // --- Validation ---
    if (empty($school_id)) $errors[] = "A school must be selected.";
    if (empty($principal_name)) $errors[] = "Principal name is required.";
    if (empty($batch)) $errors[] = "Batch selection is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (empty($gender)) $errors[] = "Gender is required.";
    
    if (empty($errors)) {
        mysqli_autocommit($conn, false);
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Step 1. Insert into users table
            $user_role = 'schooladmin';
            $insert_user_query = "INSERT INTO users (role, email, password) VALUES (?, ?, ?)";
            $stmt_user = mysqli_prepare($conn, $insert_user_query);
            mysqli_stmt_bind_param($stmt_user, "sss", $user_role, $email, $hashed_password);
            if (!mysqli_stmt_execute($stmt_user)) {
                throw new Exception("User record creation failed: " . mysqli_stmt_error($stmt_user));
            }
            $new_user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_user);

            // Step 2. Insert into 'principal' table
            $insert_principal_query = "INSERT INTO principal (id, principal_image, school_id, principal_name, email, password, phone, principal_dob, gender, blood_group, address, qualification, salary, batch) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_principal = mysqli_prepare($conn, $insert_principal_query);
            mysqli_stmt_bind_param($stmt_principal, "issssssssssdss", $new_user_id, $image_path_for_db, $school_id, $principal_name, $email, $hashed_password, $phone, $principal_dob, $gender, $blood_group, $address, $qualification, $salary, $batch);
            if (!mysqli_stmt_execute($stmt_principal)) {
                throw new Exception("Principal record creation failed: " . mysqli_stmt_error($stmt_principal));
            }
            mysqli_stmt_close($stmt_principal);

            // Step 3. Insert into 'principal_timings' table
            $insert_timing_query = "INSERT INTO principal_timings (principal_id, day_of_week, opens_at, closes_at, is_closed) VALUES (?, ?, ?, ?, ?)";
            $stmt_timing = mysqli_prepare($conn, $insert_timing_query);

            foreach ($timings as $day => $details) {
                $is_closed = isset($details['is_closed']) ? 1 : 0;
                $opens_at = ($is_closed || empty($details['opens_at'])) ? null : $details['opens_at'];
                $closes_at = ($is_closed || empty($details['closes_at'])) ? null : $details['closes_at'];
                mysqli_stmt_bind_param($stmt_timing, "isssi", $new_user_id, $day, $opens_at, $closes_at, $is_closed);
                if (!mysqli_stmt_execute($stmt_timing)) {
                    throw new Exception("Failed to save timings for $day: " . mysqli_stmt_error($stmt_timing));
                }
            }
            mysqli_stmt_close($stmt_timing);
            
            mysqli_commit($conn);
            header("Location: ../../pages/principal/principal_list.php?success=Principal enrolled successfully");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            if(mysqli_errno($conn) == 1062){
                $errors[] = "A principal with this email already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

$school_result = [];
if (!empty($batch)) {
    $school_query = "SELECT s.id, s.school_name FROM school s WHERE NOT EXISTS (SELECT 1 FROM principal p WHERE p.school_id = s.id AND p.batch = ?) ORDER BY s.school_name";
    $stmt = mysqli_prepare($conn, $school_query);
    mysqli_stmt_bind_param($stmt, "s", $batch);
    mysqli_stmt_execute($stmt);
    $school_result = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enroll Principal - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom.css">
</head>
<body id="page-top">
    <div id="wrapper">
    <?php include '../sidebar.php';?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '.././header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Enroll New Principal</h1>
                        <a href="../../pages/principal/principal_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Principal Information</h6></div>
                        <div class="card-body">
                             <form method="POST" enctype="multipart/form-data" id="principalForm">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="../../assets/img/default-user.jpg" alt="Principal Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group"><label for="principal_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label><input type="file" class="d-none" id="principal_image" name="principal_image" accept="image/*"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="principal_name">Principal Name *</label><input type="text" class="form-control" id="principal_name" name="principal_name" value="<?php echo htmlspecialchars($_POST['principal_name'] ?? ''); ?>" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div>
                                            <div class="form-group col-md-6"><label for="password">Password *</label><input type="password" class="form-control" id="password" name="password" required></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="batch">Batch *</label>
                                        <select class="form-control" id="batch" name="batch" required onchange="document.getElementById('principalForm').submit()">
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning" <?= ($batch == 'Morning') ? 'selected' : '' ?>>Morning</option>
                                            <option value="Evening" <?= ($batch == 'Evening') ? 'selected' : '' ?>>Evening</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="school_id">School *</label>
                                        <select class="form-control" id="school_id" name="school_id" required>
                                            <option value="">-- Select School --</option>
                                            <?php if (!empty($school_result)) {
                                                while ($row = mysqli_fetch_assoc($school_result)) {
                                                    $selected = ($school_id_posted == $row['id']) ? 'selected' : '';
                                                    echo "<option value='{$row['id']}' $selected>" . htmlspecialchars($row['school_name']) . "</option>";
                                                }
                                            } ?>
                                        </select>
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
                                        // Use 24-hour format for default values to match input type="time"
                                        $opens_at = $posted_day['opens_at'] ?? '10:00';
                                        $closes_at = $posted_day['closes_at'] ?? '20:00';
                                    ?>
                                    <div class="form-row align-items-center mb-2 timing-row" data-day="<?php echo $day; ?>">
                                        <div class="col-md-2"><label class="mb-0"><?php echo $day; ?></label></div>
                                        <div class="col-md-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input closed-checkbox" id="closed_<?php echo $day; ?>" name="timings[<?php echo $day; ?>][is_closed]" <?php if ($is_closed) echo 'checked'; ?>>
                                                <label class="custom-control-label" for="closed_<?php echo $day; ?>">Closed</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <div class="input-group-prepend"><span class="input-group-text small">Opens at</span></div>
                                                <input type="time" class="form-control opens-at" name="timings[<?php echo $day; ?>][opens_at]" value="<?php echo htmlspecialchars($opens_at); ?>" <?php if ($is_closed) echo 'disabled'; ?>>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <div class="input-group-prepend"><span class="input-group-text small">Closes at</span></div>
                                                <input type="time" class="form-control closes-at" name="timings[<?php echo $day; ?>][closes_at]" value="<?php echo htmlspecialchars($closes_at); ?>" <?php if ($is_closed) echo 'disabled'; ?>>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="phone">Phone</label><input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" maxlength="10"></div>
                                    <div class="form-group col-md-6"><label for="principal_dob">Date of Birth</label><input type="date" class="form-control" id="principal_dob" name="principal_dob" value="<?php echo htmlspecialchars($_POST['principal_dob'] ?? ''); ?>"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required><option value="">-- Select Gender --</option><option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option><option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option><option value="Others" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option></select></div>
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group</label><select class="form-control" id="blood_group" name="blood_group"><option value="">-- Select Blood Group --</option><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']; foreach ($bg_options as $bg) { $selected = (isset($_POST['blood_group']) && $_POST['blood_group'] == $bg) ? 'selected' : ''; echo "<option value='{$bg}' {$selected}>" . $bg . "</option>"; } ?></select></div>
                                </div>
                                <div class="form-row">
                                     <div class="form-group col-md-6"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>"></div>
                                     <div class="form-group col-md-6"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>" step="0.01" min="0"></div>
                                </div>
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group mt-4">
                                    <button type="submit" name="enroll_principal" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll Principal</button>
                                    <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Reset Form</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../footer.php'; ?>
        </div>
    </div>
    
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    
    <script>
        $(document).ready(function() {
            // Image Preview
            $('#principal_image').on('change', function(event) {
                if (event.target.files && event.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imagePreview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(event.target.files[0]);
                }
            });

            // Timings schedule logic: Disable time inputs when "Closed" is checked
            $('.closed-checkbox').on('change', function() {
                const row = $(this).closest('.timing-row');
                const timeInputs = row.find('input[type="time"]');
                if ($(this).is(':checked')) {
                    timeInputs.prop('disabled', true);
                } else {
                    timeInputs.prop('disabled', false);
                }
            });

            // Reset form
            $('button[type="reset"]').on('click', function() {
                $('#principalForm')[0].reset();
                $('#imagePreview').attr('src', '../../assets/img/default-user.jpg');
                // Re-evaluate the disabled state of time inputs after reset
                $('.closed-checkbox').trigger('change'); 
            });

            // Trigger the change on page load to set the initial state
            $('.closed-checkbox').trigger('change');
        });
    </script>
</body>
</html>
