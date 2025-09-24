<?php
// pages/hr/process_add_fees.php
header('Content-Type: application/json');
require_once "../../includes/connect.php";
require_once "../../encryption.php";
require_once "../../includes/ajax_helpers.php";
require_once "../../includes/log_system.php";

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST" && is_ajax_request()) {

    $role = null;
    $userId = null;
    if (isset($_COOKIE['encrypted_user_role'])) {
        $role = decrypt_id($_COOKIE['encrypted_user_role']);
    }
    if (isset($_COOKIE['encrypted_user_id'])) {
        $userId = decrypt_id($_COOKIE['encrypted_user_id']);
    }

    // Check if user is HR
    if ($role !== 'hr' || !$userId) {
        $response['message'] = "Unauthorized access.";
        echo json_encode($response);
        exit;
    }

    try {
        $school_id = filter_input(INPUT_POST, 'school_id', FILTER_VALIDATE_INT);
        $standard = filter_input(INPUT_POST, 'standard', FILTER_SANITIZE_STRING);
        $academic_year = filter_input(INPUT_POST, 'academic_year', FILTER_SANITIZE_STRING);
        $fee_type = filter_input(INPUT_POST, 'fee_type', FILTER_SANITIZE_STRING);
        $fee_amount = filter_input(INPUT_POST, 'fee_amount', FILTER_VALIDATE_FLOAT);
        
        $fee_month = date('n'); // Get current month (1-12)
        $fee_year = date('Y'); // Get current year

        if (!$school_id || !$standard || !$academic_year || !$fee_type || $fee_amount === false || $fee_amount < 0) {
            $response['message'] = "Invalid input. Please fill all required fields correctly.";
            echo json_encode($response);
            exit;
        }

        // Check if fee has already been added for this month/standard/fee_type to prevent duplicates
        $stmt_check = $conn->prepare('SELECT COUNT(*) FROM student_fees WHERE school_id = ? AND std = ? AND academic_year = ? AND fee_month = ? AND fee_year = ? AND fee_type = ?');
        $stmt_check->execute([$school_id, $standard, $academic_year, $fee_month, $fee_year, $fee_type]);
        if ($stmt_check->fetchColumn() > 0) {
            $response['message'] = "Fees of this type for this standard, month, and year have already been added.";
            echo json_encode($response);
            exit;
        }
        
        // Fetch all student IDs for the specified school, standard, and academic year
        $stmt_students = $conn->prepare('SELECT id FROM student WHERE school_id = ? AND std = ? AND academic_year = ?');
        $stmt_students->execute([$school_id, $standard, $academic_year]);
        $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

        if (empty($students)) {
            $response['message'] = "No students found for this standard and academic year.";
            echo json_encode($response);
            exit;
        }

        $conn->beginTransaction();

        $stmt_insert = $conn->prepare('INSERT INTO student_fees (student_id, school_id, academic_year, std, fee_month, fee_year, amount, fee_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

        $inserted_count = 0;
        foreach ($students as $student) {
            if ($stmt_insert->execute([$student['id'], $school_id, $academic_year, $standard, $fee_month, $fee_year, $fee_amount, $fee_type])) {
                $inserted_count++;
                
                // Add a notification for each student
                $notification_message = "A new fee of ₹" . number_format($fee_amount, 2) . " has been added for '{$fee_type}'.";
                $notification_link = 'index.php?page=view_fees'; // Assuming a new student fees page
                
                $stmt_notif = $conn->prepare('INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)');
                $stmt_notif->execute([$student['id'], $notification_message, $notification_link, 'new_fee']);
            }
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Fees for {$standard} successfully added for {$inserted_count} students.";
        log_interaction($role, $userId, 'Added fee type: ' . $fee_type . ' for standard ' . $standard . ' for ' . $inserted_count . ' students.', $hr_data['hr_name'] ?? 'HR');
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Fee addition error: " . $e->getMessage());
        $response['message'] = "A system error occurred. Please try again later.";
    }
} else {
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
?>