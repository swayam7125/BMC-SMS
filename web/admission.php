<?php
require_once '../includes/connect.php'; // Adjust path if needed
$message = '';

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $student_name = trim($_POST['studentName']);
  $student_dob = trim($_POST['studentDOB']);
  $grade = trim($_POST['admissionGrade']);
  $parent_name = trim($_POST['parentName']);
  $parent_email = trim($_POST['parentEmail']);
  $parent_phone = trim($_POST['parentPhone']);

  if (empty($student_name) || empty($student_dob) || empty($grade) || empty($parent_name) || empty($parent_email) || empty($parent_phone)) {
    $message = '<div class="alert alert-danger">Please fill out all required fields.</div>';
  } else {
    try {
      $stmt = $conn->prepare(
        "INSERT INTO admission_inquiries (student_name, student_dob, grade_applying_for, parent_name, parent_email, parent_phone) 
                 VALUES (?, ?, ?, ?, ?, ?)"
      );
      $stmt->execute([$student_name, $student_dob, $grade, $parent_name, $parent_email, $parent_phone]);
      $message = '<div class="alert alert-success">Thank you for your inquiry! Our admissions team will contact you shortly.</div>';
    } catch (PDOException $e) {
      $message = '<div class="alert alert-danger">Sorry, there was an error submitting your request. Please try again later.</div>';
      error_log("Admission form error: " . $e->getMessage());
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Admissions | BMC School</title>
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
            <li class="nav-item"><a class="nav-link" href="admission.php">Admissions <span class="sr-only">(current)</span></a></li>
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
        <section class="admission-form-section py-5">
          <div class="content-header">
            <h2>Admission Inquiry Form</h2>
            <h6 class="section-subtitle text-muted">Fill out the form below to begin the admission process. Our team will get back to you shortly.</h6>
          </div>

          <?php echo $message; ?>

          <div class="card shadow-sm">
            <div class="card-body p-4 p-md-5">
              <form method="POST" action="admission.php" novalidate>
                <h5 class="mb-4 text-primary">Student Information</h5>
                <div class="form-row">
                  <div class="form-group col-md-6"><label for="studentName">Student's Full Name *</label><input type="text" class="form-control" name="studentName" id="studentName" required></div>
                  <div class="form-group col-md-6"><label for="studentDOB">Date of Birth *</label><input type="date" class="form-control" name="studentDOB" id="studentDOB" required></div>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-6"><label for="admissionGrade">Grade Applying For *</label><select name="admissionGrade" id="admissionGrade" class="form-control" required>
                      <option selected disabled value="">Choose...</option><?php for ($i = 1; $i <= 12; $i++) {
                                                                              echo "<option>Grade $i</option>";
                                                                            } ?>
                    </select></div>
                </div>
                <hr class="my-4">
                <h5 class="mb-4 text-primary">Parent/Guardian Information</h5>
                <div class="form-row">
                  <div class="form-group col-md-6"><label for="parentName">Parent's Full Name *</label><input type="text" class="form-control" name="parentName" id="parentName" required></div>
                  <div class="form-group col-md-6"><label for="parentEmail">Email Address *</label><input type="email" class="form-control" name="parentEmail" id="parentEmail" required></div>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-6"><label for="parentPhone">Phone Number *</label><input type="tel" class="form-control" name="parentPhone" id="parentPhone" required></div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Submit Inquiry</button>
              </form>
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