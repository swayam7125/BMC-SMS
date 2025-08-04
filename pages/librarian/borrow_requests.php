<?php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = null;
$user_id = null;
$school_id = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'librarian') {
    header("Location: ../../login.php");
    exit;
}

if ($user_id) {
    $stmt = $conn->prepare("SELECT school_id FROM librarian WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $school_id = $stmt->get_result()->fetch_assoc()['school_id'];
    $stmt->close();
}

if (!$school_id) {
    die("Access denied.");
}

$requests = [];
$sql = "SELECT br.request_id, b.title, u.email as borrower_email, br.borrower_role, br.request_date, br.status, br.action_date, br.requested_due_date, l_user.email as librarian_email
        FROM borrow_requests br 
        JOIN books b ON br.book_id = b.book_id 
        JOIN users u ON br.borrower_id = u.id 
        LEFT JOIN users l_user ON br.librarian_id = l_user.id
        WHERE br.school_id = ? 
        ORDER BY FIELD(br.status, 'Pending', 'Approved', 'Rejected', 'Collected'), br.request_date DESC";
$stmt_req = $conn->prepare($sql);
$stmt_req->bind_param("i", $school_id);
$stmt_req->execute();
$requests = $stmt_req->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_req->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Borrowing Requests</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
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
                    <h1 class="h3 mb-4 text-gray-800">Book Borrowing Requests</h1>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success_message']; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error_message']; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Request History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Requester</th>
                                            <th>Request Date</th>
                                            <th>Requested Due Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($requests)): ?>
                                            <?php foreach ($requests as $req): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($req['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($req['borrower_email']); ?> (<?php echo ucfirst(htmlspecialchars($req['borrower_role'])); ?>)</td>
                                                    <td><?php echo date('d-m-Y', strtotime($req['request_date'])); ?></td>
                                                    <td><?php echo date('d-m-Y', strtotime($req['requested_due_date'])); ?></td>
                                                    <td>
                                                        <?php if ($req['status'] == 'Pending') echo '<span class="badge badge-warning">Pending</span>'; ?>
                                                        <?php if ($req['status'] == 'Approved') echo '<span class="badge badge-success">Approved</span>'; ?>
                                                        <?php if ($req['status'] == 'Rejected') echo '<span class="badge badge-danger">Rejected</span>'; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($req['status'] == 'Pending'): ?>
                                                            <a href="handle_borrow_request.php?action=approve&id=<?php echo $req['request_id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve request and issue book?');">Approve</a>
                                                            <button type="button" class="btn btn-danger btn-sm reject-btn" data-toggle="modal" data-target="#rejectionModal" data-id="<?php echo $req['request_id']; ?>">Reject</button>
                                                        <?php else: ?>
                                                            <small class="text-muted">Processed on <?php echo date('d-m-Y', strtotime($req['action_date'])); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center">No borrowing requests found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="rejectionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reason for Rejection</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <form action="handle_borrow_request.php" method="POST">
                    <div class="modal-body">
                        <p>Please provide a reason for rejecting this request.</p>
                        <input type="hidden" name="request_id" id="modal_request_id">
                        <input type="hidden" name="action" value="reject">
                        <div class="form-group">
                            <textarea class="form-control" name="rejection_reason" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button class="btn btn-danger" type="submit">Submit Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include_once "../../includes/logout_modal.php"?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.reject-btn').on('click', function() {
            var requestId = $(this).data('id');
            $('#modal_request_id').val(requestId);
        });
    });
    </script>
</body>
</html>