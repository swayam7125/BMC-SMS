<?php
// It's good practice to use PHPMailer for sending emails as it's more reliable than the mail() function.
// If you haven't installed it, run: composer require phpmailer/phpmailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// You might need to adjust the path to the autoloader based on your project structure
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

function send_otp_email($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com'; // Set your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_email@example.com'; // SMTP username
        $mail->Password   = 'your_password'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('no-reply@bmcsms.com', 'BMC-SMS');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset OTP';
        $mail->Body    = "Your One-Time Password (OTP) for resetting your password is: <b>{$otp}</b>. It is valid for 10 minutes.";
        $mail->AltBody = "Your One-Time Password (OTP) for resetting your password is: {$otp}. It is valid for 10 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends an email to a student upon admission approval.
 *
 * @param string $email The student's email address.
 * @param string $student_name The student's name.
 * @param string $password The temporary password for the student's new account.
 * @return bool True on success, false on failure.
 */
function sendAdmissionApprovalEmail($email, $student_name, $password) {
    $mail = new PHPMailer(true);
    try {
        // Server settings (configure with your actual SMTP details)
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@example.com';
        $mail->Password = 'your_password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('admissions@bmcsms.com', 'BMC-SMS Admissions');
        $mail->addAddress($email, $student_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Congratulations! Your Admission has been Approved';
        $mail->Body    = "
            <p>Dear {$student_name},</p>
            <p>We are pleased to inform you that your admission to our institution has been approved.</p>
            <p>An account has been created for you in our student portal. You can log in using the following credentials:</p>
            <ul>
                <li><strong>Email:</strong> {$email}</li>
                <li><strong>Password:</strong> {$password}</li>
            </ul>
            <p>We strongly recommend that you change your password after your first login.</p>
            <p>Welcome aboard!</p>
            <p>Best regards,<br>The Admissions Team</p>";
        $mail->AltBody = "Dear {$student_name},\nYour admission has been approved. Your login email is {$email} and your password is {$password}. Please change it after your first login.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Admission Approval Email could not be sent to {$email}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends an email to a student upon admission rejection.
 *
 * @param string $email The student's email address.
 * @param string $student_name The student's name.
 * @param string $reason The reason for rejection.
 * @return bool True on success, false on failure.
 */
function sendAdmissionRejectionEmail($email, $student_name, $reason) {
    $mail = new PHPMailer(true);
    try {
        // Server settings (configure with your actual SMTP details)
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@example.com';
        $mail->Password = 'your_password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('admissions@bmcsms.com', 'BMC-SMS Admissions');
        $mail->addAddress($email, $student_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Update on Your Admission Application';
        $mail->Body    = "
            <p>Dear {$student_name},</p>
            <p>Thank you for your interest in our institution. After careful review of your application, we regret to inform you that we are unable to offer you admission at this time.</p>
            <p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>
            <p>We wish you the best in your future academic endeavors.</p>
            <p>Sincerely,<br>The Admissions Team</p>";
        $mail->AltBody = "Dear {$student_name},\nWe regret to inform you that we are unable to offer you admission. Reason: " . $reason;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Admission Rejection Email could not be sent to {$email}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}