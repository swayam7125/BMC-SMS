<?php
session_start();
include_once "../connect.php";

// Initialize variables
$feedback_message = '';
$feedback_type = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Validate required POST fields (removed 'teacher_image' as it's in $_FILES)
    $required_fields = [
        'teacher_name',
        'phone',
        'school_id',
        'dob',
        'gender',
        'blood_group',
        'address',
        'email',
        'password',
        'qualification',
        'subject',
        'language_known',
        'salary',
        'std',
        'experience'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $feedback_message = "All fields are required. Missing: " . $field;
            $feedback_type = "danger";
            break;
        }
    }

    // Validate file upload separately (optional - uncomment if image is mandatory)
    /*
    if (empty($feedback_message)) {
        if (!isset($_FILES['teacher_image']) || $_FILES['teacher_image']['error'] !== UPLOAD_ERR_OK) {
            $feedback_message = "Please upload a teacher photo";
            $feedback_type = "danger";
        }
    }
    */

    if (empty($feedback_message)) {
        // Sanitize inputs
        $teacher_name = mysqli_real_escape_string($conn, $_POST['teacher_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $school_id = intval($_POST['school_id']);
        $dob = mysqli_real_escape_string($conn, $_POST['dob']);
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];
        $qualification = mysqli_real_escape_string($conn, $_POST['qualification']);
        $subject = mysqli_real_escape_string($conn, $_POST['subject']);
        $language_known = mysqli_real_escape_string($conn, $_POST['language_known']);
        $salary = mysqli_real_escape_string($conn, $_POST['salary']);
        $std = mysqli_real_escape_string($conn, $_POST['std']);
        $experience = mysqli_real_escape_string($conn, $_POST['experience']);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $feedback_message = "Invalid email format";
            $feedback_type = "danger";
        }
        // Validate password strength
        elseif (strlen($password) < 8) {
            $feedback_message = "Password must be at least 8 characters";
            $feedback_type = "danger";
        } else {
            // Begin transaction
            mysqli_begin_transaction($conn);

            try {
                // 1. Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // 2. Insert into users table
                $user_query = "INSERT INTO users (role, email, password) VALUES ('teacher', ?, ?)";
                $stmt = mysqli_prepare($conn, $user_query);
                mysqli_stmt_bind_param($stmt, "ss", $email, $hashed_password);
                mysqli_stmt_execute($stmt);

                // Get the auto-incremented user ID
                $user_id = mysqli_insert_id($conn);

                // 3. Handle file upload
                $teacher_image = '';
                if (isset($_FILES['teacher_image']) && $_FILES['teacher_image']['error'] === UPLOAD_ERR_OK) {
                    $target_dir = "../../pages/teacher/uploads/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $imageFileType = strtolower(pathinfo($_FILES["teacher_image"]["name"], PATHINFO_EXTENSION));
                    $new_filename = uniqid() . '.' . $imageFileType;
                    $destination = $target_dir . $new_filename;

                    if (move_uploaded_file($_FILES["teacher_image"]["tmp_name"], $destination)) {
                        $teacher_image = $destination;
                    }
                }

                // 4. Insert into teachers table
                $teacher_query = "INSERT INTO teacher (
                    teacher_image, teacher_name, phone, school_id, dob, gender, 
                    blood_group, address, email, password, qualification, subject, 
                    language_known, salary, std, experience
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($conn, $teacher_query);
                mysqli_stmt_bind_param(
                    $stmt,
                    "sssisssssssssiss",
                    $teacher_image,
                    $teacher_name,
                    $phone,
                    $school_id,
                    $dob,
                    $gender,
                    $blood_group,
                    $address,
                    $email,
                    $hashed_password,
                    $qualification,
                    $subject,
                    $language_known,
                    $salary,
                    $std,
                    $experience
                );
                mysqli_stmt_execute($stmt);

                // Commit transaction
                mysqli_commit($conn);

                // Set success message and redirect
                $_SESSION['feedback_message'] = "Enrollment successful!";
                $_SESSION['feedback_type'] = "success";
                header("Location: /BMC-SMS/pages/teacher/teacher_list.php");
                exit();
            } catch (Exception $e) {
                // Rollback on error
                mysqli_rollback($conn);
                $feedback_message = "Error: " . $e->getMessage();
                $feedback_type = "danger";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Teachers Enrollment</title>
    <!-- Custom fonts -->
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .lg-form-width {
            max-width: 100rem;
        }
    </style>
</head>

<body class="bg-gradient-primary">
    <div class="container min-vh-100 d-flex justify-content-center align-items-center my-5">
        <div class="card shadow-lg w-100 lg-form-width">
            <div class="card-body py-5 px-5">
                <h1 class="h2 text-center text-gray-900 my-3">Teacher Enrollment Form</h1>

                <?php if (!empty($feedback_message)): ?>
                    <div class="alert alert-<?php echo $feedback_type; ?>" role="alert">
                        <?php echo $feedback_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>"
                enctype="multipart/form-data">
                <fieldset class="mb-4">
                    <legend class="fs-5 mt-3 mb-2">Basic Information</legend>
                    <div class="row g-3">
                            <div class="col-12">
                                <label for="teacher_image" class="form-label">Teacher Photo</label>
                                <input type="file" class="form-control" id="teacher_image" name="teacher_image"
                                    accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label for="teachers_name" class="form-label">Teachers Name</label>
                                <input type="text" class="form-control" id="teacher_name" name="teacher_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Contact Number</label>
                                <input type="number" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="col-md-6">
                                <label for="school_id" class="required">School Name</label>
                                <select id="school_id" name="school_id" class="form-select" required>
                                    <option value="">Select School</option>
                                    <?php
                                    include_once '../../includes/connect.php';
                                    $school_query = "SELECT id, school_name FROM school";
                                    $school_result = mysqli_query($conn, $school_query);
                                    if ($school_result && mysqli_num_rows($school_result) > 0) {
                                        while ($row = mysqli_fetch_assoc($school_result)) {
                                            echo "<option value='{$row['id']}'>{$row['school_name']}</option>";
                                        }
                                    } else {
                                        echo "<option disabled>No schools found</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                           
                            <div class="col-md-6">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="dob" name="dob" required>
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Others">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="blood_group" class="form-label">Blood Group</label>
                                <select class="form-select" id="blood_group" name="blood_group" required>
                                    <option value="">Select</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Residential Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="fs-5 mt-3 mb-2">Account Information</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="fs-5 mt-3 mb-2">Professional Information</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="qualification" class="form-label">Qualification</label>
                                <input type="text" class="form-control" id="qualification" name="qualification"  required>
                            </div>
                            <div class="col-md-6">
                                <label for="subject" class="form-label">Subject Specialization</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="col-md-6">
                                <label for="language_known" class="form-label">Languages Known</label>
                                <input type="text" class="form-control" id="language_known" name="language_known"  required>
                            </div>
                            <div class="col-md-6">
                                <label for="salary" class="form-label">Salary</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="salary" name="salary"  required>  
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="std" class="form-label">Teaching Standards</label>
                                 <input type="text" class="form-control" id="std" name="std" required>
                            </div>
                            <div class="col-md-6">
                                <label for="experience" class="form-label">Years of Experience</label>
                                <input type="text" class="form-control" id="experience" name="experience" min="0" max="50"  required>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="termsAgreement" name="termsAgreement"
                            required>
                        <label class="form-check-label" for="termsAgreement">I confirm that all information provided is
                            accurate and complete</label>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="reset" class="btn btn-secondary">Reset Form</button>
                        <button type="submit" name="submit" class="btn btn-primary">Submit Enrollment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>