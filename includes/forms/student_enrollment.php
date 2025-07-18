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
                <form action="stu_enrollment.php" method="post" enctype="multipart/form-data">
                    <fieldset class="mb-4">
                        <legend class="fs-5 mb-3">Personal Information</legend>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="student_image" class="form-label">Student Photo</label>
                                <input type="file" class="form-control" id="student_image" name="student_image" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label for="student_name" class="form-label">Student Name</label>
                                <input type="text" class="form-control" id="student_name" name="student_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="rollno" class="form-label">Roll Number</label>
                                <input type="text" class="form-control" id="rollno" name="rollno" required>
                            </div>
                            <div class="col-md-6">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="dob" name="dob" required>
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
                        <legend class="fs-5 mb-3">Academic Information</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="school_id" class="form-label">School ID</label>
                                <input type="number" class="form-control" id="school_id" name="school_id" required>
                            </div>
                            <div class="col-md-6">
                                <label for="std" class="form-label">Standard</label>
                                <select class="form-select" id="std" name="std" required>
                                    <option value="">Select</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="academic_year" class="form-label">Academic Year</label>
                                <select class="form-select" id="academic_year" name="academic_year" required>
                                    <option value="">Select</option>
                                    <option value="2024-2025">2024-2025</option>
                                    <option value="2025-2026">2025-2026</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="fs-5 mb-3">Parent/Guardian Information</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="father_name" class="form-label">Father's Name</label>
                                <input type="text" class="form-control" id="father_name" name="father_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="father_phone" class="form-label">Father's Phone Number</label>
                                <input type="tel" class="form-control" id="father_phone" name="father_phone" required>
                            </div>
                            <div class="col-md-6">
                                <label for="mother_name" class="form-label">Mother's Name</label>
                                <input type="text" class="form-control" id="mother_name" name="mother_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="mother_phone" class="form-label">Mother's Phone Number</label>
                                <input type="tel" class="form-control" id="mother_phone" name="mother_phone" required>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="termsAgreement" name="termsAgreement" required>
                        <label class="form-check-label" for="termsAgreement">I confirm that all information provided is accurate and complete</label>
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