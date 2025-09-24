<?php
// pages/hr/process_add_fees.php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
require_once "../../includes/connect.php";
require_once "../../encryption.php";
require_once "../../includes/log_system.php";

/** @var PDO $conn */

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Authentication check
        $role = null;
        $userId = null;
        if (isset($_COOKIE['encrypted_user_role'])) {
            $role = decrypt_id($_COOKIE['encrypted_user_role']);
        }
        if (isset($_COOKIE['encrypted_user_id'])) {
            $userId = decrypt_id($_COOKIE['encrypted_user_id']);
        }
        
        if ($role !== 'hr' || !$userId) {
            $response['message'] = "Unauthorized access. Please log in again.";
            ob_end_clean(); 
            echo json_encode($response); 
            exit;
        }

        // Verify HR user exists and get school_id
        $stmt_verify = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
        $stmt_verify->execute([$userId]);
        $hr_data = $stmt_verify->fetch(PDO::FETCH_ASSOC);
        
        if (!$hr_data) {
            $response['message'] = "HR account not found. Please contact administrator.";
            ob_end_clean(); 
            echo json_encode($response); 
            exit;
        }

        // Input validation and sanitization
        $school_id = filter_input(INPUT_POST, 'school_id', FILTER_VALIDATE_INT);
        $standard = filter_input(INPUT_POST, 'standard', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $academic_year = filter_input(INPUT_POST, 'academic_year', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $fee_type_input = filter_input(INPUT_POST, 'fee_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $fee_amount = filter_input(INPUT_POST, 'fee_amount', FILTER_VALIDATE_FLOAT);
        
        // Additional validation
        if (!$school_id || $school_id !== $hr_data['school_id']) {
            $response['message'] = "Invalid school access. You can only manage fees for your assigned school.";
            ob_end_clean(); 
            echo json_encode($response); 
            exit;
        }
        
        $fee_type = trim($fee_type_input);
        $fee_month = date('n');
        $fee_year = date('Y');

        // Validate inputs
        if (empty($standard) || empty($academic_year) || empty($fee_type) || $fee_amount === false) {
            $response['message'] = "All fields are required. Please fill in all the information.";
            ob_end_clean(); 
            echo json_encode($response); 
            exit;
        }

        // Validate fee type length
        if (strlen($fee_type) > 50) {
            $response['message'] = "Fee Type cannot be longer than 50 characters.";
            ob_end_clean(); 
            echo json_encode($response); 
            exit;
        }

        // Validate fee amount
        if ($fee_amount <= 0 || $fee_amount > 999999.99) {
            $response['message'] = "Fee amount must be between ₹0.01 and ₹999,999.99.";
            ob_end_clean(); 
            echo json_encode($response); 
            exit;
        }

        // Validate academic year format
        if (!preg_match('/^\d{4}-\d{4}$/', $academic_year)) {
            $response['message'] = "Invalid academic year format.";
            ob_end_clean(); 
            echo json_encode($response); 
            exit;
        }

        // Check if fees already exist for this combination
        $stmt_check = $conn->prepare('
            SELECT COUNT(*) FROM student_fees 
            WHERE school_id = ? AND std = ? AND academic_year = ? 
            AND fee_month = ? AND fee_year = ? AND fee_type = ?
        ');
        $stmt_check->execute([$school_id, $standard, $academic_year, $fee_month, $fee_year, $fee_type]);
        
        if ($stmt_check->fetchColumn() > 0) {
            $response['message'] = "Fees of type '{$fee_type}' for {$standard} class in {$academic_year} have already been assigned for this month.";
            ob_end_clean(); 
            echo json_encode($response); 
            exit;
        }
        
        // Get students for the specified criteria
        $stmt_students = $conn->prepare('
            SELECT id, student_name FROM student 
            WHERE school_id = ? AND std = ? AND academic_year = ? 
            ORDER BY rollno, student_name
        ');
        $stmt_students->execute([$school_id, $standard, $academic_year]);
        $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

        if (empty($students)) {
            $response['message'] = "No students found for {$standard} class in {$academic_year}. Please ensure students are enrolled first.";
            ob_end_clean(); 
            echo json_encode($response); 
            exit;
        }

        // Begin transaction for data consistency
        $conn->beginTransaction();
        
        try {
            // Insert fee records for all students
            $stmt_insert = $conn->prepare('
                INSERT INTO student_fees (student_id, school_id, academic_year, std, fee_month, fee_year, amount, fee_type, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'Unpaid\')
            ');
            
            // Prepare notification statement
            $stmt_notif = $conn->prepare('
                INSERT INTO notifications (user_id, message, link, type, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ');
            
            $inserted_count = 0;
            $failed_count = 0;
            
            foreach ($students as $student) {
                try {
                    if ($stmt_insert->execute([$student['id'], $school_id, $academic_year, $standard, $fee_month, $fee_year, $fee_amount, $fee_type])) {
                        $inserted_count++;
                        
                        // Create notification for student
                        $notification_message = "A new fee of ₹" . number_format($fee_amount, 2) . " has been added for '{$fee_type}'. Please check your fee section for payment.";
                        $notification_link = 'pages/student/view_fees.php';
                        
                        try {
                            $stmt_notif->execute([$student['id'], $notification_message, $notification_link, 'new_fee']);
                        } catch (PDOException $notif_error) {
                            // Log notification error but don't fail the fee insertion
                            error_log("Notification insert failed for student {$student['id']}: " . $notif_error->getMessage());
                        }
                    } else {
                        $failed_count++;
                    }
                } catch (PDOException $insert_error) {
                    $failed_count++;
                    error_log("Fee insert failed for student {$student['id']}: " . $insert_error->getMessage());
                }
            }

             // DEBUGGING LINE TO CHECK THE FINAL COUNT
            $response['debug_final_count'] = $inserted_count;
            
            if ($inserted_count > 0) {
                $conn->commit();
                
                $success_message = "Successfully assigned '{$fee_type}' fees to {$inserted_count} students in {$standard} class.";
                if ($failed_count > 0) {
                    $success_message .= " ({$failed_count} failed - please check logs)";
                }
                
                $response['success'] = true;
                $response['message'] = $success_message;
                
                // Log the action
                log_interaction(
                    $role, 
                    $userId, 
                    "Added fee type: {$fee_type} (₹{$fee_amount}) for {$standard} class ({$academic_year}) - {$inserted_count} students affected.", 
                    'HR User'
                );
                
            } else {
                $conn->rollBack();
                $response['message'] = "Failed to assign fees to any students. Please try again or contact support.";
            }
            
        } catch (PDOException $transaction_error) {
            $conn->rollBack();
            error_log("Transaction error in process_add_fees.php: " . $transaction_error->getMessage());
            $response['message'] = "A database error occurred while processing fees. Please try again.";
        }

    } else {
        $response['message'] = "Invalid request method. POST method required.";
    }

} catch (PDOException $e) {
    // Rollback transaction if it's active
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Database error in process_add_fees.php: " . $e->getMessage());
    $response['message'] = "A database error occurred while processing fees. Please try again later.";
    
} catch (Exception $e) {
    // Rollback transaction if it's active
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("General error in process_add_fees.php: " . $e->getMessage());
    
    // Keep the custom message from our validation exceptions
    if (empty($response['message']) || $response['message'] === 'An unknown error occurred.') {
        $response['message'] = "An error occurred while processing your request. Please try again.";
    }
}

// Clean output buffer and send JSON response
ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>