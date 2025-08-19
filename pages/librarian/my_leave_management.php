<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
// include_once '../../includes/email_functions.php'; // Uncomment if email is set up

$message = '';
$librarian_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$librarian_name = 'N/A';
$librarian_email = 'N/A';
$leave_history = [];

try {
    if ($librarian_id) {
        // Mark notifications as read
        if (isset($_GET['notif_id']) && is_numeric($_GET['notif_id'])) {
            $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
            $stmt_mark_read->execute([$_GET['notif_id'], $librarian_id]);
        }

        $stmt_user = $conn->prepare("SELECT librarian_name, email FROM librarian WHERE id = ?");
        $stmt_user->execute([$librarian_id]);
        if ($user = $stmt_user->fetch(PDO::FETCH_ASSOC)) {
            $librarian_name = $user['librarian_name'];
            $librarian_email = $user['email'];
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $from_date = $_POST['from_date'];
            $to_date = $_POST['to_date'];
            $reason = $_POST['reason'];
            $leave_type = $_POST['leave_type'] ?? 'Full Day';

            if (empty($from_date) || empty($to_date) || empty($reason)) {
                $message = '<div class="alert alert-danger">All fields are required.</div>';
            } else {
                if ($from_date != $to_date) {
                    $leave_type = 'Full Day';
                }

                $conn->beginTransaction();

                $stmt_insert = $conn->prepare("INSERT INTO librarian_leave_applications (librarian_id, from_date, to_date, reason, leave_type) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert->execute([$librarian_id, $from_date, $to_date, $reason, $leave_type]);

                // Find the principal to send a notification
                $stmt_principal = $conn->prepare("SELECT p.id, p.email, p.principal_name FROM principal p JOIN librarian l ON p.school_id = l.school_id WHERE l.id = ?");
                $stmt_principal->execute([$librarian_id]);
                if ($principal_data = $stmt_principal->fetch(PDO::FETCH_ASSOC)) {
                    $notification_message = "New leave request from " . htmlspecialchars($librarian_name);
                    $link = "pages/principal/librarian_leave_management.php";
                    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, 'librarian_leave_request')");
                    $stmt_notify->execute([$principal_data['id'], $notification_message, $link]);
                    // Email sending logic here
                }

                $conn->commit();
                $message = '<div class="alert alert-success">Leave application submitted successfully!</div>';
            }
        }

        // Fetch Leave History
        $stmt_history = $conn->prepare("SELECT from_date, to_date, leave_type, reason, applied_on, status, rejection_reason FROM librarian_leave_applications WHERE librarian_id = ? ORDER BY applied_on DESC");
        $stmt_history->execute([$librarian_id]);
        $leave_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    $message = '<div class="alert alert-danger">An error occurred: ' . $e->getMessage() . '</div>';
    error_log("Librarian Leave Management Error: " . $e->getMessage());
}

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Librarian Leave Management</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
<?php
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Librarian Leave Management</h1>
                    <?php echo $message; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Apply for Leave</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="my_leave_management.php">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($librarian_name); ?>" readonly>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="from_date">From Date</label>
                                            <input type="date" class="form-control" id="from_date" name="from_date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="to_date">To Date</label>
                                            <input type="date" class="form-control" id="to_date" name="to_date" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group" id="leave_type_container" style="display: none;">
                                    <label for="leave_type">Leave Type</label>
                                    <select class="form-control" id="leave_type" name="leave_type">
                                        <option value="Full Day">Full Day</option>
                                        <option value="First Half">First Half</option>
                                        <option value="Second Half">Second Half</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="reason">Reason for Leave</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Application</button>
                            </form>
                        </div>
                    </div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">My Application History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>From Date</th>
                                            <th>To Date</th>
                                            <th>Leave Type</th>
                                            <th>Reason</th>
                                            <th>Applied On</th>
                                            <th>Status</th>
                                            <th>Admin Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($leave_history)):
                                            foreach ($leave_history as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['from_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['to_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                                    <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($row['applied_on']))); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_color = 'secondary';
                                                        if ($row['status'] == 'Approved') {
                                                            $status_color = 'success';
                                                        } elseif ($row['status'] == 'Rejected') {
                                                            $status_color = 'danger';
                                                        }
                                                        echo '<span class="badge badge-' . $status_color . ' p-2">' . htmlspecialchars($row['status']) . '</span>';
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['rejection_reason'] ?? 'N/A'); ?></td>
                                                </tr>
                                            <?php endforeach;
                                        else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No leave applications found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
<?php
if (!is_ajax_request()) {
?>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fromDateInput = document.getElementById('from_date');
            const toDateInput = document.getElementById('to_date');
            const leaveTypeContainer = document.getElementById('leave_type_container');

            const today = new Date().toISOString().split('T')[0];
            fromDateInput.setAttribute('min', today);

            function toggleLeaveTypeVisibility() {
                if (fromDateInput.value && fromDateInput.value === toDateInput.value) {
                    leaveTypeContainer.style.display = 'block';
                } else {
                    leaveTypeContainer.style.display = 'none';
                }
            }
            fromDateInput.addEventListener('change', function() {
                toDateInput.min = fromDateInput.value;
                if (new Date(toDateInput.value) < new Date(fromDateInput.value)) {
                    toDateInput.value = fromDateInput.value;
                }
                toggleLeaveTypeVisibility();
            });
            toDateInput.addEventListener('change', toggleLeaveTypeVisibility);
            toggleLeaveTypeVisibility();
        });
    </script>
</body>

</html>
<?php
}
?>