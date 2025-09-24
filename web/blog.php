<?php
require_once '../includes/connect.php';
try {
  $stmt = $conn->query("SELECT id, title, content, featured_image_path FROM blog_posts ORDER BY created_at DESC");
  $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $posts = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Blog | BMC School</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/aos/css/aos.css">
  <link rel="stylesheet" href="css/style.min.css">
</head>

<body>
  <?php include 'header.php'; ?>
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
                <div class="alert alert-info text-center">No blog posts found. Please check back later!</div>
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
  <?php include 'footer.php'; ?>
  <script src="vendors/jquery/jquery.min.js"></script>
  <script src="vendors/bootstrap/bootstrap.min.js"></script>
  <script src="vendors/aos/js/aos.js"></script>
  <script src="js/landingpage.js"></script>
</body>

</html>