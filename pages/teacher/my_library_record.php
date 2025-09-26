<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if this is an AJAX request
if (is_ajax_request()) {
    // Start output buffering to capture the HTML
    ob_start();
}

$role = null;
$user_id = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$borrowing_records = [];
$error_message = '';

try {
    // Mark notifications as read
    $stmt_mark_all_read = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND type = 'borrow_status' AND is_read = FALSE");
    $stmt_mark_all_read->execute([$user_id]);

    $query = "SELECT br.record_id, b.title, b.author, br.checkout_date, br.due_date, br.return_date, br.is_returned, br.fine_amount, br.fine_status
              FROM borrowing_records br
              JOIN books b ON br.book_id = b.book_id
              WHERE br.borrower_id = ? AND br.borrower_role = ?
              ORDER BY br.checkout_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id, $role]);
    $borrowing_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
    error_log("Teacher My Library Record Error: " . $e->getMessage());
}

$pageTitle = 'My Library Record';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-4 text-gray-800">My Library Borrowing Record</h1>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Borrowing History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>Checkout Date</th>
                                            <th>Due Date</th>
                                            <th>Return Date</th>
                                            <th>Status</th>
                                            <th>Fine</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($borrowing_records)): foreach ($borrowing_records as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['author']); ?></td>
                                                    <td><?php echo date('d M, Y', strtotime($record['checkout_date'])); ?></td>
                                                    <td><?php echo date('d M, Y', strtotime($record['due_date'])); ?></td>
                                                    <td><?php echo $record['return_date'] ? date('d M, Y', strtotime($record['return_date'])) : '-'; ?></td>
                                                    <td>
                                                        <?php if ($record['is_returned']): ?>
                                                            <span class="badge badge-success">Returned</span>
                                                        <?php elseif (strtotime($record['due_date']) < time()): ?>
                                                            <span class="badge badge-danger">Overdue</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning">Borrowed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($record['fine_amount'] > 0): ?>
                                                            <span class="badge badge-danger">₹<?php echo number_format($record['fine_amount'], 2); ?> (<?php echo htmlspecialchars($record['fine_status']); ?>)</span>
                                                        <?php else: ?>
                                                            No Fine
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                        else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No borrowing records found.</td>
                                            </tr>
                                        <?php endif; ?>
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
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [[2, "desc"]] // Order by Checkout Date Descending
            });
        });
    </script>
</body>
<?php
// Add this block at the very end of the file
if (is_ajax_request()) {
    // Get the captured HTML
    $content = ob_get_clean();
    
    // Extract just the main content area for the AJAX response
    if (preg_match('/<div class="container-fluid".*?>(.*?)<\/div>/s', $content, $matches)) {
        echo '<div class="container-fluid">' . $matches[1] . '</div>';
    } else {
        // Fallback if the main container isn't found
        echo $content;
    }
    // Stop the script for AJAX requests
    exit;
}
?>
</html>