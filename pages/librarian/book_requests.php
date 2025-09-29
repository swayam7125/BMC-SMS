<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

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
        die("Could not determine your school. Access denied.");
    }

    $sql = 'SELECT br.request_id, br.book_title, br.author, br.reason, br.requester_role, u.email as requester_email 
            FROM "book_requests" br 
            JOIN "users" u ON br.requester_id = u.id 
            WHERE br.school_id = ? AND br.status = \'Pending\' 
            ORDER BY br.created_at ASC';

    $stmt_requests = $conn->prepare($sql);
    $stmt_requests->execute([$school_id]);
    $requests = $stmt_requests->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$pageTitle = "Book Acquisition Requests";
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title><?php echo htmlspecialchars($pageTitle); ?></title>

        <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
        <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/sidebar.css">
        <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
        <link rel="stylesheet" href="../../assets/css/table-to-card.css">
        <link rel="stylesheet" href="../../assets/css/responsive.css" />

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
                        <h1 class="h3 mb-4 text-gray-800">Book Acquisition Requests</h1>

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
                                                <th>Author</th>
                                                <th>Requested By</th>
                                                <th>Reason</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($requests)): ?>
                                                <?php foreach ($requests as $request): ?>
                                                    <tr class="align-middle">
                                                        <td><?php echo htmlspecialchars($request['book_title']); ?></td>
                                                        <td><?php echo htmlspecialchars($request['author']); ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($request['requester_email']); ?>
                                                            <span class="badge badge-secondary ml-1"><?php echo htmlspecialchars(ucfirst($request['requester_role'])); ?></span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($request['reason']); ?></td>
                                                        <td class="text-center">
                                                            <a href="handle_acquisition_request.php?action=approve&id=<?php echo $request['request_id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Approve</a>
                                                            <a href="handle_acquisition_request.php?action=reject&id=<?php echo $request['request_id']; ?>" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Reject</a>
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
                                                    <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($request['book_title']); ?></h6>
                                                    <small class="text-muted">by <?php echo htmlspecialchars($request['author']); ?></small>
                                                </div>
                                                <div class="card-body">
                                                    <div class="info-item">
                                                        <span class="font-weight-bold">Requested By:</span>
                                                        <span><?php echo htmlspecialchars($request['requester_email']); ?></span>
                                                    </div>
                                                    <div class="info-item">
                                                        <span class="font-weight-bold">Role:</span>
                                                        <span class="badge badge-secondary"><?php echo htmlspecialchars(ucfirst($request['requester_role'])); ?></span>
                                                    </div>
                                                    <p class="mt-3"><strong>Reason:</strong> <?php echo htmlspecialchars($request['reason']); ?></p>
                                                </div>
                                                <div class="card-footer d-flex justify-content-end">
                                                    <a href="handle_acquisition_request.php?action=reject&id=<?php echo $request['request_id']; ?>" class="btn btn-danger btn-sm mr-2"><i class="fas fa-times"></i> Reject</a>
                                                    <a href="handle_acquisition_request.php?action=approve&id=<?php echo $request['request_id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Approve</a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if (empty($requests)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-gray-400"></i>
                                        <p class="mt-3">No pending acquisition requests.</p>
                                    </div>
                                <?php endif; ?>
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
        <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
        <?php include_once "../../includes/logout_modal.php" ?>

        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>
        <script src="../../assets/js/responsive-tables.js"></script>

    </body>

    </html>
<?php
$conn = null;
?>