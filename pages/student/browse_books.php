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

if ($role !== 'student') {
    header("Location: ../../login.php");
    exit;
}

if ($user_id) {
    $stmt = $conn->prepare("SELECT school_id FROM student WHERE id = ?");
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

$books = [];
$sql = "SELECT book_id, title, author, isbn, publisher FROM books WHERE school_id = ? AND quantity_available > 0 ORDER BY title";
$stmt_books = $conn->prepare($sql);
$stmt_books->bind_param("i", $school_id);
$stmt_books->execute();
$books = $stmt_books->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_books->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse & Request Books</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Browse & Request Books</h1>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Available Books</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Publisher</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($books)): ?>
                                            <?php foreach ($books as $book): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['publisher']); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-primary btn-sm request-btn" 
                                                                data-toggle="modal" 
                                                                data-target="#requestModal" 
                                                                data-book-id="<?php echo $book['book_id']; ?>" 
                                                                data-book-title="<?php echo htmlspecialchars($book['title']); ?>">
                                                            <i class="fas fa-hand-holding-hand"></i> Request to Borrow
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center">No books are currently available in the library.</td></tr>
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

    <div class="modal fade" id="requestModal" tabindex="-1" role="dialog" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestModalLabel">Request to Borrow Book</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <form action="../../includes/actions/handle_borrow_request_user.php" method="POST">
                    <div class="modal-body">
                        <p>You are requesting to borrow: <strong id="modal_book_title"></strong></p>
                        <input type="hidden" name="book_id" id="modal_book_id">
                        <div class="form-group">
                            <label for="requested_due_date">Desired Return Date *</label>
                            <input type="date" class="form-control" id="requested_due_date" name="requested_due_date" required>
                            <small class="form-text text-muted">Please select your preferred return date. The librarian will confirm the final due date upon approval.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">Submit Request</button>
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
        // Set min date for date picker to today
        var today = new Date().toISOString().split('T')[0];
        $('#requested_due_date').attr('min', today);

        // Populate modal with book info when "Request" button is clicked
        $('.request-btn').on('click', function() {
            var bookId = $(this).data('book-id');
            var bookTitle = $(this).data('book-title');
            $('#modal_book_id').val(bookId);
            $('#modal_book_title').text(bookTitle);
        });
    });
    </script>
</body>
</html>