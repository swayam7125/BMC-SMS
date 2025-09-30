<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$role = null;
$user_id = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Ensure the user is a teacher
if ($role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$school_id = null;
try {
    $stmt = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
    $stmt->execute([$user_id]);
    $school_id = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

if (!$school_id) {
    die("Could not determine school for the teacher.");
}

$books = [];
try {
    $stmt = $conn->prepare("SELECT book_id, title, author, quantity_available FROM books WHERE school_id = ? ORDER BY title");
    $stmt->execute([$school_id]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("DB Error in browse_books.php (teacher): " . $e->getMessage());
    die("An error occurred while fetching books. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Library Books</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-4 text-gray-800">Browse Library Books</h1>
                    <div id="message-container"></div>
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
                                            <th>Available Copies</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($books as $book): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td><?php echo htmlspecialchars($book['quantity_available']); ?></td>
                                                <td>
                                                    <?php if ($book['quantity_available'] > 0): ?>
                                                        <button class="btn btn-primary btn-sm request-borrow-btn" data-book-id="<?php echo $book['book_id']; ?>" data-toggle="modal" data-target="#borrowModal">
                                                            <i class="fas fa-hand-holding"></i> Request to Borrow
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary btn-sm" disabled>Out of Stock</button>
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
?>        
</div>
        </div>
    </div>

    <div class="modal fade" id="borrowModal" tabindex="-1" role="dialog" aria-labelledby="borrowModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="borrowModalLabel">Request Book</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <form id="borrow-form" action="../../includes/actions/handle_borrow_request_user.php" method="POST">
                        <p>Please select your desired due date for returning the book.</p>
                        <input type="hidden" name="book_id" id="modal_book_id">
                        <input type="hidden" name="borrower_id" value="<?php echo htmlspecialchars($user_id); ?>">
                        <input type="hidden" name="borrower_role" value="<?php echo htmlspecialchars($role); ?>">
                        <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($school_id); ?>">
                        <div class="form-group">
                            <label for="requested_due_date">Requested Due Date</label>
                            <input type="date" class="form-control" id="requested_due_date" name="requested_due_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
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
            $('#dataTable').DataTable();

            $('.request-borrow-btn').on('click', function() {
                var bookId = $(this).data('book-id');
                $('#modal_book_id').val(bookId);
                
                var today = new Date();
                var minDate = new Date(today);
                minDate.setDate(today.getDate() + 1); // Minimum due date is tomorrow
                var maxDate = new Date(today);
                maxDate.setDate(today.getDate() + 15); // Maximum due date is 15 days from now
                
                $('#requested_due_date').attr('min', minDate.toISOString().split('T')[0]);
                $('#requested_due_date').attr('max', maxDate.toISOString().split('T')[0]);
            });

            $('#borrow-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        $('#borrowModal').modal('hide');
                        var messageClass = response.status === 'success' ? 'alert-success' : 'alert-danger';
                        $('#message-container').html('<div class="alert ' + messageClass + '">' + response.message + '</div>');
                    },
                    error: function() {
                         $('#borrowModal').modal('hide');
                         $('#message-container').html('<div class="alert alert-danger">An unexpected error occurred.</div>');
                    }
                });
            });
        });
    </script>
</body>
</html>