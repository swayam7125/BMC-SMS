<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
include_once '../../includes/log_system.php'; // Log system included

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacher_id = $userId;
$success_msg = '';
$error_msg = '';
$school_id = null;
$principal_id = null;

try {
    // Fetch teacher's school_id to find the principal
    $stmt_school = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
    $stmt_school->execute([$teacher_id]);
    $school_id = $stmt_school->fetchColumn();

    if ($school_id) {
        $stmt_principal = $conn->prepare("SELECT id FROM principal WHERE school_id = ?");
        $stmt_principal->execute([$school_id]);
        $principal_id = $stmt_principal->fetchColumn();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $from_date = $_POST['from_date'];
        $to_date = $_POST['to_date'];
        $reason = trim($_POST['reason']);
        $leave_type = $_POST['leave_type'];

        if (empty($from_date) || empty($to_date) || empty($reason) || empty($leave_type)) {
            $error_msg = "All fields are required.";
        } else {
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO leave_applications (teacher_id, from_date, to_date, reason, leave_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$teacher_id, $from_date, $to_date, $reason, $leave_type]);

            // Notify the principal
            if ($principal_id) {
                $notification_msg = "New leave request from " . htmlspecialchars($userName);
                $notification_link = "pages/principal/teacher_leave_management.php";
                $notification_type = "leave_request";

                $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
                $stmt_notify->execute([$principal_id, $notification_msg, $notification_link, $notification_type]);
            }

            $conn->commit();
            $success_msg = "Leave application submitted successfully.";
            // Log the successful action
            log_interaction($role, $userId, "LEAVE: Submitted a new leave application from {$from_date} to {$to_date}.", $userName);
        }
    }

    // Fetch past leave applications for the teacher
    $stmt_leaves = $conn->prepare("SELECT from_date, to_date, reason, leave_type, status, rejection_reason, applied_on FROM leave_applications WHERE teacher_id = ? ORDER BY applied_on DESC");
    $stmt_leaves->execute([$teacher_id]);
    $past_leaves = $stmt_leaves->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $error_msg = "Database Error: " . $e->getMessage();
    // Log the database error
    log_interaction($role, $userId, "LEAVE ERROR: Failed to submit leave application. DB Error: " . $e->getMessage(), $userName);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Management</title>
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
?>        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
<?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Leave Management</h1>
                    <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
                    <?php if ($error_msg): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Apply for Leave</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="from_date">From Date</label>
                                        <input type="date" class="form-control" id="from_date" name="from_date" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="to_date">To Date</label>
                                        <input type="date" class="form-control" id="to_date" name="to_date" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="leave_type">Leave Type</label>
                                        <select class="form-control" id="leave_type" name="leave_type" required>
                                            <option value="Full Day">Full Day</option>
                                            <option value="First Half">First Half</option>
                                            <option value="Second Half">Second Half</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="reason">Reason</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Application</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Past Leave Applications</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Applied On</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Reason for Rejection</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($past_leaves as $leave): ?>
                                        <tr>
                                            <td><?php echo date('d-m-Y', strtotime($leave['applied_on'])); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($leave['from_date'])); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($leave['to_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $leave['status'] == 'Approved' ? 'success' : ($leave['status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                                    <?php echo htmlspecialchars($leave['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($leave['rejection_reason'] ?? 'N/A'); ?></td>
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
    <?php include_once "../../includes/logout_modal.php" ?>
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
            var today = new Date().toISOString().split('T')[0];
            document.getElementById("from_date").setAttribute('min', today);
            document.getElementById("to_date").setAttribute('min', today);

            $('#from_date').on('change', function() {
                var fromDate = $(this).val();
                $('#to_date').attr('min', fromDate);
                if ($('#to_date').val() < fromDate) {
                    $('#to_date').val(fromDate);
                }
            });
        });
    </script>
</body>
</html>