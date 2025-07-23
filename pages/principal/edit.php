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

// Fetch current principal data
$query = "SELECT * FROM principal WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $principal_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) === 0) {
    header("Location: principal_list.php?error=Principal not found");
    exit;
}
$principal = mysqli_fetch_assoc($result);
$original_image_path = $principal['principal_image'];
$original_email = $principal['email'];
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Retrieve all form data ---
    $principal_name = trim($_POST['principal_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $principal_dob = $_POST['principal_dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $salary = trim($_POST['salary']);
    $school_id = intval($_POST['school_id']);
    $batch = $_POST['batch']; // Retrieve batch from form

    $image_path_for_db = $original_image_path;

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
                if (!empty($original_image_path) && file_exists($original_image_path)) {
                    @unlink($original_image_path);
                }
                $image_path_for_db = $destination;
            } else {
                $errors[] = "Failed to move uploaded file.";
            }
        } else {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    }

    // --- Validation ---
    if (empty($school_id)) $errors[] = "A school must be selected.";
    if (empty($principal_name)) $errors[] = "Principal name is required.";
    if (empty($batch)) $errors[] = "Batch selection is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($gender)) $errors[] = "Gender is required.";

    if (empty($errors)) {
        mysqli_autocommit($conn, false);
        try {
            // REMOVED: Password logic is gone.
            // UPDATED: Query no longer updates the password. It now includes batch.
            $update_principal_query = "UPDATE principal SET 
                principal_image=?, principal_name=?, email=?, phone=?, 
                principal_dob=?, gender=?, blood_group=?, address=?, 
                qualification=?, salary=?, school_id=?, batch=? 
                WHERE id=?";

            $stmt_principal = mysqli_prepare($conn, $update_principal_query);

            // UPDATED: `bind_param` call simplified without password, includes batch.
            mysqli_stmt_bind_param(
                $stmt_principal,
                "sssssssssdisi",
                $image_path_for_db,
                $principal_name,
                $email,
                $phone,
                $principal_dob,
                $gender,
                $blood_group,
                $address,
                $qualification,
                $salary,
                $school_id,
                $batch,
                $principal_id
            );

            if (!mysqli_stmt_execute($stmt_principal)) {
                throw new Exception("Error updating principal record: " . mysqli_stmt_error($stmt_principal));
            }
            mysqli_stmt_close($stmt_principal);

            // Update the 'users' table ONLY if the email changed.
            if ($email !== $original_email) {
                $update_user_query = "UPDATE users SET email=? WHERE email=?";
                $stmt_user = mysqli_prepare($conn, $update_user_query);
                mysqli_stmt_bind_param($stmt_user, "ss", $email, $original_email);
                if (!mysqli_stmt_execute($stmt_user)) {
                    throw new Exception("Error updating user record: " . mysqli_stmt_error($stmt_user));
                }
                mysqli_stmt_close($stmt_user);
            }

            mysqli_commit($conn);
            header("Location: principal_list.php?id=" . $principal_id . "&success=Principal updated successfully");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            if (mysqli_errno($conn) == 1062) {
                $errors[] = "A principal with this email or phone number already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
            $principal = $_POST;
            $principal['id'] = $principal_id;
        }
    }
}

// Fetch all schools for the dropdown
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
    <!-- Corrected Font Awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include_once '../../includes/sidebar/BMC_sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- top bar code -->
                <?php include_once '../../includes/header.php'; ?>
                <!-- end of top bar code -->
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
                                    <div class="form-group col-md-6"><label for="principal_dob">Date of Birth</label><input type="date" class="form-control" id="principal_dob" name="principal_dob" value="<?php echo htmlspecialchars($principal['principal_dob']); ?>"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="batch">Batch *</label>
                                        <select class="form-control" id="batch" name="batch" required>
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning" <?php echo ($principal['batch'] == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                                            <option value="Evening" <?php echo ($principal['batch'] == 'Evening') ? 'selected' : ''; ?>>Evening</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Timings (Read-only)</label>
                                        <div id="timingDetails" class="border p-2 rounded bg-light" style="min-height: 58px;"></div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male" <?php echo ($principal['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($principal['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Others" <?php echo ($principal['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                                        </select></div>
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group</label><select class="form-control" id="blood_group" name="blood_group"><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                                                                                                                                                    echo "<option value=''>-- Select Blood Group --</option>";
                                                                                                                                                                                    foreach ($bg_options as $bg) {
                                                                                                                                                                                        $selected = ($principal['blood_group'] == $bg) ? 'selected' : '';
                                                                                                                                                                                        echo "<option value='{$bg}' {$selected}>" . strtoupper($bg) . "</option>";
                                                                                                                                                                                    } ?></select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($principal['qualification']); ?>"></div>
                                    <div class="form-group col-md-6"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($principal['salary']); ?>" step="0.01" min="0"></div>
                                </div>
                                <div class="form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($principal['address']); ?></textarea></div>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update School</button>
                                    <a href="principal_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <?php
            include '../../includes/footer.php';
            ?>
            <!-- End of Footer -->
        </div>
    </div>
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        document.getElementById('principal_image').addEventListener('change', function(event) {
            if (event.target.files[0]) {
                document.getElementById('imagePreview').src = URL.createObjectURL(event.target.files[0]);
            }
        });

        $(document).ready(function() {
            const timingDetails = $('#timingDetails');
            const batchSelect = $('#batch');
            const timings = {
                Morning: `<div><strong>Mon-Sat:</strong> 7:00 AM - 2:00 PM</div><div><strong>Sunday:</strong> 10:00 AM - 12:00 PM</div>`,
                Evening: `<div><strong>Mon-Sat:</strong> 11:00 AM - 6:00 PM</div><div><strong>Sunday:</strong> 10:00 AM - 12:00 PM</div>`
            };

            function updateTimings() {
                const selectedBatch = batchSelect.val();
                if (selectedBatch && timings[selectedBatch]) {
                    timingDetails.html(timings[selectedBatch]);
                } else {
                    timingDetails.html('<div class="text-muted">Select a batch to see timings.</div>');
                }
            }
            batchSelect.on('change', updateTimings);
            updateTimings();
        });
    </script>
</body>

</html>