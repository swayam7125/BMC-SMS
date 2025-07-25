<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: principal_list.php?error=Invalid ID provided");
    exit;
}

$principal_id = intval($_GET['id']);
$errors = [];

//FETCH EXISTING DATA ---
$principal = null;
$timings = [];

// Fetch main principal data
$query_principal = "SELECT * FROM principal WHERE id = ?";
$stmt_principal_fetch = mysqli_prepare($conn, $query_principal);
mysqli_stmt_bind_param($stmt_principal_fetch, "i", $principal_id);
mysqli_stmt_execute($stmt_principal_fetch);
$result_principal = mysqli_stmt_get_result($stmt_principal_fetch);
if (mysqli_num_rows($result_principal) === 0) {
    header("Location: principal_list.php?error=Principal not found");
    exit;
}
$principal = mysqli_fetch_assoc($result_principal);
$original_image_path = $principal['principal_image'];
$original_email = $principal['email'];
$original_batch = $principal['batch'];
mysqli_stmt_close($stmt_principal_fetch);

// Fetch timings data
$query_timings = "SELECT * FROM principal_timings WHERE principal_id = ?";
$stmt_timings_fetch = mysqli_prepare($conn, $query_timings);
mysqli_stmt_bind_param($stmt_timings_fetch, "i", $principal_id);
mysqli_stmt_execute($stmt_timings_fetch);
$result_timings = mysqli_stmt_get_result($stmt_timings_fetch);
while($row = mysqli_fetch_assoc($result_timings)){
    $timings[$row['day_of_week']] = $row;
}
mysqli_stmt_close($stmt_timings_fetch);


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $principal_name = trim($_POST['principal_name']);
    $new_email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $principal_dob = $_POST['principal_dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $salary = trim($_POST['salary']);
    $school_id = intval($_POST['school_id']);
    $new_batch = $_POST['batch'];
    $posted_timings = $_POST['timings'] ?? [];

    $image_path_for_db = $original_image_path;

    //Handle Photo Upload ---
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
                if (!empty($original_image_path) && file_exists($original_image_path)) {
                    @unlink($original_image_path);
                }
                $image_path_for_db = $destination;
            } else { $errors[] = "Failed to move uploaded file."; }
        } else { $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed."; }
    }

    //Validation ---
    if (empty($school_id)) $errors[] = "A school must be selected.";
    if (empty($principal_name)) $errors[] = "Principal name is required.";
    if (empty($new_batch)) $errors[] = "Batch selection is required.";
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($gender)) $errors[] = "Gender is required.";

    if (empty($errors)) {
        mysqli_autocommit($conn, false);
        try {
            // Batch Swap Logic
            $other_principal_id_to_swap = null;
            if ($new_batch !== $original_batch) {
                $swap_check_query = "SELECT id FROM principal WHERE school_id = ? AND batch = ? AND id != ?";
                $stmt_swap_check = mysqli_prepare($conn, $swap_check_query);
                mysqli_stmt_bind_param($stmt_swap_check, "isi", $school_id, $new_batch, $principal_id);
                mysqli_stmt_execute($stmt_swap_check);
                $swap_result = mysqli_stmt_get_result($stmt_swap_check);
                if ($other_principal = mysqli_fetch_assoc($swap_result)) {
                    $other_principal_id_to_swap = $other_principal['id'];
                }
                mysqli_stmt_close($stmt_swap_check);
            }
            if ($other_principal_id_to_swap) {
                $temp_batch_name = 'TEMP_SWAP';
                $stmt_step1 = mysqli_prepare($conn, "UPDATE principal SET batch = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_step1, "si", $temp_batch_name, $other_principal_id_to_swap);
                if (!mysqli_stmt_execute($stmt_step1)) throw new Exception("Swap Step 1 Failed: " . mysqli_stmt_error($stmt_step1));
                mysqli_stmt_close($stmt_step1);
                $stmt_step3 = mysqli_prepare($conn, "UPDATE principal SET batch = ? WHERE id = ? AND batch = ?");
                mysqli_stmt_bind_param($stmt_step3, "sis", $original_batch, $other_principal_id_to_swap, $temp_batch_name);
                if (!mysqli_stmt_execute($stmt_step3)) throw new Exception("Swap Step 3 Failed: " . mysqli_stmt_error($stmt_step3));
                mysqli_stmt_close($stmt_step3);
            }

            if ($new_email !== $original_email) {
                $update_user_query = "UPDATE users SET email=? WHERE id=? AND role='schooladmin'";
                $stmt_user = mysqli_prepare($conn, $update_user_query);
                mysqli_stmt_bind_param($stmt_user, "si", $new_email, $principal_id);
                if (!mysqli_stmt_execute($stmt_user)) throw new Exception("Error updating user record: " . mysqli_stmt_error($stmt_user));
                mysqli_stmt_close($stmt_user);
            }

            $update_principal_query = "UPDATE principal SET principal_image=?, principal_name=?, email=?, phone=?, principal_dob=?, gender=?, blood_group=?, address=?, qualification=?, salary=?, school_id=?, batch=? WHERE id=?";
            $stmt_principal_update = mysqli_prepare($conn, $update_principal_query);
            mysqli_stmt_bind_param($stmt_principal_update, "sssssssssdisi", $image_path_for_db, $principal_name, $new_email, $phone, $principal_dob, $gender, $blood_group, $address, $qualification, $salary, $school_id, $new_batch, $principal_id);
            if (!mysqli_stmt_execute($stmt_principal_update)) throw new Exception("Error updating principal record: " . mysqli_stmt_error($stmt_principal_update));
            mysqli_stmt_close($stmt_principal_update);

            $upsert_timing_query = "INSERT INTO principal_timings (principal_id, day_of_week, opens_at, closes_at, is_closed) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE opens_at = VALUES(opens_at), closes_at = VALUES(closes_at), is_closed = VALUES(is_closed)";
            $stmt_timing_upsert = mysqli_prepare($conn, $upsert_timing_query);
            
            foreach ($posted_timings as $day => $details) {
                $is_closed = isset($details['is_closed']) ? 1 : 0;
                $opens_at = ($is_closed || empty($details['opens_at'])) ? null : $details['opens_at'];
                $closes_at = ($is_closed || empty($details['closes_at'])) ? null : $details['closes_at'];
                
                mysqli_stmt_bind_param($stmt_timing_upsert, "isssi", $principal_id, $day, $opens_at, $closes_at, $is_closed);
                
                if (!mysqli_stmt_execute($stmt_timing_upsert)) {
                    throw new Exception("Failed to save timings for $day: " . mysqli_stmt_error($stmt_timing_upsert));
                }
            }
            mysqli_stmt_close($stmt_timing_upsert);

            mysqli_commit($conn);
            $success_message = "Principal updated successfully.";
            if ($other_principal_id_to_swap) $success_message .= " Batches were swapped.";
            header("Location: principal_list.php?success=" . urlencode($success_message));
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Database error: " . $e->getMessage();
            $principal = $_POST;
            $principal['id'] = $principal_id;
            $principal['principal_image'] = $image_path_for_db;
            $timings = [];
            foreach ($posted_timings as $day => $details) {
                $timings[$day] = $details;
            }
        }
    } else {
        $principal = array_merge($principal, $_POST);
        $timings = [];
         foreach ($posted_timings as $day => $details) {
            $timings[$day] = $details;
        }
    }
}

$schools_query = "SELECT id, school_name FROM school ORDER BY school_name";
$schools_result = mysqli_query($conn, $schools_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Principal - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
            <link rel="stylesheet" href="../../assets/css/sidebar.css">

</head>
<body id="page-top">
    <div id="wrapper">
    <?php include '../../includes/sidebar.php';?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Principal</h1>
                        <a href="principal_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Principal Information</h6></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img src="<?php echo htmlspecialchars(!empty($principal['principal_image']) && file_exists($principal['principal_image']) ? $principal['principal_image'] : '../../assets/img/default-user.jpg'); ?>" alt="Principal Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="principal_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Change Photo</label>
                                            <input type="file" class="d-none" id="principal_image" name="principal_image">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="principal_name">Name *</label><input type="text" class="form-control" id="principal_name" name="principal_name" value="<?php echo htmlspecialchars($principal['principal_name']); ?>" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($principal['email']); ?>" required></div>
                                            <div class="form-group col-md-6"><label for="phone">Phone</label><input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($principal['phone']); ?>" maxlength="10"></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="school_id">School *</label><select class="form-control" id="school_id" name="school_id" required>
                                            <option value="">-- Select School --</option><?php mysqli_data_seek($schools_result, 0);
                                                                                            while ($school = mysqli_fetch_assoc($schools_result)) {
                                                                                                $selected = ($school['id'] == $principal['school_id']) ? 'selected' : '';
                                                                                                echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>";
                                                                                            } ?>
                                        </select></div>
                                    <div class="form-group col-md-6">
                                        <label for="batch">Batch *</label>
                                        <select class="form-control" id="batch" name="batch" required>
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning" <?php echo ($principal['batch'] == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                                            <option value="Evening" <?php echo ($principal['batch'] == 'Evening') ? 'selected' : ''; ?>>Evening</option>
                                        </select>
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
                                        $opens_at = !empty($day_timing['opens_at']) ? date("H:i", strtotime($day_timing['opens_at'])) : '10:00';
                                        $closes_at = !empty($day_timing['closes_at']) ? date("H:i", strtotime($day_timing['closes_at'])) : '20:00';
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
                                    <div class="form-group col-md-6"><label for="principal_dob">Date of Birth</label><input type="date" class="form-control" id="principal_dob" name="principal_dob" value="<?php echo htmlspecialchars($principal['principal_dob']); ?>"></div>
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male" <?php echo ($principal['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($principal['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Others" <?php echo ($principal['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group</label><select class="form-control" id="blood_group" name="blood_group"><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                                                                                                                                                    echo "<option value=''>-- Select Blood Group --</option>";
                                                                                                                                                                                    foreach ($bg_options as $bg) {
                                                                                                                                                                                        $selected = ($principal['blood_group'] == $bg) ? 'selected' : '';
                                                                                                                                                                                        echo "<option value='{$bg}' {$selected}>" . strtoupper($bg) . "</option>";
                                                                                                                                                                                    } ?></select></div>
                                    <div class="form-group col-md-6"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($principal['qualification']); ?>"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($principal['salary']); ?>" step="0.01" min="0"></div>
                                    <div class="form-group col-md-6"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($principal['address']); ?></textarea></div>
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
    
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Image preview script
            document.getElementById('principal_image').addEventListener('change', function(event) {
                if (event.target.files[0]) {
                    document.getElementById('imagePreview').src = URL.createObjectURL(event.target.files[0]);
                }
            });

            // Checkbox logic to disable/enable time inputs
            $('.closed-checkbox').on('change', function() {
                const row = $(this).closest('.timing-row');
                const timeInputs = row.find('input[type="time"]'); // Target by type
                timeInputs.prop('disabled', $(this).is(':checked'));
            });

            // Trigger on page load to set initial state
            $('.closed-checkbox').trigger('change');
        });
    </script>
</body>
</html>
