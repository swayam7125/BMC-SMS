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

if ($role !== 'librarian') {
    header("Location: ../../login.php");
    exit;
}

$books = [];
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // --- CORRECTED: Using PDO ---
    if ($user_id) {
        $stmt = $conn->prepare('SELECT "school_id" FROM "librarian" WHERE "id" = ?');
        $stmt->execute([$user_id]);
        $school_id = $stmt->fetchColumn();
    }

    if (!$school_id) {
        die("Could not determine the librarian's school. Access denied.");
    }

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

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
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
                    <h1 class="h3 mb-4 text-gray-800">Book List</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">All Books</h6>
                             <a href="add_new_book.php" class="btn btn-primary btn-icon-split btn-sm">
                                <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                                <span class="text">Add New Book</span>
                            </a>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="book_list.php" class="mb-4">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search by title, author, ISBN..." value="<?php echo htmlspecialchars($search_query); ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit" aria-label="Search">
                                            <i class="fas fa-search fa-sm"></i>
                                        </button>
                                        <?php if (!empty($search_query)): ?>
                                            <a href="book_list.php" class="btn btn-secondary" title="Clear Search" aria-label="Clear Search">
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
                                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['quantity_total']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['quantity_available']); ?></td>
                                                    <td>
                                                        <a href="book_edit.php?id=<?php echo $book['book_id']; ?>" class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                                        <a href="delete.php?id=<?php echo $book['book_id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this book?');"><i class="fas fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">
                                                    <?php if (!empty($search_query)): ?>
                                                        No books found matching your search for "<?php echo htmlspecialchars($search_query); ?>".
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
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
<?php $conn = null; ?>