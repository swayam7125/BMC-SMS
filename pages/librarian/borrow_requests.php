<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
*/
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

$success_message = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : null;
$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;

$role = null;
$user_id = null;
$school_id = null;
$requests = [];

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

try {
    if ($user_id) {
        $stmt = $conn->prepare('SELECT "school_id" FROM "librarian" WHERE "id" = ?');
        $stmt->execute([$user_id]);
        $school_id = $stmt->fetchColumn();
    }

    if (!$school_id) {
        die("Access denied.");
    }

    $sql = "SELECT br.request_id, b.title, u.email as borrower_email, br.borrower_role, br.request_date, br.requested_due_date
            FROM borrow_requests br
            JOIN books b ON br.book_id = b.book_id
            JOIN users u ON br.borrower_id = u.id
            WHERE b.school_id = ? AND br.status = 'Pending'
            ORDER BY br.request_date ASC";

    $stmt_requests = $conn->prepare($sql);
    $stmt_requests->execute([$school_id]);
    $requests = $stmt_requests->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
}

$pageTitle = "Book Borrow Requests";
?>

<?php
/*
|--------------------------------------------------------------------------
| RESPONSIVE & PROFESSIONAL FRONTEND (VIEW)
|--------------------------------------------------------------------------
*/
if (!is_ajax_request()):
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title><?php echo htmlspecialchars($pageTitle); ?></title>

        <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
        <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/sidebar.css">
        <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">

        <style>
            .mobile-card-view {
                display: none;
            }

            @media (max-width: 991.98px) {
                .desktop-table {
                    display: none;
                }

                .mobile-card-view {
                    display: block;
                }

                .info-item {
                    display: flex;
                    justify-content: space-between;
                    padding-bottom: 0.5rem;
                    margin-bottom: 0.5rem;
                    border-bottom: 1px solid #e3e6f0;
                }

                .info-item:last-of-type {
                    border-bottom: none;
                    padding-bottom: 0;
                    margin-bottom: 0;
                }
            }
        </style>
    </head>

    <body id="page-top">
        <div id="wrapper">
            <?php include_once '../../includes/sidebar.php'; ?>
            <div id="content-wrapper" class="d-flex flex-column">
                <div id="content">
                    <?php include_once '../../includes/header.php'; ?>
                    <div class="container-fluid">
                        <h1 class="h3 mb-4 text-gray-800">Book Borrow Requests</h1>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Pending Requests</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive desktop-table">
                                    <table class="table table-hover" width="100%" cellspacing="0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Book Title</th>
                                                <th>Requested By</th>
                                                <th>Request Date</th>
                                                <th>Desired Return Date</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($requests)): ?>
                                                <?php foreach ($requests as $request): ?>
                                                    <tr class="align-middle">
                                                        <td><?php echo htmlspecialchars($request['title']); ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($request['borrower_email']); ?>
                                                            <span class="badge badge-secondary ml-1"><?php echo htmlspecialchars(ucfirst($request['borrower_role'])); ?></span>
                                                        </td>
                                                        <td><?php echo date('d-m-Y', strtotime($request['request_date'])); ?></td>
                                                        <td><?php echo date('d-m-Y', strtotime($request['requested_due_date'])); ?></td>
                                                        <td class="text-center">
                                                            <a href="handle_borrow_request.php?action=approve&id=<?php echo $request['request_id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Approve</a>
                                                            <button class="btn btn-danger btn-sm reject-btn" data-toggle="modal" data-target="#rejectModal" data-id="<?php echo $request['request_id']; ?>"><i class="fas fa-times"></i> Reject</button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mobile-card-view">
                                    <?php if (!empty($requests)): ?>
                                        <?php foreach ($requests as $request): ?>
                                            <div class="card shadow-sm mb-3">
                                                <div class="card-header bg-light py-3">
                                                    <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($request['title']); ?></h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="info-item">
                                                        <span class="font-weight-bold">Requested By:</span>
                                                        <span><?php echo htmlspecialchars($request['borrower_email']); ?></span>
                                                    </div>
                                                    <div class="info-item">
                                                        <span class="font-weight-bold">Role:</span>
                                                        <span class="badge badge-secondary"><?php echo htmlspecialchars(ucfirst($request['borrower_role'])); ?></span>
                                                    </div>
                                                    <div class="info-item">
                                                        <span class="font-weight-bold">Request Date:</span>
                                                        <span><?php echo date('d-m-Y', strtotime($request['request_date'])); ?></span>
                                                    </div>
                                                    <div class="info-item">
                                                        <span class="font-weight-bold">Desired Return:</span>
                                                        <span><?php echo date('d-m-Y', strtotime($request['requested_due_date'])); ?></span>
                                                    </div>
                                                </div>
                                                <div class="card-footer d-flex justify-content-end">
                                                    <button class="btn btn-danger btn-sm reject-btn mr-2" data-toggle="modal" data-target="#rejectModal" data-id="<?php echo $request['request_id']; ?>"><i class="fas fa-times"></i> Reject</button>
                                                    <a href="handle_borrow_request.php?action=approve&id=<?php echo $request['request_id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Approve</a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if (empty($requests)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-gray-400"></i>
                                        <p class="mt-3">No pending borrow requests.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include_once '../../includes/footer.php'; ?>
            </div>
        </div>

        <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="handle_borrow_request.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Reject Borrow Request</h5>
                            <button class="close" type="button" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                        </div>
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

        <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
        <?php include_once "../../includes/logout_modal.php" ?>

        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
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
<?php
endif; // End ajax check
$conn = null;
?>