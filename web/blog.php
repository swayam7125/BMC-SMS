<?php
require_once '../includes/connect.php';

try {
  // Fetches the featured_image_path for the main blog grid
  $stmt = $conn->query("SELECT id, title, content, featured_image_path FROM blog_posts ORDER BY created_at DESC");
  $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $posts = [];
  error_log("Blog page DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Blog | BMC School</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/aos/css/aos.css">
  <link rel="stylesheet" href="css/style.min.css">
</head>

<body>

  <header id="header-section">
    <nav class="navbar navbar-expand-lg pl-3 pl-sm-0" id="navbar">
      <div class="container">
        <div class="navbar-brand-wrapper d-flex w-100">
          <img src="images/Group2.svg" alt="BMC School Logo">
          <button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#navbarSupportedContent">
            <span class="mdi mdi-menu navbar-toggler-icon"></span>
          </button>
        </div>
        <div class="collapse navbar-collapse navbar-menu-wrapper" id="navbarSupportedContent">
          <ul class="navbar-nav align-items-lg-center align-items-start ml-auto">
            <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="admission.php">Admissions</a></li>
            <li class="nav-item"><a class="nav-link" href="blog.php">Blog <span class="sr-only">(current)</span></a></li>
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
        <section class="blog-section py-5">
          <div class="content-header">
            <h2>School News & Blog</h2>
            <h6 class="section-subtitle text-muted">The latest updates, stories, and insights from the BMC School community.</h6>
          </div>
          <div class="row">
            <?php if (empty($posts)): ?>
              <div class="col-12">
                <div class="alert alert-info text-center">No blog posts found at this time. Please check back later!</div>
              </div>
            <?php else: ?>
              <?php foreach ($posts as $post): ?>
                <div class="col-12 col-md-6 col-lg-4 stretch-card mb-4" data-aos="zoom-in">
                  <div class="card h-100 shadow-sm">
                    <img class="card-img-top" style="height: 220px; object-fit: cover;" src="<?php echo htmlspecialchars($post['featured_image_path'] ?? 'https://via.placeholder.com/350x220'); ?>" alt="Featured image for <?php echo htmlspecialchars($post['title']); ?>">
                    <div class="card-body d-flex flex-column">
                      <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                      <p class="card-text text-muted"><?php echo htmlspecialchars(substr($post['content'], 0, 120)); ?>...</p>
                      <a href="blog-post.php?id=<?php echo $post['id']; ?>" class="btn btn-info mt-auto">Read More</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>
      </div>
    </div>
  </main>

  <footer class="border-top">
    <p class="text-center text-muted pt-4">Copyright © <?php echo date("Y"); ?> BMC School. All rights reserved.</p>
  </footer>

  <script src="vendors/jquery/jquery.min.js"></script>
  <script src="vendors/bootstrap/bootstrap.min.js"></script>
  <script src="vendors/aos/js/aos.js"></script>
  <script src="js/landingpage.js"></script>
</body>

</html>