<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
include_once '../../includes/log_system.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'hr') {
    header("Location: ../../login.php");
    exit;
}

$hr_id = $userId;
$school_id = null;
$success_msg = '';
$error_msg = '';
$past_periods = [];
$exam_names = ['First Term Exam', 'Second Term Exam', 'Final Exam'];

try {
    // 1. Fetch HR's school_id
    $stmt_school = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
    $stmt_school->execute([$hr_id]);
    $school_id = $stmt_school->fetchColumn();
    if (!$school_id) {
        die("Could not retrieve HR's school information.");
    }

    // 2. Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $exam_name = trim($_POST['exam_name']);
        $start_date = trim($_POST['start_date']);
        $end_date = trim($_POST['end_date']);
        $due_date = trim($_POST['due_date']);

        // Basic validation
        if (empty($exam_name) || empty($start_date) || empty($end_date) || empty($due_date)) {
            $error_msg = "All fields are required.";
        } elseif (!in_array($exam_name, $exam_names)) {
            $error_msg = "Invalid exam name selected.";
        } else {
            // Check for date logical constraints
            if (strtotime($start_date) > strtotime($end_date)) {
                $error_msg = "Start Date cannot be after End Date.";
            } elseif (strtotime($due_date) > strtotime($start_date)) {
                $error_msg = "Form Due Date must be on or before the Exam Start Date.";
            } else {
                // Insert into database
                $stmt = $conn->prepare("
                    INSERT INTO exam_registration_periods 
                    (school_id, exam_name, start_date, end_date, form_due_date, created_by_user_id) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$school_id, $exam_name, $start_date, $end_date, $due_date, $hr_id]);
                
                $success_msg = "Exam registration period for '{$exam_name}' set successfully!";
                
                // Log the action
                log_interaction($role, $userId, "EXAM REGISTRATION: Set period for '{$exam_name}' (Dates: {$start_date} to {$end_date}).", $userName);
            }
        }
    }
    
    // 3. Fetch past periods
    $stmt_past = $conn->prepare("
        SELECT exam_name, start_date, end_date, form_due_date, created_at 
        FROM exam_registration_periods 
        WHERE school_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt_past->execute([$school_id]);
    $past_periods = $stmt_past->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Check if it's a unique constraint violation (duplicate exam entry for the year)
    if ($e->getCode() === '23505') {
         $error_msg = "Error: An entry for this exam name already exists in the database.";
    } else {
        $error_msg = "Database Error: " . $e->getMessage();
    }
    log_interaction($role, $userId, "EXAM REGISTRATION ERROR: A database error occurred. " . $e->getMessage(), $userName);
}

// Format date for display
function format_date($date_str) {
    return date('d-M-Y', strtotime($date_str));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Exam Registration</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
<?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage Exam Registration Periods</h1>

                    <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
                    <?php if ($error_msg): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Set Exam Dates</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="exam_name">Exam Name *</label>
                                    <select class="form-control" id="exam_name" name="exam_name" required>
                                        <option value="">Select Exam</option>
                                        <?php foreach ($exam_names as $name): ?>
                                            <option value="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="start_date">Starting Date of Exam *</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="end_date">Ending Date of Exam *</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="due_date">Due Date to submit form *</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" required>
                                    <small class="form-text text-muted">Students must submit their exam form/registration by this date.</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit</button>
                                <button type="reset" class="btn btn-secondary">Cancel</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Past Exam Registration Periods</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date Set</th>
                                            <th>Exam Name</th>
                                            <th>Exam Start Date</th>
                                            <th>Exam End Date</th>
                                            <th>Form Due Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($past_periods as $period): ?>
                                            <tr>
                                                <td><?php echo date('d-M-Y h:i A', strtotime($period['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($period['exam_name']); ?></td>
                                                <td><?php echo format_date($period['start_date']); ?></td>
                                                <td><?php echo format_date($period['end_date']); ?></td>
                                                <td><?php echo format_date($period['form_due_date']); ?></td>
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
?>
        </div>
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
            $('#dataTable').DataTable({
                "order": [[ 0, "desc" ]]
            });
            
            // Client-side date validation
            $('#start_date').on('change', function() {
                const startDate = $(this).val();
                $('#end_date').attr('min', startDate);
                $('#due_date').attr('max', startDate);
            });

            $('#end_date').on('change', function() {
                const endDate = $(this).val();
                if ($('#start_date').val() && $('#start_date').val() > endDate) {
                    alert('End Date cannot be before Start Date.');
                    $(this).val('');
                }
            });
            
            $('#due_date').on('change', function() {
                const dueDate = $(this).val();
                if ($('#start_date').val() && dueDate > $('#start_date').val()) {
                    alert('Due Date must be on or before the Exam Start Date.');
                    $(this).val('');
                }
            });
        });
    </script>
</body>
</html>