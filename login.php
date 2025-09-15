<?php
require_once "./includes/connect.php";
require_once "./includes/ajax_helpers.php";
require_once "./includes/response.php";
require_once "encryption.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is already logged in
if (isset($_COOKIE['encrypted_user_role'])) {
    header("Location: index.php?page=dashboard");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        Response::error('Invalid request verification.', 403);
    }

    // Validate and sanitize input
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Response::error('Invalid email format.');
    }

    $password = trim($_POST['password']);
    $user_lat = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $user_lon = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

    try {
        // OPTIMIZED QUERY - Get user data from all possible tables
        $query = "SELECT u.id, u.password, u.role, u.account_status,
                         COALESCE(s.student_name, t.teacher_name, p.principal_name, l.librarian_name, py.payroll_name, 'Super Admin') as user_name,
                         COALESCE(s.student_image, t.teacher_image, p.principal_image, l.librarian_image, py.payroll_image, NULL) as profile_image
                  FROM users u
                  LEFT JOIN student s ON u.id = s.id AND u.role = 'student'
                  LEFT JOIN teacher t ON u.id = t.id AND u.role = 'teacher'
                  LEFT JOIN principal p ON u.id = p.id AND u.role = 'principal'
                  LEFT JOIN librarian l ON u.id = l.id AND u.role = 'librarian'
                  LEFT JOIN payroll py ON u.id = py.id AND u.role = 'payroll'
                  WHERE u.email = ?";

        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Check account status
            if ($user['account_status'] !== 'Active') {
                Response::error('Your account is currently ' . $user['account_status'] . '. Please contact administration.');
            }

            // Set session variables (optional, for additional security)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['user_name'];

            // Set cookies
            $cookie_expiry = isset($_POST['remember_me']) ? time() + (86400 * 30) : 0; // 30 days or session
            $cookie_options = [
                'expires' => $cookie_expiry,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']), // Only over HTTPS if available
                'httponly' => true, // Prevent JavaScript access
                'samesite' => 'Lax' // CSRF protection
            ];

            setcookie('encrypted_user_id', encrypt_id($user['id']), $cookie_options);
            setcookie('encrypted_user_role', encrypt_id($user['role']), $cookie_options);
            setcookie('encrypted_user_name', encrypt_id($user['user_name']), $cookie_options);
            setcookie('encrypted_profile_image', encrypt_id($user['profile_image'] ?? ''), $cookie_options);

            // Log successful login (optional)
            error_log("Successful login: User ID " . $user['id'] . " (" . $user['user_name'] . ") logged in.");

            // FIXED: Use the routing system instead of direct dashboard.php
            Response::success(
                'Login successful! Redirecting...',
                null,
                'index.php?page=dashboard'
            );
        } else {
            // Log failed login attempt (optional)
            error_log("Failed login attempt for email: " . $email);
            Response::error('Invalid email or password.');
        }
    } catch (PDOException $e) {
        // Log the actual error for debugging
        error_log("Login Database Error: " . $e->getMessage());
        Response::error('A database error occurred. Please try again later.', 500);
    } catch (Exception $e) {
        // Log any other errors
        error_log("Login General Error: " . $e->getMessage());
        Response::error('An unexpected error occurred. Please try again later.', 500);
    }
}

// Generate a new CSRF token for the login form
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BMC-SMS - Login</title>
    <link rel="shortcut icon" href="./assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="./assets/css/login.css" />
</head>

<body>
    <div class="container-fluid">
        <div class="row vh-100">
            <!-- Left side - Branding -->
            <div class="col-lg-6 login-branding-panel d-none d-lg-flex">
                <div class="logo">BMC-SMS</div>
                <p class="tagline">Efficiently Managing Education</p>
            </div>

            <!-- Right side - Login Form -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center bg-light">
                <div class="login-form-container">
                    <h2 class="form-title">Welcome Back!</h2>
                    <p class="form-subtitle">Please enter your details to sign in.</p>

                    <!-- Alert placeholder for messages -->
                    <div id="login-alert-placeholder"></div>

                    <form id="loginForm">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <!-- Email Input -->
                        <div class="form-group position-relative">
                            <i class="fas fa-envelope form-icon"></i>
                            <input type="email" name="email" id="email" class="form-control form-control-custom" placeholder="Email" required autocomplete="email" />
                        </div>

                        <!-- Password Input -->
                        <div class="form-group position-relative">
                            <i class="fas fa-lock form-icon"></i>
                            <input type="password" name="password" id="password" class="form-control form-control-custom" placeholder="Password" required autocomplete="current-password" />
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMeCheckbox" />
                                <label class="form-check-label" for="rememberMeCheckbox">Remember me</label>
                            </div>
                            <a href="#" class="forgot-password-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot Password?</a>
                        </div>

                        <!-- Hidden fields for geolocation -->
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-custom-login">
                            <span class="button-text">Sign In</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="reset-alert-placeholder"></div>

                    <!-- Send OTP Form -->
                    <form id="sendOtpForm">
                        <p>Enter your email address and we'll send you an OTP to reset your password.</p>
                        <div class="mb-3">
                            <label for="resetEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="resetEmail" required>
                        </div>
                        <button type="submit" class="btn btn-custom-login w-100">Send OTP</button>
                    </form>

                    <!-- Reset Password Form -->
                    <form id="resetPasswordForm" class="d-none">
                        <p>An OTP has been sent to <strong id="userEmailDisplay"></strong>. Please enter it below along with your new password.</p>
                        <input type="hidden" id="hiddenEmail" name="email">
                        <div class="mb-3">
                            <label for="otp" class="form-label">One-Time Password (OTP)</label>
                            <input type="text" class="form-control" id="otp" name="otp" required maxlength="6">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                        <button type="submit" class="btn btn-custom-login w-100">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/login.js"></script>
</body>

</html>