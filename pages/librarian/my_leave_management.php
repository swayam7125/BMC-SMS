<?php
// --- Includes & Setup ---
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

$message = '';
$librarian_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$librarian_name = 'N/A';
$leave_history = [];

try {
    if ($librarian_id) {
        // Fetch librarian's name
        $stmt_user = $conn->prepare("SELECT librarian_name FROM librarian WHERE id = ?");
        $stmt_user->execute([$librarian_id]);
        $librarian_name = $stmt_user->fetchColumn();

        // Handle form submission
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // ... (Your existing solid form handling logic) ...
            $from_date = $_POST['from_date'];
            $to_date = $_POST['to_date'];
            $reason = trim($_POST['reason']);
            $leave_type = $_POST['leave_type'] ?? 'Full Day';

            if (empty($from_date) || empty($to_date) || empty($reason)) {
                $message = '<div class="alert alert-danger">All fields are required.</div>';
            } else {
                $conn->beginTransaction();
                $stmt_insert = $conn->prepare("INSERT INTO librarian_leave_applications (librarian_id, from_date, to_date, reason, leave_type) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert->execute([$librarian_id, $from_date, $to_date, $reason, $leave_type]);

                // Notify Principal
                $stmt_principal = $conn->prepare("SELECT p.id FROM principal p JOIN librarian l ON p.school_id = l.school_id WHERE l.id = ?");
                $stmt_principal->execute([$librarian_id]);
                if ($principal_data = $stmt_principal->fetch(PDO::FETCH_ASSOC)) {
                    $notification_message = "New leave request from " . htmlspecialchars($librarian_name);
                    $link = "pages/principal/librarian_leave_management.php";
                    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, 'librarian_leave_request')");
                    $stmt_notify->execute([$principal_data['id'], $notification_message, $link]);
                }
                $conn->commit();
                $message = '<div class="alert alert-success">Leave application submitted successfully!</div>';
            }
        }

        // Fetch Leave History
        $stmt_history = $conn->prepare("SELECT * FROM librarian_leave_applications WHERE librarian_id = ? ORDER BY applied_on DESC");
        $stmt_history->execute([$librarian_id]);
        $leave_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    $message = '<div class="alert alert-danger">An error occurred: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Librarian Leave Management</title>
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
        <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
        <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/sidebar.css">
        <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
        <link rel="stylesheet" href="../../assets/css/table-to-card.css">
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
                        <h1 class="h3 mb-4 text-gray-800">My Leave Management</h1>
                        <?php echo $message; ?>
                        <div class="row">
                            <div class="col-lg-5 mb-4">
                                <div class="card shadow h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Apply for Leave</h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <div class="form-group"><label>Name</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($librarian_name); ?>" readonly></div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group"><label for="from_date">From Date *</label><input type="date" class="form-control" id="from_date" name="from_date" required></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group"><label for="to_date">To Date *</label><input type="date" class="form-control" id="to_date" name="to_date" required></div>
                                                </div>
                                            </div>
                                            <div class="form-group" id="leave_type_container" style="display: none;"><label for="leave_type">Leave Type</label><select class="form-control" id="leave_type" name="leave_type">
                                                    <option value="Full Day">Full Day</option>
                                                    <option value="First Half">First Half</option>
                                                    <option value="Second Half">Second Half</option>
                                                </select></div>
                                            <div class="form-group"><label for="reason">Reason for Leave *</label><textarea class="form-control" id="reason" name="reason" rows="4" required></textarea></div>
                                            <button type="submit" class="btn btn-primary">Submit Application</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7 mb-4">
                                <div class="card shadow h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">My Application History</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover" id="leaveHistoryTable" width="100%">
                                                <thead>
                                                    <tr>
                                                        <th>From</th>
                                                        <th>To</th>
                                                        <th>Type</th>
                                                        <th>Status</th>
                                                        <th>Remark</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($leave_history as $row): ?>
                                                        <tr>
                                                            <td><?php echo date('d-m-Y', strtotime($row['from_date'])); ?></td>
                                                            <td><?php echo date('d-m-Y', strtotime($row['to_date'])); ?></td>
                                                            <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                                            <td><?php $s_color = 'secondary';
                                                                if ($row['status'] == 'Approved') $s_color = 'success';
                                                                elseif ($row['status'] == 'Rejected') $s_color = 'danger';
                                                                echo '<span class="badge badge-' . $s_color . '">' . htmlspecialchars($row['status']) . '</span>'; ?></td>
                                                            <td><?php echo htmlspecialchars($row['rejection_reason'] ?? 'N/A'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
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
        <?php include_once "../../includes/logout_modal.php" ?>
        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>
        <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                $('#leaveHistoryTable').DataTable({
                    "responsive": true,
                    "autoWidth": false,
                    "order": []
                });

                const fromDate = document.getElementById('from_date');
                const toDate = document.getElementById('to_date');
                const leaveTypeContainer = document.getElementById('leave_type_container');

                const today = new Date().toISOString().split('T')[0];
                fromDate.setAttribute('min', today);
                toDate.setAttribute('min', today);

                function toggleLeaveType() {
                    leaveTypeContainer.style.display = (fromDate.value && fromDate.value === toDate.value) ? 'block' : 'none';
                }

                fromDate.addEventListener('change', function() {
                    toDate.min = fromDate.value;
                    if (new Date(toDate.value) < new Date(fromDate.value)) {
                        toDate.value = fromDate.value;
                    }
                    toggleLeaveType();
                });
                toDate.addEventListener('change', toggleLeaveType);
                toggleLeaveType();
            });
        </script>
    </body>

    </html>
