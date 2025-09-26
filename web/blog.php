<?php
// --- 1. Safely Include Database Connection ---
@include_once '../includes/connect.php';

// Define the ID of the school to feature
$school_id_to_feature = 4;
// Define the BASE URL for uploaded files on your server (adjust if different)
$base_upload_url = '/BMC-SMS/';

// --- 2. Initialize $school_info (REQUIRED for header.php to work) ---
$school_info = [
    'school_name' => 'Sanskar Bharti Vidyalay', 
    'logo_path' => 'images/Group2.svg' 
];

$posts = []; // Initialize posts array
$conn_exists = isset($conn);

if ($conn_exists) {
    try {
        // Fetch specific school details from the 'school' table for header/footer (ID 4)
        $stmt_school = $conn->prepare("SELECT school_name, school_logo AS logo_path, email, phone, address FROM school WHERE id = :school_id");
        $stmt_school->bindParam(':school_id', $school_id_to_feature, PDO::PARAM_INT);
        $stmt_school->execute();
        $fetched_info = $stmt_school->fetch(PDO::FETCH_ASSOC);

        if ($fetched_info) {
            $school_info = array_merge($school_info, $fetched_info);

            // Logic for logo path (copied from header.php for consistency)
            if (isset($fetched_info['logo_path']) && !empty($fetched_info['logo_path']) && strpos($fetched_info['logo_path'], $base_upload_url) !== 0) {
                $school_info['logo_path'] = $base_upload_url . $fetched_info['logo_path'];
            }
        }
        
        // --- 3. Fetch blog posts ---
        $stmt_blog = $conn->query("SELECT id, title, content, featured_image_path, author_name FROM public.blog_posts ORDER BY created_at DESC");
        $posts = $stmt_blog->fetchAll(PDO::FETCH_ASSOC);

        // --- FINAL FIX: Ensure featured image paths are correctly constructed ---
        foreach ($posts as &$post) {
            if (!empty($post['featured_image_path'])) {
                // Normalize slashes
                $path = str_replace('\\', '/', $post['featured_image_path']);

                // If the path is relative ('images/...' or 'uploads/...'), prepend the base URL.
                if (strpos($path, 'uploads/') === 0 || strpos($path, 'images/') === 0) {
                    $post['featured_image_path'] = $base_upload_url . $path;
                } 
                // If it's already an absolute path starting with the base_upload_url, leave it.
                else if (strpos($path, $base_upload_url) === 0) {
                    $post['featured_image_path'] = $path;
                }
                // Otherwise, use the path as is (this covers the 'images/Group1.png' style paths where the file is in the same directory as web/images)
                // Note: The correct path for "Group1.png" might just be "images/Group1.png" if the web root structure includes an /images/ folder.
                
                // Final safety: if the image starts with 'images/', assume it's relative to the 'web' directory if not already absolute.
                if (strpos($path, 'images/') === 0 && strpos($path, $base_upload_url) === false) {
                     $post['featured_image_path'] = 'images/' . ltrim($path, 'images/');
                }
                
                // Fallback: Use the final path determined above
                if (!isset($post['featured_image_path']) || empty($post['featured_image_path'])) {
                     $post['featured_image_path'] = $path;
                }
            }
        }
        unset($post); 
        // --- END FINAL FIX ---

    } catch (PDOException $e) {
        error_log("DB Error in blog.php: " . $e->getMessage());
    }
}
$pageTitle = 'Blog';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . htmlspecialchars($school_info['school_name']) : htmlspecialchars($school_info['school_name']); ?></title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/owl-carousel/css/owl.carousel.min.css">
  <link rel="stylesheet" href="vendors/owl-carousel/css/owl.theme.default.css">
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
            <h6 class="section-subtitle text-muted">The latest updates, stories, and insights from the <?php echo htmlspecialchars($school_info['school_name']); ?> community.</h6>
          </div>
          <div class="row mt-3">
            <?php if (empty($posts)): ?>
              <div class="col-12">
                <div class="alert alert-info text-center">No blog posts found. Please check back later!</div>
              </div>
            <?php else: ?>
              <?php foreach ($posts as $post): ?>
                <div class="col-12 col-md-6 col-lg-4 stretch-card mb-4" data-aos="zoom-in">
                  <div class="card h-100 shadow-sm">
                    <img class="card-img-top" style="height: 220px; object-fit: cover;" 
                         src="<?php echo htmlspecialchars($post['featured_image_path'] ?? 'https://via.placeholder.com/350x220'); ?>" 
                         alt="Featured image for <?php echo htmlspecialchars($post['title']); ?>">
                    <div class="card-body d-flex flex-column">
                      <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                      <p class="card-text text-muted">Author: <?php echo htmlspecialchars($post['author_name'] ?? 'N/A'); ?></p>
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
  <script src="vendors/owl-carousel/js/owl.carousel.min.js"></script> 
  <script src="vendors/aos/js/aos.js"></script>
  <script src="js/landingpage.js"></script>
</body>

</html>