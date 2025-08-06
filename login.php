<?php
// ===============================================================
// ========= PHP LOGIC FOR LOGIN PROCESSING STARTS HERE ==========
// ===============================================================

include_once "./includes/connect.php";
include_once "encryption.php";

function haversine_distance($lat1, $lon1, $lat2, $lon2) {
    // This function is not used in the login logic, but we'll leave it
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c * 1000;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    header('Content-Type: application/json');
    $response = [];

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $user_lat = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $user_lon = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ['status' => 'error', 'message' => 'Invalid email or password.'];
    } else {
        try {
            // Step 1: Verify user against the 'users' table
            $query = 'SELECT "id", "password", "role", "account_status" FROM "users" WHERE "email" = ?';
            $stmt = $conn->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['account_status'] === 'suspended') {
                    $response = ['status' => 'error', 'message' => 'Your account has been suspended. Please contact the administrator.'];
                } else {
                    // Step 2: Fetch the role-specific details (name and image)
                    $profileDetails = null;
                    $profileQuery = '';
                    
                    switch ($user['role']) {
                        case 'principal':
                            $profileQuery = 'SELECT principal_name as "name", principal_image as "image" FROM principal WHERE id = ?';
                            break;
                        case 'teacher':
                            $profileQuery = 'SELECT teacher_name as "name", teacher_image as "image" FROM teacher WHERE id = ?';
                            break;
                        case 'student':
                            $profileQuery = 'SELECT student_name as "name", student_image as "image" FROM student WHERE id = ?';
                            break;
                        case 'librarian':
                             $profileQuery = 'SELECT librarian_name as "name", librarian_image as "image" FROM librarian WHERE id = ?';
                            break;
                        case 'superadmin':
                            $profileDetails = ['name' => 'Super Admin', 'image' => null];
                            break;
                    }
                    
                    if (!empty($profileQuery)) {
                        $stmtProfile = $conn->prepare($profileQuery);
                        $stmtProfile->execute([$user['id']]);
                        $profileDetails = $stmtProfile->fetch(PDO::FETCH_ASSOC);
                    }

                    if ($profileDetails) {
                        $cookie_options = [
                            'expires' => time() + 86400, 'path' => '/', 'secure' => true,
                            'httponly' => true, 'samesite' => 'Lax'
                        ];

                        setcookie("encrypted_user_id", encrypt_id($user['id']), $cookie_options);
                        setcookie("encrypted_user_role", encrypt_id($user['role']), $cookie_options);
                        setcookie("encrypted_user_name", encrypt_id($profileDetails['name']), $cookie_options);
                        setcookie("encrypted_profile_image", encrypt_id($profileDetails['image'] ?? ''), $cookie_options);

                        $response = ['status' => 'success', 'redirect' => 'dashboard.php'];
                    } else {
                         $response = ['status' => 'error', 'message' => 'User profile not found. Please contact support.'];
                    }
                }
            } else {
                $response = ['status' => 'error', 'message' => 'Invalid email or password.'];
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $response = ['status' => 'error', 'message' => 'A server error occurred. Please try again later.'];
        }
    }
    echo json_encode($response);
    $conn = null;
    exit();
}

// --- PHP LOGIC ENDS HERE ---
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
                <div class="logo"><i class="far fa-smile"></i></div>
                <h1>Welcome Back!</h1>
                <p>Your central hub for school management and monitoring.</p>
            </div>
            <div class="col-12 col-lg-7 login-form-panel">
                <div class="login-form-container">
                    <h2>Login</h2>
                    <p class="subtitle">Please enter your credentials to proceed.</p>
                    <div id="login-alert-placeholder"></div>
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