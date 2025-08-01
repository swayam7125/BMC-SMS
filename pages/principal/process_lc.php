<?php
// Includes and session start
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// You must download FPDF from http://www.fpdf.org/ and place the fpdf.php file
// inside a directory named 'fpdf' in this folder.
require('fpdf/fpdf.php');

// Check for valid session and role
if (!isset($_COOKIE['encrypted_user_role']) || decrypt_id($_COOKIE['encrypted_user_role']) !== 'schooladmin') {
    header("Location: generate_lc.php?error=Unauthorized access.");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: generate_lc.php?error=Invalid request method.");
    exit;
}

// Get and sanitize input
$student_email = trim($_POST['student_email'] ?? '');
$leaving_date = trim($_POST['leaving_date'] ?? '');
$reason_for_leaving = trim($_POST['reason_for_leaving'] ?? '');
$principal_user_id = decrypt_id($_COOKIE['encrypted_user_id']); 

if (empty($student_email) || empty($leaving_date) || empty($reason_for_leaving)) {
    header("Location: generate_lc.php?error=All fields are required.");
    exit;
}

try {
    mysqli_begin_transaction($conn);

    // 1. Fetch student, school, and principal details
    $query = "SELECT s.*, sch.school_name, sch.address AS school_address, sch.phone AS school_phone, sch.email AS school_email, u.id AS user_id, u.role AS user_role
              FROM student s 
              LEFT JOIN school sch ON s.school_id = sch.id
              LEFT JOIN users u ON s.email = u.email
              WHERE s.email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $student_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $student_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$student_data) {
        mysqli_rollback($conn);
        header("Location: generate_lc.php?error=Student with that email not found.");
        exit;
    }

    $student_id = $student_data['id']; 
    $user_id = $student_data['user_id']; 

    // 2. Fetch principal's name for the signature block
    $principal_query = "SELECT principal_name FROM principal WHERE id = ?";
    $stmt_principal = mysqli_prepare($conn, $principal_query);
    mysqli_stmt_bind_param($stmt_principal, "i", $principal_user_id);
    mysqli_stmt_execute($stmt_principal);
    $principal_result = mysqli_stmt_get_result($stmt_principal);
    $principal_data = mysqli_fetch_assoc($principal_result);
    $principal_name = $principal_data['principal_name'] ?? 'Principal Name';
    mysqli_stmt_close($stmt_principal);

    // 3. Insert into `deleted_students` table
    $deleted_at = date('Y-m-d H:i:s');
    $insert_deleted_query = "INSERT INTO deleted_students 
                             (id, student_name, email, rollno, std, academic_year, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, school_id, reason_for_leaving, deleted_by_role, deleted_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conn, $insert_deleted_query);
    
    mysqli_stmt_bind_param($stmt_insert, "isssssssssssssisss",
        $student_id,
        $student_data['student_name'],
        $student_data['email'],
        $student_data['rollno'],
        $student_data['std'],
        $student_data['academic_year'],
        $student_data['dob'],
        $student_data['gender'],
        $student_data['blood_group'],
        $student_data['address'],
        $student_data['father_name'],
        $student_data['father_phone'],
        $student_data['mother_name'],
        $student_data['mother_phone'],
        $student_data['school_id'],
        $reason_for_leaving,
        $student_data['user_role'],
        $deleted_at
    );
    if (!mysqli_stmt_execute($stmt_insert)) {
        throw new Exception("Failed to insert into deleted_students: " . mysqli_stmt_error($stmt_insert));
    }
    mysqli_stmt_close($stmt_insert);

    // 4. Delete user from `users` and `student` tables (ON DELETE CASCADE handles `student` table)
    $delete_user_query = "DELETE FROM users WHERE id = ?";
    $stmt_delete_user = mysqli_prepare($conn, $delete_user_query);
    mysqli_stmt_bind_param($stmt_delete_user, "i", $user_id);
    if (!mysqli_stmt_execute($stmt_delete_user)) {
        throw new Exception("Failed to delete from users table: " . mysqli_stmt_error($stmt_delete_user));
    }
    mysqli_stmt_close($stmt_delete_user);

    mysqli_commit($conn);

    // --- Generate the new, professional PDF Leaving Certificate ---
    class PDF extends FPDF {
        function Header() {
            // No header in the provided image format
        }
        function Footer() {
            // No footer in the provided image format
        }
    }

    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);

    // Header Section
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 10, strtoupper(htmlspecialchars($student_data['school_name'])), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, htmlspecialchars($student_data['school_address']), 0, 1, 'C');
    $pdf->Cell(0, 5, 'Email: ' . htmlspecialchars($student_data['school_email']) . ' | Phone: ' . htmlspecialchars($student_data['school_phone']), 0, 1, 'C');
    $pdf->Ln(10);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(15);

    // Title Section
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'LEAVING CERTIFICATE', 0, 1, 'C');
    $pdf->Ln(10);

    // Certificate Details (First Paragraph)
    $pdf->SetFont('Arial', '', 12);
    $paragraph = 'This is to certify that ' . htmlspecialchars($student_data['student_name']) . ', son/daughter of ' . htmlspecialchars($student_data['father_name']) . 
                 ', was a bonafide student of this school from ' . htmlspecialchars($student_data['academic_year']) . 
                 '. The student was studying in standard ' . htmlspecialchars($student_data['std']) . 
                 ' and his/her date of birth is ' . date('F j, Y', strtotime($student_data['dob'])) . '.';
    $pdf->MultiCell(0, 7, $paragraph);
    $pdf->Ln(5);

    // Reason for Leaving
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Reason for Leaving:', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(0, 7, htmlspecialchars($reason_for_leaving));
    $pdf->Ln(5);
    
    // Concluding Paragraph
    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(0, 7, 'The student bears a good moral character. We wish the student all the best for his/her future endeavors.');

    $pdf->Ln(20);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(50, 5, 'Certificate No.: ' . $student_id, 0, 0, 'L');
    $pdf->Cell(100, 5, '', 0, 0, 'L');
    $pdf->Cell(40, 5, 'Date: ' . date('d/m/Y'), 0, 1, 'R');
    $pdf->Ln(20);

    // Signature Block
    $pdf->Cell(0, 5, '_________________________', 0, 1, 'R');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 7, strtoupper(htmlspecialchars($principal_name)), 0, 1, 'R');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, 'Principal', 0, 1, 'R');

    // Output the PDF
    $pdf->Output('D', 'Leaving_Certificate_' . $student_data['student_name'] . '.pdf');

} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: generate_lc.php?error=An error occurred: " . urlencode($e->getMessage()));
    exit;
}
?>