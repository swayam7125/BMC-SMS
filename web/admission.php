<?php
$pageTitle = 'Admissions'; // Add this line
@include_once '../includes/connect.php'; // Use @include_once for safe connection

// --- Start Dynamic School Info Fetch (Required for header.php) ---
$school_id_to_feature = 4;
$school_info = [
    'school_name' => 'Sanskar Bharti Vidyalay',
    'logo_path' => 'images/Group2.svg'
];
if (isset($conn)) {
    try {
        $stmt_school = $conn->prepare("SELECT school_name, school_logo AS logo_path FROM school WHERE id = :school_id");
        $stmt_school->bindParam(':school_id', $school_id_to_feature, PDO::PARAM_INT);
        $stmt_school->execute();
        $fetched_info = $stmt_school->fetch(PDO::FETCH_ASSOC);

        if ($fetched_info) {
            $school_info = array_merge($school_info, $fetched_info);
            $school_info['logo_path'] = str_replace('\\', '/', $school_info['logo_path']);
            // Add base path logic if needed, similar to header.php
        }
    } catch (PDOException $e) {
        error_log("Admission page DB error: " . $e->getMessage());
    }
}
// --- End Dynamic School Info Fetch ---


$message = '';

function generateAdmissionID($length = 10)
{
  return substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Collect and sanitize form data
  // Note: These arrays will be set correctly once php.ini is fixed.
  $first_name = trim($_POST['firstName'] ?? '');
  $middle_name = trim($_POST['middleName'] ?? '');
  $last_name = trim($_POST['lastName'] ?? '');
  $prev_school = trim($_POST['previousSchool'] ?? '');
  $prev_grade = trim($_POST['previousGrade'] ?? '');
  $prev_year = trim($_POST['previousYear'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');

  $admission_id = generateAdmissionID();
  $marksheet_path = null;
  $aadhaar_path = null;

  // --- File Upload Logic ---
  // You might need to change 'uploads/documents/' to '/BMC-SMS/uploads/documents/' for web path access
  $upload_dir = '../uploads/documents/'; // Adjust path relative to current script (web/admission.php)
  $web_path_prefix = 'uploads/documents/'; // Path stored in DB for web access

  if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
  }

  // Marksheet Upload
  if (isset($_FILES['marksheet']) && $_FILES['marksheet']['error'] == 0) {
    // Add unique identifier to filename to prevent collisions and simplify security
    $ext = pathinfo($_FILES['marksheet']['name'], PATHINFO_EXTENSION);
    $marksheet_filename = $admission_id . '_marksheet_' . time() . '.' . $ext;
    if (move_uploaded_file($_FILES['marksheet']['tmp_name'], $upload_dir . $marksheet_filename)) {
      $marksheet_path = $web_path_prefix . $marksheet_filename;
    }
  }
  // Aadhaar Card Upload
  if (isset($_FILES['aadhaar']) && $_FILES['aadhaar']['error'] == 0) {
    $ext = pathinfo($_FILES['aadhaar']['name'], PATHINFO_EXTENSION);
    $aadhaar_filename = $admission_id . '_aadhaar_' . time() . '.' . $ext;
    if (move_uploaded_file($_FILES['aadhaar']['tmp_name'], $upload_dir . $aadhaar_filename)) {
      $aadhaar_path = $web_path_prefix . $aadhaar_filename;
    }
  }

  // --- Database Insertion ---
  if ($conn) { // Only attempt DB insertion if connection exists
    try {
      $stmt = $conn->prepare(
        "INSERT INTO admission_applications (admission_id, first_name, middle_name, last_name, previous_school, previous_grade, previous_year, marksheet_path, aadhaar_path, email, phone) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
      );
      $stmt->execute([$admission_id, $first_name, $middle_name, $last_name, $prev_school, $prev_grade, $prev_year, $marksheet_path, $aadhaar_path, $email, $phone]);
      $message = '<div class="alert alert-success">Thank you! Your application has been submitted successfully. Your Admission ID is: <strong>' . $admission_id . '</strong>. Please save it for tracking your application status.</div>';

      // ⭐ NEW: Send Notification to all HR users
      $notification_message = "New admission application received. Student: {$first_name} {$last_name}. ID: {$admission_id}";
      $notification_type = 'new_admission_request';

      // Assuming a 'users' table exists with a 'role' column. If not, adjust the query to fetch HR IDs.
      $stmt_hr_ids = $conn->prepare("SELECT id FROM users WHERE role = 'hr'");
      $stmt_hr_ids->execute();
      $hr_user_ids = $stmt_hr_ids->fetchAll(PDO::FETCH_COLUMN);

      $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, is_read) VALUES (?, ?, ?, false)");
      foreach ($hr_user_ids as $hr_id) {
          $stmt_notify->execute([$hr_id, $notification_type, $notification_message]);
      }
      // ⭐ END NEW Notification

    } catch (PDOException $e) {
      $message = '<div class="alert alert-danger">Sorry, there was an error. Please try again.</div>';
      error_log("Admission form database insert error: " . $e->getMessage());
    }
  } else {
      $message = '<div class="alert alert-danger">Application submission failed. Database connection is down.</div>';
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title><?php echo htmlspecialchars($pageTitle); ?> | <?php echo htmlspecialchars($school_info['school_name'] ?? 'BMC School'); ?></title>
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
                  <div class="form-group col-md-4"><label for="firstName">First Name *</label><input type="text" class="form-control" name="firstName" value="<?php echo htmlspecialchars($_POST['firstName'] ?? ''); ?>" required></div>
                  <div class="form-group col-md-4"><label for="middleName">Middle Name</label><input type="text" class="form-control" name="middleName" value="<?php echo htmlspecialchars($_POST['middleName'] ?? ''); ?>"></div>
                  <div class="form-group col-md-4"><label for="lastName">Last Name *</label><input type="text" class="form-control" name="lastName" value="<?php echo htmlspecialchars($_POST['lastName'] ?? ''); ?>" required></div>
                </div>
                <hr class="my-4">
                <h5 class="mb-4 text-primary">Previous Academic Details</h5>
                <div class="form-row">
                  <div class="form-group col-md-6"><label for="previousSchool">Previous School Name</label><input type="text" class="form-control" name="previousSchool" value="<?php echo htmlspecialchars($_POST['previousSchool'] ?? ''); ?>"></div>
                  <div class="form-group col-md-3"><label for="previousGrade">Previous Grade</label><input type="text" class="form-control" name="previousGrade" value="<?php echo htmlspecialchars($_POST['previousGrade'] ?? ''); ?>"></div>
                  <div class="form-group col-md-3"><label for="previousYear">Year</label><input type="text" class="form-control" name="previousYear" value="<?php echo htmlspecialchars($_POST['previousYear'] ?? ''); ?>"></div>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-6"><label for="marksheet">Previous Grade Marksheet (PDF, JPG, PNG)</label><input type="file" class="form-control-file" name="marksheet"></div>
                  <div class="form-group col-md-6"><label for="aadhaar">Aadhaar Card (PDF, JPG, PNG)</label><input type="file" class="form-control-file" name="aadhaar"></div>
                </div>
                <hr class="my-4">
                <h5 class="mb-4 text-primary">Contact Information</h5>
                <div class="form-row">
                  <div class="form-group col-md-6"><label for="email">Email ID *</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div>
                  <div class="form-group col-md-6"><label for="phone">Phone Number *</label><input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required></div>
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