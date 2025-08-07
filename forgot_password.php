<?php
// forgot_password.php

include_once "./includes/connect.php";
// Include your new centralized email function
require_once './includes/email_functions.php';

header('Content-Type: application/json');
$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    try {
        // Check if the user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            // Generate OTP, hash it, and set an expiration time
            $otp = random_int(100000, 999999);
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $expires_at = date("Y-m-d H:i:s", time() + 600); // 10 minutes

            // Store the hashed OTP in the database
            $update_stmt = $conn->prepare("UPDATE users SET otp_hash = ?, otp_expires_at = ? WHERE email = ?");
            $update_stmt->execute([$otp_hash, $expires_at, $email]);

            // --- Use your new email function to send the OTP ---
            $subject = 'Your Password Reset OTP';
            $body = 'Your One-Time Password (OTP) to reset your password is: <b>' . $otp . '</b>';

            if (send_email($email, $subject, $body)) {
                $response = ['status' => 'success', 'message' => 'An OTP has been sent to your email address.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to send OTP email. Please contact support.'];
            }
        } else {
            // To prevent user enumeration, show a generic message even if the email doesn't exist.
            $response = ['status' => 'success', 'message' => 'If an account with that email exists, an OTP has been sent.'];
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response = ['status' => 'error', 'message' => 'A database error occurred.'];
    }
} else {
    $response = ['status' => 'error', 'message' => 'Invalid request.'];
}

echo json_encode($response);
$conn = null;
