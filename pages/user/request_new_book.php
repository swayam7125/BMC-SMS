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

// Allow only students and teachers
if ($role !== 'student' && $role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

// Fetch the user's school ID
if ($user_id) {
    $table = ($role === 'student') ? 'student' : 'teacher';
    $stmt = $conn->prepare("SELECT school_id FROM $table WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($userData = $result->fetch_assoc()) {
            $school_id = $userData['school_id'];
        }
        $stmt->close();
    }
}

if (!$school_id) {
    die("Could not determine your school. Action denied.");
}

$success_msg = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_title = trim($_POST['book_title']);
    $author = trim($_POST['author']);
    $reason = trim($_POST['reason']);

    if (empty($book_title)) $errors[] = "Book Title is required.";

    if (empty($errors)) {
        $stmt_insert = $conn->prepare("INSERT INTO book_requests (requester_id, requester_role, school_id, book_title, author, reason) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt_insert) {
            $stmt_insert->bind_param("isssss", $user_id, $role, $school_id, $book_title, $author, $reason);
            if ($stmt_insert->execute()) {
                $success_msg = "Your request has been submitted successfully! The librarian will review it shortly.";
            } else {
                $errors[] = "Database error: Could not submit your request.";
            }
            $stmt_insert->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request a New Book - School Management System</title>
    
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
                    <h1 class="h3 mb-4 text-gray-800">Request a New Book for the Library</h1>
                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success"><?php echo $success_msg; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Book Details</h6>
                        </div>
                        <div class="card-body">
                            <p>If you can't find a book you're looking for, you can request it here. Please provide as much detail as possible.</p>
                            <form method="POST">
                                <div class="form-group">
                                    <label for="book_title">Book Title *</label>
                                    <input type="text" class="form-control" id="book_title" name="book_title" required>
                                </div>
                                <div class="form-group">
                                    <label for="author">Author</label>
                                    <input type="text" class="form-control" id="author" name="author">
                                </div>
                                <div class="form-group">
                                    <label for="reason">Reason for Request</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="e.g., Required for project, good for learning, etc."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Request</button>
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
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>