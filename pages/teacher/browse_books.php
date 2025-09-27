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
$school_id = null;
$books = [];

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

try {
    if ($user_id) {
        // PDO Change: Converted to PDO
        $stmt = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
        $stmt->execute([$user_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $school_id = $data['school_id'];
        }
    }

    if (!$school_id) {
        die("Could not determine your school. Access denied.");
    }

    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

    // PostgreSQL Change: Using ILIKE for case-insensitive search
    $sql = "SELECT book_id, title, author, isbn, publisher FROM books WHERE school_id = ? AND quantity_available > 0";
    $params = [$school_id];

    if (!empty($search_query)) {
        $sql .= " AND (title ILIKE ? OR author ILIKE ? OR publisher ILIKE ?)";
        $search_param = "%" . $search_query . "%";
        array_push($params, $search_param, $search_param, $search_param);
    }
    $sql .= " ORDER BY title";

    // PDO Change: Converted to PDO
    $stmt_books = $conn->prepare($sql);
    $stmt_books->execute($params);
    $books = $stmt_books->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("DB Error in browse_books.php (teacher): " . $e->getMessage());
    die("An error occurred while fetching books. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Browse & Request Books</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Browse & Request Books</h1>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_GET['success']); ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_GET['error']); ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Available Books</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="browse_books.php" class="mb-4">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search by title, author, publisher..." value="<?php echo htmlspecialchars($search_query); ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit" aria-label="Search"><i class="fas fa-search fa-sm"></i></button>
                                        <?php if (!empty($search_query)): ?>
                                            <a href="browse_books.php" class="btn btn-secondary" title="Clear Search" aria-label="Clear Search"><i class="fas fa-times fa-sm"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
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
                                        <?php if (!empty($books)): foreach ($books as $book): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['publisher']); ?></td>
                                                    <td><button type="button" class="btn btn-primary btn-sm request-btn" data-toggle="modal" data-target="#requestModal" data-book-id="<?php echo $book['book_id']; ?>" data-book-title="<?php echo htmlspecialchars($book['title']); ?>"><i class="fas fa-hand-holding-hand"></i> Request to Borrow</button></td>
                                                </tr>
                                            <?php endforeach;
                                        else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center"><?php echo !empty($search_query) ? 'No books found matching your search.' : 'No books are currently available.'; ?></td>
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
    <div class="modal fade" id="requestModal" tabindex="-1" role="dialog" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestModalLabel">Request to Borrow Book</h5><button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
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
                    <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Submit Request</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var today = new Date().toISOString().split('T')[0];
            $('#requested_due_date').attr('min', today);
            $('.request-btn').on('click', function() {
                $('#modal_book_id').val($(this).data('book-id'));
                $('#modal_book_title').text($(this).data('book-title'));
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