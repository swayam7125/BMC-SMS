<?php
include_once "./includes/connect.php"; // Ensure this path is correct
include_once "encryption.php"; // Ensure this path is correct

// FUNCTION: Calculate distance between two GPS coordinates using the Haversine formula.
function haversine_distance($lat1, $lon1, $lat2, $lon2)
{
    $earth_radius = 6371; // Earth radius in kilometers
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c * 1000; // Return distance in meters
}

// This part of the code now handles the AJAX request from the login form.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    // We will send a JSON response, so set the content type header.
    header('Content-Type: application/json');
    $response = []; // Initialize the response array.

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $user_lat = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $user_lon = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ['status' => 'error', 'message' => 'Invalid email or password.'];
    } else {
        $query = "SELECT id, password, role, account_status FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);

                if (password_verify($password, $user['password'])) {
                    if ($user['account_status'] === 'suspended') {
                        $response = ['status' => 'error', 'message' => 'Your account has been suspended. Please contact the administrator.'];
                    } else {
                        // --- START: GEOLOCATION AND TIME-BASED ATTENDANCE LOGIC FOR PRINCIPAL ---
                        if ($user['role'] === 'principal') {
                            $principal_id = $user['id'];
                            $attendance_status = 'Absent'; // Default status

                            // Get principal's school and batch
                            $details_query = mysqli_prepare($conn, "SELECT school_id, batch FROM principal WHERE id = ?");
                            mysqli_stmt_bind_param($details_query, "i", $principal_id);
                            mysqli_stmt_execute($details_query);
                            $details_result = mysqli_stmt_get_result($details_query);

                            if ($details_result && mysqli_num_rows($details_result) > 0) {
                                $principal_details = mysqli_fetch_assoc($details_result);
                                $school_id = $principal_details['school_id'];
                                $principal_batch = $principal_details['batch'];

                                // Get school's location
                                $school_loc_query = mysqli_prepare($conn, "SELECT latitude, longitude FROM school WHERE id = ?");
                                mysqli_stmt_bind_param($school_loc_query, "i", $school_id);
                                mysqli_stmt_execute($school_loc_query);
                                $school_loc_result = mysqli_stmt_get_result($school_loc_query);
                                $school_location = mysqli_fetch_assoc($school_loc_result);

                                $location_ok = false;
                                if ($user_lat && $user_lon && !empty($school_location['latitude']) && !empty($school_location['longitude'])) {
                                    $distance = haversine_distance($user_lat, $user_lon, $school_location['latitude'], $school_location['longitude']);
                                    if ($distance <= 300) { // 300-meter tolerance
                                        $location_ok = true;
                                    }
                                }

                                $current_hour = (int)date('H');
                                $time_ok = ($principal_batch === 'Morning' && $current_hour < 10) || ($principal_batch === 'Evening' && $current_hour < 14);

                                if ($location_ok && $time_ok) {
                                    $attendance_status = 'Present';
                                }

                                // Insert/Update attendance
                                $current_date = date("Y-m-d");
                                $att_stmt = mysqli_prepare(
                                    $conn,
                                    "INSERT INTO principal_attendance (principal_id, school_id, attendance_date, status, login_latitude, login_longitude, login_time)
                                    VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIME())
                                    ON DUPLICATE KEY UPDATE status = VALUES(status), login_latitude = VALUES(login_latitude), login_longitude = VALUES(login_longitude), login_time = CURRENT_TIME()"
                                );
                                mysqli_stmt_bind_param($att_stmt, "iisssd", $principal_id, $school_id, $current_date, $attendance_status, $user_lat, $user_lon);
                                mysqli_stmt_execute($att_stmt);
                            }
                        }
                        // --- END OF ATTENDANCE LOGIC ---

                        // Continue with setting cookies
                        $encrypted_id = encrypt_id($user['id']);
                        $encrypted_role = encrypt_id($user['role']);
                        // ... [rest of your cookie-setting and detail fetching logic] ...
                        $profile_image = '';
                        $user_name = '';

                        switch ($user['role']) {
                            case 'student':
                                $detail_query = "SELECT student_image, student_name FROM student WHERE id = ?";
                                break;
                            case 'teacher':
                                $detail_query = "SELECT teacher_image, teacher_name FROM teacher WHERE id = ?";
                                break;
                            case 'principal':
                                $detail_query = "SELECT principal_image, principal_name FROM principal WHERE id = ?";
                                break;
                            case 'librarian':
                                $detail_query = "SELECT librarian_image, librarian_name FROM librarian WHERE id = ?";
                                break;
                            default:
                                $profile_image = '/BMC-SMS/assets/images/undraw_profile.svg';
                                $user_name = $email;
                                break;
                        }

                        if (isset($detail_query)) {
                            $detail_stmt = mysqli_prepare($conn, $detail_query);
                            if ($detail_stmt) {
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
                                    } elseif ($user['role'] == 'principal') {
                                        $profile_image = !empty($detail_row['principal_image']) ? 'pages/principal/uploads/' . basename($detail_row['principal_image']) : '/BMC-SMS/assets/images/undraw_profile.svg';
                                        $user_name = $detail_row['principal_name'];
                                    } elseif ($user['role'] == 'librarian') {
                                        $profile_image = !empty($detail_row['librarian_image']) ? 'pages/librarian/uploads/' . basename($detail_row['librarian_image']) : '/BMC-SMS/assets/images/undraw_profile.svg';
                                        $user_name = $detail_row['librarian_name'];
                                    }
                                }
                                mysqli_stmt_close($detail_stmt);
                            }
                        }

                        $encrypted_profile_image = encrypt_id($profile_image);
                        $encrypted_user_name = encrypt_id($user_name);

                        setcookie("encrypted_user_id", $encrypted_id, time() + 86400, "/");
                        setcookie("encrypted_user_role", $encrypted_role, time() + 86400, "/");
                        setcookie("encrypted_profile_image", $encrypted_profile_image, time() + 86400, "/");
                        setcookie("encrypted_user_name", $encrypted_user_name, time() + 86400, "/");

                        // Set success response
                        $response = ['status' => 'success', 'redirect' => 'index.php'];
                    }
                } else {
                    $response = ['status' => 'error', 'message' => 'Invalid email or password.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'Invalid email or password.'];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['status' => 'error', 'message' => 'System error. Please try again later.'];
        }
    }
    // Echo the JSON response and exit the script.
    echo json_encode($response);
    exit();
}
// The rest of your HTML file remains below.
// This part will now only be rendered on the initial page load (a GET request).
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>BMC-SMS -- Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/login.css">
</head>

<body>

    <div class="container-fluid p-0">
        <div class="row g-0">
            <div class="col-lg-5 d-none d-lg-flex login-branding-panel">
                <div class="logo">
                    <i class="far fa-smile"></i>
                </div>
                <h1>Welcome Back!</h1>
                <p>Your central hub for school management and monitoring.</p>
            </div>

            <div class="col-12 col-lg-7 login-form-panel">
                <div class="login-form-container">
                    <h2>Login</h2>
                    <p class="subtitle">Please enter your credentials to proceed.</p>

                    <div id="login-alert-placeholder"></div>

                    <?php /* if (!empty($error_message)): ?>
                        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; */ ?>

                    <form id="loginForm" method="POST" action="login.php" novalidate>
                        <div class="form-group">
                            <input type="email" class="form-control form-control-custom" id="email" name="email" placeholder="Email Address" required>
                            <i class="fas fa-envelope form-icon"></i>
                        </div>
                        <div class="form-group">
                            <input type="password" class="form-control form-control-custom" name="password" id="password" placeholder="Password" required>
                            <i class="fas fa-lock form-icon"></i>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>

                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-custom-login">Login</button>
                        </div>

                        <div class="text-center mt-4">
                            <a class="forgot-password-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot Password?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="reset-alert-placeholder"></div>
                    <form id="sendOtpForm">
                        <p>Enter your email address and we'll send you an OTP to reset your password.</p>
                        <div class="mb-3">
                            <label for="resetEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="resetEmail" disabled>
                        </div>
                        <button type="submit" class="btn btn-custom-login">Send OTP</button>
                    </form>

                    <form id="resetPasswordForm" class="d-none">
                        <p>An OTP has been sent to <strong id="userEmailDisplay"></strong>. Please enter it below along with your new password.</p>
                        <input type="hidden" id="hiddenEmail" name="email">
                        <div class="mb-3">
                            <label for="otp" class="form-label">One-Time Password (OTP)</label>
                            <input type="text" class="form-control" id="otp" name="otp" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-custom-login">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/login.js"></script>
</body>

</html>