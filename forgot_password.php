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

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['email'])) {
    $response['message'] = 'Invalid request. Please submit the form correctly.';
    echo json_encode($response);
    exit();
}

try {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format provided.');
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        $otp = random_int(100000, 999999);
        $otp_hash = password_hash((string)$otp, PASSWORD_DEFAULT);
        $expiry = date("Y-m-d H:i:s", strtotime('+15 minutes'));

        $conn->query("DELETE FROM password_resets WHERE email = '{$conn->real_escape_string($email)}'");

        $insert_stmt = $conn->prepare("INSERT INTO password_resets (user_id, email, otp_hash, expires_at) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("isss", $user_id, $email, $otp_hash, $expiry);

        if ($insert_stmt->execute()) {
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
            $subject = "Your Password Reset Code";
            $body = "
                <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                    <h2>Password Reset Request</h2>
                    <p>Hello,</p>
                    <p>Your One-Time Password (OTP) is:</p>
                    <p style='text-align:center; font-size: 24px; font-weight: bold; letter-spacing: 5px; background-color: #f0f0f0; padding: 10px 20px; border-radius: 5px;'>
                        $otp
                    </p>
                    <p>This code is valid for 15 minutes. If you did not request a password reset, please ignore this email.</p>
                </div>
            ";

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            $response['status'] = 'success';
            $response['message'] = 'An OTP has been sent to your email address.';
        } else {
            throw new Exception("Failed to store reset request in the database.");
        }
    } else {
        $response['status'] = 'success';
        $response['message'] = 'If an account with that email exists, a reset code has been sent.';
    }
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'Mailer Error: ' . $e->getMessage();
}

echo json_encode($response);
