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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $batch = $_POST['batch']; // ADDED: Retrieve batch

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
    if (empty($blood_group)) $errors[] = "Blood group is required.";
    
    if (empty($errors)) {
        mysqli_autocommit($conn, false);
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 1. Insert into users table FIRST to get the user_id
            $user_role = 'schooladmin'; // Assuming principal role maps to 'schooladmin' in users table
            $insert_user_query = "INSERT INTO users (role, email, password) VALUES (?, ?, ?)";
            $stmt_user = mysqli_prepare($conn, $insert_user_query);
            mysqli_stmt_bind_param($stmt_user, "sss", $user_role, $email, $hashed_password);
            if (!mysqli_stmt_execute($stmt_user)) {
                throw new Exception("User record creation failed: " . mysqli_stmt_error($stmt_user));
            }
            // Get the last inserted ID from the users table
            $new_user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_user);

            // 2. Insert into 'principal' table using the new_user_id as its primary key
            // Note: The 'id' column in the principal table must now be primary key AND foreign key referencing users.id
            $insert_principal_query = "INSERT INTO principal (
                id, principal_image, school_id, principal_name, email, password, phone,
                principal_dob, gender, blood_group, address, qualification, salary, batch
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // One more '?' for ID

            $stmt_principal = mysqli_prepare($conn, $insert_principal_query);
            // Add 'i' for the new_user_id (integer) at the beginning of bind_param types
            mysqli_stmt_bind_param(
                $stmt_principal, "iisssssssssds", // Add 'i' at the start
                $new_user_id, // Pass the ID from the users table
                $image_path_for_db, $school_id, $principal_name, $email, $hashed_password,
                $phone, $principal_dob, $gender, $blood_group, $address, $qualification, $salary, $batch
            );

            if (!mysqli_stmt_execute($stmt_principal)) {
                throw new Exception("Principal record creation failed: " . mysqli_stmt_error($stmt_principal));
            }
            mysqli_stmt_close($stmt_principal);
            
            mysqli_commit($conn);
            header("Location: ../../pages/principal/principal_list.php?success=Principal enrolled successfully");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            if(mysqli_errno($conn) == 1062){
                // This error is likely now from the 'users' table's unique email or phone constraint
                $errors[] = "A principal with this email or phone number already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch schools that don't have a principal yet
$school_query = "SELECT s.id, s.school_name FROM school s LEFT JOIN principal p ON s.id = p.school_id WHERE p.school_id IS NULL ORDER BY s.school_name";
$school_result = mysqli_query($conn, $school_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enroll Principal - School Management System</title>
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
                             <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="../../assets/img/default-user.jpg" alt="Principal Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group"><label for="principal_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label><input type="file" class="d-none" id="principal_image" name="principal_image"></div>
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
                                    <div class="form-group col-md-4"><label for="school_id">Assign to School *</label><select class="form-control" id="school_id" name="school_id" required><option value="">-- Select School --</option><?php while ($school = mysqli_fetch_assoc($school_result)) { $selected = (isset($_POST['school_id']) && $_POST['school_id'] == $school['id']) ? 'selected' : ''; echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>"; } if(mysqli_num_rows($school_result) == 0) { echo "<option disabled>No unassigned schools</option>"; } ?></select></div>
                                    <div class="form-group col-md-4">
                                        <label for="batch">Batch *</label>
                                        <select class="form-control" id="batch" name="batch" required>
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning" <?php echo (isset($_POST['batch']) && $_POST['batch'] == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                                            <option value="Evening" <?php echo (isset($_POST['batch']) && $_POST['batch'] == 'Evening') ? 'selected' : ''; ?>>Evening</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Timings</label>
                                        <div id="timingDetails" class="border p-2 rounded" style="min-height: 40px;"></div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="phone">Phone</label><input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" maxlength="10"></div>
                                    <div class="form-group col-md-6"><label for="principal_dob">Date of Birth</label><input type="date" class="form-control" id="principal_dob" name="principal_dob" value="<?php echo htmlspecialchars($_POST['principal_dob'] ?? ''); ?>"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required><option value="">-- Select Gender --</option><option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option><option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option><option value="Others" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option></select></div>
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><option value="">-- Select Blood Group --</option><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']; foreach ($bg_options as $bg) { $selected = (isset($_POST['blood_group']) && $_POST['blood_group'] == $bg) ? 'selected' : ''; echo "<option value='{$bg}' {$selected}>" . $bg . "</option>"; } ?></select></div>
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
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll Principal</button>
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
        $(document).ready(function() {
            // ADDED: Logic for principal timings
            const timingDetails = $('#timingDetails');
            const batchSelect = $('#batch');

            const timings = {
                principal: {
                    Morning: `
                        <div><strong>Mon-Sat:</strong> 7:00 AM - 2:00 PM</div>
                        <div><strong>Sunday:</strong> 10:00 AM - 12:00 PM</div>
                    `,
                    Evening: `
                        <div><strong>Mon-Sat:</strong> 11:00 AM - 6:00 PM</div>
                        <div><strong>Sunday:</strong> 10:00 AM - 12:00 PM</div>
                    `
                }
            };

            function updateTimings() {
                const selectedBatch = batchSelect.val();
                if (selectedBatch && timings.principal[selectedBatch]) {
                    timingDetails.html(timings.principal[selectedBatch]);
                } else {
                    timingDetails.html('');
                }
            }
            batchSelect.on('change', updateTimings);
            updateTimings(); 
        });
        document.getElementById('principal_image').addEventListener('change', function(event) {
            if (event.target.files[0]) {
                document.getElementById('imagePreview').src = URL.createObjectURL(event.target.files[0]);
            }
        });
    </script>
</body>
</html>