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

$school_id = null;
try {
    $stmt_school = $conn->prepare("SELECT school_id FROM librarian WHERE id = ?");
    $stmt_school->execute([$userId]);
    $school_id = $stmt_school->fetchColumn();
} catch (PDOException $e) {
    die("Error fetching school ID: " . $e->getMessage());
}

$categories = [];
try {
    $stmt_cat = $conn->prepare("SELECT category_id, category_name FROM book_categories ORDER BY category_name");
    $stmt_cat->execute();
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching categories: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher = $_POST['publisher']; // Publisher is now included
    $isbn = $_POST['isbn'];
    $category_id = $_POST['category_id'];
    $new_category = trim($_POST['new_category']);
    $total_quantity = $_POST['total_quantity'];

    try {
        if (!empty($new_category)) {
            // Check if category already exists
            $stmt_check = $conn->prepare("SELECT category_id FROM book_categories WHERE lower(category_name) = lower(?)");
            $stmt_check->execute([$new_category]);
            $existing_cat = $stmt_check->fetch(PDO::FETCH_ASSOC);
            if ($existing_cat) {
                $category_id = $existing_cat['category_id'];
            } else {
                // Insert new category and get its ID
                $stmt_insert_cat = $conn->prepare("INSERT INTO book_categories (category_name) VALUES (?) RETURNING category_id");
                $stmt_insert_cat->execute([$new_category]);
                $category_id = $stmt_insert_cat->fetchColumn();
            }
        }
        
        // Corrected SQL Query to include all original fields
        $stmt = $conn->prepare(
            "INSERT INTO books (school_id, title, author, publisher, isbn, category_id, quantity_total, quantity_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$school_id, $title, $author, $publisher, $isbn, $category_id, $total_quantity, $total_quantity]);
        
        log_interaction($role, $userId, "ADD BOOK: Added new book '{$title}'.", $userName);
        $_SESSION['success'] = "Book added successfully!";
        header("Location: book_list.php");
        exit;
    } catch (PDOException $e) {
        log_interaction($role, $userId, "ADD BOOK ERROR: " . $e->getMessage(), $userName);
        $error_msg = "Database Error: Could not add the book. " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Book</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Add New Book</h1>
                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label for="author">Author</label>
                                    <input type="text" class="form-control" name="author" required>
                                </div>
                                <div class="form-group">
                                    <label for="publisher">Publisher</label>
                                    <input type="text" class="form-control" name="publisher">
                                </div>
                                <div class="form-group">
                                    <label for="isbn">ISBN Number</label>
                                    <input type="text" class="form-control" name="isbn">
                                </div>
                                <div class="form-group">
                                    <label for="category_id">Category</label>
                                    <select class="form-control" name="category_id">
                                        <option value="">Select a category</option>
                                        <?php foreach($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="new_category">Or Add a New Category</label>
                                    <input type="text" class="form-control" name="new_category" placeholder="e.g., Science Fiction">
                                </div>
                                <div class="form-group">
                                    <label for="total_quantity">Total Quantity</label> 
                                    <input type="number" class="form-control" name="total_quantity" required min="1">
                                </div>
                                <button type="submit" class="btn btn-primary">Add Book</button>
                            </form>
                        </div>
                    </div>
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