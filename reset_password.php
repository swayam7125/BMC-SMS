<?php
date_default_timezone_set('Asia/Kolkata');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/includes/PHPMailer/src/Exception.php';
require_once __DIR__ . '/includes/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/includes/PHPMailer/src/SMTP.php';

include_once "./includes/connect.php";

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($email) || empty($otp) || empty($new_password) || empty($confirm_password)) {
        $response['message'] = 'Please fill in all required fields.';
    } elseif ($new_password !== $confirm_password) {
        $response['message'] = 'The new passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long.';
    } else {
        // --- FIXED: Converted to PDO syntax and NOW() to CURRENT_TIMESTAMP ---
        try {
            $stmt = $conn->prepare('SELECT * FROM "password_resets" WHERE "email" = ? AND "expires_at" > CURRENT_TIMESTAMP');
            $stmt->execute([$email]);
            $reset_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reset_data && password_verify($otp, $reset_data['otp_hash'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update_stmt = $conn->prepare('UPDATE "users" SET "password" = ? WHERE "id" = ?');
                if ($update_stmt->execute([$hashed_password, $reset_data['user_id']])) {
                    
                    // Password updated, now delete the reset request.
                    $delete_stmt = $conn->prepare('DELETE FROM "password_resets" WHERE "email" = ?');
                    $delete_stmt->execute([$email]);

                    $response['status'] = 'success';
                    $response['message'] = 'Your password has been reset successfully! You can now log in.';
                    
                    // Optional: Send confirmation email (code remains the same)

                } else {
                    $response['message'] = 'Failed to update your password. Please try again.';
                }
            } else {
                $response['message'] = 'Invalid or expired OTP. Please check and try again.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
$conn = null; // Close connection
?>
