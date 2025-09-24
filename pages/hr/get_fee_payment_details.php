<?php
// pages/hr/get_fee_payment_details.php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
require_once "../../includes/connect.php";
require_once "../../encryption.php";

$response = ['success' => false, 'paid' => [], 'unpaid' => [], 'message' => ''];

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

        if ($role !== 'hr' || !$userId) {
            $response['message'] = "Unauthorized access. Please log in again.";
            throw new Exception('Authentication failed');
        }

        // Verify HR user has access to the school
        $school_id = filter_input(INPUT_POST, 'school_id', FILTER_VALIDATE_INT);
        if (!$school_id) {
            $response['message'] = "Invalid school ID provided.";
            throw new Exception('Invalid school ID');
        }

        $stmt_verify = $conn->prepare("SELECT COUNT(*) FROM hr WHERE id = ? AND school_id = ?");
        $stmt_verify->execute([$userId, $school_id]);
        if ($stmt_verify->fetchColumn() == 0) {
            $response['message'] = "Access denied to this school's data.";
            throw new Exception('Access denied');
        }

        // Get and validate input parameters
        $standard = filter_input(INPUT_POST, 'standard', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $academic_year = filter_input(INPUT_POST, 'academic_year', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $fee_type = filter_input(INPUT_POST, 'fee_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $fee_month = filter_input(INPUT_POST, 'fee_month', FILTER_VALIDATE_INT);
        $fee_year = filter_input(INPUT_POST, 'fee_year', FILTER_VALIDATE_INT);

        if (!$standard || !$academic_year || !$fee_type || !$fee_month || !$fee_year) {
            $response['message'] = "Missing required fee details.";
            throw new Exception('Missing parameters');
        }

        // Validate fee_month and fee_year ranges
        if ($fee_month < 1 || $fee_month > 12) {
            $response['message'] = "Invalid fee month provided.";
            throw new Exception('Invalid fee month');
        }

        if ($fee_year < 2000 || $fee_year > 2100) {
            $response['message'] = "Invalid fee year provided.";
            throw new Exception('Invalid fee year');
        }

        // Fetch student payment statuses
        $stmt = $conn->prepare("
            SELECT s.student_name, s.rollno, sf.status, sf.paid_at
            FROM student_fees sf
            JOIN student s ON sf.student_id = s.id
            WHERE sf.school_id = ? 
              AND sf.std = ? 
              AND sf.academic_year = ? 
              AND sf.fee_type = ?
              AND sf.fee_month = ?
              AND sf.fee_year = ?
            ORDER BY 
                CASE WHEN sf.status = 'Paid' THEN 0 ELSE 1 END,
                s.rollno, 
                s.student_name
        ");
        
        $stmt->execute([$school_id, $standard, $academic_year, $fee_type, $fee_month, $fee_year]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            $response['message'] = "No student fee records found for the specified criteria.";
            $response['success'] = true; // Not an error, just no data
        } else {
            foreach ($results as $row) {
                $student_data = [
                    'student_name' => $row['student_name'],
                    'rollno' => $row['rollno'],
                    'paid_at' => $row['paid_at']
                ];

                if ($row['status'] === 'Paid') {
                    $response['paid'][] = $student_data;
                } else {
                    $response['unpaid'][] = $student_data;
                }
            }
            $response['success'] = true;
        }

    } else {
        $response['message'] = "Invalid request method. POST required.";
        throw new Exception('Invalid request method');
    }

} catch (PDOException $e) {
    error_log("Database error in get_fee_payment_details.php: " . $e->getMessage());
    $response['message'] = "A database error occurred while fetching payment details.";
    
} catch (Exception $e) {
    error_log("Error in get_fee_payment_details.php: " . $e->getMessage());
    // Keep the custom message from our validation exceptions
    if (empty($response['message'])) {
        $response['message'] = "An error occurred while processing your request.";
    }
}

// Clean output and send response
ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>