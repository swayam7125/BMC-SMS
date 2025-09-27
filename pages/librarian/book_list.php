<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
|
| This section handles all server-side operations:
| 1. Includes necessary files and authorizes the user.
| 2. Fetches the list of books from the database for the librarian's school.
|
*/

// Core Includes
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

// --- Authorization ---
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

if ($role !== 'librarian' || !$user_id) {
    header("Location: ../../login.php");
    exit;
}

try {
    // --- Data Fetching ---
    // Get the librarian's school_id to ensure they only see their school's books
    $stmt_school = $conn->prepare("SELECT school_id FROM librarian WHERE id = ?");
    $stmt_school->execute([$user_id]);
    $school_id = $stmt_school->fetchColumn();

    if ($school_id) {
        $stmt_books = $conn->prepare("SELECT * FROM books WHERE school_id = ? ORDER BY title ASC");
        $stmt_books->execute([$school_id]);
        $books = $stmt_books->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$pageTitle = "Book List";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/table-to-card.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <style>
        .mobile-card-view {
            display: none;
        }

        /* Hide mobile cards by default */

        /* Responsive Breakpoint for Tablets and below */
        @media (max-width: 991.98px) {
            .desktop-table {
                display: none;
            }

            /* Hide table on small screens */
            .mobile-card-view {
                display: block;
            }

            /* Show cards on small screens */
            .info-item {
                display: flex;
                justify-content: space-between;
                padding-bottom: 0.5rem;
                margin-bottom: 0.5rem;
                border-bottom: 1px solid #e3e6f0;
            }

            .info-item:last-of-type {
                border-bottom: none;
                padding-bottom: 0;
                margin-bottom: 0;
            }
        }
    </style>
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Book Management</h1>
                        <a href="add_new_book.php" class="btn btn-primary shadow-sm">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Book
                        </a>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Library Book List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive desktop-table">
                                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>ISBN</th>
                                            <th class="text-center">Total</th>
                                            <th class="text-center">Available</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($books as $book): ?>
                                            <tr class="align-middle">
                                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($book['quantity_total']); ?></td>
                                                <td class="text-center font-weight-bold <?php echo ($book['quantity_available'] > 0) ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo htmlspecialchars($book['quantity_available']); ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="book_edit.php?id=<?php echo $book['book_id']; ?>" class="btn btn-info btn-sm" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mobile-card-view">
                                <?php if (!empty($books)): ?>
                                    <?php foreach ($books as $book): ?>
                                        <div class="card shadow-sm mb-3">
                                            <div class="card-header bg-light py-3">
                                                <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($book['title']); ?></h6>
                                                <small class="text-muted">by <?php echo htmlspecialchars($book['author']); ?></small>
                                            </div>
                                            <div class="card-body">
                                                <div class="info-item">
                                                    <span class="font-weight-bold">ISBN:</span>
                                                    <span><?php echo htmlspecialchars($book['isbn'] ?: 'N/A'); ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="font-weight-bold">Total Copies:</span>
                                                    <span><?php echo htmlspecialchars($book['quantity_total']); ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="font-weight-bold">Available:</span>
                                                    <span class="font-weight-bold <?php echo ($book['quantity_available'] > 0) ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo htmlspecialchars($book['quantity_available']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-footer text-right">
                                                <a href="book_edit.php?id=<?php echo $book['book_id']; ?>" class="btn btn-info btn-icon-split btn-sm">
                                                    <span class="icon text-white-50"><i class="fas fa-edit"></i></span>
                                                    <span class="text">Edit</span>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-book-dead fa-3x text-gray-400"></i>
                                        <p class="mt-3">No books found in the library. <a href="add_new_book.php">Add the first one!</a></p>
                                    </div>
                                <?php endif; ?>
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
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable for the desktop table
            if ($.fn.DataTable) {
                $('#dataTable').DataTable({
                    "order": [
                        [0, "asc"]
                    ], // Sort by title
                    "pageLength": 25
                });
            }
        });
    </script>
</body>

</html>
<?php
$conn = null;
?>