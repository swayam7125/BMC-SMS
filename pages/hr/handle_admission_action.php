<?php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // ADDED: Log system dependency
include_once '../../includes/email_functions.php';

// --- USER AUTHENTICATION AND DETAILS ---
$hr_user_id = null;
$hr_role = null;
$hr_user_name = 'Unknown';

if (isset($_COOKIE['encrypted_user_id'])) {
    $hr_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (isset($_COOKIE['encrypted_user_role'])) {
    $hr_role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_name'])) {
    $hr_user_name = decrypt_id($_COOKIE['encrypted_user_name']);
}

// Ensure the user is HR personnel
if ($hr_role !== 'hr' || !$hr_user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// --- HANDLE POST REQUEST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admission_id = $_POST['admission_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $rejection_reason = $_POST['rejection_reason'] ?? '';

    if (!$admission_id || !$action) {
        echo json_encode(['status' => 'error', 'message' => 'Missing admission ID or action.']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Fetch admission details to get student name and email for logging and notifications
        $stmt = $conn->prepare("SELECT student_name, student_email, school_id, standard_id FROM new_admissions WHERE id = ?");
        $stmt->execute([$admission_id]);
        $admission_details = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admission_details) {
            throw new Exception("Admission record not found.");
        }

        $student_name = $admission_details['student_name'];

        if ($action === 'approve') {
            // Update the admission status
            $stmt = $conn->prepare("UPDATE new_admissions SET status = 'Approved' WHERE id = ?");
            $stmt->execute([$admission_id]);

            // Create a new user account for the student
            $password = bin2hex(random_bytes(4)); // Generate an 8-character random password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $user_stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'student')");
            $user_stmt->execute([$admission_details['student_email'], $hashed_password]);
            $new_student_user_id = $conn->lastInsertId();

            // Insert into the student table
            $student_stmt = $conn->prepare("INSERT INTO student (id, student_name, school_id, standard_id) VALUES (?, ?, ?, ?)");
            $student_stmt->execute([$new_student_user_id, $admission_details['student_name'], $admission_details['school_id'], $admission_details['standard_id']]);

            // Send approval email with login credentials
            sendAdmissionApprovalEmail($admission_details['student_email'], $admission_details['student_name'], $password);
            
            $log_message = "Approved admission for student: {$student_name} (ID: {$admission_id}).";

        } elseif ($action === 'reject') {
            // Update the admission status with a reason
            $stmt = $conn->prepare("UPDATE new_admissions SET status = 'Rejected', rejection_reason = ? WHERE id = ?");
            $stmt->execute([$rejection_reason, $admission_id]);

            // Send rejection email
            sendAdmissionRejectionEmail($admission_details['student_email'], $admission_details['student_name'], $rejection_reason);
            
            $log_message = "Rejected admission for student: {$student_name} (ID: {$admission_id}). Reason: {$rejection_reason}";
        }

        $conn->commit();
        
        // --- LOG THE ACTION ---
        log_interaction($hr_role, $hr_user_id, $log_message, $hr_user_name);
        // ----------------------

        echo json_encode(['status' => 'success', 'message' => "Admission has been successfully {$action}d."]);

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // --- LOG THE ERROR ---
        $error_log_message = "Failed to {$action} admission for ID: {$admission_id}. Error: " . $e->getMessage();
        log_interaction($hr_role, $hr_user_id, $error_log_message, $hr_user_name);
        // ---------------------

        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>