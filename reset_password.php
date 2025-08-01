<?php
// --- Set Timezone to prevent expiry issues ---
date_default_timezone_set('Asia/Kolkata');

// --- PHPMailer Integration ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Use the correct path since your PHPMailer folder is inside 'includes'
require_once __DIR__ . '/includes/PHPMailer/src/Exception.php';
require_once __DIR__ . '/includes/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/includes/PHPMailer/src/SMTP.php';

// --- Database Connection ---
include_once "./includes/connect.php";

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // --- Form Validation ---
    if (empty($email) || empty($otp) || empty($new_password) || empty($confirm_password)) {
        $response['message'] = 'Please fill in all required fields.';
    } elseif ($new_password !== $confirm_password) {
        $response['message'] = 'The new passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long.';
    } else {
        // --- OTP Verification ---
        $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $reset_data = $result->fetch_assoc();

            if (password_verify($otp, $reset_data['otp_hash'])) {
                // OTP is correct. Proceed to update the password.
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $reset_data['user_id']);

                if ($update_stmt->execute()) {
                    // --- Send Password Change Confirmation Email ---
                    try {
                        $mail = new PHPMailer(true);

                        // Server settings
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'sms0407111726@gmail.com';
                        $mail->Password   = 'damsvtdbkypkmvrn';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;

                        // Recipients
                        $mail->setFrom('sms0407111726@gmail.com', 'BMC School System');
                        $mail->addAddress($email);

                        // Content
                        $subject = "Your Password Has Been Changed";
                        $body = "
                            <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                                <h2>Password Changed Successfully</h2>
                                <p>Hello,</p>
                                <p>This is a confirmation that the password for your account with the email <strong>$email</strong> has just been changed.</p>
                                <p>If you did not make this change, please contact our support team immediately.</p>
                                <p>Thank you,<br>The BMC School System Team</p>
                            </div>
                        ";

                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        $mail->Body    = $body;

                        $mail->send();
                    } catch (Exception $e) {
                        // If email fails, don't block the user. Just log the error.
                        error_log("Password reset confirmation email could not be sent. Mailer Error: {$e->getMessage()}");
                    }
                    // --- End of Email Logic ---

                    // Password updated successfully. Now delete the reset request.
                    $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                    $delete_stmt->bind_param("s", $email);
                    $delete_stmt->execute();

                    $response['status'] = 'success';
                    $response['message'] = 'Your password has been reset successfully! You can now log in.';
                } else {
                    $response['message'] = 'Failed to update your password. Please try again.';
                }
            } else {
                $response['message'] = 'The OTP you entered is invalid. Please check and try again.';
            }
        } else {
            $response['message'] = 'Invalid or expired OTP. Please request a new one.';
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
