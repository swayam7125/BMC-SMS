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

    // --- FIXED: Converted to PDO syntax ---
    $stmt = $conn->prepare('SELECT "id" FROM "users" WHERE "email" = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_id = $user['id'];
        $otp = random_int(100000, 999999);
        $otp_hash = password_hash((string)$otp, PASSWORD_DEFAULT);
        $expiry = date("Y-m-d H:i:s", strtotime('+15 minutes'));

        // Delete any old OTPs for this user
        $delete_stmt = $conn->prepare('DELETE FROM "password_resets" WHERE "email" = ?');
        $delete_stmt->execute([$email]);

        // Insert the new OTP
        $insert_stmt = $conn->prepare('INSERT INTO "password_resets" ("user_id", "email", "otp_hash", "expires_at") VALUES (?, ?, ?, ?)');
        
        if ($insert_stmt->execute([$user_id, $email, $otp_hash, $expiry])) {
            $mail = new PHPMailer(true);
            // SMTP configuration... (remains the same)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sms0407111726@gmail.com';
            $mail->Password   = 'damsvtdbkypkmvrn';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('sms0407111726@gmail.com', 'BMC School System');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Your Password Reset Code";
            $mail->Body    = "Your One-Time Password (OTP) is: <b>$otp</b>. It is valid for 15 minutes.";

            $mail->send();
            $response['status'] = 'success';
            $response['message'] = 'An OTP has been sent to your email address.';
        } else {
            throw new Exception("Failed to store reset request in the database.");
        }
    } else {
        // To prevent user enumeration, send a generic success message even if the email doesn't exist.
        $response['status'] = 'success';
        $response['message'] = 'If an account with that email exists, a reset code has been sent.';
    }
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

echo json_encode($response);
$conn = null; // Close connection
?>
 