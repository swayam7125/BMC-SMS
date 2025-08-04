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

if ($user_id) {
    $stmt = $conn->prepare("SELECT school_id FROM librarian WHERE id = ?");
    if ($stmt) {
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

$books = [];
$stmt_books = $conn->prepare("SELECT * FROM books WHERE school_id = ? ORDER BY title");
if ($stmt_books) {
    $stmt_books->bind_param("i", $school_id);
    $stmt_books->execute();
    $result_books = $stmt_books->get_result();
    $books = $result_books->fetch_all(MYSQLI_ASSOC);
    $stmt_books->close();
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
                        <div class="card-header py-3">
                             <a href="add_new_book.php" class="btn btn-primary btn-icon-split btn-sm float-right">
                                <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                                <span class="text">Add New Book</span>
                            </a>
                            <h6 class="m-0 font-weight-bold text-primary">All Books</h6>
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
                                            <th>Type</th>
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
                                                    <td><?php echo $book['is_digital'] ? '<span class="badge badge-info">Digital</span>' : '<span class="badge badge-secondary">Physical</span>'; ?></td>
                                                    <td>
                                                        <a href="edit_book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-warning btn-circle btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                                        <a href="delete_book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-danger btn-circle btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this book?');"><i class="fas fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="7" class="text-center">No books found.</td></tr>
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