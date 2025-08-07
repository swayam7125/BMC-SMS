<?php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = null;
$user_id = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

$librarian_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($librarian_id <= 0) {
    header("Location: librarian_list.php?error=Invalid ID.");
    exit;
}

$librarian = null;
$timings = [];

try {
    // --- FORM SUBMISSION LOGIC ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve and sanitize form data
        $librarian_name = trim($_POST['librarian_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $blood_group = $_POST['blood_group'];
        $address = trim($_POST['address']);
        $qualification = trim($_POST['qualification']);
        $salary = trim($_POST['salary']);
        $posted_timings = $_POST['timings'] ?? [];

        $conn->beginTransaction();
        
        // 1. Update the 'librarian' table
        $stmt_update_lib = $conn->prepare(
            'UPDATE "librarian" SET 
             "librarian_name" = ?, "email" = ?, "phone" = ?, "dob" = ?, "gender" = ?, 
             "blood_group" = ?, "address" = ?, "qualification" = ?, "salary" = ?
             WHERE "id" = ?'
        );
        $stmt_update_lib->execute([
            $librarian_name, $email, $phone, $dob, $gender,
            $blood_group, $address, $qualification, $salary,
            $librarian_id
        ]);
        
        // 2. Update the 'users' table if email has changed
        $stmt_check_user = $conn->prepare('SELECT "email" FROM "users" WHERE "id" = ?');
        $stmt_check_user->execute([$librarian_id]);
        $current_email = $stmt_check_user->fetchColumn();
        if ($current_email !== $email) {
            $stmt_update_user = $conn->prepare('UPDATE "users" SET "email" = ? WHERE "id" = ?');
            $stmt_update_user->execute([$email, $librarian_id]);
        }

        // 3. Update timings using ON CONFLICT for an UPSERT operation
        $stmt_timing = $conn->prepare(
            'INSERT INTO "librarian_timings" (librarian_id, day_of_week, opens_at, closes_at, is_closed) 
             VALUES (?, ?, ?, ?, ?)
             ON CONFLICT (librarian_id, day_of_week) DO UPDATE SET
             opens_at = EXCLUDED.opens_at,
             closes_at = EXCLUDED.closes_at,
             is_closed = EXCLUDED.is_closed'
        );
        foreach ($posted_timings as $day => $details) {
            $is_closed = isset($details['is_closed']);
            $opens_at = ($is_closed || empty($details['opens_at'])) ? null : $details['opens_at'];
            $closes_at = ($is_closed || empty($details['closes_at'])) ? null : $details['closes_at'];
            $stmt_timing->execute([$librarian_id, $day, $opens_at, $closes_at, $is_closed]);
        }

        $conn->commit();
        header("Location: view.php?id=$librarian_id&success=1");
        exit;
    }

    // --- DATA FETCHING FOR FORM DISPLAY ---
    $stmt_fetch_lib = $conn->prepare('SELECT * FROM "librarian" WHERE "id" = ?');
    $stmt_fetch_lib->execute([$librarian_id]);
    $librarian = $stmt_fetch_lib->fetch(PDO::FETCH_ASSOC);

    if (!$librarian) {
        header("Location: librarian_list.php?error=Librarian not found.");
        exit;
    }
    
    $stmt_fetch_timings = $conn->prepare('SELECT * FROM "librarian_timings" WHERE "librarian_id" = ?');
    $stmt_fetch_timings->execute([$librarian_id]);
    $timings_result = $stmt_fetch_timings->fetchAll(PDO::FETCH_ASSOC);
    foreach($timings_result as $row){
        $timings[$row['day_of_week']] = $row;
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Librarian - <?php echo htmlspecialchars($librarian['librarian_name']); ?></title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Edit Librarian Details</h1>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="edit.php?id=<?php echo $librarian_id; ?>" method="POST">
                                <h6 class="font-weight-bold text-primary">Basic Information</h6>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="librarian_name">Librarian Name *</label><input type="text" class="form-control" id="librarian_name" name="librarian_name" value="<?php echo htmlspecialchars($librarian['librarian_name']); ?>" required></div>
                                    <div class="form-group col-md-6"><label for="email">Email *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($librarian['email']); ?>" required></div>
                                </div>
                                <hr>
                                <h6 class="font-weight-bold text-primary">Personal & Professional Details</h6>
                                 <div class="form-row">
                                    <div class="form-group col-md-4"><label for="phone">Phone *</label><input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($librarian['phone']); ?>" maxlength="10" required></div>
                                    <div class="form-group col-md-4"><label for="dob">Date of Birth</label><input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($librarian['dob']); ?>"></div>
                                    <div class="form-group col-md-4"><label for="gender">Gender *</label><select class="form-control" id="gender" name="gender" required>
                                            <option value="Male" <?php echo ($librarian['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($librarian['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Others" <?php echo ($librarian['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                     <div class="form-group col-md-4"><label for="blood_group">Blood Group</label><select class="form-control" id="blood_group" name="blood_group">
                                            <?php $bg_options = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                            foreach ($bg_options as $bg) {
                                                $selected = ($librarian['blood_group'] == $bg) ? 'selected' : '';
                                                echo "<option value='{$bg}' {$selected}>" . $bg . "</option>";
                                            } ?>
                                        </select></div>
                                    <div class="form-group col-md-4"><label for="qualification">Qualification</label><input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($librarian['qualification']); ?>"></div>
                                    <div class="form-group col-md-4"><label for="salary">Salary</label><input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($librarian['salary']); ?>" step="0.01" min="0"></div>
                                </div>
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($librarian['address']); ?></textarea>
                                </div>
                                <hr>
                                <h6 class="font-weight-bold text-primary mb-3">Update Weekly Timings</h6>
                                <div id="timings-schedule">
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day):
                                        $timing = $timings[$day] ?? [];
                                        $is_closed = !empty($timing['is_closed']);
                                        $opens_at = $timing['opens_at'] ?? '09:00';
                                        $closes_at = $timing['closes_at'] ?? '17:00';
                                    ?>
                                        <div class="form-row align-items-center mb-2 timing-row">
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
                                                    <input type="time" class="form-control" name="timings[<?php echo $day; ?>][opens_at]" value="<?php echo htmlspecialchars($opens_at); ?>" <?php if ($is_closed) echo 'disabled'; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text small">Closes at</span></div>
                                                    <input type="time" class="form-control" name="timings[<?php echo $day; ?>][closes_at]" value="<?php echo htmlspecialchars($closes_at); ?>" <?php if ($is_closed) echo 'disabled'; ?>>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <hr>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Details</button>
                                <a href="view.php?id=<?php echo $librarian_id; ?>" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.closed-checkbox').on('change', function() {
                const row = $(this).closest('.timing-row');
                const timeInputs = row.find('input[type="time"]');
                timeInputs.prop('disabled', $(this).is(':checked'));
            });
            $('.closed-checkbox').trigger('change');
        });
    </script>
</body>
</html>
<?php $conn = null; ?>
