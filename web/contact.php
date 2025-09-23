<?php
require_once '../includes/connect.php'; // Adjust path if needed
$message = '';

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = trim($_POST['contactName']);
  $email = trim($_POST['contactEmail']);
  $content = trim($_POST['contactMessage']);

  if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($content)) {
    $message = '<div class="alert alert-danger">Please fill out all fields correctly.</div>';
  } else {
    try {
      $stmt = $conn->prepare("INSERT INTO contact_messages (sender_name, sender_email, message) VALUES (?, ?, ?)");
      $stmt->execute([$name, $email, $content]);
      $message = '<div class="alert alert-success">Thank you for your message! We will get back to you soon.</div>';
    } catch (PDOException $e) {
      $message = '<div class="alert alert-danger">Sorry, there was an error sending your message.</div>';
      error_log("Contact form error: " . $e->getMessage());
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Contact Us | BMC School</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <li class="nav-item"><a class="nav-link" href="contact.php">Contact <span class="sr-only">(current)</span></a></li>
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
        <section class="contact-section py-5">
          <div class="content-header">
            <h2>Get in Touch</h2>
            <h6 class="section-subtitle text-muted">We'd love to hear from you. Please feel free to reach out with any questions.</h6>
          </div>

          <?php echo $message; ?>

          <div class="row">
            <div class="col-lg-6 mb-4 mb-lg-0">
              <div class="card shadow-sm h-100">
                <div class="card-body p-4 p-md-5">
                  <h5 class="mb-4 text-primary">Send Us a Message</h5>
                  <form method="POST" action="contact.php" novalidate>
                    <div class="form-group"><label for="contactName">Your Name *</label><input type="text" name="contactName" class="form-control" id="contactName" required></div>
                    <div class="form-group"><label for="contactEmail">Your Email *</label><input type="email" name="contactEmail" class="form-control" id="contactEmail" required></div>
                    <div class="form-group"><label for="contactMessage">Message *</label><textarea name="contactMessage" class="form-control" id="contactMessage" rows="5" required></textarea></div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                  </form>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card shadow-sm h-100">
                <div class="card-body p-4 p-md-5">
                  <h5 class="mb-4 text-primary">Our Information</h5>
                  <p><strong><i class="mdi mdi-map-marker mr-2"></i>Address:</strong><br><span class="ml-4">123 Education Lane, Knowledge City, 456789</span></p>
                  <p><strong><i class="mdi mdi-phone mr-2"></i>Phone:</strong><br><span class="ml-4">+91 123 456 7890</span></p>
                  <p><strong><i class="mdi mdi-email mr-2"></i>Email:</strong><br><span class="ml-4">info@bmcschool.com</span></p>
                  <p><strong><i class="mdi mdi-clock mr-2"></i>Office Hours:</strong><br><span class="ml-4">Monday - Friday: 8:00 AM - 4:00 PM</span></p>
                </div>
              </div>
            </div>
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
</body>

</html>