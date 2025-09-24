<?php
// pages/student/process_pay_fees.php
ob_start(); // --- IMPROVEMENT: Start output buffering to catch stray output

header('Content-Type: application/json');
require_once "../../includes/connect.php";
require_once "../../encryption.php";
require_once "../../includes/ajax_helpers.php";

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax_request()) {
    // ... (user authentication logic remains the same) ...
    $role = null;
    $userId = null;
    if (isset($_COOKIE['encrypted_user_role'])) {
        $role = decrypt_id($_COOKIE['encrypted_user_role']);
    }
    if (isset($_COOKIE['encrypted_user_id'])) {
        $userId = decrypt_id($_COOKIE['encrypted_user_id']);
    }

    if ($role !== 'student' || !$userId) {
        $response['message'] = 'Unauthorized access.';
        ob_end_clean(); // --- IMPROVEMENT: Clean buffer before output
        echo json_encode($response);
        exit;
    }

    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $fee_id = filter_input(INPUT_POST, 'fee_id', FILTER_VALIDATE_INT);
    $paid_at = date('Y-m-d H:i:s');

    if ($amount === false || $amount <= 0 || !$fee_id) {
        $response['message'] = 'Invalid amount or fee ID provided.';
        ob_end_clean(); // --- IMPROVEMENT: Clean buffer before output
        echo json_encode($response);
        exit;
    }
    
    // ... (The rest of the database logic is already solid and remains the same) ...
    try {
        $conn->beginTransaction();
        $stmt_update = $conn->prepare(
            "UPDATE student_fees SET status = 'Paid', paid_at = ? WHERE id = ? AND student_id = ? AND amount = ? AND status = 'Unpaid'"
        );
        $stmt_update->execute([$paid_at, $fee_id, $userId, $amount]);

        if ($stmt_update->rowCount() > 0) {
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Payment successful! The page will now reload.';
        } else {
            $conn->rollBack();
            $stmt_check = $conn->prepare("SELECT status FROM student_fees WHERE id = ? AND student_id = ?");
            $stmt_check->execute([$fee_id, $userId]);
            $current_status = $stmt_check->fetchColumn();
            
            if ($current_status === 'Paid') {
                $response['message'] = 'This fee has already been paid.';
            } else {
                $response['message'] = 'Payment failed. The fee details may have changed or do not match. Please refresh and try again.';
            }
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Payment processing error: " . $e->getMessage());
        $response['message'] = 'A system error occurred during payment. Please try again.';
    }
    
    ob_end_clean(); // --- IMPROVEMENT: Clean buffer before final output
    echo json_encode($response);
    exit;

} else {
    $response['message'] = 'Invalid request method.';
    ob_end_clean(); // --- IMPROVEMENT: Clean buffer before output
    echo json_encode($response);
    exit;
}
?>