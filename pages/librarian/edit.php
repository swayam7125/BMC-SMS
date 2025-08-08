<?php
include_once '../../includes/connect.php'; // Your PDO connection file
include_once '../../encryption.php';

// Check if user is logged in
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: librarian_list.php?error=Invalid ID provided");
    exit;
}

$librarian_id = (int)$_GET['id'];
$errors = [];
$librarian = [];
$timings = [];

try {
    // --- FETCH EXISTING LIBRARIAN DATA ---
    $sql_librarian = "SELECT * FROM librarian WHERE id = ?";
    $stmt_librarian_fetch = $conn->prepare($sql_librarian);
    $stmt_librarian_fetch->execute([$librarian_id]);
    $librarian = $stmt_librarian_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$librarian) {
        header("Location: librarian_list.php?error=Librarian not found");
        exit;
    }
    // Store original values before any POST modifications
    $original_email = $librarian['email'];
    $original_image_path = $librarian['librarian_image'] ?? null;

    // Fetch timings
    $sql_timings = "SELECT * FROM librarian_timings WHERE librarian_id = ?";
    $stmt_timings_fetch = $conn->prepare($sql_timings);
    $stmt_timings_fetch->execute([$librarian_id]);
    while ($row = $stmt_timings_fetch->fetch(PDO::FETCH_ASSOC)) {
        $timings[$row['day_of_week']] = $row;
    }
} catch (PDOException $e) {
    die("Database error while fetching data: " . $e->getMessage());
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form Data Retrieval
    $librarian_name = trim($_POST['librarian_name']);
    $new_email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $salary = trim($_POST['salary']);
    $posted_timings = $_POST['timings'] ?? [];
    
    $image_path_for_db = $original_image_path;

    // --- Handle Photo Upload ---
    if (isset($_FILES['librarian_image']) && $_FILES['librarian_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['librarian_image'];
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/librarian/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'librarian_' . $librarian_id . '_' . uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $image_path_for_db = '/BMC-SMS/pages/librarian/uploads/' . $new_filename;
            if (!empty($original_image_path) && file_exists($_SERVER['DOCUMENT_ROOT'] . $original_image_path)) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $original_image_path);
            }
        } else {
            $errors[] = "Failed to move uploaded file.";
        }
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            if ($new_email !== $original_email) {
                $sql_update_users = "UPDATE users SET email = ? WHERE id = ? AND role = 'librarian'";
                $stmt_users = $conn->prepare($sql_update_users);
                $stmt_users->execute([$new_email, $librarian_id]);
            }

            $sql_update_librarian = "UPDATE librarian SET librarian_image = ?, librarian_name = ?, phone = ?, dob = ?, gender = ?, blood_group = ?, address = ?, email = ?, qualification = ?, salary = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update_librarian);
            $stmt_update->execute([
                $image_path_for_db, $librarian_name, $phone, $dob, $gender, 
                $blood_group, $address, $new_email, $qualification, $salary, $librarian_id
            ]);

            $sql_upsert_timing = "INSERT INTO librarian_timings (librarian_id, day_of_week, opens_at, closes_at, is_closed) 
                                  VALUES (?, ?, ?, ?, ?) 
                                  ON CONFLICT (librarian_id, day_of_week) 
                                  DO UPDATE SET opens_at = EXCLUDED.opens_at, closes_at = EXCLUDED.closes_at, is_closed = EXCLUDED.is_closed";
            $stmt_timing_upsert = $conn->prepare($sql_upsert_timing);
            foreach ($posted_timings as $day => $details) {
            // FIX: Convert PHP boolean to an integer (0 or 1) for PostgreSQL's boolean type.
                $is_closed_db = isset($details['is_closed']) ? 1 : 0;
                $opens_at = ($is_closed_db || empty($details['opens_at'])) ? null : $details['opens_at'];
                $closes_at = ($is_closed_db || empty($details['closes_at'])) ? null : $details['closes_at'];
                $stmt_timing_upsert->execute([$librarian_id, $day, $opens_at, $closes_at, $is_closed_db]);
            }

            $conn->commit();
            header("Location: librarian_list.php?success=Librarian updated successfully");
            exit;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $errors[] = "Database update failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Librarian - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Librarian</h1>
                        <a href="librarian_list.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img src="<?php echo htmlspecialchars(!empty($librarian['librarian_image'] ?? null) && file_exists($_SERVER['DOCUMENT_ROOT'] . $librarian['librarian_image']) ? $librarian['librarian_image'] : '../../assets/images/unisex.png'); ?>" alt="Librarian Photo" id="imagePreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-group">
                                            <label for="librarian_image" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Change Photo</label>
                                            <input type="file" class="d-none" id="librarian_image" name="librarian_image" onchange="document.getElementById('imagePreview').src = window.URL.createObjectURL(this.files[0])">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-6 form-group"><label for="librarian_name">Librarian Name *</label><input type="text" class="form-control" id="librarian_name" name="librarian_name" value="<?php echo htmlspecialchars($librarian['librarian_name'] ?? ''); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($librarian['email'] ?? ''); ?>" required></div>
                                            <div class="col-md-6 form-group"><label for="phone">Phone *</label><input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($librarian['phone'] ?? ''); ?>" maxlength="10" required></div>
                                            <div class="col-md-6 form-group"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($librarian['dob'] ?? ''); ?>"></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-primary font-weight-bold">Personal & Professional Details</h6>
                                <div class="row mt-3">
                                    <div class="col-md-4 form-group"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="Male" <?php echo (($librarian['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo (($librarian['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Others" <?php echo (($librarian['gender'] ?? '') == 'Others') ? 'selected' : ''; ?>>Others</option>
                                        </select></div>
                                    <div class="col-md-4 form-group"><label for="blood_group">Blood Group *</label><select class="form-control" id="blood_group" name="blood_group" required><?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                        foreach ($bg_options as $bg) {
                                            $selected = (($librarian['blood_group'] ?? '') == $bg) ? 'selected' : '';
                                            echo "<option value='{$bg}' {$selected}>{$bg}</option>";
                                        } ?></select></div>
                                    <div class="col-md-4 form-group"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($librarian['qualification'] ?? ''); ?>"></div>
                                    <div class="col-md-8 form-group"><label for="address">Address</label><textarea class="form-control" id="address" name="address" rows="1"><?php echo htmlspecialchars($librarian['address'] ?? ''); ?></textarea></div>
                                    <div class="col-md-4 form-group"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($librarian['salary'] ?? '0.00'); ?>" step="0.01" min="0"></div>
                                </div>
                                <hr>
                                
                                <h6 class="font-weight-bold text-primary mb-3">Weekly Timings</h6>
                                <div id="timings-schedule">
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day):
                                        $day_timing = $timings[$day] ?? [];
                                        $is_closed = isset($day_timing['is_closed']) && $day_timing['is_closed'];
                                        $opens_at = !empty($day_timing['opens_at']) ? date("H:i", strtotime($day_timing['opens_at'])) : '09:00';
                                        $closes_at = !empty($day_timing['closes_at']) ? date("H:i", strtotime($day_timing['closes_at'])) : '17:00';
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

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Librarian</button>
                                    <a href="librarian_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
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
            $('.closed-checkbox').on('change', function() {
                var row = $(this).closest('.timing-row');
                var timeInputs = row.find('.opens-at, .closes-at');
                if ($(this).is(':checked')) {
                    timeInputs.prop('disabled', true);
                } else {
                    timeInputs.prop('disabled', false);
                }
            });
             // Trigger change on page load to set initial state
            $('.closed-checkbox').trigger('change');
        });
    </script>
</body>
</html>