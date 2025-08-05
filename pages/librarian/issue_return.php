<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = null;
$user_id = null;
$school_id = null;
$success_message = null;
$error_message = null;

// Check for success or error message cookies
if (isset($_COOKIE['success_message'])) {
    $success_message = $_COOKIE['success_message'];
    // Clear the cookie by setting its expiration time to the past
    setcookie('success_message', '', time() - 3600, "/");
}
if (isset($_COOKIE['error_message'])) {
    $error_message = $_COOKIE['error_message'];
    // Clear the cookie
    setcookie('error_message', '', time() - 3600, "/");
}


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
    if($stmt){
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($librarian_data = $result->fetch_assoc()) {
            $school_id = $librarian_data['school_id'];
        }
        $stmt->close();
    }
}

if (!$school_id) {
    die("Could not determine the librarian's school. Access denied.");
}

// Get available physical books for issuing
$available_books_query = "SELECT book_id, title, author FROM books WHERE school_id = ? AND quantity_available > 0 AND is_digital = 0";
$stmt_avail = $conn->prepare($available_books_query);
$stmt_avail->bind_param("i", $school_id);
$stmt_avail->execute();
$available_books = $stmt_avail->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_avail->close();

// Get currently issued (not returned) books
$issued_books_query = "SELECT br.*, b.title, u.email as borrower_email FROM borrowing_records br JOIN books b ON br.book_id = b.book_id JOIN users u ON br.borrower_id = u.id WHERE b.school_id = ? AND br.is_returned = 0 ORDER BY br.due_date ASC";
$stmt_issued = $conn->prepare($issued_books_query);
$stmt_issued->bind_param("i", $school_id);
$stmt_issued->execute();
$issued_books = $stmt_issued->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_issued->close();

// Get history of returned books
$returned_history_query = "SELECT br.*, b.title, u.email as borrower_email FROM borrowing_records br JOIN books b ON br.book_id = b.book_id JOIN users u ON br.borrower_id = u.id WHERE b.school_id = ? AND br.is_returned = 1 ORDER BY br.return_date DESC";
$stmt_history = $conn->prepare($returned_history_query);
$stmt_history->bind_param("i", $school_id);
$stmt_history->execute();
$returned_history = $stmt_history->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_history->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue & Return Books - School Management System</title>
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
                <h1 class="h3 mb-4 text-gray-800">Issue & Return Books</h1>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4 h-100">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Issue a Book (In-Person)</h6></div>
                            <div class="card-body">
                                <form action="handle_issue.php" method="post">
                                    <div class="form-group">
                                        <label for="book_id">Book *</label>
                                        <select class="form-control" id="book_id" name="book_id" required>
                                            <option value="">-- Select a Book --</option>
                                            <?php foreach ($available_books as $book) {
                                                echo "<option value='{$book['book_id']}'>" . htmlspecialchars($book['title'] . ' by ' . $book['author']) . "</option>";
                                            } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="borrower_role">Borrower Role *</label>
                                        <select class="form-control" id="borrower_role" name="borrower_role" required>
                                            <option value="">-- Select Role --</option>
                                            <option value="student">Student</option>
                                            <option value="teacher">Teacher</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="borrower_id">Borrower *</label>
                                        <select class="form-control" id="borrower_id" name="borrower_id" required>
                                            <option value="">-- First Select a Role --</option>
                                        </select>
                                    </div>
                                    <p class="text-info small">Note: The due date will be automatically set to 14 days from today.</p>
                                    <button type="submit" class="btn btn-success"><i class="fas fa-check-circle"></i> Issue Book</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                         <div class="card shadow mb-4 h-100">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Currently Issued Books</h6></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr><th>Book Title</th><th>Borrower</th><th>Due Date</th><th>Action</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($issued_books)): ?>
                                                <tr><td colspan='4' class='text-center'>No books are currently issued.</td></tr>
                                            <?php else: foreach($issued_books as $ib): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($ib['title']) ?></td>
                                                <td><?= htmlspecialchars($ib['borrower_email']) ?></td>
                                                <td><?= date('d-m-Y', strtotime($ib['due_date'])) ?></td>
                                                <td><a href="handle_return.php?record_id=<?= $ib['record_id']?>" class="btn btn-info btn-sm" onclick="return confirm('Are you sure you want to mark this book as returned?');">Return</a></td>
                                            </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow mt-5 mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Returned Books History</h6></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="returnedHistoryTable">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Borrower Email</th>
                                        <th>Role</th>
                                        <th>Checkout Date</th>
                                        <th>Due Date</th>
                                        <th>Return Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($returned_history)): ?>
                                        <tr><td colspan='6' class='text-center'>No returned book records found.</td></tr>
                                    <?php else: foreach($returned_history as $rh): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($rh['title']) ?></td>
                                        <td><?= htmlspecialchars($rh['borrower_email']) ?></td>
                                        <td><?= ucfirst($rh['borrower_role']) ?></td>
                                        <td><?= date('d-m-Y', strtotime($rh['checkout_date'])) ?></td>
                                        <td><?= date('d-m-Y', strtotime($rh['due_date'])) ?></td>
                                        <td><?= date('d-m-Y', strtotime($rh['return_date'])) ?></td>
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
<script src="../../assets/js/sb-admin-2.min.js"></script>
<script>
$(document).ready(function() {
    $('#borrower_role').on('change', function() {
        var role = $(this).val();
        var borrowerSelect = $('#borrower_id');
        borrowerSelect.html('<option value="">Loading...</option>');

        if (role) {
            $.ajax({
                url: 'get_borrowers.php',
                type: 'GET',
                data: { role: role },
                dataType: 'json',
                success: function(data) {
                    borrowerSelect.html('<option value="">-- Select a Borrower --</option>');
                    if(data.length > 0) {
                        $.each(data, function(key, value) {
                            borrowerSelect.append('<option value="' + value.id + '">' + value.name + ' (' + value.email + ')</option>');
                        });
                    } else {
                        borrowerSelect.html('<option value="">No users found for this role</option>');
                    }
                },
                error: function() {
                    borrowerSelect.html('<option value="">Failed to load data</option>');
                }
            });
        } else {
            borrowerSelect.html('<option value="">-- First Select a Role --</option>');
        }
    });
});
</script>
</body>
</html>