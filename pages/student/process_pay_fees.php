<?php
ob_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
require_once "../../includes/connect.php";
require_once "../../encryption.php";

// Helper function for Indian currency formatting
if (!function_exists('formatIndianCurrency')) {
    function formatIndianCurrency($number) {
        $number = (string)round($number, 2);
        $parts = explode('.', $number);
        $integer_part = $parts[0];
        $decimal_part = isset($parts[1]) ? '.' . str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : '.00';

        $len = strlen($integer_part);
        if ($len <= 3) {
            return '₹' . $integer_part . $decimal_part;
        }

        $last_three = substr($integer_part, -3);
        $rest_units = substr($integer_part, 0, -3);
        $rest_formatted = strrev(implode(',', str_split(strrev($rest_units), 2)));
        return '₹' . $rest_formatted . ',' . $last_three . $decimal_part;
    }
}

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Authentication check
        $role = null;
        $userId = null;
        
        if (isset($_COOKIE['encrypted_user_role'])) {
            $role = decrypt_id($_COOKIE['encrypted_user_role']);
        }
        if (isset($_COOKIE['encrypted_user_id'])) {
            $userId = decrypt_id($_COOKIE['encrypted_user_id']);
        }

        if ($role !== 'student' || !$userId) {
            $response['message'] = 'Unauthorized access. Please log in again.';
            throw new Exception('Authentication failed');
        }

        // Input validation
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $fee_id = filter_input(INPUT_POST, 'fee_id', FILTER_VALIDATE_INT);
        $paid_at = date('Y-m-d H:i:s');

        if ($amount === false || $amount <= 0) {
            $response['message'] = 'Invalid amount provided.';
            throw new Exception('Invalid amount');
        }

        if (!$fee_id) {
            $response['message'] = 'Invalid fee ID provided.';
            throw new Exception('Invalid fee ID');
        }

        // Start transaction
        $conn->beginTransaction();

        // Update fee status to paid
        $stmt_update = $conn->prepare(
            "UPDATE student_fees 
             SET status = 'Paid', paid_at = ? 
             WHERE id = ? AND student_id = ? AND amount = ? AND status = 'Unpaid'"
        );
        $stmt_update->execute([$paid_at, $fee_id, $userId, $amount]);

        if ($stmt_update->rowCount() > 0) {
            // Get fee and student details for notification
            $stmt_details = $conn->prepare("
                SELECT s.student_name, s.std, s.rollno, s.school_id, sf.fee_type, sf.amount
                FROM student_fees sf
                JOIN student s ON sf.student_id = s.id
                WHERE sf.id = ?
            ");
            $stmt_details->execute([$fee_id]);
            $details = $stmt_details->fetch(PDO::FETCH_ASSOC);

            if ($details) {
                // Get all HR users for the school
                $stmt_hr = $conn->prepare("SELECT id FROM hr WHERE school_id = ?");
                $stmt_hr->execute([$details['school_id']]);
                $hr_users = $stmt_hr->fetchAll(PDO::FETCH_ASSOC);

                // Create notification message
                $message = sprintf(
                    "%s (Class: %s, Roll: %s) has paid the '%s' fee of %s.",
                    $details['student_name'],
                    $details['std'],
                    $details['rollno'],
                    $details['fee_type'],
                    formatIndianCurrency($details['amount'])
                );
                
                $link = 'pages/hr/manage_fees.php';
                $type = 'fee_payment';

                // Insert notifications for HR users
                $stmt_notify = $conn->prepare(
                    "INSERT INTO notifications (user_id, message, link, type, created_at) VALUES (?, ?, ?, ?, NOW())"
                );

                foreach ($hr_users as $hr) {
                    try {
                        $stmt_notify->execute([$hr['id'], $message, $link, $type]);
                    } catch (PDOException $e) {
                        // Log notification error but don't fail the payment
                        error_log("Notification insert error: " . $e->getMessage());
                    }
                }
            }

            // Commit transaction
            $conn->commit();
            
            $response['success'] = true;
            $response['message'] = 'Payment successful! Your receipt is being generated...';
            $response['fee_id'] = $fee_id;
            
        } else {
            // Rollback and check why update failed
            $conn->rollBack();
            
            $stmt_check = $conn->prepare("SELECT status FROM student_fees WHERE id = ? AND student_id = ?");
            $stmt_check->execute([$fee_id, $userId]);
            $current_status = $stmt_check->fetchColumn();
            
            if ($current_status === 'Paid') {
                $response['message'] = 'This fee has already been paid.';
            } elseif ($current_status === false) {
                $response['message'] = 'Fee record not found or access denied.';
            } else {
                $response['message'] = 'Payment failed. The fee details may have changed. Please refresh and try again.';
            }
        }
        
    } else {
        $response['message'] = 'Invalid request method.';
    }

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Payment processing PDO error: " . $e->getMessage());
    $response['message'] = 'A database error occurred during payment. Please try again.';
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Payment processing error: " . $e->getMessage());
    // Keep the custom message from our validation exceptions
    if (empty($response['message']) || $response['message'] === 'An unknown error occurred.') {
        $response['message'] = 'An error occurred during payment processing. Please try again.';
    }
}

// Clean output and send response
ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>