<?php
// pages/hr/process_add_fees.php
ob_start(); // --- IMPROVEMENT: Start output buffering

header('Content-Type: application/json');
require_once "../../includes/connect.php";
require_once "../../encryption.php";
require_once "../../includes/ajax_helpers.php";
require_once "../../includes/log_system.php";

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && is_ajax_request()) {
    // ... (user authentication logic remains the same) ...
    $role = null;
    $userId = null;
    if (isset($_COOKIE['encrypted_user_role'])) {
        $role = decrypt_id($_COOKIE['encrypted_user_role']);
    }
    if (isset($_COOKIE['encrypted_user_id'])) {
        $userId = decrypt_id($_COOKIE['encrypted_user_id']);
    }
    if ($role !== 'hr' || !$userId) {
        $response['message'] = "Unauthorized access.";
        ob_end_clean(); echo json_encode($response); exit;
    }

    try {
        $school_id = filter_input(INPUT_POST, 'school_id', FILTER_VALIDATE_INT);
        $standard = filter_input(INPUT_POST, 'standard', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $academic_year = filter_input(INPUT_POST, 'academic_year', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $fee_type_input = filter_input(INPUT_POST, 'fee_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $fee_amount = filter_input(INPUT_POST, 'fee_amount', FILTER_VALIDATE_FLOAT);
        
        $fee_type = trim($fee_type_input);
        $fee_month = date('n');
        $fee_year = date('Y');

        // --- IMPROVEMENT: Added a length check for fee_type ---
        if (strlen($fee_type) > 50) {
            $response['message'] = "Fee Type cannot be longer than 50 characters.";
            ob_end_clean(); echo json_encode($response); exit;
        }

        if (!$school_id || empty($standard) || empty($academic_year) || empty($fee_type) || $fee_amount === false || $fee_amount <= 0) {
            $response['message'] = "Invalid input. Please fill all required fields correctly.";
            ob_end_clean(); echo json_encode($response); exit;
        }

        // ... (The rest of the logic is solid and remains the same) ...
        $stmt_check = $conn->prepare('SELECT COUNT(*) FROM student_fees WHERE school_id = ? AND std = ? AND academic_year = ? AND fee_month = ? AND fee_year = ? AND fee_type = ?');
        $stmt_check->execute([$school_id, $standard, $academic_year, $fee_month, $fee_year, $fee_type]);
        if ($stmt_check->fetchColumn() > 0) {
            $response['message'] = "Fees of this type for this standard, month, and year have already been assigned.";
            ob_end_clean(); echo json_encode($response); exit;
        }
        
        $stmt_students = $conn->prepare('SELECT id FROM student WHERE school_id = ? AND std = ? AND academic_year = ?');
        $stmt_students->execute([$school_id, $standard, $academic_year]);
        $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

        if (empty($students)) {
            $response['message'] = "No students found for the selected standard and academic year.";
            ob_end_clean(); echo json_encode($response); exit;
        }

        $conn->beginTransaction();
        $stmt_insert = $conn->prepare('INSERT INTO student_fees (student_id, school_id, academic_year, std, fee_month, fee_year, amount, fee_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'Unpaid\')');
        $stmt_notif = $conn->prepare('INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)');
        $inserted_count = 0;
        foreach ($students as $student) {
            if ($stmt_insert->execute([$student['id'], $school_id, $academic_year, $standard, $fee_month, $fee_year, $fee_amount, $fee_type])) {
                $inserted_count++;
                $notification_message = "A new fee of ₹" . number_format($fee_amount, 2) . " has been added for '{$fee_type}'.";
                $notification_link = 'index.php?page=view_fees'; 
                $stmt_notif->execute([$student['id'], $notification_message, $notification_link, 'new_fee']);
            }
        }
        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Fees successfully assigned to {$inserted_count} students in standard {$standard}.";
        log_interaction($role, $userId, 'Added fee type: ' . $fee_type . ' for standard ' . $standard . ' for ' . $inserted_count . ' students.', 'HR User');

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Fee addition error: " . $e->getMessage());
        $response['message'] = "A database error occurred. Please try again later.";
    }
} else {
    $response['message'] = "Invalid request method.";
}

ob_end_clean(); // --- IMPROVEMENT: Clean buffer before final output
echo json_encode($response);
?>