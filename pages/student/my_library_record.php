<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$role = null;
$user_id = null;
$borrow_requests = [];
$borrowing_history = [];

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'student' || !$user_id) {
    header("Location: ../../login.php");
    exit;
}

try {
    // PDO Change: Converted to PDO
    // Fetch pending and recent borrow requests
    $sql_requests = "SELECT br.request_date, br.status, br.rejection_reason, b.title 
                     FROM borrow_requests br
                     JOIN books b ON br.book_id = b.book_id
                     WHERE br.borrower_id = ? ORDER BY br.request_date DESC";
    $stmt_requests = $conn->prepare($sql_requests);
    $stmt_requests->execute([$user_id]);
    $borrow_requests = $stmt_requests->fetchAll(PDO::FETCH_ASSOC);

    // Fetch borrowing history
    $sql_history = "SELECT b.title, b.author, br.checkout_date, br.due_date, br.return_date, br.fine_amount, br.fine_status 
                    FROM borrowing_records br
                    JOIN books b ON br.book_id = b.book_id
                    WHERE br.borrower_id = ? ORDER BY br.checkout_date DESC";
    $stmt_history = $conn->prepare($sql_history);
    $stmt_history->execute([$user_id]);
    $borrowing_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Library Record Error: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

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
    <link rel="stylesheet" href="../../assets/css/responsive.css" />

</head>

<body id="page-top">
    <div id="wrapper">
        <?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?> <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?> <div class="container-fluid">
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
                                        <tr>
                                            <td colspan="4" class="text-center">You have not made any borrowing
                                                requests.</td>
                                        </tr>
                                        <?php else: foreach ($borrow_requests as $request): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['title']); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($request['request_date'])); ?></td>
                                            <td>
                                                <?php
                                                        $status = htmlspecialchars($request['status']);
                                                        $badge_class = 'badge-secondary';
                                                        if ($status == 'Pending') $badge_class = 'badge-warning';
                                                        if ($status == 'Approved') $badge_class = 'badge-success';
                                                        if ($status == 'Rejected') $badge_class = 'badge-danger';
                                                        if ($status == 'Collected') $badge_class = 'badge-info';
                                                        echo "<span class='badge {$badge_class}'>{$status}</span>";
                                                        ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($request['rejection_reason'] ?? 'N/A'); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach;
                                        endif; ?>
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
                                        <tr>
                                            <td colspan="6" class="text-center">You have no borrowing history.</td>
                                        </tr>
                                        <?php else: foreach ($borrowing_history as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['title']); ?></td>
                                            <td><?php echo htmlspecialchars($record['author']); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($record['checkout_date'])); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($record['due_date'])); ?></td>
                                            <td><?php echo $record['return_date'] ? date('d-m-Y', strtotime($record['return_date'])) : 'Not Returned'; ?>
                                            </td>
                                            <td>
                                                <?php if ($record['fine_amount'] > 0): ?>
                                                ₹<?php echo htmlspecialchars(number_format($record['fine_amount'], 2)); ?>
                                                (<?php echo htmlspecialchars($record['fine_status']); ?>)
                                                <?php else: ?>
                                                No Fine
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach;
                                        endif; ?>
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
?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>

</body>

</html>