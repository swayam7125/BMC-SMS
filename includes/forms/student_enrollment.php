<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
$userId = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

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

$errors = [];
$schools = [];
$standards = [];

try {
    $stmt_schools = $conn->query('SELECT "id", "school_name" FROM "school" ORDER BY "school_name"');
    $schools = $stmt_schools->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Step 1: Gather all form data ---
    $student_name = trim($_POST['student_name']);
    $rollno = trim($_POST['rollno']);
    $std = trim($_POST['std']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $academic_year = $_POST['academic_year'];
    $school_id = ($role === 'principal') ? $admin_school_id : $_POST['school_id'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $father_name = trim($_POST['father_name']);
    $father_phone = trim($_POST['father_phone']);
    $mother_name = trim($_POST['mother_name']);
    $mother_phone = trim($_POST['mother_phone']);
    $stop_id = !empty($_POST['stop_id']) ? (int)$_POST['stop_id'] : null; // ADDED: Transport Stop ID
    $image_path_for_db = null;

    // --- Step 2: Perform all validations together ---

    // File upload validation
    if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['student_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_ext, $allowed_exts)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    }

    // Form field validation
    if (empty($student_name)) $errors[] = "Student name is required.";
    if (empty($school_id)) $errors[] = "A school must be selected.";
    if (empty($std)) $errors[] = "Standard / Class is required.";
    if (empty($rollno)) $errors[] = "Roll number is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($password)) $errors[] = "Password is required.";

    // Duplicate roll number validation (only if other validations have passed so far)
    if (empty($errors)) {
        try {
            $stmt_check_rollno = $conn->prepare('SELECT COUNT(*) FROM "student" WHERE "school_id" = ? AND "std" = ? AND "rollno" = ?');
            $stmt_check_rollno->execute([$school_id, $std, $rollno]);
            if ($stmt_check_rollno->fetchColumn() > 0) {
                $errors[] = "Roll number '{$rollno}' already exists for this standard and school.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error while checking for roll number: " . $e->getMessage();
        }
    }

    // --- Step 3: If there are NO errors, process file and save to database ---
    if (empty($errors)) {
        // Handle the file move now that we know all data is valid
        if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../../pages/student/uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $new_filename = 'student_' . uniqid('', true) . '.' . strtolower(pathinfo($_FILES['student_image']['name'], PATHINFO_EXTENSION));
            $destination = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES['student_image']['tmp_name'], $destination)) {
                $image_path_for_db = "pages/student/uploads/" . $new_filename;
            } else {
                // This is a final safeguard in case moving the file fails
                $errors[] = "Critical error: Could not move uploaded file. Check directory permissions.";
            }
        }

        // Final check for the critical file move error before committing to DB
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_role = 'student';

                // Insert into 'users' table
                $stmt_user = $conn->prepare('INSERT INTO "users" ("role", "email", "password") VALUES (?, ?, ?)');
                $stmt_user->execute([$user_role, $email, $hashed_password]);
                $new_user_id = $conn->lastInsertId();

                // Insert into 'student' table
                $stmt_student = $conn->prepare('INSERT INTO "student" (id, student_image, student_name, rollno, std, email, password, academic_year, school_id, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, stop_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt_student->execute([$new_user_id, $image_path_for_db, $student_name, $rollno, $std, $email, $hashed_password, $academic_year, $school_id, $dob, $gender, $blood_group, $address, $father_name, $father_phone, $mother_name, $mother_phone, $stop_id]);

                $conn->commit();
                header("Location: ../../pages/student/student_list.php?success=Student enrolled successfully");
                exit();
            } catch (PDOException $e) {
                $conn->rollBack();
                if ($e->getCode() == 23505) { // Unique constraint violation
                    $errors[] = "A student with this email already exists.";
                } else {
                    $errors[] = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Enroll Student - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                        <h1 class="h3 mb-0 text-gray-800">Enroll New Student</h1>
                        <a href="../../pages/student/student_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>Photo Preview</label><br>
                                        <img src="../../assets/images/undraw_profile.svg" alt="Student Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="student_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Photo</label>
                                            <input type="file" class="d-none" id="student_image" name="student_image">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="student_name">Student Name *</label><input type="text" class="form-control" id="student_name" name="student_name" value="<?php echo htmlspecialchars($_POST['student_name'] ?? ''); ?>" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div>
                                            <div class="form-group col-md-6"><label for="password">Password *</label><input type="password" class="form-control" id="password" name="password" required></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-primary">Academic Details</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6">
                                        <label for="school_id">School *</label>
                                        <?php if ($role === 'principal'): ?>
                                            <select class="form-control" name="school_id_disabled" disabled>
                                                <option value="<?php echo $admin_school_id; ?>" selected><?php echo htmlspecialchars($admin_school_name); ?></option>
                                            </select>
                                            <input type="hidden" name="school_id" value="<?php echo $admin_school_id; ?>">
                                        <?php else: ?>
                                            <select class="form-control" id="school_id" name="school_id" required>
                                                <option value="">-- Select School --</option>
                                                <?php
                                                if ($schools) {
                                                    foreach ($schools as $school) {
                                                        $selected = (isset($_POST['school_id']) && $_POST['school_id'] == $school['id']) ? 'selected' : '';
                                                        echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group col-md-6"><label for="academic_year">Academic Year *</label><select class="form-control" id="academic_year" name="academic_year" required>
                                            <option value="">-- Select Year --</option><?php for ($i = -1; $i < 3; $i++) {
                                                                                            $year = date("Y") + $i;
                                                                                            $acad_year = $year . '-' . ($year + 1);
                                                                                            $selected = (isset($_POST['academic_year']) && $_POST['academic_year'] == $acad_year) ? 'selected' : '';
                                                                                            echo "<option value='{$acad_year}' {$selected}>{$acad_year}</option>";
                                                                                        } ?>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="std">Standard / Class *</label>
                                        <select class="form-control" id="std" name="std" required>
                                            <option value="">-- Select a school first --</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4"><label for="rollno">Roll Number *</label><input type="text" class="form-control" id="rollno" name="rollno" value="<?php echo htmlspecialchars($_POST['rollno'] ?? ''); ?>" required></div>
                                    <div class="form-group col-md-4">
                                        <label for="stop_id">Assign Transport Stop (Optional)</label>
                                        <select class="form-control" id="stop_id" name="stop_id">
                                            <option value="">-- No Transport --</option>
                                            <?php
                                            $school_to_check = ($role === 'principal') ? $admin_school_id : ($_POST['school_id'] ?? null);
                                            if ($school_to_check) {
                                                $stmt_routes = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM routes r JOIN stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
                                                $stmt_routes->execute([$school_to_check]);
                                                $current_route = '';
                                                while($row = $stmt_routes->fetch(PDO::FETCH_ASSOC)) {
                                                    if ($row['route_name'] !== $current_route) {
                                                        if ($current_route !== '') echo '</optgroup>';
                                                        $current_route = $row['route_name'];
                                                        echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                    }
                                                    $selected = (isset($_POST['stop_id']) && $_POST['stop_id'] == $row['stop_id']) ? 'selected' : '';
                                                    echo "<option value='" . $row['stop_id'] . "' {$selected}>" . htmlspecialchars($row['stop_name']) . "</option>";
                                                }
                                                if ($current_route !== '') echo '</optgroup>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-primary">Personal Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6"><label for="dob">Date of Birth *</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>" required></div>
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Others" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required>
                                            <option value="">-- Select Blood Group --</option><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                                                                foreach ($bg_options as $bg) {
                                                                                                    $selected = (isset($_POST['blood_group']) && $_POST['blood_group'] == $bg) ? 'selected' : '';
                                                                                                    echo "<option value='{$bg}' {$selected}>" . ($bg) . "</option>";
                                                                                                } ?>
                                        </select></div>
                                    <div class="form-group col-md-6"><label for="address">Residential Address *</label><textarea class="form-control" id="address" name="address" rows="1" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea></div>
                                </div>
                                <hr>
                                <h6 class="text-primary">Parent/Guardian Information</h6>
                                <div class="form-row mt-3">
                                    <div class="form-group col-md-6"><label for="father_name">Father's Name *</label><input type="text" class="form-control" id="father_name" name="father_name" value="<?php echo htmlspecialchars($_POST['father_name'] ?? ''); ?>" required></div>
                                    <div class="form-group col-md-6"><label for="father_phone">Father's Phone *</label><input type="tel" class="form-control" id="father_phone" name="father_phone" value="<?php echo htmlspecialchars($_POST['father_phone'] ?? ''); ?>" maxlength="10" required></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="mother_name">Mother's Name *</label><input type="text" class="form-control" id="mother_name" name="mother_name" value="<?php echo htmlspecialchars($_POST['mother_name'] ?? ''); ?>" required></div>
                                    <div class="form-group col-md-6"><label for="mother_phone">Mother's Phone *</label><input type="tel" class="form-control" id="mother_phone" name="mother_phone" value="<?php echo htmlspecialchars($_POST['mother_phone'] ?? ''); ?>" maxlength="10" required></div>
                                </div>
                                <div class="form-group mt-4">
                                    <button type="submit" name="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll Student</button>
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

    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        const studentImageInput = document.getElementById('student_image');
        if (studentImageInput) {
            studentImageInput.addEventListener('change', function(event) {
                if (event.target.files[0]) {
                    document.getElementById('imagePreview').src = URL.createObjectURL(event.target.files[0]);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const schoolSelect = document.getElementById('school_id');
            const standardSelect = document.getElementById('std');

            function fetchStandards(schoolId) {
                if (!standardSelect) return;
                
                standardSelect.innerHTML = '<option value="">-- Loading standards --</option>';

                if (schoolId) {
                    fetch('../../pages/student/get_standards_by_school_id.php?school_id=' + schoolId)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(standards => {
                            standardSelect.innerHTML = '<option value="">-- Select Standard --</option>';
                            if (standards.length > 0) {
                                standards.forEach(std => {
                                    const option = document.createElement('option');
                                    option.value = std.standard_name;
                                    option.textContent = std.standard_name;
                                    standardSelect.appendChild(option);
                                });
                            } else {
                                standardSelect.innerHTML = '<option value="">No standards found for this school</option>';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching standards:', error);
                            standardSelect.innerHTML = '<option value="">-- Error loading standards --</option>';
                        });
                } else {
                    standardSelect.innerHTML = '<option value="">-- Select a school first --</option>';
                }
            }

            const preselectedSchoolInput = document.querySelector('input[name="school_id"][type="hidden"]');
            if (preselectedSchoolInput && preselectedSchoolInput.value) {
                fetchStandards(preselectedSchoolInput.value);
            }

            if (schoolSelect) {
                schoolSelect.addEventListener('change', function() {
                    fetchStandards(this.value);
                });
            }
        });
    </script>
</body>

</html>