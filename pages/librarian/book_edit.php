<?php
include_once '../../includes/connect.php'; // Your PDO connection file
include_once '../../encryption.php';

// --- AUTHENTICATION & AUTHORIZATION ---
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

// Get the librarian's school_id for security
try {
    if ($user_id) {
        $stmt_school = $conn->prepare("SELECT school_id FROM librarian WHERE id = ?");
        $stmt_school->execute([$user_id]);
        $school_id = $stmt_school->fetchColumn();
    }
} catch (PDOException $e) {
    die("Database error fetching user data: " . $e->getMessage());
}


if (!$school_id) {
    header("Location: ../../login.php?error=Could not verify user's school.");
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: book_list.php?error=Invalid book ID provided");
    exit;
}
$book_id = (int)$_GET['id'];
$errors = [];
$book = [];

try {
    // --- FETCH EXISTING BOOK DATA ---
    $stmt_book_fetch = $conn->prepare("SELECT * FROM books WHERE book_id = ? AND school_id = ?");
    $stmt_book_fetch->execute([$book_id, $school_id]);
    $book = $stmt_book_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        header("Location: book_list.php?error=Book not found or access denied.");
        exit;
    }
} catch (PDOException $e) {
    die("Database error while fetching book data: " . $e->getMessage());
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form Data Retrieval
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $quantity_total = filter_var($_POST['quantity_total'], FILTER_VALIDATE_INT);
    $quantity_available = filter_var($_POST['quantity_available'], FILTER_VALIDATE_INT);
    $is_digital = isset($_POST['is_digital']) ? 1 : 0;

    if ($quantity_available > $quantity_total) {
        $errors[] = "Available quantity cannot be greater than the total quantity.";
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            $sql_update = "UPDATE books SET title = ?, author = ?, isbn = ?, quantity_total = ?, quantity_available = ?, is_digital = ? WHERE book_id = ? AND school_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->execute([
                $title, $author, $isbn, $quantity_total, $quantity_available, $is_digital, $book_id, $school_id
            ]);
            $conn->commit();
            header("Location: book_list.php?success=Book updated successfully");
            exit;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $errors[] = "Database update failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Book - School Management System</title>

    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Book</h1>
                        <a href="book_list.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group"><label for="title">Title *</label><input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($book['title'] ?? ''); ?>" required></div>
                                <div class="form-group"><label for="author">Author *</label><input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($book['author'] ?? ''); ?>" required></div>
                                <div class="form-group"><label for="isbn">ISBN</label><input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>"></div>
                                <div class="row">
                                    <div class="col-md-6 form-group"><label for="quantity_total">Total Quantity *</label><input type="number" class="form-control" id="quantity_total" name="quantity_total" value="<?php echo htmlspecialchars($book['quantity_total'] ?? 0); ?>" required></div>
                                    <div class="col-md-6 form-group"><label for="quantity_available">Available Quantity *</label><input type="number" class="form-control" id="quantity_available" name="quantity_available" value="<?php echo htmlspecialchars($book['quantity_available'] ?? 0); ?>" required></div>
                                </div>
                                <hr>
                                <div class="form-group form-check">
                                    <input type="checkbox" class="form-check-input" id="is_digital" name="is_digital" <?php echo !empty($book['is_digital']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_digital">This is a digital book (eBook)</label>
                                </div>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Book</button>
                                    <a href="book_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                                </div>
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

    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>