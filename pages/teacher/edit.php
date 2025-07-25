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
    header("Location: teacher_list.php?error=Invalid ID provided");
    exit;
}

$teacher_id = intval($_GET['id']);
$errors = [];

// --- CHANGE 1: FETCH TEACHER AND TIMINGS DATA ---
// Fetch current teacher data
$query_teacher = "SELECT * FROM teacher WHERE id = ?";
$stmt_teacher_fetch = mysqli_prepare($conn, $query_teacher);
mysqli_stmt_bind_param($stmt_teacher_fetch, "i", $teacher_id);
mysqli_stmt_execute($stmt_teacher_fetch);
$result_teacher = mysqli_stmt_get_result($stmt_teacher_fetch);

if (mysqli_num_rows($result_teacher) === 0) {
    header("Location: teacher_list.php?error=Teacher not found");
    exit;
}
$teacher = mysqli_fetch_assoc($result_teacher);
$original_email = $teacher['email'];
$original_image_path = $teacher['teacher_image'];
mysqli_stmt_close($stmt_teacher_fetch);

// Fetch timings data for the teacher
$timings = [];
$query_timings = "SELECT * FROM teacher_timings WHERE teacher_id = ?";
$stmt_timings_fetch = mysqli_prepare($conn, $query_timings);
mysqli_stmt_bind_param($stmt_timings_fetch, "i", $teacher_id);
mysqli_stmt_execute($stmt_timings_fetch);
$result_timings = mysqli_stmt_get_result($stmt_timings_fetch);
while($row = mysqli_fetch_assoc($result_timings)){
    $timings[$row['day_of_week']] = $row;
}
mysqli_stmt_close($stmt_timings_fetch);
// --- END OF CHANGE 1 ---

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Form Data Retrieval ---
    $teacher_name = trim($_POST['teacher_name']);
    $new_email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $school_id = intval($_POST['school_id']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $subject = trim($_POST['subject']);
    $language_known = trim($_POST['language_known']);
    $salary = trim($_POST['salary']);
    $std = implode(',', (isset($_POST['std']) ? $_POST['std'] : []));
    $experience = trim($_POST['experience']);
    $batch = $_POST['batch'];
    $posted_timings = $_POST['timings'] ?? []; // Get timings from form

    $class_teacher = isset($_POST['class_teacher']) ? 1 : 0;
    $class_teacher_std = $class_teacher ? ($_POST['class_teacher_std'] ?? null) : null;

    $image_path_for_db = $original_image_path;

    // --- Validation ---
    if (empty($teacher_name)) $errors[] = "Teacher name is required.";
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($batch)) $errors[] = "Batch selection is required.";
    if ($class_teacher && empty($class_teacher_std)) {
        $errors[] = "Please select a standard for the class teacher.";
    }

    // --- Handle Photo Upload ---
    if (isset($_FILES['teacher_image']) && $_FILES['teacher_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['teacher_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_exts)) {
            $target_dir = "../../pages/teacher/uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $new_filename = uniqid('teacher_', true) . '.' . $file_ext;
            $destination = $target_dir . $new_filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $image_path_for_db = $destination;
                if (!empty($original_image_path) && file_exists($original_image_path)) {
                    @unlink($original_image_path);
                }
            } else { $errors[] = "Failed to move uploaded file."; }
        } else { $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed."; }
    }

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            // Update users table if email changes
            if ($new_email !== $original_email) {
                $update_users = "UPDATE users SET email = ? WHERE id = ? AND role = 'teacher'";
                $stmt_users = mysqli_prepare($conn, $update_users);
                mysqli_stmt_bind_param($stmt_users, "si", $new_email, $teacher_id);
                if (!mysqli_stmt_execute($stmt_users)) {
                    throw new Exception("Failed to update users table: " . mysqli_stmt_error($stmt_users));
                }
                mysqli_stmt_close($stmt_users);
            }

            // Update teacher table
            $update_teacher = "UPDATE teacher SET teacher_image = ?, teacher_name = ?, phone = ?, school_id = ?, dob = ?, gender = ?, blood_group = ?, address = ?, email = ?, qualification = ?, subject = ?, language_known = ?, salary = ?, std = ?, experience = ?, batch = ?, class_teacher = ?, class_teacher_std = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $update_teacher);
            mysqli_stmt_bind_param($stmt_update, "sssissssssssssisisi", $image_path_for_db, $teacher_name, $phone, $school_id, $dob, $gender, $blood_group, $address, $new_email, $qualification, $subject, $language_known, $salary, $std, $experience, $batch, $class_teacher, $class_teacher_std, $teacher_id);
            if (!mysqli_stmt_execute($stmt_update)) throw new Exception("Failed to update teacher table: " . mysqli_stmt_error($stmt_update));
            mysqli_stmt_close($stmt_update);

            // --- CHANGE 2: UPSERT (UPDATE OR INSERT) THE TIMINGS ---
            $upsert_timing_query = "INSERT INTO teacher_timings (teacher_id, day_of_week, opens_at, closes_at, is_closed) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE opens_at = VALUES(opens_at), closes_at = VALUES(closes_at), is_closed = VALUES(is_closed)";
            $stmt_timing_upsert = mysqli_prepare($conn, $upsert_timing_query);
            foreach ($posted_timings as $day => $details) {
                $is_closed = isset($details['is_closed']) ? 1 : 0;
                $opens_at = ($is_closed || empty($details['opens_at'])) ? null : $details['opens_at'];
                $closes_at = ($is_closed || empty($details['closes_at'])) ? null : $details['closes_at'];
                mysqli_stmt_bind_param($stmt_timing_upsert, "isssi", $teacher_id, $day, $opens_at, $closes_at, $is_closed);
                if (!mysqli_stmt_execute($stmt_timing_upsert)) {
                    throw new Exception("Failed to save timings for $day: " . mysqli_stmt_error($stmt_timing_upsert));
                }
            }
            mysqli_stmt_close($stmt_timing_upsert);
            // --- END OF CHANGE 2 ---

            mysqli_commit($conn);
            header("Location: teacher_list.php?success=Teacher updated successfully");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Database update failed: " . $e->getMessage();
        }
    }
    // Repopulate data on error
    $teacher = array_merge($teacher, $_POST);
    $timings = $posted_timings;
}

$schools_query = "SELECT id, school_name FROM school ORDER BY school_name";
$schools_result = mysqli_query($conn, $schools_query);
$selected_stds = explode(',', $teacher['std']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Teacher - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body id="page-top">
    <div id="wrapper">
    <?php include '../../includes/sidebar.php';?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Teacher</h1>
                        <a href="teacher_list.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Teacher Information</h6></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img src="<?php echo htmlspecialchars(!empty($teacher['teacher_image']) && file_exists($teacher['teacher_image']) ? $teacher['teacher_image'] : '../../assets/img/default-user.jpg'); ?>" alt="Teacher Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group"><label for="teacher_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Change Photo</label><input type="file" class="d-none" id="teacher_image" name="teacher_image"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-6 form-group"><label for="teacher_name">Teacher Name *</label><input type="text" class="form-control" id="teacher_name" name="teacher_name" value="<?php echo htmlspecialchars($teacher['teacher_name']); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="phone">Phone *</label><input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($teacher['phone']); ?>" maxlength="10" required></div>
                                            <div class="col-md-6 form-group"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($teacher['dob']); ?>"></div>
                                            <div class="col-md-6 form-group"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required><option value="Male" <?php echo ($teacher['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option><option value="Female" <?php echo ($teacher['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option><option value="Others" <?php echo ($teacher['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option></select></div>
                                            <div class="col-md-6 form-group"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']; foreach ($bg_options as $bg) { $selected = ($teacher['blood_group'] == $bg) ? 'selected' : ''; echo "<option value='{$bg}' {$selected}>{$bg}</option>"; } ?></select></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($teacher['address']); ?></textarea></div>
                                <hr>
                                <h6 class="text-primary font-weight-bold">Professional Details</h6>
                                <div class="row mt-3">
                                    <div class="col-md-6 form-group"><label for="school_id">School *</label><select class="form-control" id="school_id" name="school_id" required><?php mysqli_data_seek($schools_result, 0); while ($school = mysqli_fetch_assoc($schools_result)) { $selected = ($school['id'] == $teacher['school_id']) ? 'selected' : ''; echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>"; } ?></select></div>
                                    <div class="form-group col-md-6"><label for="batch">Batch *</label><select class="form-control" id="batch" name="batch" required><option value="">-- Select Batch --</option><option value="Morning" <?php echo ($teacher['batch'] == 'Morning') ? 'selected' : ''; ?>>Morning</option><option value="Evening" <?php echo ($teacher['batch'] == 'Evening') ? 'selected' : ''; ?>>Evening</option></select></div>
                                    <div class="col-md-4 form-group"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($teacher['qualification']); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="subject">Subject Specialization</label><input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($teacher['subject']); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="language_known">Languages Known</label><input type="text" class="form-control" id="language_known" name="language_known" value="<?php echo htmlspecialchars($teacher['language_known']); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="experience">Years of Experience</label><input type="number" class="form-control" id="experience" name="experience" min="0" value="<?php echo htmlspecialchars($teacher['experience']); ?>"></div>
                                    <div class="col-md-4 form-group"><label for="std">Teaching Standards</label><select class="form-control multi-select" id="std" name="std[]" multiple="multiple"><?php $stds_options = ['Nursery', 'Junior', 'Senior', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']; foreach ($stds_options as $std_val): ?><option value="<?php echo $std_val; ?>" <?php echo in_array($std_val, $selected_stds) ? 'selected' : ''; ?>><?php echo $std_val; ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-4 form-group"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($teacher['salary']); ?>" step="0.01" min="0"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="class_teacher" name="class_teacher" value="1" <?php echo !empty($teacher['class_teacher']) ? 'checked' : ''; ?>><label class="form-check-label" for="class_teacher">Is Class Teacher?</label></div></div>
                                    <div class="form-group col-md-6" id="classTeacherStdGroup" style="display: none;"><label for="class_teacher_std">Class Teacher for Standard *</label><select class="form-control" id="class_teacher_std" name="class_teacher_std"><option value="">-- Select Standard --</option><?php $stds_for_class_teacher = ['Nursery', 'Junior', 'Senior', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']; foreach ($stds_for_class_teacher as $std_val): ?><option value="<?php echo $std_val; ?>" <?php echo ($teacher['class_teacher_std'] == $std_val) ? 'selected' : ''; ?>><?php echo $std_val; ?></option><?php endforeach; ?></select></div>
                                </div>
                                
                                <!-- --- CHANGE 3: ADD THE TIMINGS FORM SECTION --- -->
                                <hr>
                                <h6 class="font-weight-bold text-primary mb-3">Weekly Timings</h6>
                                <div id="timings-schedule">
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day):
                                        $day_timing = $timings[$day] ?? [];
                                        $is_closed = !empty($day_timing['is_closed']);
                                        $opens_at = !empty($day_timing['opens_at']) ? date("H:i", strtotime($day_timing['opens_at'])) : '10:00';
                                        $closes_at = !empty($day_timing['closes_at']) ? date("H:i", strtotime($day_timing['closes_at'])) : '18:00';
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
                                <!-- --- END OF CHANGE 3 --- -->

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Teacher</button>
                                    <a href="teacher_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <!-- Logout Modal etc. -->
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.multi-select').select2();

            // Class Teacher checkbox logic
            const isClassTeacherCheckbox = $('#class_teacher');
            const classTeacherStdGroup = $('#classTeacherStdGroup');
            function toggleClassTeacherStd() {
                if (isClassTeacherCheckbox.is(':checked')) {
                    classTeacherStdGroup.show();
                    $('#class_teacher_std').prop('required', true);
                } else {
                    classTeacherStdGroup.hide();
                    $('#class_teacher_std').prop('required', false).val('');
                }
            }
            isClassTeacherCheckbox.on('change', toggleClassTeacherStd);
            toggleClassTeacherStd(); // Initial check

            // Image Preview
            $('#teacher_image').on('change', function(event) { if (event.target.files[0]) { $('#imagePreview').attr('src', URL.createObjectURL(event.target.files[0])); } });

            // Timings schedule logic
            $('.closed-checkbox').on('change', function() {
                const row = $(this).closest('.timing-row');
                const timeInputs = row.find('input[type="time"]');
                timeInputs.prop('disabled', $(this).is(':checked'));
            }).trigger('change'); // Trigger on page load
        });
    </script>
</body>
</html>
