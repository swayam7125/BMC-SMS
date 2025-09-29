<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
include_once '../../includes/log_system.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$role = null;
$user_id = null;
$school_id = null;
$acting_user_name = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}
$acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'Librarian';

if ($role !== 'librarian') {
    header("Location: ../../login.php");
    exit;
}

try {
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

        if (empty($title)) $errors[] = "Book title is required.";
        if (empty($author)) $errors[] = "Author name is required.";
        if ($quantity <= 0) $errors[] = "Quantity must be at least 1.";

        if (empty($errors)) {
            $sql = "INSERT INTO books (school_id, title, author, isbn, publisher, quantity_total, quantity_available) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$school_id, $title, $author, $isbn, $publisher, $quantity, $quantity]);

            // ⭐ LOGGING: Log the asset creation action
            $log_message = "ASSET CREATION: Added {$quantity} copies of book: '{$title}' (ISBN: {$isbn}).";
            log_interaction($role, $user_id, $log_message, $acting_user_name);

            header("Location: book_list.php?success=Book '" . urlencode($title) . "' was added successfully.");
            exit;
        }
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$pageTitle = "Add New Book";
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
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/responsive.css" />

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
                        <h1 class="h3 mb-0 text-gray-800">Add New Book</h1>
                        <a href="book_list.php" class="btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Book List
                        </a>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-plus-circle mr-2"></i>New Book Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="title">Book Title <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-book"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g., The Great Gatsby" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="author">Author <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-edit"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="author" name="author" placeholder="e.g., F. Scott Fitzgerald" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="isbn">ISBN (Optional)</label>
                                        <input type="text" class="form-control" id="isbn" name="isbn" placeholder="e.g., 978-0743273565">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="publisher">Publisher (Optional)</label>
                                        <input type="text" class="form-control" id="publisher" name="publisher" placeholder="e.g., Scribner">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="quantity_total">Total Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="quantity_total" name="quantity_total" required min="1" placeholder="e.g., 10">
                                    <small class="form-text text-muted">Enter the total number of copies for this book.</small>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Save Book</button>
                                </div>
                            </form>
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
    <script src="../../assets/js/responsive-tables.js"></script>
</body>

</html>
<?php
$conn = null;
?>