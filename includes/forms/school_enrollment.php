<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>School Enrollment</title>
    <!-- Custom fonts -->
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>


<body class="bg-gradient-primary">
    <div class="container min-vh-100 d-flex justify-content-center align-items-center my-5">
        <div class="card shadow-lg w-100 lg-form-width">
            <div class="card-body py-5 px-5">
                <h1 class="h2 text-center text-gray-900 my-3">School Enrollment Form</h1>
                <?php
                $feedback_message = '';
                $feedback_type = '';
                if (isset($_POST['submitBtn'])) {
                    // ...existing code for PHP form handling...
                    $school_no = $_POST['school_no'] ?? '';
                    $school_name = $_POST['school_name'] ?? '';
                    $email_address = $_POST['email_address'] ?? '';
                    $standard = $_POST['standard'] ?? '';
                    $principal_name = $_POST['principal_name'] ?? '';
                    $address = $_POST['address'] ?? '';
                    $phone = $_POST['phone'] ?? '';

                    // Example DB insert logic (replace with your actual logic)
                    // $insert_query = "INSERT INTO school_1 VALUES (...)";
                    // $insert_result = mysqli_query($conn, $insert_query);
                    $insert_result = true; // Simulate success

                    if ($insert_result) {
                        $feedback_message = 'Enrollment submitted successfully!';
                        $feedback_type = 'success';
                    } else {
                        $feedback_message = 'There was an error submitting the enrollment.';
                        $feedback_type = 'danger';
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
                        <label for="school_name" class="form-label">School Name</label>
                        <input type="text" class="form-control" id="school_name" name="school_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="1" required></textarea>
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
                            <button type="submit" name="submit" class="btn btn-primary w-100">Submit Enrollment</button>
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
    <?php
   include_once '../../includes/connect.php';
    if (isset($_POST['submit'])) {
        $school_name = $_POST['school_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];

        $insert_query = "INSERT INTO school VALUES (NULL, '$school_name', '$email','$phone', '$address')";
        $insert_result = mysqli_query($conn, $insert_query);

        if ($insert_result) {
            // if (headers_sent()) {
            //     die("Headers already sent. Cannot redirect.");
            // }
            header("Location: ../../pages/school/school_list.php");
            exit();
        }
    }
    ?>
</body>


</html>