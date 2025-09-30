<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php';

session_start();
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'librarian') {
    header("Location: ../../login.php");
    exit;
}

$book_id_from_url = $_GET['id'] ?? null;
if (!$book_id_from_url) {
    header("Location: book_list.php");
    exit;
}

$book = null;
$categories = [];
$error_msg = '';

try {
    $stmt_cat = $conn->prepare("SELECT category_id, category_name FROM book_categories ORDER BY category_name");
    $stmt_cat->execute();
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = $_POST['title'];
        $author = $_POST['author'];
        $publisher = $_POST['publisher'];
        $isbn = $_POST['isbn'];
        $category_id = $_POST['category_id'];
        $quantity_total = $_POST['quantity_total'];
        $quantity_available = $_POST['quantity_available'];

        if (empty($title) || empty($author) || empty($category_id) || !is_numeric($quantity_total) || !is_numeric($quantity_available)) {
            $error_msg = "Please fill in all fields correctly.";
        } elseif ($quantity_available > $quantity_total) {
            $error_msg = "Available copies cannot be greater than total quantity.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE books SET title = ?, author = ?, publisher = ?, isbn = ?, category_id = ?, quantity_total = ?, quantity_available = ? WHERE book_id = ?"
            );
            $stmt->execute([$title, $author, $publisher, $isbn, $category_id, $quantity_total, $quantity_available, $book_id_from_url]);
            
            $_SESSION['success'] = "Book details updated successfully!";
            log_interaction($role, $userId, "BOOK EDIT: Updated details for Book ID {$book_id_from_url}.", $userName);
            header("Location: book_list.php");
            exit;
        }
    }

    $stmt_book = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
    $stmt_book->execute([$book_id_from_url]);
    $book = $stmt_book->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $_SESSION['error'] = "Book not found.";
        header("Location: book_list.php");
        exit;
    }
} catch (PDOException $e) {
    $error_msg = "Database error: " . $e->getMessage();
    log_interaction($role, $userId, "BOOK EDIT ERROR: " . $e->getMessage(), $userName);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Book</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Edit Book</h1>
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                    <?php endif; ?>
                    <?php if ($book): ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="book_edit.php?id=<?php echo htmlspecialchars($book_id_from_url); ?>" method="post">
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? $book['title'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="author">Author</label>
                                    <input type="text" class="form-control" name="author" value="<?php echo htmlspecialchars($_POST['author'] ?? $book['author'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="publisher">Publisher</label>
                                    <input type="text" class="form-control" name="publisher" value="<?php echo htmlspecialchars($_POST['publisher'] ?? $book['publisher'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="isbn">ISBN Number</label>
                                    <input type="text" class="form-control" name="isbn" value="<?php echo htmlspecialchars($_POST['isbn'] ?? $book['isbn'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="category_id">Category</label>
                                    <select class="form-control" name="category_id" required>
                                        <option value="">Select a category</option>
                                        <?php foreach($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>" <?php echo (($book['category_id']) == $category['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="quantity_total">Total Quantity</label>
                                    <input type="number" class="form-control" name="quantity_total" value="<?php echo htmlspecialchars($_POST['quantity_total'] ?? $book['quantity_total'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="quantity_available">Available Copies</label>
                                    <input type="number" class="form-control" name="quantity_available" value="<?php echo htmlspecialchars($_POST['quantity_available'] ?? $book['quantity_available'] ?? ''); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Book</button>
                                <a href="book_list.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>