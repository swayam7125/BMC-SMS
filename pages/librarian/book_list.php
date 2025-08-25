<?php
// All includes are now at the top. We use require_once to prevent any clashes.
require_once '../../includes/connect.php';
require_once '../../includes/functions.php';
require_once '../../includes/ajax_helpers.php'; // Required for is_ajax_request()
require_once '../../encryption.php';

// --- This is the entire data-fetching logic. It will only run once. ---
$books = [];
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'librarian') {
    // For a non-AJAX request, a simple redirect is fine.
    if (!is_ajax_request()) {
        header("Location: ../../login.php");
        exit;
    }
    // For an AJAX request, send a proper error response.
    Response::error('Access denied', 'login.php');
}

try {
    if ($user_id) {
        $stmt = $conn->prepare('SELECT "school_id" FROM "librarian" WHERE "id" = ?');
        $stmt->execute([$user_id]);
        $school_id = $stmt->fetchColumn();

        if ($school_id) {
            $sql = 'SELECT book_id, title, author, isbn, quantity_total, quantity_available FROM "books" WHERE "school_id" = ?';
            $params = [$school_id];

            if (!empty($search_query)) {
                $sql .= ' AND ("title" ILIKE ? OR "author" ILIKE ? OR "isbn" ILIKE ?)';
                $search_param = "%" . $search_query . "%";
                array_push($params, $search_param, $search_param, $search_param);
            }
            $sql .= ' ORDER BY "title"';

            $stmt_books = $conn->prepare($sql);
            $stmt_books->execute($params);
            $books = $stmt_books->fetchAll(PDO::FETCH_ASSOC);
        } else {
             if (!is_ajax_request()) die("Could not determine the librarian's school.");
             Response::error("Could not determine the librarian's school.");
        }
    }
} catch (PDOException $e) {
    if (!is_ajax_request()) die("Database Error: " . $e->getMessage());
    Response::error("Database Error: " . $e->getMessage());
}
// --- End of data-fetching logic ---


// We now check if this is an AJAX request to decide whether to load the full page template.
// This is the same pattern used in your working add_new_book.php file.
if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book List - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
<?php
} // End of the first is_ajax_request() check.
?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Book List</h1>
                    
                    <?php display_flash_messages(); ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">All Books</h6>
                            <a href="<?php echo url('pages/librarian/add_new_book.php'); ?>" class="btn btn-primary btn-icon-split btn-sm" data-ajax-link>
                                <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                                <span class="text">Add New Book</span>
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>ISBN</th>
                                            <th>Total</th>
                                            <th>Available</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($books)): ?>
                                            <?php foreach ($books as $book): ?>
                                                <tr>
                                                    <td><?php echo h($book['title']); ?></td>
                                                    <td><?php echo h($book['author']); ?></td>
                                                    <td><?php echo h($book['isbn']); ?></td>
                                                    <td><?php echo h($book['quantity_total']); ?></td>
                                                    <td><?php echo h($book['quantity_available']); ?></td>
                                                    <td>
                                                        <a href="<?php echo url('pages/librarian/book_edit.php?id=' . $book['book_id']); ?>" 
                                                           class="btn btn-primary btn-sm" title="Edit" data-ajax-link>
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-danger btn-sm" title="Delete"
                                                                onclick="deleteBook(<?php echo $book['book_id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">
                                                    <?php if (!empty($search_query)): ?>
                                                        No books found matching your search for "<?php echo h($search_query); ?>".
                                                    <?php else: ?>
                                                        No books found.
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                // This script is also part of the main content
                function deleteBook(bookId) {
                    if (confirm('Are you sure you want to delete this book?')) {
                        $.ajax({
                            url: '<?php echo url('pages/librarian/delete.php'); ?>',
                            method: 'POST',
                            data: { 
                                id: bookId,
                                csrf_token: '<?php echo generate_csrf_token(); ?>'
                            },
                            dataType: 'json', // Expect a JSON response
                            success: function(response) {
                                if (response.success) {
                                    // Instead of reloading the whole page, navigate to the book list via AJAX
                                    window.history.pushState({}, '', '<?php echo url('pages/librarian/book_list.php'); ?>');
                                    $('#main-content').load('<?php echo url('pages/librarian/book_list.php'); ?>');
                                } else {
                                    alert(response.message || 'Error deleting book');
                                }
                            },
                            error: function() {
                                alert('Error communicating with the server.');
                            }
                        });
                    }
                }
                </script>
<?php
// This is the closing part of the template, which is skipped for AJAX requests.
if (!is_ajax_request()) {
?>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        // This initializes the DataTable plugin on the table
        $(document).ready(function() {
            if ($.fn.DataTable) {
                $('#dataTable').DataTable({
                    "order": [[0, "asc"]],
                    "pageLength": 25,
                    "destroy": true // Important for AJAX reloads
                });
            }
        });
    </script>
</body>
</html>
<?php
} // End of the second is_ajax_request() check.

$conn = null; 
?>