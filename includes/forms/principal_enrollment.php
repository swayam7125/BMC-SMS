<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Principal Enrollment</title>
    <!-- Custom fonts -->
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-primary">
    <div class="container w-50 min-vh-100 d-flex justify-content-center align-items-center">
        <div class="card shadow-lg w-100">
            <div class="card-body">
                <h1 class="h2 text-center text-gray-900 my-3">Principal Enrollment Form</h1>
                <?php
                include_once '../../includes/connect.php';
                $feedback_message = '';
                $feedback_type = '';

                if (isset($_POST['submit'])) {
                    try {
                        $school_id = $_POST['school_id'];
                        $principal_name = $_POST['principal_name'];
                        $email = $_POST['email'];
                        $phone = $_POST['phone'];
                        $principal_dob = $_POST['principal_dob'];
                        $gender = $_POST['gender'];
                        $blood_group = $_POST['blood_group'];
                        $address = $_POST['address'];
                        $qualification = $_POST['qualification'];
                        $salary = $_POST['salary'];

                        $insert_query = "INSERT INTO principal (
                            school_id, principal_name, email, phone, principal_dob,
                            gender, blood_group, address, qualification, salary
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                        $stmt = mysqli_prepare($conn, $insert_query);
                        mysqli_stmt_bind_param(
                            $stmt,
                            "issssssssd",
                            $school_id,
                            $principal_name,
                            $email,
                            $phone,
                            $principal_dob,
                            $gender,
                            $blood_group,
                            $address,
                            $qualification,
                            $salary
                        );

                        if (mysqli_stmt_execute($stmt)) {
                            header("Location: /BMC/pages/principal/principal_list.php");
                            exit();
                        } else {
                            throw new Exception("Error inserting principal data");
                        }
                    } catch (Exception $e) {
                        $feedback_message = "Error: " . $e->getMessage();
                        $feedback_type = "danger";
                    }
                }
                ?>
                <?php if (!empty($feedback_message)): ?>
                <div class="alert alert-<?php echo $feedback_type; ?>">
                    <?php echo htmlspecialchars($feedback_message); ?>
                </div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="school_id" class="required">School ID</label>
                        <select id="school_id" name="school_id" class="form-select" required>
                            <option value="">-- Select School --</option>
                            <?php
                            $school_query = "SELECT s.id, s.school_name 
                        FROM school s
                        LEFT JOIN principal p ON s.id = p.school_id 
                        WHERE p.school_id IS NULL";
                            $school_result = mysqli_query($conn, $school_query);
                            if ($school_result && mysqli_num_rows($school_result) > 0) {
                                while ($row = mysqli_fetch_assoc($school_result)) {
                                    echo "<option value='{$row['id']}'>{$row['school_name']}</option>";
                                }
                            } else {
                                echo "<option disabled>No schools available</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="principal_name" class="form-label">Principal Name</label>
                        <input type="text" class="form-control" id="principal_name" name="principal_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" maxlength="10" required>
                    </div>
                    <div class="mb-3">
                        <label for="principal_dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="principal_dob" name="principal_dob" required>
                    </div>
                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="blood_group" class="form-label">Blood Group</label>
                        <select class="form-control" id="blood_group" name="blood_group" required>
                            <option value="">Select Blood Group</option>
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
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="qualification" class="form-label">Qualification</label>
                        <input type="text" class="form-control" id="qualification" name="qualification" required>
                    </div>
                    <div class="mb-3">
                        <label for="salary" class="form-label">Salary</label>
                        <input type="number" class="form-control" id="salary" name="salary" step="0.01" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="termsAgreement" name="termsAgreement"
                            required>
                        <label class="form-check-label" for="termsAgreement">I confirm that all information provided is
                            accurate and complete</label>
                    </div>
                    <div class="row gap-2 justify-content-between px-3">
                        <div class="col-12 col-md-4">
                            <button type="reset" class="btn btn-secondary w-100">Reset Form</button>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" name="submit" class="btn btn-primary w-100">Submit
                                Enrollment</button>
                        </div>
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