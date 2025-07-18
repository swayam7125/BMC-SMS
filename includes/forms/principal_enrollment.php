<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Student Enrollment</title>
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
                <h1 class="h2 text-center text-gray-900 my-3">Student Enrollment Form</h1>
                <?php
                $feedback_message = '';
                $feedback_type = '';
                if (isset($_POST['submitBtn'])) {
                    // ...existing code for PHP form handling...
                    // ...existing feedback logic...
                }
                ?>
                <?php if (!empty($feedback_message)): ?>
                    <div class="alert alert-<?php echo $feedback_type; ?>">
                        <?php echo htmlspecialchars($feedback_message); ?>
                    </div>
                <?php endif; ?>
                <form action="principal-enroll.php" method="post" enctype="multipart/form-data">
                    <fieldset class="mb-4">
                        <legend class="fs-5 mt-3 mb-2">Personal Information</legend>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="principal_image" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" id="principal_image" name="principal_image" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label for="principal_full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="principal_full_name" name="principal_full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="fs-5 mt-3 mb-2">Professional Information</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="qualification" class="form-label">Qualification</label>
                                <input type="text" class="form-control" id="qualification" name="qualification" required>
                            </div>
                            <div class="col-md-6">
                                <label for="salary" class="form-label">Salary</label>
                                <input type="number" class="form-control" id="salary" name="salary" min="0" max="10000000" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label for="school_name" class="form-label">School Name</label>
                                <input type="text" class="form-control" id="school_name" name="school_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="school_no" class="form-label">School Number</label>
                                <input type="text" class="form-control" id="school_no" name="school_no" required>
                            </div>
                        </div>
                    </fieldset>

                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="termsAgreement" name="termsAgreement" required>
                        <label class="form-check-label" for="termsAgreement">I confirm that all information provided is accurate and complete</label>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="reset" class="btn btn-secondary">Reset Form</button>
                        <button type="submit" name="submitBtn" class="btn btn-primary">Submit Enrollment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <?php
    // ...existing code for PHP form handling...
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $student_image = $_POST['student_image'];
        $student_name = $_POST['student_name'];
        $rollno = $_POST['rollno'];
        $std = $_POST['std'];
        $academic_year = $_POST['academic_year'];
        $school_id = $_POST['school_id'];
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $blood_group = $_POST['blood_group'];
        $address = $_POST['address'];
        $father_name = $_POST['father_name'];
        $father_phone = $_POST['father_phone'];
        $mother_name = $_POST['mother_name'];
        $mother_phone = $_POST['mother_phone'];
        // $password = $_POST['password'];

        $insert_query = "INSERT INTO students VALUES (NULL'$student_image', '$student_name', '$rollno','$std','$academic_year','$school_id', '$dob', '$gender', '$blood_group', '$address', '$father_name', '$father_phone', '$mother_name', '$mother_phone')";
        $insert_result = mysqli_query($conn, $insert_query);
    }
    ?>
</body>

</html>