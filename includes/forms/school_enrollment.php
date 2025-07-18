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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
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
                <form method="post" enctype="multipart/form-data">
                    <fieldset class="mb-4">
                        <legend class="fs-5 mb-3">School Information</legend>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="school_logo" class="form-label">School Logo</label>
                                <input type="file" class="form-control" id="school_logo" name="school_logo" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label for="school_name" class="form-label">School Name</label>
                                <input type="text" class="form-control" id="school_name" name="school_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="school_no" class="form-label">School Number</label>
                                <input type="text" class="form-control" id="school_no" name="school_no" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email_address" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email_address" name="email_address" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="fs-5 mb-3">Additional Details</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="principal_name" class="form-label">Principal Name</label>
                                <input type="text" class="form-control" id="principal_name" name="principal_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="standard" class="form-label">Standards Offered</label>
                                <select class="form-select" id="standard" name="standard" required>
                                    <option value="">Select</option>
                                    <option value="1-5">1st to 5th</option>
                                    <option value="1-8">1st to 8th</option>
                                    <option value="1-10">1st to 10th</option>
                                    <option value="1-12">1st to 12th</option>
                                </select>
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
    if (isset($_POST['submitBtn'])) {
        $school_no = $_POST['school_no'] ?? '';
        $school_name = $_POST['school_name'] ?? '';
        $email_address = $_POST['email_address'] ?? '';
        $standard = $_POST['standard'] ?? '';
        $principal_name = $_POST['principal_name'] ?? '';
        $address = $_POST['address'] ?? '';
        $phone = $_POST['phone'] ?? '';

        // ...existing code for DB insert...
    }
    ?>
</body>
</html>