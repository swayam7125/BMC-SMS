<?php
include_once 'connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="AdminKit">
    <meta name="keywords"
        content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="canonical" href="https://demo-basic.adminkit.io/ui-buttons.html" />
    <link rel="stylesheet" href="/adminkit-dev/static/css/admin-custom-style.css">
    <link href="css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <title>School Enrollment</title>
    <style>
        /* Main Theme Colors and Variables */
        :root {
            --primary-color: #3a7dda;
            --secondary-color: #f5f7fb;
            --text-color: #1e2022;
            --border-color: #e7eaf0;
            --success-color: #42ba96;
            --danger-color: #df4759;
            --warning-color: #f3b02a;
            --sidebar-bg: #1b2537;
            --sidebar-text: #ffffff;
            --card-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            --form-bg: #ffffff;
        }

        /* Base Layout */
        /* body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.5;
            margin: 0;
        } */

        .container-fluid {
            padding: 1.5rem 2rem;
        }

        /* Form Header Styling */
        h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .form-description {
            color: #6c757d;
            margin-bottom: 2rem;
        }

        /* Form Sections */
        .form-section {
            background-color: var(--form-bg);
            border-radius: 5px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-color);
        }

        /* Form Rows and Groups */
        .form-row {
            display: flex;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 0 0 calc(50% - 1.5rem);
            margin: 0 0.75rem 1.25rem;
        }

        @media (max-width: 768px) {
            .form-group {
                flex: 0 0 calc(100% - 1.5rem);
            }
        }

        /* Labels */
        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        label.required:after {
            content: " *";
            color: var(--danger-color);
        }

        /* Form Controls */
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"],
        input[type="date"],
        input[type="password"],
        textarea,
        select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.5;
            color: var(--text-color);
            background-color: rgb(215, 217, 221);
            background-clip: padding-box;
            border: 1px solid var(--border-color);
            border-radius: 0.25rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(58, 125, 218, 0.25);
        }

        textarea {
            resize: vertical;
        }

        /* Checkboxes */
        .checkbox-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .checkbox-item input[type="checkbox"] {
            margin-right: 0.75rem;
            margin-top: 0.25rem;
        }

        /* Languages Known Section */
        .language-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .language-tag {
            background-color: var(--secondary-color);
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
        }

        .language-tag span {
            margin-right: 0.5rem;
        }

        .remove-language {
            cursor: pointer;
            color: var(--danger-color);
            border: none;
            background: none;
            font-size: 0.75rem;
        }

        #languageInput {
            display: flex;
            margin-bottom: 0.75rem;
        }

        #languageKnown {
            flex: 1;
            margin-right: 0.5rem;
        }

        #addLanguageBtn {
            padding: 0.5rem 0.75rem;
            background-color: var(--secondary-color);
            border: 1px solid var(--border-color);
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.875rem;
        }

        #addLanguageBtn:hover {
            background-color: #e9ecef;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
            cursor: pointer;
        }

        .btn-primary {
            color: #fff;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2e68b8;
            border-color: #2b61ad;
        }

        .btn-secondary {
            color: var(--text-color);
            background-color: var(--secondary-color);
            border-color: var(--border-color);
        }

        .btn-secondary:hover {
            background-color: #e2e6ea;
            border-color: #dae0e5;
        }

        .btn-container {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* Footer */
        .form-footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            color: #6c757d;
            font-size: 0.875rem;
        }

        .pdf {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            background: #f1f1f1;
        }
    </style>
</head>

<body>
    <div class="wrapper p-0 m-0">
        <div class="sidebar">
        <div class="main">
            <form action="enrollment_form.php" method="post">
                <main class="content p-4">
                    <div class="container-fluid p-0">
                        <h1 class="h3 mb-3"><strong class="h1">School Enrollment</strong> Form</h1>

                        <div class="form-section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="school_name" class="required">School Name</label>
                                    <input type="text" id="school_name" name="school_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="email_address" class="required">Email Address</label>
                                    <input type="email" id="email_address" name="email_address" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="principal_name" class="required">PHONE</label>
                                    <input type="tel" id="phone" name="" required>
                                </div>
                                <div class="form-group">
                                    <label for="address" class="required">Address</label>
                                    <textarea id="address" name="address" rows="1" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="termsAgreement" name="termsAgreement" required>
                                <label for="termsAgreement" class="required">I confirm that all information
                                    provided is
                                    accurate and complete</label>
                            </div>
                        </div>

                        <div class="btn-container">
                            <button type="reset" class="btn btn-secondary" id="resetBtn">Reset Form</button>
                            <button type="submit" name="submitBtn" class="btn btn-primary" id="submitBtn"
                                onsubmit="return validatePassword()">Submit
                                Enrollment</button>
                        </div>
                    </div>
                </main>
            </form>
            <footer class="footer">
                <!-- <div class="container-fluid"> -->
                <div class="row text-muted">
                    <div class="col-6 text-start">
                    </div>
                    <div class="col-6 text-end">
                        <p class="mb-0"><strong>schoolAdmin</strong></p>
                    </div>
                </div>
                <!-- </div> -->
            </footer>
        </div>
    </div>

    <script src="js/app.js"></script>
    <?php
    if (isset($_POST['submitBtn'])) {
        $school_no = $_POST['school_no'];
        $school_name = $_POST['school_name'];
        $email_address = $_POST['email_address'];
        $standard = $_POST['standard'];
        $principal_name = $_POST['principal_name'];
        $address = $_POST['address'];

        $insert_query = "INSERT INTO school_1 VALUES ('$school_no', '$school_name', '$email_address', '$standard', '$principal_name', '$address')";
        $insert_result = mysqli_query($conn, $insert_query);

        if ($insert_result) {
            if (headers_sent()) {
                die("Headers already sent. Cannot redirect.");
            }
            header("Location: enrollment_form.php");
            exit();
        }
    }
    ?>

</body>

</html>