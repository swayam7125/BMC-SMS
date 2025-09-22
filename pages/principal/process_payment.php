<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // ADDED: Log system dependency

date_default_timezone_set('Asia/Kolkata');

// Authorization check for principal using cookies
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'Principal'; // ADDED: Acting user name

if ($role !== 'principal' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve POST data
    $teacher_id = filter_input(INPUT_POST, 'teacher_id', FILTER_VALIDATE_INT);
    // The principal is the user executing this. We use $userId for the hr_user_id/payroll_user_id.
    $school_id = filter_input(INPUT_POST, 'school_id', FILTER_VALIDATE_INT);
    $salary_month = filter_input(INPUT_POST, 'salary_month', FILTER_VALIDATE_INT);
    $salary_year = filter_input(INPUT_POST, 'salary_year', FILTER_VALIDATE_INT);
    $base_salary = filter_input(INPUT_POST, 'base_salary', FILTER_VALIDATE_FLOAT);
    $total_working_days = filter_input(INPUT_POST, 'total_working_days', FILTER_VALIDATE_INT);
    $present_days = filter_input(INPUT_POST, 'present_days', FILTER_VALIDATE_INT);
    $absent_days = filter_input(INPUT_POST, 'absent_days', FILTER_VALIDATE_INT);
    $deduction_amount = filter_input(INPUT_POST, 'deduction_amount', FILTER_VALIDATE_FLOAT);
    $net_salary_paid = filter_input(INPUT_POST, 'net_salary_paid', FILTER_VALIDATE_FLOAT);

    try {
        $conn->beginTransaction();

        // 1. Double-check to prevent duplicate payment
        $check_stmt = $conn->prepare("SELECT id FROM teacher_payroll WHERE teacher_id = ? AND salary_month = ? AND salary_year = ?");
        $check_stmt->execute([$teacher_id, $salary_month, $salary_year]);
        if ($check_stmt->fetch()) {
            throw new Exception("Salary for this teacher for this month has already been processed.");
        }

        // 2. Insert the payment record
        // Renamed column from principal_id/payroll_user_id to hr_user_id
        $sql = "INSERT INTO teacher_payroll (teacher_id, hr_user_id, school_id, salary_month, salary_year, base_salary, total_working_days, present_days, absent_days, deduction_amount, net_salary_paid) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $teacher_id, $userId, $school_id, $salary_month, $salary_year,
            $base_salary, $total_working_days, $present_days, $absent_days,
            $deduction_amount, $net_salary_paid
        ]);

        // 3. Create a notification for the teacher
        $monthName = date('F', mktime(0, 0, 0, $salary_month, 10));
        $currency_paid = formatIndianCurrency($net_salary_paid);
        $message = "Your salary for $monthName $salary_year amounting to " . $currency_paid . " has been processed.";
        
        $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, link) VALUES (?, ?, ?, ?)");
        $notify_stmt->execute([$teacher_id, $message, 'salary', 'pages/teacher/view_salary_history.php']);

        $conn->commit();
        
        // ⭐ LOGGING: Log the critical payment action
        $log_message = "PAYROLL: Teacher ID {$teacher_id} salary processed for {$monthName} {$salary_year}. Net amount paid: {$currency_paid}.";
        log_interaction($role, $userId, $log_message, $acting_user_name);
        
        // CORRECTION: Redirect with a URL parameter for the success message
        $success_message = "Salary has been processed successfully!";
        $redirect_url = "generate_payroll.php?month=" . urlencode($salary_month) . "&year=" . urlencode($salary_year) . "&success=" . urlencode($success_message);
        header("Location: " . $redirect_url);
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        // Redirect with an error message
        $error_message = "Error: " . $e->getMessage();
        $redirect_url = "generate_payroll.php?month=" . urlencode($salary_month) . "&year=" . urlencode($salary_year) . "&error=" . urlencode($error_message);
        header("Location: " . $redirect_url);
        exit();
    }
} else {
    // Redirect back if accessed directly
    header("Location: generate_payroll.php");
    exit();
}
// This function is needed for the notification message
function formatIndianCurrency($number) {
    $number = (string)round($number, 2); $parts = explode('.', $number); $integer_part = $parts[0]; $decimal_part = isset($parts[1]) ? '.' . str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : ''; $len = strlen($integer_part); if ($len <= 3) { return '₹' . $integer_part . $decimal_part; } $last_three = substr($integer_part, -3); $rest_units = substr($integer_part, 0, -3); $rest_formatted = strrev(implode(',', str_split(strrev($rest_units), 2))); return '₹' . $rest_formatted . ',' . $last_three . $decimal_part;
}