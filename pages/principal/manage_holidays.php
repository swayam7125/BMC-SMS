<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
include_once '../../includes/log_system.php'; // Log system included

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

$school_id = null;
try {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Database error fetching school ID: " . $e->getMessage());
}

if (!$school_id) {
    die("Error: Could not determine your school.");
}

$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_holiday'])) {
            $holiday_date = $_POST['holiday_date'];
            $description = trim($_POST['description']);

            if (empty($holiday_date) || empty($description)) {
                $errors[] = "Both date and description are required.";
            } else {
                $stmt = $conn->prepare("INSERT INTO holidays (school_id, holiday_date, description) VALUES (?, ?, ?)");
                if ($stmt->execute([$school_id, $holiday_date, $description])) {
                    $_SESSION['success_message'] = "Holiday for '{$description}' on {$holiday_date} added successfully!";
                    // Log the action
                    log_interaction($role, $userId, "HOLIDAYS: Added new holiday '{$description}' on {$holiday_date}.", $userName);
                } else {
                    $errors[] = "Failed to add holiday. It might already exist for this date.";
                    log_interaction($role, $userId, "HOLIDAYS ERROR: Failed to add holiday '{$description}' on {$holiday_date}.", $userName);
                }
            }
        } elseif (isset($_POST['delete_holiday'])) {
            $holiday_id = $_POST['holiday_id'];
            
            // For logging, get the holiday details before deleting
            $stmt_get = $conn->prepare("SELECT holiday_date, description FROM holidays WHERE id = ?");
            $stmt_get->execute([$holiday_id]);
            $holiday_details = $stmt_get->fetch(PDO::FETCH_ASSOC);

            $stmt = $conn->prepare("DELETE FROM holidays WHERE id = ? AND school_id = ?");
            if ($stmt->execute([$holiday_id, $school_id])) {
                $_SESSION['success_message'] = "Holiday deleted successfully!";
                if ($holiday_details) {
                    // Log the action with details
                    log_interaction($role, $userId, "HOLIDAYS: Deleted holiday '" . $holiday_details['description'] . "' for date " . $holiday_details['holiday_date'] . ".", $userName);
                }
            } else {
                $errors[] = "Failed to delete holiday.";
                 log_interaction($role, $userId, "HOLIDAYS ERROR: Failed to delete holiday ID {$holiday_id}.", $userName);
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
        log_interaction($role, $userId, "HOLIDAYS ERROR: A database error occurred. " . $e->getMessage(), $userName);
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
    }
    
    header("Location: manage_holidays.php");
    exit();
}

// Fetch existing holidays
$holidays = [];
try {
    $stmt = $conn->prepare("SELECT id, holiday_date, description FROM holidays WHERE school_id = ? ORDER BY holiday_date ASC");
    $stmt->execute([$school_id]);
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Database error fetching holidays: " . $e->getMessage();
}

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['form_errors'])) {
    $errors = array_merge($errors, $_SESSION['form_errors']);
    unset($_SESSION['form_errors']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Holidays - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>
<body id="page-top">
    <div id="wrapper">
<?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?>        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
<?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?>                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage Holidays</h1>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
                    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $error): echo "<p class='mb-0'>".htmlspecialchars($error)."</p>"; endforeach; ?></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add New Holiday</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="manage_holidays.php">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <input type="date" class="form-control" name="holiday_date" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <input type="text" class="form-control" name="description" placeholder="Holiday Description" required>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <button type="submit" name="add_holiday" class="btn btn-primary btn-block">Add Holiday</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Holiday List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($holidays as $holiday): ?>
                                        <tr>
                                            <td><?php echo date('d-M-Y', strtotime($holiday['holiday_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($holiday['description']); ?></td>
                                            <td>
                                                <form method="POST" action="manage_holidays.php" onsubmit="return confirm('Are you sure you want to delete this holiday?');">
                                                    <input type="hidden" name="holiday_id" value="<?php echo $holiday['id']; ?>">
                                                    <button type="submit" name="delete_holiday" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?>        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"; ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });
    </script>
</body>
</html>