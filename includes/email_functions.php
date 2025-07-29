<?php
// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer files
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function send_email($to, $subject, $body)
{
    // Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        // --- Server settings ---
        // Enable verbose debug output (optional, remove for production)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; 

        $mail->isSMTP();                                   // Send using SMTP
        $mail->Host = 'smtp.gmail.com';              // Set the SMTP server to send through
        $mail->SMTPAuth = true;                          // Enable SMTP authentication
        $mail->Username = 'sms0407111726@gmail.com';        // SMTP username (your Gmail address)
        $mail->Password = 'damsvtdbkypkmvrn';           // SMTP password (your Gmail App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;    // Enable implicit TLS encryption
        $mail->Port = 465;                           // TCP port to connect to

        // --- Recipients ---
        $mail->setFrom('your_email@gmail.com', 'BMC School System');
        $mail->addAddress($to); // Add a recipient

        // --- Content ---
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body); // Plain text version for non-HTML mail clients

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error message instead of displaying it to the user
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>