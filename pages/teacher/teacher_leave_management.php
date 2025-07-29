<?php
// Step 1: Include all necessary files at the top
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/email_functions.php'; // <-- The new email functions file

// Initialize variables
$message = '';
$teacher_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$teacher_name = 'N/A';
$teacher_email = 'N/A';

if ($teacher_id) {
    // --- START: Mark notification as read ---
    if (isset($_GET['notif_id']) && is_numeric($_GET['notif_id'])) {
        $notification_id = $_GET['notif_id'];
        $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt_mark_read->bind_param("ii", $notification_id, $teacher_id);
        $stmt_mark_read->execute();
        $stmt_mark_read->close();
    }
    // --- END: Mark notification as read ---

    // Fetch teacher's name and email for the form
    $stmt_user = $conn->prepare("SELECT teacher_name, email FROM teacher WHERE id = ?");
    $stmt_user->bind_param("i", $teacher_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($user = $result_user->fetch_assoc()) {
        $teacher_name = $user['teacher_name'];
        $teacher_email = $user['email'];
    }
    $stmt_user->close();

    // --- Handle Leave Application Form Submission ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $from_date = $_POST['from_date'];
        $to_date = $_POST['to_date'];
        $reason = $_POST['reason'];
        $leave_type = isset($_POST['leave_type']) ? $_POST['leave_type'] : 'Full Day';

        if (empty($from_date) || empty($to_date) || empty($reason)) {
            $message = '<div class="alert alert-danger">All fields are required.</div>';
        } else {
            if ($from_date != $to_date) {
                $leave_type = 'Full Day';
            }

            // Insert into database
            $stmt_insert = $conn->prepare("INSERT INTO leave_applications (teacher_id, from_date, to_date, reason, leave_type) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("issss", $teacher_id, $from_date, $to_date, $reason, $leave_type);

            if ($stmt_insert->execute()) {
                $message = '<div class="alert alert-success">Leave application submitted successfully!</div>';

                // --- START: Notification & Email Logic ---
                // 1. Get the principal's user ID, email, and name
                $stmt_principal = $conn->prepare(
                    "SELECT p.id, p.email, p.principal_name 
                     FROM principal p 
                     JOIN teacher t ON p.school_id = t.school_id 
                     WHERE t.id = ?"
                );
                $stmt_principal->bind_param("i", $teacher_id);
                $stmt_principal->execute();
                $principal_data = $stmt_principal->get_result()->fetch_assoc();

                if ($principal_data) {
                    $principal_user_id = $principal_data['id'];
                    $principal_email = $principal_data['email'];
                    $principal_name = $principal_data['principal_name'];

                    // 2. Create in-app notification
                    $notification_message = "New leave request from " . htmlspecialchars($teacher_name);
                    $link = "/pages/principal/principal_leave_requests.php";
                    $type = 'leave_request';
                    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
                    $stmt_notify->bind_param("isss", $principal_user_id, $notification_message, $link, $type);
                    $stmt_notify->execute();
                    $stmt_notify->close();

                    // 3. Send the email notification
                    $email_subject = "New Leave Application from " . htmlspecialchars($teacher_name);
                    $email_body = "
                        <p>Dear " . htmlspecialchars($principal_name) . ",</p>
                        <p>A new leave application has been submitted by <strong>" . htmlspecialchars($teacher_name) . "</strong>.</p>
                        <p><strong>Dates:</strong> " . htmlspecialchars($from_date) . " to " . htmlspecialchars($to_date) . "</p>
                        <p><strong>Leave Type:</strong> " . htmlspecialchars($leave_type) . "</p>
                        <p><strong>Reason:</strong> " . nl2br(htmlspecialchars($reason)) . "</p>
                        <p>Please log in to the school portal to approve or reject the request.</p>
                    ";

                    send_email($principal_email, $email_subject, $email_body);
                }
                $stmt_principal->close();
                // --- END: Notification & Email Logic ---

            } else {
                $message = '<div class="alert alert-danger">Error submitting application.</div>';
            }
            $stmt_insert->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leave Management</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Leave Management</h1>
                    <?php echo $message; ?>

                    <!-- Leave Application Form Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Apply for Leave</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="teacher_leave_management.php">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo htmlspecialchars($teacher_name); ?>" readonly>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="from_date">From Date</label>
                                            <input type="date" class="form-control" id="from_date" name="from_date"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="to_date">To Date</label>
                                            <input type="date" class="form-control" id="to_date" name="to_date"
                                                required>
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
                                    <textarea class="form-control" id="reason" name="reason" rows="4"
                                        required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Application</button>
                            </form>
                        </div>
                    </div>

                    <!-- Leave History Card -->
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
                                        <?php
                                        if ($teacher_id) {
                                            // Re-establish connection if it was closed, or just use it if still open
                                            if ($conn->ping() === false) {
                                                $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                                            }

                                            $stmt_history = $conn->prepare("SELECT from_date, to_date, leave_type, reason, applied_on, status, rejection_reason FROM leave_applications WHERE teacher_id = ? ORDER BY applied_on DESC");
                                            $stmt_history->bind_param("i", $teacher_id);
                                            $stmt_history->execute();
                                            $result_history = $stmt_history->get_result();

                                            if ($result_history && $result_history->num_rows > 0) {
                                                while ($row = $result_history->fetch_assoc()) {
                                                    $status_color = 'secondary';
                                                    if ($row['status'] == 'Approved')
                                                        $status_color = 'success';
                                                    elseif ($row['status'] == 'Rejected')
                                                        $status_color = 'danger';

                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($row['from_date']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['to_date']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['leave_type']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
                                                    echo "<td>" . htmlspecialchars(date('d-m-Y H:i', strtotime($row['applied_on']))) . "</td>";
                                                    echo '<td><span class="badge badge-' . $status_color . ' p-2">' . htmlspecialchars($row['status']) . '</span></td>';
                                                    echo "<td>" . (!empty($row['rejection_reason']) ? htmlspecialchars($row['rejection_reason']) : 'N/A') . "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo '<tr><td colspan="7" class="text-center">You have not applied for any leave yet.</td></tr>';
                                            }
                                            $stmt_history->close();
                                        }
                                        $conn->close();
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <!-- JavaScript for half-day logic -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');
        const leaveTypeContainer = document.getElementById('leave_type_container');
        const leaveTypeSelect = document.getElementById('leave_type');

        function toggleLeaveTypeVisibility() {
            if (fromDateInput.value && fromDateInput.value === toDateInput.value) {
                leaveTypeContainer.style.display = 'block';
            } else {
                leaveTypeContainer.style.display = 'none';
                leaveTypeSelect.value = 'Full Day';
            }
        }
        fromDateInput.addEventListener('change', function() {
            toDateInput.min = fromDateInput.value;
            if (toDateInput.value < fromDateInput.value) {
                toDateInput.value = fromDateInput.value;
            }
            toggleLeaveTypeVisibility();
        });
        toDateInput.addEventListener('change', toggleLeaveTypeVisibility);
    });
    </script>
</body>

</html>