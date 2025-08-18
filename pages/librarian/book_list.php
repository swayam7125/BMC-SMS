<?php
require_once '../../includes/connect.php';
require_once '../../includes/functions.php';
require_once '../../includes/ajax_helpers.php';
require_once '../../encryption.php';

// Authentication
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'librarian') {
    Response::error('Access denied', 'login.php');
}

// Get search parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Get librarian's school
    if ($user_id) {
        $stmt = $conn->prepare('SELECT "school_id" FROM "librarian" WHERE "id" = ?');
        $stmt->execute([$user_id]);
        $school_id = $stmt->fetchColumn();

        if (!$school_id) {
            Response::error('Could not determine the librarian\'s school', 'login.php');
        }
    }

    // Fetch books
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

    // Generate content
    ob_start();
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
                <form method="GET" action="<?php echo url('pages/librarian/book_list.php'); ?>" class="mb-4" data-ajax-form>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by title, author, ISBN..." 
                               value="<?php echo h($search_query); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit" aria-label="Search">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                            <?php if (!empty($search_query)): ?>
                                <a href="<?php echo url('pages/librarian/book_list.php'); ?>" 
                                   class="btn btn-secondary" 
                                   title="Clear Search" 
                                   aria-label="Clear Search"
                                   data-ajax-link>
                                    <i class="fas fa-times fa-sm"></i>
                                </a>
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
                                               class="btn btn-primary btn-sm" 
                                               title="Edit" 
                                               data-ajax-link>
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-danger btn-sm"
                                                    title="Delete"
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
    function deleteBook(bookId) {
        if (confirm('Are you sure you want to delete this book?')) {
            $.ajax({
                url: '<?php echo url('pages/librarian/delete.php'); ?>',
                method: 'POST',
                data: { 
                    id: bookId,
                    csrf_token: '<?php echo generate_csrf_token(); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.message || 'Error deleting book');
                    }
                },
                error: function() {
                    alert('Error communicating with server');
                }
            });
        }
    }

    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('#dataTable').DataTable({
                "order": [[0, "asc"]],
                "pageLength": 25
            });
        }
    });
    </script>
    <?php
    $content = ob_get_clean();

    // Handle the page request
    handle_page_request([
        'content_file' => __FILE__,
        'title' => 'Book List - BMC-SMS',
        'content' => $content,
        'scripts' => [
            'vendor/datatables/jquery.dataTables.min.js',
            'vendor/datatables/dataTables.bootstrap4.min.js'
        ],
        'styles' => [
            'vendor/datatables/dataTables.bootstrap4.min.css'
        ]
    ]);

} catch (PDOException $e) {
    Response::error("Database Error: " . $e->getMessage());
}

$conn = null;