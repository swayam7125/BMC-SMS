<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../includes/connect.php";
require_once __DIR__ . "/../../encryption.php";
require_once __DIR__ . '/../../includes/dompdf/autoload.inc.php'; 
require_once __DIR__ . '/../../includes/log_system.php'; // Log system included

use Dompdf\Dompdf;
use Dompdf\Options;

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if (!$userId) {
    die("Authentication error. Please log in again.");
}
$student_id = $userId;

$fee_ids_for_receipt = [];
// Determine if it's a single or bulk payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fee_ids']) && is_array($_POST['fee_ids'])) {
    $fee_ids_for_receipt = $_POST['fee_ids'];
} elseif (isset($_GET['fee_id'])) {
    $fee_ids_for_receipt[] = $_GET['fee_id'];
}

if (empty($fee_ids_for_receipt)) {
    die("No fee selected for payment or download.");
}

// --- Payment Processing (only for unpaid fees) ---
$unpaid_fees_to_pay = [];
$placeholders = implode(',', array_fill(0, count($fee_ids_for_receipt), '?'));

try {
    $sql_check = "SELECT id, status FROM student_fees WHERE id IN ($placeholders) AND student_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $params = array_merge($fee_ids_for_receipt, [$student_id]);
    $stmt_check->execute($params);
    $fees_to_check = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

    if (count($fees_to_check) !== count($fee_ids_for_receipt)) {
        throw new Exception("One or more selected fees do not belong to you or do not exist.");
    }

    foreach ($fees_to_check as $fee) {
        if ($fee['status'] === 'Unpaid') {
            $unpaid_fees_to_pay[] = $fee['id'];
        }
    }

    if (!empty($unpaid_fees_to_pay)) {
        $conn->beginTransaction();
        
        $update_stmt = $conn->prepare("UPDATE student_fees SET status = 'Paid', payment_date = NOW(), amount_paid = (SELECT amount FROM fees WHERE id = student_fees.fee_id) WHERE id = ?");
        $fee_details_stmt = $conn->prepare("SELECT f.fee_type, f.amount, s.school_id FROM student_fees sf JOIN fees f ON sf.fee_id = f.id JOIN student s ON sf.student_id = s.id WHERE sf.id = ?");
        
        $total_paid = 0;
        $paid_fee_types = [];
        $school_id = null;

        foreach ($unpaid_fees_to_pay as $student_fee_id) {
            $fee_details_stmt->execute([$student_fee_id]);
            $details = $fee_details_stmt->fetch();
            $total_paid += $details['amount'];
            $paid_fee_types[] = "'" . $details['fee_type'] . "'";
            $school_id = $details['school_id'];
            $update_stmt->execute([$student_fee_id]);
        }

        if ($school_id) {
            $student_info_stmt = $conn->prepare("SELECT student_name, std FROM student WHERE id = ?");
            $student_info_stmt->execute([$student_id]);
            $student_info = $student_info_stmt->fetch();

            $hr_stmt = $conn->prepare("SELECT id FROM hr WHERE school_id = ?");
            $hr_stmt->execute([$school_id]);
            $hr_users = $hr_stmt->fetchAll(PDO::FETCH_COLUMN);

            $message = htmlspecialchars($student_info['student_name']) . " (Std: " . $student_info['std'] . ") has paid " . count($unpaid_fees_to_pay) . " fee(s) (" . implode(', ', $paid_fee_types) . ") totaling ₹" . number_format($total_paid, 2) . ".";
            $link = "pages/hr/manage_fees.php";

            if (!empty($hr_users)) {
                $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
                foreach ($hr_users as $hr_user_id) {
                    $notify_stmt->execute([$hr_user_id, $message, $link, 'fee_payment']);
                }
            }
        }
        $conn->commit();
        // Log the successful payment
        log_interaction($role, $userId, "FEE PAYMENT: Successfully paid " . count($unpaid_fees_to_pay) . " fee(s) totaling Rs. " . $total_paid, $userName);
    }

    // --- PDF Generation ---
    $sql_receipt = "
        SELECT s.student_name, s.rollno, s.std, sc.school_name, sc.address, sc.school_logo, f.fee_type, sf.amount_paid, sf.payment_date, sf.id as transaction_id
        FROM student_fees sf
        JOIN student s ON sf.student_id = s.id
        JOIN fees f ON sf.fee_id = f.id
        JOIN school sc on s.school_id = sc.id
        WHERE sf.id IN ($placeholders) AND sf.student_id = ?";
    $stmt_receipt = $conn->prepare($sql_receipt);
    $stmt_receipt->execute($params);
    $receipt_data = $stmt_receipt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($receipt_data)) { throw new Exception("Could not find fee details for the receipt."); }
    
    // Log receipt generation
    $fee_ids_str = implode(', ', $fee_ids_for_receipt);
    log_interaction($role, $userId, "FEE RECEIPT: Generating receipt for payment ID(s): {$fee_ids_str}.", $userName);


    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    ob_start();
    // This assumes fee_receipt.php only contains the HTML structure for the PDF
    include 'fee_receipt_template.php'; 
    $html = ob_get_clean();

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $transaction_ids_str = implode('-', array_column($receipt_data, 'transaction_id'));
    $dompdf->stream("receipt-" . $transaction_ids_str . ".pdf", ["Attachment" => false]);
    exit();

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Log the error
    log_interaction($role, $userId, "FEE PAYMENT/RECEIPT ERROR: " . $e->getMessage(), $userName);
    die("An error occurred: " . $e->getMessage());
}
?>