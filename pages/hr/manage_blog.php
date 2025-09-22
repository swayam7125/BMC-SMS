<?php
require_once '../../includes/connect.php';
require_once '../../encryption.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $author = trim($_POST['author_name']);
    $images = $_FILES['blog_images'];
    $image_paths = [];

    // --- Image Upload Handling ---
    // The target directory inside your 'web' folder
    $upload_dir = '../web/uploads/blog/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!empty($images['name'][0])) {
        foreach ($images['name'] as $key => $name) {
            if ($images['error'][$key] === 0) {
                $tmp_name = $images['tmp_name'][$key];
                // Sanitize filename and create a unique name
                $file_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $new_filename = 'blog_' . time() . '_' . uniqid() . '.' . $file_ext;
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($tmp_name, $destination)) {
                    // Store the web-accessible path, relative to the 'web' folder
                    $image_paths[] = 'uploads/blog/' . $new_filename;
                }
            }
        }
    }

    // The first uploaded image becomes the featured image for the blog list
    $featured_image = $image_paths[0] ?? null;
    // Store all uploaded image paths as a JSON string for the carousel
    $gallery_json = json_encode($image_paths);

    // --- Database Insertion ---
    try {
        $stmt = $conn->prepare(
            "INSERT INTO blog_posts (title, content, author_name, featured_image_path, gallery_images) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$title, $content, $author, $featured_image, $gallery_json]);
        $message = '<div class="alert alert-success mt-3">Blog post created successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger mt-3">Database error: ' . $e->getMessage() . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage Blog Posts</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header">
                <h1 class="h3 text-gray-800">Create New Blog Post</h1>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea name="content" id="content" rows="10" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="author_name">Author Name</label>
                        <input type="text" name="author_name" id="author_name" class="form-control" value="Admin" required>
                    </div>
                    <div class="form-group">
                        <label for="blog_images">Images (Hold Ctrl/Cmd to select multiple)</label>
                        <input type="file" name="blog_images[]" id="blog_images" class="form-control-file" multiple accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary">Publish Post</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>