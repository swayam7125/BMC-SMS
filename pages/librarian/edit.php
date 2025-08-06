<?php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = null;
$user_id = null;

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

$school_id = null;
$book = null;

try {
    // --- CORRECTED: Using PDO ---
    if ($user_id) {
        $stmt_school = $conn->prepare('SELECT "school_id" FROM "librarian" WHERE "id" = ?');
        $stmt_school->execute([$user_id]);
        $school_id = $stmt_school->fetchColumn();
    }

    if (!$school_id) {
        header("Location: ../../login.php?error=Could not verify user's school.");
        exit;
    }

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: book_list.php?error=No book ID specified.");
        exit;
    }
    $book_id = intval($_GET['id']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = $_POST['title'];
        $author = $_POST['author'];
        $isbn = $_POST['isbn'];
        $quantity_total = intval($_POST['quantity_total']);
        $quantity_available = intval($_POST['quantity_available']);
        $is_digital = isset($_POST['is_digital']) ? 1 : 0;

        if ($quantity_available > $quantity_total) {
            $_SESSION['error_message'] = "Available quantity cannot be greater than the total quantity.";
        } else {
            $stmt_update = $conn->prepare('UPDATE "books" SET "title" = ?, "author" = ?, "isbn" = ?, "quantity_total" = ?, "quantity_available" = ?, "is_digital" = ? WHERE "book_id" = ? AND "school_id" = ?');
            if ($stmt_update->execute([$title, $author, $isbn, $quantity_total, $quantity_available, $is_digital, $book_id, $school_id])) {
                $_SESSION['success_message'] = "Book updated successfully!";
                header("Location: book_list.php");
                exit;
            } else {
                $_SESSION['error_message'] = "Error updating book.";
            }
        }
    }

    $stmt_fetch = $conn->prepare('SELECT * FROM "books" WHERE "book_id" = ? AND "school_id" = ?');
    $stmt_fetch->execute([$book_id, $school_id]);
    $book = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $_SESSION['error_message'] = "Error: Book not found or you do not have permission to edit it.";
        header("Location: book_list.php");
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Edit Book</h1>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Update Book Details</h6>
                        </div>
                        <div class="card-body">
                            <form action="edit.php?id=<?php echo $book_id; ?>" method="POST">
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="author">Author</label>
                                    <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="isbn">ISBN</label>
                                    <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="quantity_total">Total Quantity</label>
                                            <input type="number" class="form-control" id="quantity_total" name="quantity_total" value="<?php echo htmlspecialchars($book['quantity_total']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="quantity_available">Available Quantity</label>
                                            <input type="number" class="form-control" id="quantity_available" name="quantity_available" value="<?php echo htmlspecialchars($book['quantity_available']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group form-check">
                                    <input type="checkbox" class="form-check-input" id="is_digital" name="is_digital" <?php if ($book['is_digital']) echo 'checked'; ?>>
                                    <label class="form-check-label" for="is_digital">This is a digital book (eBook)</label>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Book</button>
                                <a href="book_list.php" class="btn btn-secondary">Cancel</a>
                            </form>
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