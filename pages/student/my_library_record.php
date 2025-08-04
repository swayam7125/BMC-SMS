<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = null;
$user_id = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'student') {
    header("Location: ../../login.php");
    exit;
}

// Fetch pending and recent borrow requests
$borrow_requests = [];
$sql_requests = "SELECT br.request_date, br.status, br.rejection_reason, b.title 
                 FROM borrow_requests br
                 JOIN books b ON br.book_id = b.book_id
                 WHERE br.borrower_id = ? ORDER BY br.request_date DESC";
$stmt_requests = $conn->prepare($sql_requests);
$stmt_requests->bind_param("i", $user_id);
$stmt_requests->execute();
$borrow_requests = $stmt_requests->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_requests->close();

// Fetch borrowing history
$borrowing_history = [];
$sql_history = "SELECT b.title, b.author, br.checkout_date, br.due_date, br.return_date, br.fine_amount, br.fine_status 
                FROM borrowing_records br
                JOIN books b ON br.book_id = b.book_id
                WHERE br.borrower_id = ? ORDER BY br.checkout_date DESC";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param("i", $user_id);
$stmt_history->execute();
$borrowing_history = $stmt_history->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_history->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Library Record</title>
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
                    <h1 class="h3 mb-4 text-gray-800">My Library Record</h1>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">My Borrowing Requests</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Request Date</th>
                                            <th>Status</th>
                                            <th>Librarian's Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($borrow_requests)): ?>
                                            <tr><td colspan="4" class="text-center">You have not made any borrowing requests.</td></tr>
                                        <?php else: foreach ($borrow_requests as $request): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($request['title']); ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($request['request_date'])); ?></td>
                                                <td>
                                                    <?php if ($request['status'] == 'Pending'): ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php elseif ($request['status'] == 'Approved'): ?>
                                                        <span class="badge badge-success">Approved</span>
                                                    <?php elseif ($request['status'] == 'Rejected'): ?>
                                                        <span class="badge badge-danger">Rejected</span>
                                                    <?php elseif ($request['status'] == 'Collected'): ?>
                                                        <span class="badge badge-info">Collected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['rejection_reason'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">My Borrowing History</h6>
                        </div>
                        <div class="card-body">
                           <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>Checkout Date</th>
                                            <th>Due Date</th>
                                            <th>Return Date</th>
                                            <th>Fine</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($borrowing_history)): ?>
                                            <tr><td colspan="6" class="text-center">You have no borrowing history.</td></tr>
                                        <?php else: foreach ($borrowing_history as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['title']); ?></td>
                                                <td><?php echo htmlspecialchars($record['author']); ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($record['checkout_date'])); ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($record['due_date'])); ?></td>
                                                <td><?php echo $record['return_date'] ? date('d-m-Y', strtotime($record['return_date'])) : 'Not Returned'; ?></td>
                                                <td>
                                                    <?php if ($record['fine_amount'] > 0): ?>
                                                        ₹<?php echo htmlspecialchars($record['fine_amount']); ?> (<?php echo htmlspecialchars($record['fine_status']); ?>)
                                                    <?php else: ?>
                                                        No Fine
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
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