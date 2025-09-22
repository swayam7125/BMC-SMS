<?php
require_once '../includes/connect.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id === 0) {
  header("Location: blog.php");
  exit;
}

try {
  // Fetch the specific blog post
  $stmt = $conn->prepare("SELECT title, content, author_name, created_at, gallery_images FROM blog_posts WHERE id = ?");
  $stmt->execute([$post_id]);
  $post = $stmt->fetch(PDO::FETCH_ASSOC);

  // If no post is found with that ID, redirect to the main blog page
  if (!$post) {
    header("Location: blog.php");
    exit;
  }

  // Decode the JSON string of image paths into a PHP array
  $gallery_images = json_decode($post['gallery_images'] ?? '[]', true);

  // Fetch recent posts for the sidebar, excluding the current one
  $stmt_recent = $conn->prepare("SELECT id, title FROM blog_posts WHERE id != ? ORDER BY created_at DESC LIMIT 5");
  $stmt_recent->execute([$post_id]);
  $recent_posts = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Blog post DB Error: " . $e->getMessage());
  die("A database error occurred. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title><?php echo htmlspecialchars($post['title']); ?> | BMC School</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="css/style.min.css">
</head>

<body>

  <header id="header-section">
    <nav class="navbar navbar-expand-lg pl-3 pl-sm-0" id="navbar">
      <div class="container">
        <div class="navbar-brand-wrapper d-flex w-100"><img src="images/Group2.svg" alt="BMC School Logo"><button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"><span class="mdi mdi-menu navbar-toggler-icon"></span></button></div>
        <div class="collapse navbar-collapse navbar-menu-wrapper" id="navbarSupportedContent">
          <ul class="navbar-nav align-items-lg-center align-items-start ml-auto">
            <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="admission.php">Admissions</a></li>
            <li class="nav-item"><a class="nav-link" href="blog.php">Blog</a></li>
            <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            <li class="nav-item"><a class="nav-link" href="/login.php">Login</a></li>
            <li class="nav-item btn-contact-us pl-4 pl-lg-0"><a class="btn btn-info" href="/signup.php">Sign Up</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <main>
    <div class="content-wrapper">
      <div class="container">
        <section class="blog-post-section py-5">
          <div class="row">
            <div class="col-lg-8">
              <div class="post-header mb-4">
                <h1 class="font-weight-bold"><?php echo htmlspecialchars($post['title']); ?></h1>
                <p class="text-muted">Posted on <?php echo date('F j, Y', strtotime($post['created_at'])); ?> by <?php echo htmlspecialchars($post['author_name']); ?></p>
              </div>

              <?php if (!empty($gallery_images) && count($gallery_images) > 1): ?>
                <div id="blogPostCarousel" class="carousel slide mb-4" data-ride="carousel">
                  <ol class="carousel-indicators">
                    <?php foreach ($gallery_images as $index => $image): ?>
                      <li data-target="#blogPostCarousel" data-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>"></li>
                    <?php endforeach; ?>
                  </ol>
                  <div class="carousel-inner rounded shadow-sm">
                    <?php foreach ($gallery_images as $index => $image): ?>
                      <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($image); ?>" class="d-block w-100" alt="Blog post image <?php echo $index + 1; ?>">
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <a class="carousel-control-prev" href="#blogPostCarousel" role="button" data-slide="prev"><span class="carousel-control-prev-icon"></span><span class="sr-only">Previous</span></a>
                  <a class="carousel-control-next" href="#blogPostCarousel" role="button" data-slide="next"><span class="carousel-control-next-icon"></span><span class="sr-only">Next</span></a>
                </div>
              <?php elseif (!empty($gallery_images) && count($gallery_images) === 1): ?>
                <img src="<?php echo htmlspecialchars($gallery_images[0]); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="img-fluid mb-4 rounded shadow-sm">
              <?php endif; // If no images, this block is skipped entirely 
              ?>

              <article class="blog-content">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
              </article>
            </div>

            <div class="col-lg-4">
              <div class="card shadow-sm mt-5 mt-lg-0">
                <div class="card-body">
                  <h5 class="card-title text-primary">Recent Posts</h5>
                  <ul class="list-unstyled">
                    <?php if (empty($recent_posts)): ?>
                      <li>
                        <p class="text-muted">No other posts yet.</p>
                      </li>
                    <?php else: ?>
                      <?php foreach ($recent_posts as $recent): ?>
                        <li class="mt-2"><a href="blog-post.php?id=<?php echo $recent['id']; ?>"><?php echo htmlspecialchars($recent['title']); ?></a></li>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </main>

  <footer class="border-top mt-5">
    <p class="text-center text-muted pt-4">Copyright © <?php echo date("Y"); ?> BMC School. All rights reserved.</p>
  </footer>

  <script src="vendors/jquery/jquery.min.js"></script>
  <script src="vendors/bootstrap/bootstrap.min.js"></script>
</body>

</html>