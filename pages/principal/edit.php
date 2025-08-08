<?php
// Includes for database connection and encryption functions.
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Define the base URL if it's not already defined. This is crucial for creating correct file paths.
if (!defined('BASE_URL')) {
    define('BASE_URL', '/BMC-SMS/');
}

/**
 * Generates a web-accessible URL for an image path stored in the database.
 * It checks if the path is a full URL, or constructs a path from the document root.
 *
 * @param string|null $db_image_path The path stored in the database.
 * @return string|null The web-accessible path or null if not found.
 */
function getWebAccessibleImagePath($db_image_path)
{
    if (empty($db_image_path)) return null;
    // If it's already a full URL, return it.
    if (filter_var($db_image_path, FILTER_VALIDATE_URL)) return $db_image_path;
    // Construct the full filesystem path to check if the file exists.
    $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_URL . ltrim($db_image_path, '/');
    if (@is_file($filesystem_path)) {
        // Return the web-accessible relative path.
        return BASE_URL . ltrim($db_image_path, '/');
    }
    return null;
}

// --- User Authentication Check ---
// Every secure page should start with this check.
// It decrypts the user role from the cookie and ensures the user is logged in.
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
if (!$role) {
    // If no role is found, redirect to the login page and stop script execution.
    header("Location: ../../login.php");
    exit;
}

// --- ID Validation ---
// Check if a principal ID is provided in the URL.
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: principal_list.php?error=Invalid ID provided");
    exit;
}

// Sanitize the ID to ensure it's an integer.
$principal_id = intval($_GET['id']);
$errors = [];
$principal = null;
$timings = [];
$schools_result = [];

try {
    // --- MODIFIED: Moved the school fetching logic to the top ---
    // This ensures the school list is always available for the form dropdown, even if there's a POST error.
    $schools_result = $conn->query("SELECT id, school_name FROM school ORDER BY school_name")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch the existing principal's data to populate the form.
    $stmt_principal_fetch = $conn->prepare("SELECT * FROM principal WHERE id = ?");
    $stmt_principal_fetch->execute([$principal_id]);
    if ($stmt_principal_fetch->rowCount() === 0) {
        header("Location: principal_list.php?error=Principal not found");
        exit;
    }
    $principal = $stmt_principal_fetch->fetch(PDO::FETCH_ASSOC);
    // Store original values for comparison later.
    $original_image_path = $principal['principal_image'];
    $original_email = $principal['email'];
    $original_batch = $principal['batch'];

    // Fetch the existing principal's weekly timings.
    $stmt_timings_fetch = $conn->prepare("SELECT * FROM principal_timings WHERE principal_id = ?");
    $stmt_timings_fetch->execute([$principal_id]);
    while ($row = $stmt_timings_fetch->fetch(PDO::FETCH_ASSOC)) {
        $timings[$row['day_of_week']] = $row;
    }

    // --- Form Submission Logic ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize and retrieve all form data.
        $principal_name = trim($_POST['principal_name']);
        $new_email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender = $_POST['gender'];
        $blood_group = $_POST['blood_group'];
        $address = trim($_POST['address']);
        $qualification = trim($_POST['qualification']);
        $salary = trim($_POST['salary']);
        $school_id = intval($_POST['school_id']);
        $new_batch = $_POST['batch'];
        $posted_timings = $_POST['timings'] ?? [];
        $image_path_for_db = $original_image_path;

        // --- Image Upload Logic ---
        if (isset($_FILES['principal_image']) && $_FILES['principal_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['principal_image'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($file_ext, $allowed_exts)) {
                $target_dir_relative = "uploads/principal_images/";
                $full_target_dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_URL . $target_dir_relative;
                if (!file_exists($full_target_dir)) mkdir($full_target_dir, 0777, true);
                $new_filename = 'principal_' . $principal_id . '_' . time() . '.' . $file_ext;
                $destination = $full_target_dir . $new_filename;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $image_path_for_db = $target_dir_relative . $new_filename;
                } else {
                    $errors[] = "Failed to move uploaded file.";
                }
            } else {
                $errors[] = "Invalid file type.";
            }
        }

        // --- Database Update Logic ---
        if (empty($errors)) {
            $conn->beginTransaction();

            // Handle batch swapping if another principal is in the same school/batch.
            if ($new_batch !== $original_batch) {
                $stmt_swap_check = $conn->prepare("SELECT id FROM principal WHERE school_id = ? AND batch = ? AND id != ?");
                $stmt_swap_check->execute([$school_id, $new_batch, $principal_id]);
                if ($other_principal = $stmt_swap_check->fetch(PDO::FETCH_ASSOC)) {
                    $other_principal_id_to_swap = $other_principal['id'];
                    $stmt_swap = $conn->prepare("UPDATE principal SET batch = ? WHERE id = ?");
                    $stmt_swap->execute([$original_batch, $other_principal_id_to_swap]);
                }
            }

            // If email is changed, update the corresponding record in the 'users' table.
            if ($new_email !== $original_email) {
                $stmt_user = $conn->prepare("UPDATE users SET email=? WHERE id=? AND role='principal'");
                $stmt_user->execute([$new_email, $principal_id]);
            }

            // Update the main principal record.
            $update_principal_query = "UPDATE principal SET principal_image=?, principal_name=?, email=?, phone=?, dob=?, gender=?, blood_group=?, address=?, qualification=?, salary=?, school_id=?, batch=? WHERE id=?";
            $stmt_principal_update = $conn->prepare($update_principal_query);
            $stmt_principal_update->execute([$image_path_for_db, $principal_name, $new_email, $phone, $dob, $gender, $blood_group, $address, $qualification, $salary, $school_id, $new_batch, $principal_id]);

            // Upsert (UPDATE or INSERT) the weekly timings.
            $upsert_timing_query = "INSERT INTO principal_timings (principal_id, day_of_week, opens_at, closes_at, is_closed) VALUES (?, ?, ?, ?, ?) ON CONFLICT (principal_id, day_of_week) DO UPDATE SET opens_at = EXCLUDED.opens_at, closes_at = EXCLUDED.closes_at, is_closed = EXCLUDED.is_closed";
            $stmt_timing_upsert = $conn->prepare($upsert_timing_query);
            foreach ($days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
                $details = $posted_timings[$day] ?? [];

                // *** FIX STARTS HERE ***
                // The original code passed a PHP boolean (from isset) directly to the database.
                // When 'is_closed' was not set, this resulted in `false`, which PDO sent as an empty string `''`.
                // PostgreSQL's boolean type does not accept `''`, causing the `SQLSTATE[22P02]` error.
                // The fix is to convert the PHP boolean to an integer (1 or 0), which is a valid boolean representation in SQL.
                
                $is_closed_bool = isset($details['is_closed']);
                $is_closed_for_db = $is_closed_bool ? 1 : 0; // Convert boolean to 1 or 0 for the database.

                // Set times to null if the day is marked as closed.
                $opens_at = ($is_closed_bool || empty($details['opens_at'])) ? null : $details['opens_at'];
                $closes_at = ($is_closed_bool || empty($details['closes_at'])) ? null : $details['closes_at'];
                
                // Execute the query with the corrected boolean value.
                $stmt_timing_upsert->execute([$principal_id, $day, $opens_at, $closes_at, $is_closed_for_db]);
                // *** FIX ENDS HERE ***
            }

            // If all queries were successful, commit the transaction.
            $conn->commit();
            header("Location: principal_list.php?success=Principal updated successfully.");
            exit;
        }
    }
} catch (Exception $e) {
    // If any error occurs, roll back the transaction and display the error message.
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    $errors[] = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Principal - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                                        <?php
                                        $default_image_path = BASE_URL . 'assets/img/default-user.jpg';
                                        $current_image_web_path = getWebAccessibleImagePath($principal['principal_image']) ?? $default_image_path;
                                        ?>
                                        <img src="<?php echo htmlspecialchars($current_image_web_path); ?>" alt="Principal Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_image_path); ?>';">
                                        <div class="form-group"><label for="principal_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Change Photo</label><input type="file" class="d-none" id="principal_image" name="principal_image" onchange="document.getElementById('imagePreview').src = window.URL.createObjectURL(this.files[0])"></div>
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
                                            <option value="">-- Select School --</option>
                                            <?php foreach ($schools_result as $school) {
                                                $selected = ($school['id'] == $principal['school_id']) ? 'selected' : '';
                                                echo "<option value='{$school['id']}' {$selected}>" . htmlspecialchars($school['school_name']) . "</option>";
                                            } ?>
                                        </select></div>
                                    <div class="form-group col-md-6"><label for="batch">Batch *</label><select class="form-control" id="batch" name="batch" required>
                                            <option value="">-- Select Batch --</option>
                                            <option value="Morning" <?php echo ($principal['batch'] == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                                            <option value="Evening" <?php echo ($principal['batch'] == 'Evening') ? 'selected' : ''; ?>>Evening</option>
                                        </select></div>
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
                                        <div class="form-row align-items-center mb-2 timing-row">
                                            <div class="col-md-2"><label class="mb-0"><?php echo $day; ?></label></div>
                                            <div class="col-md-2">
                                                <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="closed_<?php echo $day; ?>" name="timings[<?php echo $day; ?>][is_closed]" <?php if ($is_closed) echo 'checked'; ?>><label class="custom-control-label" for="closed_<?php echo $day; ?>">Closed</label></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text small">Opens at</span></div><input type="time" class="form-control" name="timings[<?php echo $day; ?>][opens_at]" value="<?php echo htmlspecialchars($opens_at); ?>" <?php if ($is_closed) echo 'disabled'; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text small">Closes at</span></div><input type="time" class="form-control" name="timings[<?php echo $day; ?>][closes_at]" value="<?php echo htmlspecialchars($closes_at); ?>" <?php if ($is_closed) echo 'disabled'; ?>>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($principal['dob']); ?>"></div>
                                    <div class="form-group col-md-6"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male" <?php echo ($principal['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($principal['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Others" <?php echo ($principal['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="blood_group">Blood Group</label><select class="form-control" id="blood_group" name="blood_group"><?php 
                                        $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                        echo "<option value=''>-- Select Blood Group --</option>";
                                        foreach ($bg_options as $bg) {
                                            $selected = ($principal['blood_group'] == $bg) ? 'selected' : '';
                                            echo "<option value='{$bg}' {$selected}>" . strtoupper($bg) . "</option>";
                                        } 
                                        ?></select></div>
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
        document.addEventListener('DOMContentLoaded', function() {
            // This script handles the "Closed" checkbox for each day's timing.
            // When checked, it disables the time input fields for that day.
            document.querySelectorAll('.timing-row .custom-control-input').forEach(function(checkbox) {
                const row = checkbox.closest('.timing-row');
                const timeInputs = row.querySelectorAll('input[type="time"]');
                function toggle() {
                    timeInputs.forEach(input => input.disabled = checkbox.checked);
                }
                checkbox.addEventListener('change', toggle);
                // Run on page load to set initial state.
                toggle();
            });
        });
    </script>
</body>
</html>