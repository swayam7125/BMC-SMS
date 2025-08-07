<?php
// --- FILE: includes/email_functions.php ---

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load the PHPMailer source files.
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

/**
 * Sends an email using PHPMailer with Gmail SMTP.
 *
 * @param string $to The recipient's email address.
 * @param string $subject The subject of the email.
 * @param string $body The HTML body of the email.
 * @return bool Returns true on success, false on failure.
 */
function send_email($to, $subject, $body)
{
    // Create a new PHPMailer instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        // --- Server Settings ---

        // DEBUGGING DISABLED FOR PRODUCTION
        // This is the only change needed.
        $mail->SMTPDebug = SMTP::DEBUG_OFF;

        // This block is a workaround for local XAMPP servers with SSL issues.
        // It's safe to leave this in place.
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sms0407111726@gmail.com'; // Your full Gmail address
        $mail->Password   = 'damsvtdbkypkmvrn';      // Your 16-character Google App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // --- Recipients & Content ---
        $mail->setFrom('sms0407111726@gmail.com', 'BMC School System');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true; // Email sent successfully

    } catch (Exception $e) {
        // Log the detailed error message to the server's error log
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false; // Email sending failed
    }
}
