<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

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

try {
    // --- CORRECTED: Using PDO to get school_id ---
    if ($user_id) {
        $stmt = $conn->prepare('SELECT "school_id" FROM "librarian" WHERE "id" = ?');
        $stmt->execute([$user_id]);
        $school_id = $stmt->fetchColumn();
    }

    if (!$school_id) {
        die("Could not determine the librarian's school. Action denied.");
    }

    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $isbn = trim($_POST['isbn']);
        $publisher = trim($_POST['publisher']);
        $quantity = (int)$_POST['quantity_total'];
        
        if (empty($title)) $errors[] = "Title is required.";
        if (empty($author)) $errors[] = "Author is required.";
        if ($quantity <= 0) $errors[] = "Quantity must be a positive number.";

        if (empty($errors)) {
            // --- CORRECTED: Using PDO to insert the new book ---
            $stmt_insert = $conn->prepare('INSERT INTO "books" (school_id, title, author, isbn, publisher, quantity_total, quantity_available) VALUES (?, ?, ?, ?, ?, ?, ?)');
            if ($stmt_insert->execute([$school_id, $title, $author, $isbn, $publisher, $quantity, $quantity])) {
                header("Location: book_list.php?success=Book added successfully");
                exit();
            } else {
                $errors[] = "Database error: Failed to add book.";
            }
        }
    }
} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
}

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book - School Management System</title>
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
<?php
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Add New Book</h1>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                             <a href="book_list.php" class="btn btn-secondary btn-icon-split btn-sm float-right">
                                <span class="icon text-white-50"><i class="fas fa-arrow-left"></i></span>
                                <span class="text">Back to List</span>
                            </a>
                            <h6 class="m-0 font-weight-bold text-primary">Book Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="title">Title *</label><input type="text" class="form-control" id="title" name="title" required></div>
                                    <div class="form-group col-md-6"><label for="author">Author *</label><input type="text" class="form-control" id="author" name="author" required></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="isbn">ISBN</label><input type="text" class="form-control" id="isbn" name="isbn"></div>
                                    <div class="form-group col-md-6"><label for="publisher">Publisher</label><input type="text" class="form-control" id="publisher" name="publisher"></div>
                                </div>
                                <div class="form-group"><label for="quantity_total">Total Quantity *</label><input type="number" class="form-control" id="quantity_total" name="quantity_total" required min="1"></div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add Book</button>
                            </form>
                        </div>
                    </div>
                </div>
<?php
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
</body>
</html>
<?php
}
$conn = null; 
?>