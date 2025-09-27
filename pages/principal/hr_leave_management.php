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
    die("Database error: " . $e->getMessage());
}

if (!$school_id) {
    die("Error: Could not determine your school.");
}

// Fetch leave applications for the school
$leave_applications = [];
try {
    $stmt = $conn->prepare("
        SELECT la.id, h.hr_name, la.from_date, la.to_date, la.reason, la.leave_type, la.status, la.applied_on 
        FROM hr_leave_applications la
        JOIN hr h ON la.hr_id = h.id
        WHERE h.school_id = ?
        ORDER BY la.applied_on DESC
    ");
    $stmt->execute([$school_id]);
    $leave_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error
    log_interaction($role, $userId, "LEAVE MGMT (HR) ERROR: Failed to fetch leave applications. DB Error: " . $e->getMessage(), $userName);
    die("Database error fetching leave applications: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HR Leave Management</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
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
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">HR Leave Management</h1>
                    <div id="message-container"></div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Leave Applications</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Applied On</th>
                                            <th>HR Name</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Type</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leave_applications as $app): ?>
                                        <tr>
                                            <td><?php echo date('d-m-Y', strtotime($app['applied_on'])); ?></td>
                                            <td><?php echo htmlspecialchars($app['hr_name']); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($app['from_date'])); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($app['to_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($app['leave_type']); ?></td>
                                            <td><?php echo htmlspecialchars($app['reason']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $app['status'] == 'Approved' ? 'success' : ($app['status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                                    <?php echo htmlspecialchars($app['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($app['status'] == 'Pending'): ?>
                                                    <button class="btn btn-success btn-sm action-btn" data-action="Approved" data-id="<?php echo $app['id']; ?>">Approve</button>
                                                    <button class="btn btn-danger btn-sm action-btn" data-action="Rejected" data-id="<?php echo $app['id']; ?>" data-toggle="modal" data-target="#rejectionModal">Reject</button>
                                                <?php endif; ?>
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
    <div class="modal fade" id="rejectionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rejection Reason</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="rejectionForm">
                        <input type="hidden" name="leave_id" id="rejection_leave_id">
                        <input type="hidden" name="status" value="Rejected">
                        <div class="form-group">
                            <label for="rejection_reason">Please provide a reason for rejection:</label>
                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id="submitRejection" class="btn btn-danger">Submit Rejection</button>
                </div>
            </div>
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
            var table = $('#dataTable').DataTable({
                 "order": [[ 0, "desc" ]]
            });
            
            function handleLeaveAction(data) {
                $.ajax({
                    url: 'update_hr_leave_status.php',
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        var messageClass = response.status === 'success' ? 'alert-success' : 'alert-danger';
                        $('#message-container').html('<div class="alert ' + messageClass + '">' + response.message + '</div>');
                        setTimeout(function() {
                            location.reload();
                        }, 1500); 
                    },
                    error: function() {
                        $('#message-container').html('<div class="alert alert-danger">An unexpected error occurred.</div>');
                    }
                });
            }

            $('.action-btn').on('click', function() {
                var action = $(this).data('action');
                var leaveId = $(this).data('id');
                if (action === 'Approved') {
                    if (confirm('Are you sure you want to approve this leave application?')) {
                        handleLeaveAction({ leave_id: leaveId, status: 'Approved' });
                    }
                } else if (action === 'Rejected') {
                    $('#rejection_leave_id').val(leaveId);
                }
            });

            $('#submitRejection').on('click', function() {
                var form = $('#rejectionForm');
                if (form[0].checkValidity()) {
                    handleLeaveAction(form.serialize());
                    $('#rejectionModal').modal('hide');
                } else {
                    form[0].reportValidity();
                }
            });
        });
    </script>
</body>
</html>