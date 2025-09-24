<?php
// pages/student/process_pay_fees.php
header('Content-Type: application/json');
require_once "../../includes/connect.php";
require_once "../../encryption.php";
require_once "../../includes/ajax_helpers.php";
require_once '../../includes/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// Authorization check
$role = null;
$userId = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'student' || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax_request()) {

    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $fee_id = filter_input(INPUT_POST, 'fee_id', FILTER_VALIDATE_INT);
    $paid_at = date('Y-m-d H:i:s');
    $invoice_path = null;

    if ($amount === false || $amount <= 0 || !$fee_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount or fee ID.']);
        exit;
    }

    try {
        $conn->beginTransaction();
        
        // Update the fee status in the database
        $stmt_update = $conn->prepare("UPDATE student_fees SET status = 'Paid', paid_at = ?, invoice_path = ? WHERE id = ? AND student_id = ? AND amount = ? AND status = 'Unpaid'");
        $stmt_update->execute([$paid_at, $invoice_path, $fee_id, $userId, $amount]);

        if ($stmt_update->rowCount() > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Payment successful!', 'fee_id' => $fee_id]);
        } else {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to process payment. Fee already paid or record mismatch.']);
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Payment processing error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again.']);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}