<?php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // ADDED: Log system dependency

// --- USER AUTHENTICATION AND DETAILS ---
$hr_user_id = null;
$hr_role = null;
$hr_user_name = 'Unknown';

if (isset($_COOKIE['encrypted_user_id'])) {
    $hr_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (isset($_COOKIE['encrypted_user_role'])) {
    $hr_role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_name'])) {
    $hr_user_name = decrypt_id($_COOKIE['encrypted_user_name']);
}

// Ensure the user is HR personnel
if ($hr_role !== 'hr' || !$hr_user_id) {
    header("Location: ../../login.php");
    exit;
}

// --- LOG PAGE VIEW ---
log_interaction($hr_role, $hr_user_id, 'Viewed the blog management page.', $hr_user_name);
// ---------------------

$school_id = null;
try {
    $stmt = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
    $stmt->execute([$hr_user_id]);
    $school_id = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Database error fetching school ID: " . $e->getMessage());
}

$message = '';
$edit_post = null;

// Handle Add/Edit/Delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete' && isset($_POST['post_id'])) {
            $post_id = filter_var($_POST['post_id'], FILTER_SANITIZE_NUMBER_INT);
            $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ? AND school_id = ?");
            $stmt->execute([$post_id, $school_id]);
            $message = '<div class="alert alert-success">Blog post deleted successfully.</div>';
            
            // --- LOG DELETE ACTION ---
            log_interaction($hr_role, $hr_user_id, "Deleted blog post with ID: {$post_id}.", $hr_user_name);
            // -------------------------

        } elseif ($action === 'add' || $action === 'edit') {
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $post_id = ($action === 'edit') ? filter_var($_POST['post_id'], FILTER_SANITIZE_NUMBER_INT) : null;

            if (empty($title) || empty($content)) {
                $message = '<div class="alert alert-danger">Title and content cannot be empty.</div>';
            } else {
                if ($action === 'add') {
                    $stmt = $conn->prepare("INSERT INTO blog_posts (school_id, author_id, author_name, title, content) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$school_id, $hr_user_id, $hr_user_name, $title, $content]);
                    $new_post_id = $conn->lastInsertId();
                    $message = '<div class="alert alert-success">New blog post added successfully.</div>';
                    
                    // --- LOG ADD ACTION ---
                    log_interaction($hr_role, $hr_user_id, "Added new blog post titled '{$title}' (ID: {$new_post_id}).", $hr_user_name);
                    // ----------------------

                } else { // Edit
                    $stmt = $conn->prepare("UPDATE blog_posts SET title = ?, content = ? WHERE id = ? AND school_id = ?");
                    $stmt->execute([$title, $content, $post_id, $school_id]);
                    $message = '<div class="alert alert-success">Blog post updated successfully.</div>';

                    // --- LOG EDIT ACTION ---
                    log_interaction($hr_role, $hr_user_id, "Updated blog post titled '{$title}' (ID: {$post_id}).", $hr_user_name);
                    // -----------------------
                }
            }
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">A database error occurred.</div>';
        error_log("Blog management error: " . $e->getMessage());

        // --- LOG DATABASE ERROR ---
        $error_log_message = "Failed to {$action} blog post. Error: " . $e->getMessage();
        log_interaction($hr_role, $hr_user_id, $error_log_message, $hr_user_name);
        // --------------------------
    }
}

// Handle fetching post for editing
if (isset($_GET['edit_id'])) {
    $edit_id = filter_var($_GET['edit_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ? AND school_id = ?");
    $stmt->execute([$edit_id, $school_id]);
    $edit_post = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all blog posts for the school
$posts = [];
try {
    $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE school_id = ? ORDER BY created_at DESC");
    $stmt->execute([$school_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error fetching blog posts: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Blog</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage Blog</h1>
                    
                    <?php echo $message; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><?php echo $edit_post ? 'Edit' : 'Add New'; ?> Blog Post</h6>
                        </div>
                        <div class="card-body">
                            <form action="manage_blog.php" method="POST">
                                <input type="hidden" name="action" value="<?php echo $edit_post ? 'edit' : 'add'; ?>">
                                <?php if ($edit_post): ?>
                                    <input type="hidden" name="post_id" value="<?php echo $edit_post['id']; ?>">
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($edit_post['title'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="content">Content</label>
                                    <textarea class="form-control" id="content" name="content" rows="5" required><?php echo htmlspecialchars($edit_post['content'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $edit_post ? 'Update Post' : 'Add Post'; ?></button>
                                <?php if ($edit_post): ?>
                                    <a href="manage_blog.php" class="btn btn-secondary">Cancel Edit</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Published Posts</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="blogTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Date Published</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($posts as $post): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($post['title']); ?></td>
                                            <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                                            <td><?php echo date('d-m-Y H:i', strtotime($post['created_at'])); ?></td>
                                            <td>
                                                <a href="manage_blog.php?edit_id=<?php echo $post['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                                <form action="manage_blog.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>

    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#blogTable').DataTable({
                "order": [[ 2, "desc" ]] // Order by date published descending
            });
        });
    </script>
</body>
</html>