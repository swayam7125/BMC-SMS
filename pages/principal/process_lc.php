<?php
require_once '../../includes/connect.php';
require_once '../../encryption.php';
require_once '../../includes/dompdf/autoload.inc.php';
require_once '../../includes/log_system.php'; // Log system included

use Dompdf\Dompdf;
use Dompdf\Options;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['student_id'])) {
    die("Student ID not provided.");
}
$student_id = decrypt_id($_GET['student_id']);

try {
    // Fetch student and school details for the LC
    $stmt = $conn->prepare("
        SELECT 
            s.student_name, s.father_name, s.mother_name, s.dob, s.std, s.date_of_joining,
            sch.school_name, sch.address as school_address, sch.school_logo
        FROM student s
        JOIN school sch ON s.school_id = sch.id
        WHERE s.id = ?
    ");
    $stmt->execute([$student_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student_data) {
        throw new Exception("Student data not found.");
    }
    
    // Log the successful action
    log_interaction($role, $userId, "LC: Generated Leaving Certificate for student: " . $student_data['student_name'] . " (ID: {$student_id}).", $userName);

    // --- PDF Generation using DOMPDF ---
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    // Get today's date for the LC
    $leaving_date = date('F j, Y');

    // Start output buffering to capture HTML
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Leaving Certificate</title>
        <style>
            body { font-family: 'Helvetica', sans-serif; line-height: 1.6; color: #333; }
            .lc-container { width: 90%; margin: auto; border: 10px solid #eee; padding: 40px; border-radius: 15px; background: #fff; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 40px; }
            .header img { max-height: 100px; margin-bottom: 10px; }
            .header h1 { margin: 0; color: #2c3e50; font-size: 28px; }
            .header h2 { margin: 5px 0 0; color: #34495e; font-size: 22px; font-weight: normal; }
            .content p { margin: 15px 0; font-size: 16px; text-align: justify; }
            .student-details-table { width: 100%; border-collapse: collapse; margin-top: 30px; margin-bottom: 30px; }
            .student-details-table td { padding: 12px; border: 1px solid #ccc; }
            .student-details-table td:first-child { font-weight: bold; width: 30%; background-color: #f9f9f9; }
            .footer { margin-top: 60px; }
            .signature-line { border-top: 1px solid #333; width: 250px; margin-top: 60px; }
        </style>
    </head>
    <body>
        <div class="lc-container">
            <div class="header">
                <?php if ($student_data['school_logo']): ?>
                    <img src="<?php echo htmlspecialchars($student_data['school_logo']); ?>" alt="School Logo">
                <?php endif; ?>
                <h1><?php echo htmlspecialchars($student_data['school_name']); ?></h1>
                <h2><?php echo htmlspecialchars($student_data['school_address']); ?></h2>
            </div>
            <h3 style="text-align: center; text-transform: uppercase; letter-spacing: 2px;">Leaving Certificate</h3>
            <div class="content">
                <p>
                    This is to certify that <strong><?php echo htmlspecialchars($student_data['student_name']); ?></strong>,
                    son/daughter of Mr. <strong><?php echo htmlspecialchars($student_data['father_name']); ?></strong> and 
                    Mrs. <strong><?php echo htmlspecialchars($student_data['mother_name']); ?></strong>, was a bonafide student of this school.
                </p>
                <table class="student-details-table">
                    <tr>
                        <td>Student Name</td>
                        <td><?php echo htmlspecialchars($student_data['student_name']); ?></td>
                    </tr>
                    <tr>
                        <td>Date of Birth</td>
                        <td><?php echo date('F j, Y', strtotime($student_data['dob'])); ?></td>
                    </tr>
                    <tr>
                        <td>Class at the time of leaving</td>
                        <td>Standard <?php echo htmlspecialchars($student_data['std']); ?></td>
                    </tr>
                    <tr>
                        <td>Date of Joining</td>
                        <td><?php echo date('F j, Y', strtotime($student_data['date_of_joining'])); ?></td>
                    </tr>
                     <tr>
                        <td>Date of Leaving</td>
                        <td><?php echo $leaving_date; ?></td>
                    </tr>
                </table>
                <p>
                    His/Her character and conduct during his/her stay at the school were satisfactory. 
                    We wish him/her all the best for his/her future endeavors.
                </p>
            </div>
            <div class="footer">
                <p>Principal's Signature</p>
                <div class="signature-line"></div>
                <strong>(<?php echo htmlspecialchars($userName); ?>)</strong>
            </div>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Deleting the student after generating the certificate
    $stmt_delete = $conn->prepare("DELETE FROM student WHERE id = ?");
    $stmt_delete->execute([$student_id]);
    
    // Log the student deletion
    log_interaction($role, $userId, "STUDENT DELETED: Deleted student " . $student_data['student_name'] . " (ID: {$student_id}) after generating LC.", $userName);

    $dompdf->stream("Leaving_Certificate_" . str_replace(' ', '_', $student_data['student_name']) . ".pdf", ["Attachment" => false]);
    exit();

} catch (Exception $e) {
    // Log any errors that occur
    log_interaction($role, $userId, "LC ERROR: Failed to generate Leaving Certificate for student ID {$student_id}. Error: " . $e->getMessage(), $userName);
    die("An error occurred: " . $e->getMessage());
}
?>