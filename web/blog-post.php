<?php
require_once '../includes/connect.php';
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($post_id === 0) {
  header("Location: blog.php");
  exit;
}
try {
  $stmt = $conn->prepare("SELECT title, content, author_name, created_at, gallery_images FROM blog_posts WHERE id = ?");
  $stmt->execute([$post_id]);
  $post = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$post) {
    header("Location: blog.php");
    exit;
  }
  $gallery_images = json_decode($post['gallery_images'] ?? '[]', true);
  $stmt_recent = $conn->prepare("SELECT id, title FROM blog_posts WHERE id != ? ORDER BY created_at DESC LIMIT 5");
  $stmt_recent->execute([$post_id]);
  $recent_posts = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Database error.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title><?php echo htmlspecialchars($post['title']); ?> | BMC School</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="css/style.min.css">
</head>

<body>
  <?php include 'header.php'; ?>
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
              <?php if (count($gallery_images) > 1): ?>
                <div id="blogPostCarousel" class="carousel slide mb-4" data-ride="carousel">
                  <ol class="carousel-indicators"><?php foreach ($gallery_images as $i => $img) {
                                                    echo '<li data-target="#blogPostCarousel" data-slide-to="' . $i . '" class="' . ($i == 0 ? 'active' : '') . '"></li>';
                                                  } ?></ol>
                  <div class="carousel-inner rounded shadow-sm"><?php foreach ($gallery_images as $i => $img) {
                                                                  echo '<div class="carousel-item ' . ($i == 0 ? 'active' : '') . '"><img src="' . htmlspecialchars($img) . '" class="d-block w-100"></div>';
                                                                } ?></div><a class="carousel-control-prev" href="#blogPostCarousel" role="button" data-slide="prev"><span class="carousel-control-prev-icon"></span></a><a class="carousel-control-next" href="#blogPostCarousel" role="button" data-slide="next"><span class="carousel-control-next-icon"></span></a>
                </div>
              <?php elseif (count($gallery_images) === 1): ?>
                <img src="<?php echo htmlspecialchars($gallery_images[0]); ?>" class="img-fluid mb-4 rounded shadow-sm">
              <?php endif; ?>
              <article class="blog-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></article>
            </div>
            <div class="col-lg-4">
              <div class="card shadow-sm mt-5 mt-lg-0">
                <div class="card-body">
                  <h5 class="card-title text-primary">Recent Posts</h5>
                  <ul class="list-unstyled">
                    <?php if (empty($recent_posts)): ?><li>
                        <p class="text-muted">No other posts yet.</p>
                      </li><?php else: foreach ($recent_posts as $recent): ?>
                        <li class="mt-2"><a href="blog-post.php?id=<?php echo $recent['id']; ?>"><?php echo htmlspecialchars($recent['title']); ?></a></li>
                    <?php endforeach;
                          endif; ?>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </main>
  <?php include 'footer.php'; ?>
  <script src="vendors/jquery/jquery.min.js"></script>
  <script src="vendors/bootstrap/bootstrap.min.js"></script>
</body>

</html>