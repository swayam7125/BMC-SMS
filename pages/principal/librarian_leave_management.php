<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'principal' || !$current_user_id) {
    header("Location: /BMC-SMS/dashboard.php");
    exit;
}

$leave_requests = [];
$leave_history = [];

try {
    // Mark notifications as read
    $stmt_mark_all_read = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND type = 'librarian_leave_request' AND is_read = FALSE");
    $stmt_mark_all_read->execute([$current_user_id]);

    // Fetch pending leave applications
    $query_pending = "SELECT l.id, li.librarian_name, l.from_date, l.to_date, l.leave_type, l.reason, l.applied_on
                      FROM librarian_leave_applications l
                      JOIN librarian li ON l.librarian_id = li.id
                      WHERE l.status = 'Pending'
                      ORDER BY l.applied_on ASC";
    $stmt_pending = $conn->prepare($query_pending);
    $stmt_pending->execute();
    $leave_requests = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

    // Fetch the last 5 approved and rejected leave applications (History)
    $query_history = "SELECT li.librarian_name, l.from_date, l.to_date, l.leave_type, l.reason, l.status, l.rejection_reason
                      FROM librarian_leave_applications l
                      JOIN librarian li ON l.librarian_id = li.id
                      WHERE l.status IN ('Approved', 'Rejected')
                      ORDER BY l.applied_on DESC
                      LIMIT 5";
    $stmt_history = $conn->prepare($query_history);
    $stmt_history->execute();
    $leave_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Principal Librarian Leave Requests Error: " . $e->getMessage());
    die("A database error occurred.");
}

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Librarian Leave Requests</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Pending Applications</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Librarian Name</th>
                                            <th>From Date</th>
                                            <th>To Date</th>
                                            <th>Leave Type</th>
                                            <th>Reason</th>
                                            <th>Applied On</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($leave_requests)): foreach ($leave_requests as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['librarian_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['from_date']); ?></td>
                                                <td><?php echo htmlspecialchars($row['to_date']); ?></td>
                                                <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                                <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($row['applied_on']))); ?></td>
                                                <td>
                                                    <a href="update_librarian_leave_status.php?id=<?php echo $row['id']; ?>&action=approve" class="btn btn-success btn-sm">Approve</a>
                                                    <button type="button" class="btn btn-danger btn-sm reject-btn" data-toggle="modal" data-target="#rejectionModal" data-id="<?php echo $row['id']; ?>">Reject</button>
                                                </td>
                                            </tr>
                                        <?php endforeach;
                                        else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No pending leave requests.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Approved & Rejected Applications</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Librarian Name</th>
                                            <th>From Date</th>
                                            <th>To Date</th>
                                            <th>Leave Type</th>
                                            <th>Reason</th>
                                            <th>Status & Rejection Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($leave_history)): foreach ($leave_history as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['librarian_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['from_date']); ?></td>
                                                <td><?php echo htmlspecialchars($row['to_date']); ?></td>
                                                <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo ($row['status'] == 'Approved') ? 'success' : 'danger'; ?> p-2"><?php echo htmlspecialchars($row['status']); ?></span>
                                                    <?php if ($row['status'] == 'Rejected' && !empty($row['rejection_reason'])): ?>
                                                        <br><small class="text-muted mt-1"><strong>Reason:</strong> <?php echo htmlspecialchars($row['rejection_reason']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach;
                                        else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No processed leave applications found.</td>
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
    <div class="modal fade" id="rejectionModal" tabindex="-1" role="dialog" aria-labelledby="rejectionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectionModalLabel">Reason for Rejection</h5><button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <form action="update_librarian_leave_status.php" method="POST">
                    <div class="modal-body">
                        <p>Please provide a reason for rejecting this leave application.</p>
                        <input type="hidden" name="leave_id" id="leave_id_input">
                        <input type="hidden" name="action" value="reject">
                        <div class="form-group"><label for="rejection_reason_textarea">Rejection Reason</label><textarea class="form-control" id="rejection_reason_textarea" name="rejection_reason" rows="4" required></textarea></div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><button class="btn btn-danger" type="submit">Submit Rejection</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.reject-btn').on('click', function() {
                var leaveId = $(this).data('id');
                $('#leave_id_input').val(leaveId);
            });
        });
    </script>
</body>

</html>
<?php
}
?>