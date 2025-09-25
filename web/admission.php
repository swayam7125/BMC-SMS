<?php
  $pageTitle = 'Admissions'; // Add this line
  require_once '../includes/connect.php';
  $message = '';

function generateAdmissionID($length = 10)
{
  return substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Collect and sanitize form data
  $first_name = trim($_POST['firstName']);
  $middle_name = trim($_POST['middleName']);
  $last_name = trim($_POST['lastName']);
  $prev_school = trim($_POST['previousSchool']);
  $prev_grade = trim($_POST['previousGrade']);
  $prev_year = trim($_POST['previousYear']);
  $email = trim($_POST['email']);
  $phone = trim($_POST['phone']);

  $admission_id = generateAdmissionID();
  $marksheet_path = null;
  $aadhaar_path = null;

  // --- File Upload Logic ---
  $upload_dir = 'uploads/documents/';
  if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
  }

  // Marksheet Upload
  if (isset($_FILES['marksheet']) && $_FILES['marksheet']['error'] == 0) {
    $marksheet_filename = $admission_id . '_marksheet_' . basename($_FILES['marksheet']['name']);
    if (move_uploaded_file($_FILES['marksheet']['tmp_name'], $upload_dir . $marksheet_filename)) {
      $marksheet_path = $upload_dir . $marksheet_filename;
    }
  }
  // Aadhaar Card Upload
  if (isset($_FILES['aadhaar']) && $_FILES['aadhaar']['error'] == 0) {
    $aadhaar_filename = $admission_id . '_aadhaar_' . basename($_FILES['aadhaar']['name']);
    if (move_uploaded_file($_FILES['aadhaar']['tmp_name'], $upload_dir . $aadhaar_filename)) {
      $aadhaar_path = $upload_dir . $aadhaar_filename;
    }
  }

  // --- Database Insertion ---
  try {
    $stmt = $conn->prepare(
      "INSERT INTO admission_applications (admission_id, first_name, middle_name, last_name, previous_school, previous_grade, previous_year, marksheet_path, aadhaar_path, email, phone) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$admission_id, $first_name, $middle_name, $last_name, $prev_school, $prev_grade, $prev_year, $marksheet_path, $aadhaar_path, $email, $phone]);
    $message = '<div class="alert alert-success">Thank you! Your application has been submitted successfully. Your Admission ID is: <strong>' . $admission_id . '</strong>. Please save it for tracking your application status.</div>';
  } catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Sorry, there was an error. Please try again.</div>';
    error_log("Admission form error: " . $e->getMessage());
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
  <?php include 'header.php'; ?>
  <main>
    <div class="content-wrapper">
      <div class="container">
        <section class="admission-form-section py-5">
          <div class="content-header">
            <h2>Admission Application Form</h2>
            <h6 class="section-subtitle text-muted">Please fill out the form below to begin the admission process.</h6>
          </div>
          <?php echo $message; ?>
          <div class="card shadow-sm">
            <div class="card-body p-4 p-md-5">
              <form method="POST" action="admission.php" enctype="multipart/form-data" novalidate>
                <h5 class="mb-4 text-primary">Student Details</h5>
                <div class="form-row">
                  <div class="form-group col-md-4"><label for="firstName">First Name *</label><input type="text" class="form-control" name="firstName" required></div>
                  <div class="form-group col-md-4"><label for="middleName">Middle Name</label><input type="text" class="form-control" name="middleName"></div>
                  <div class="form-group col-md-4"><label for="lastName">Last Name *</label><input type="text" class="form-control" name="lastName" required></div>
                </div>
                <hr class="my-4">
                <h5 class="mb-4 text-primary">Previous Academic Details</h5>
                <div class="form-row">
                  <div class="form-group col-md-6"><label for="previousSchool">Previous School Name</label><input type="text" class="form-control" name="previousSchool"></div>
                  <div class="form-group col-md-3"><label for="previousGrade">Previous Grade</label><input type="text" class="form-control" name="previousGrade"></div>
                  <div class="form-group col-md-3"><label for="previousYear">Year</label><input type="text" class="form-control" name="previousYear"></div>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-6"><label for="marksheet">Previous Grade Marksheet (PDF, JPG, PNG)</label><input type="file" class="form-control-file" name="marksheet"></div>
                  <div class="form-group col-md-6"><label for="aadhaar">Aadhaar Card (PDF, JPG, PNG)</label><input type="file" class="form-control-file" name="aadhaar"></div>
                </div>
                <hr class="my-4">
                <h5 class="mb-4 text-primary">Contact Information</h5>
                <div class="form-row">
                  <div class="form-group col-md-6"><label for="email">Email ID *</label><input type="email" class="form-control" name="email" required></div>
                  <div class="form-group col-md-6"><label for="phone">Phone Number *</label><input type="tel" class="form-control" name="phone" required></div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Submit Application</button>
              </form>
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