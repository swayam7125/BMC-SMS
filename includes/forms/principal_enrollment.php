<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

if (!defined('BASE_URL')) {
    define('BASE_URL', '/BMC-SMS/');
}

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$errors = [];
$batch = $_POST['batch'] ?? '';
$school_id_posted = $_POST['school_id'] ?? '';
$temp_image_path = $_POST['temp_image_path'] ?? null;

$schools = [];
if (!empty($batch)) {
    try {
        $stmt_schools = $conn->prepare('
            SELECT s."id", s."school_name" 
            FROM "school" s 
            WHERE NOT EXISTS (SELECT 1 FROM "principal" p WHERE p."school_id" = s."id" AND p."batch" = ?) 
            ORDER BY s."school_name"
        ');
        $stmt_schools->execute([$batch]);
        $schools = $stmt_schools->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Error fetching schools: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['principal_image']) && $_FILES['principal_image']['error'] === UPLOAD_ERR_OK) {
        $file_info = $_FILES['principal_image'];
        $file_errors = [];
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array(mime_content_type($file_info['tmp_name']), $allowed_mime_types)) {
            $file_errors[] = "Invalid photo type. Only JPG, PNG, and GIF are allowed.";
        }
        if ($file_info['size'] > 5 * 1024 * 1024) { // 5MB limit
            $file_errors[] = "Photo is too large. Maximum size is 5MB.";
        }

        if (empty($file_errors)) {
            $upload_dir_relative = 'uploads/principal_images/';
            $upload_dir_physical = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_URL . $upload_dir_relative;
            if (!is_dir($upload_dir_physical)) {
                mkdir($upload_dir_physical, 0777, true);
            }

            $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
            $new_file_name = 'principal_' . uniqid('', true) . '.' . $file_extension;
            $destination_physical_path = $upload_dir_physical . $new_file_name;

            if (move_uploaded_file($file_info['tmp_name'], $destination_physical_path)) {
                $temp_image_path = BASE_URL . $upload_dir_relative . $new_file_name;
                clearstatcache();
            } else {
                $errors[] = "Failed to move the uploaded photo.";
            }
        } else {
            $errors = array_merge($errors, $file_errors);
        }
    }

    if (isset($_POST['enroll_principal'])) {
        $school_id = $_POST['school_id'];
        $principal_name = trim($_POST['principal_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender = $_POST['gender'];
        $blood_group = $_POST['blood_group'];
        $address = trim($_POST['address']);
        $qualification = trim($_POST['qualification']);
        $salary = trim($_POST['salary']);
        $password = $_POST['password'];
        $timings = $_POST['timings'] ?? [];
        $image_path_for_db = $temp_image_path;

        // NEW: Retrieve date of joining
        $date_of_joining = $_POST['date_of_joining'] ?? null;

        if (empty($school_id)) $errors[] = "A school must be selected.";
        if (empty($principal_name)) $errors[] = "Principal name is required.";
        if (empty($batch)) $errors[] = "Batch selection is required.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
        if (empty($password)) $errors[] = "Password is required.";

        if (empty($errors)) {
            $stmt_check_email = $conn->prepare('SELECT id FROM "users" WHERE "email" = ?');
            $stmt_check_email->execute([$email]);
            if ($stmt_check_email->fetch()) {
                $errors[] = "This email address is already registered. Please use a different one.";
            }
        }

        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_role = 'principal';

                $stmt_user = $conn->prepare('INSERT INTO "users" ("role", "email", "password") VALUES (?, ?, ?)');
                $stmt_user->execute([$user_role, $email, $hashed_password]);
                $new_user_id = $conn->lastInsertId();

                // UPDATED: Added date_of_joining column
                $stmt_principal = $conn->prepare('INSERT INTO "principal" (id, principal_image, school_id, principal_name, email, password, phone, dob, gender, blood_group, address, qualification, salary, batch, date_of_joining) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                // UPDATED: Added date_of_joining value
                $stmt_principal->execute([$new_user_id, $image_path_for_db, $school_id, $principal_name, $email, $hashed_password, $phone, $dob, $gender, $blood_group, $address, $qualification, $salary, $batch, $date_of_joining]);

                $stmt_timing = $conn->prepare('INSERT INTO "principal_timings" (principal_id, day_of_week, opens_at, closes_at, is_closed) VALUES (?, ?, ?, ?, ?)');
                foreach ($timings as $day => $details) {
                    $is_closed_bool = isset($details['is_closed']);
                    $is_closed_for_db = $is_closed_bool ? 1 : 0;
                    
                    $opens_at = null;
                    if (!$is_closed_bool && !empty($details['opens_at']) && !empty($details['opens_at_ampm'])) {
                        $opens_at = $details['opens_at'] . ' ' . $details['opens_at_ampm'];
                    }

                    $closes_at = null;
                    if (!$is_closed_bool && !empty($details['closes_at']) && !empty($details['closes_at_ampm'])) {
                        $closes_at = $details['closes_at'] . ' ' . $details['closes_at_ampm'];
                    }
                    
                    $stmt_timing->execute([$new_user_id, $day, $opens_at, $closes_at, $is_closed_for_db]);
                }

                $conn->commit();
                header("Location: ../../pages/principal/principal_list.php?success=Principal enrolled successfully");
                exit();
            } catch (PDOException $e) {
                $conn->rollBack();
                if ($e->getCode() == 23505) {
                    $errors[] = "A principal with this email already exists.";
                } else {
                    $errors[] = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

if (!is_ajax_request()) {
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
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="icon" type="image/x-icon" href="../../assets/img/favicon.ico">
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
                        <h1 class="h3 mb-0 text-gray-800">Enroll New Principal</h1>
                        <a href="../../pages/principal/principal_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)) : ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error) : ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Principal Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="principalForm">
                                <input type="hidden" name="temp_image_path" value="<?php echo htmlspecialchars($temp_image_path ?? ''); ?>">
                                <input type="hidden" name="image_preview_data" id="imagePreviewData" value="<?php echo htmlspecialchars($_POST['image_preview_data'] ?? ''); ?>">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="<?php echo !empty($_POST['image_preview_data']) ? htmlspecialchars($_POST['image_preview_data']) : '../../assets/images/unisex.png'; ?>" alt="Principal Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group"><label for="principal_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label><input type="file" class="d-none" id="principal_image" name="principal_image" accept="image/*"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="principal_name">Principal Name *</label><input type="text" class="form-control" id="principal_name" name="principal_name" value="<?php echo htmlspecialchars($_POST['principal_name'] ?? ''); ?>" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div>
                                            <div class="form-group col-md-6"><label for="password">Password *</label><input type="password" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" required></div>
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
                                            <option value="">-- Select a Batch First --</option>
                                            <?php
                                            if (!empty($schools)) {
                                                foreach ($schools as $row) {
                                                    $selected = ($school_id_posted == $row['id']) ? 'selected' : '';
                                                    echo "<option value='" . htmlspecialchars($row['id']) . "' $selected>" . htmlspecialchars($row['school_name']) . "</option>";
                                                }
                                            } elseif (!empty($batch)) {
                                                echo "<option value='' disabled>No schools available for this batch</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-12">
                                        <label for="date_of_joining">Date of Joining</label>
                                        <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" value="<?php echo htmlspecialchars($_POST['date_of_joining'] ?? ''); ?>">
                                    </div>
                                </div>
                                <hr>
                                <h6 class="font-weight-bold text-primary mb-3">Weekly Timings</h6>
                                <div id="timings-schedule">
                                    <?php 
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day) : 
                                        $posted_day = $_POST['timings'][$day] ?? [];
                                        $is_closed = isset($posted_day['is_closed']);
                                        $opens_at = $posted_day['opens_at'] ?? '10:00';
                                        $opens_at_ampm = $posted_day['opens_at_ampm'] ?? 'AM';
                                        $closes_at = $posted_day['closes_at'] ?? '06:00';
                                        $closes_at_ampm = $posted_day['closes_at_ampm'] ?? 'PM';
                                    ?>
                                        <div class="form-row align-items-center mb-2 timing-row" data-day="<?php echo $day; ?>">
                                            <div class="col-md-2"><label class="mb-0"><?php echo $day; ?></label></div>
                                            <div class="col-md-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input closed-checkbox" id="closed_<?php echo $day; ?>" name="timings[<?php echo $day; ?>][is_closed]" <?php if ($is_closed) echo 'checked'; ?>><label class="custom-control-label" for="closed_<?php echo $day; ?>">Closed</label></div>
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
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="phone">Phone</label><input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" maxlength="10"></div>
                                    <div class="form-group col-md-6"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Others" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                                        </select></div>
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group</label><select class="form-control" id="blood_group" name="blood_group">
                                            <option value="">-- Select Blood Group --</option><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                                                                foreach ($bg_options as $bg) {
                                                                                                    $selected = (isset($_POST['blood_group']) && $_POST['blood_group'] == $bg) ? 'selected' : '';
                                                                                                    echo "<option value='{$bg}' {$selected}>" . $bg . "</option>";
                                                                                                } ?></select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>"></div>
                                    <div class="form-group col-md-6"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>" step="0.01" min="0"></div>
                                </div>
                                <div class="form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea></div>
                                <div class="form-group mt-4">
                                    <button type="submit" name="enroll_principal" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll Principal</button>
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
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function toggleTimeInputs() {
                document.querySelectorAll('.closed-checkbox').forEach(function(checkbox) {
                    const row = checkbox.closest('.timing-row');
                    const timeInputs = row.querySelectorAll('.time-input, .ampm-select');
                    timeInputs.forEach(function(input) {
                        input.disabled = checkbox.checked;
                    });
                });
            }

            document.querySelectorAll('.closed-checkbox').forEach(function(checkbox) {
                checkbox.addEventListener('change', toggleTimeInputs);
            });

            document.querySelector('button[type="reset"]').addEventListener('click', function() {
                document.getElementById('principalForm').reset();
                document.getElementById('imagePreview').src = '../../assets/images/unisex.png';
                document.getElementById('imagePreviewData').value = '';
                document.querySelector('input[name="temp_image_path"]').value = '';
                setTimeout(toggleTimeInputs, 50);
            });

            // Blur past dates for "Date of Joining"
            const dateInput = document.getElementById('date_of_joining');
            if (dateInput) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const formattedDate = `${year}-${month}-${day}`;
                dateInput.setAttribute('min', formattedDate);
            }

            document.getElementById('principal_image').addEventListener('change', function(event) {
                if (event.target.files && event.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('imagePreview').src = e.target.result;
                        document.getElementById('imagePreviewData').value = e.target.result;
                    }
                    reader.readAsDataURL(event.target.files[0]);
                }
            });

            toggleTimeInputs();
        });
    </script>
</body>
</html>
<?php
}
?>