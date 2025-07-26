<?php
include_once "./includes/connect.php"; // Ensure this path is correct
include_once "encryption.php"; // Ensure this path is correct

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email or password"; // Generic message
    } else {
        // First, get the user's ID, password, and role from the 'users' table
        $query = "SELECT id, password, role FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);

                // SECURE PASSWORD VERIFICATION
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    $encrypted_id = encrypt_id($user['id']);
                    $encrypted_role = encrypt_id($user['role']);

                    $profile_image = ''; // Initialize profile image
                    $user_name = '';     // Initialize user name

                    // Fetch specific user details based on role
                    // MODIFIED: Use the user's ID to fetch details from role-specific tables
                    switch ($user['role']) {
                        case 'student':
                            $detail_query = "SELECT student_image, student_name FROM student WHERE id = ?";
                            break;
                        case 'teacher':
                            $detail_query = "SELECT teacher_image, teacher_name FROM teacher WHERE id = ?";
                            break;
                        case 'schooladmin': // Assuming 'principal' maps to 'schooladmin' role
                            $detail_query = "SELECT principal_image, principal_name FROM principal WHERE id = ?";
                            break;
                        default:
                            // BMC role or other roles might not have a specific image/name in a separate table
                            // You might set a default image/name for BMC here if needed.
                            $profile_image = '/BMC-SMS/assets/images/undraw_profile.svg'; // Generic default
                            $user_name = $email; // Use email as name for BMC if no detail table
                            break;
                    }

                    if (isset($detail_query)) {
                        $detail_stmt = mysqli_prepare($conn, $detail_query);
                        if ($detail_stmt) {
                            // MODIFIED: Bind the user ID (integer)
                            mysqli_stmt_bind_param($detail_stmt, "i", $user['id']);
                            mysqli_stmt_execute($detail_stmt);
                            $detail_result = mysqli_stmt_get_result($detail_stmt);
                            if ($detail_result && mysqli_num_rows($detail_result) > 0) {
                                $detail_row = mysqli_fetch_assoc($detail_result);
                                if ($user['role'] == 'student') {
                                    $profile_image = !empty($detail_row['student_image']) ? 'pages/student/uploads/' . basename($detail_row['student_image']) : '/BMC-SMS/assets/images/undraw_profile.svg';
                                    $user_name = $detail_row['student_name'];
                                } elseif ($user['role'] == 'teacher') {
                                    $profile_image = !empty($detail_row['teacher_image']) ? 'pages/teacher/uploads/' . basename($detail_row['teacher_image']) : '/BMC-SMS/assets/images/undraw_profile.svg';
                                    $user_name = $detail_row['teacher_name'];
                                } elseif ($user['role'] == 'schooladmin') {
                                    $profile_image = !empty($detail_row['principal_image']) ? 'pages/principal/uploads/' . basename($detail_row['principal_image']) : '/BMC-SMS/assets/images/undraw_profile.svg';
                                    $user_name = $detail_row['principal_name'];
                                }
                            }
                            mysqli_stmt_close($detail_stmt);
                        }
                    }

                    // Encrypt the profile image path and user name
                    $encrypted_profile_image = encrypt_id($profile_image);
                    $encrypted_user_name = encrypt_id($user_name);

                    // Set cookies (consider adding secure and httponly flags for production)
                    // For production, remember to add `secure` and `httponly` for security.
                    // E.g., setcookie("name", "value", ["expires" => time() + 86400, "path" => "/", "secure" => true, "httponly" => true]);
                    setcookie("encrypted_user_id", $encrypted_id, time() + 86400, "/");
                    setcookie("encrypted_user_role", $encrypted_role, time() + 86400, "/");
                    setcookie("encrypted_profile_image", $encrypted_profile_image, time() + 86400, "/");
                    setcookie("encrypted_user_name", $encrypted_user_name, time() + 86400, "/");


                    header("Location: index.php");
                    exit();
                } else {
                    $error_message = "Invalid email or password"; // Generic message
                }
            } else {
                $error_message = "Invalid email or password"; // Generic message
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "System error. Please try again later.";
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
    <title>BMPSMS - Login</title>

    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-primary">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-12">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Welcome</h1>
                                    </div>

                                    <?php if (!empty($error_message)): ?>
                                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?>
                                        </div>
                                    <?php endif; ?>

                                    <form class="user" method="POST">
                                        <div class="form-group">
                                            <input type="email" class="form-control form-control-user opacity-100"
                                                name="email" placeholder="Enter Email Address..." required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" class="form-control form-control-user opacity-100"
                                                name="password" placeholder="Password" required>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Login
                                        </button>
                                        <hr>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/js/sb-admin-2.min.js"></script>

</body>

</html>