<?php
// reset_password.php

include_once "./includes/connect.php";

header('Content-Type: application/json');
$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $otp = $_POST['otp'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($email) || empty($otp) || empty($new_password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
        exit();
    }
    if (strlen($new_password) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long.']);
        exit();
    }
    if ($new_password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT otp_hash, otp_expires_at FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && !empty($user['otp_hash']) && password_verify($otp, $user['otp_hash'])) {
            if (strtotime($user['otp_expires_at']) > time()) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update_stmt = $conn->prepare("UPDATE users SET password = ?, otp_hash = NULL, otp_expires_at = NULL WHERE email = ?");
                $update_stmt->execute([$hashed_password, $email]);

                $response = ['status' => 'success', 'message' => 'Password has been reset successfully. You can now log in.'];
            } else {
                $response = ['status' => 'error', 'message' => 'The OTP has expired. Please request a new one.'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Invalid OTP. Please try again.'];
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
