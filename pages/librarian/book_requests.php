<?php
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
    $result = $stmt->get_result();
    if ($data = $result->fetch_assoc()) {
        $school_id = $data['school_id'];
    }
    $stmt->close();
}

if (!$school_id) {
    die("Could not determine your school. Access denied.");
}

// Fetch pending book acquisition requests for the school from all roles
$requests = [];
$sql = "SELECT br.request_id, br.book_title, br.author, br.reason, br.requester_role, u.email as requester_email 
        FROM book_requests br 
        JOIN users u ON br.requester_id = u.id 
        WHERE br.school_id = ? AND br.status = 'Pending'
        ORDER BY br.created_at ASC";
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
    <title>Book Acquisition Requests</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Book Acquisition Requests</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Pending New Book Requests</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>Requester (Role)</th>
                                            <th>Reason</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($requests)): ?>
                                            <?php foreach ($requests as $request): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($request['book_title']); ?></td>
                                                    <td><?php echo htmlspecialchars($request['author']); ?></td>
                                                    <td><?php echo htmlspecialchars($request['requester_email'] . ' (' . ucfirst($request['requester_role']) . ')'); ?></td>
                                                    <td><?php echo htmlspecialchars($request['reason']); ?></td>
                                                    <td>
                                                        <a href="handle_acquisition_request.php?action=approve&id=<?php echo $request['request_id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Approve</a>
                                                        <a href="handle_acquisition_request.php?action=reject&id=<?php echo $request['request_id']; ?>" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Reject</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center">No pending acquisition requests.</td></tr>
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
    <?php include_once "../../includes/logout_modal.php"?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>