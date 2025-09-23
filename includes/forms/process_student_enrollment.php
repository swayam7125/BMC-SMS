<?php
require_once '../../includes/connect.php';
require_once '../../includes/ajax_helpers.php';
require_once '../../includes/layout.php';
require_once '../../encryption.php';
require_once '../../includes/log_system.php'; // ADDED: Log system dependency

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication and role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    Response::send([
        'success' => false,
        'message' => 'Unauthorized access',
        'redirect' => '../../login.php'
    ], 403);
    exit;
}

// Identify enrolling user from session
$enrolling_user_id = $_SESSION['user_id'];
$enrolling_role = $_SESSION['user_role'];
$enrolling_user_name = $_SESSION['user_name'] ?? 'Session User'; // Retrieve user name for log

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::send([
        'success' => false,
        'message' => 'Invalid request method',
        'redirect' => 'student_enrollment.php'
    ], 405);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Validate required fields
    $required_fields = ['name', 'roll_number', 'class', 'school_id', 'email'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("$field is required");
        }
    }

    // Validate email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    // Retrieve and validate student-specific data
    $school_id = (int)$_POST['school_id'];
    $temp_password = substr(bin2hex(random_bytes(4)), 0, 8); // Generate 8-char temp password
    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
    $user_role = 'student';
    $student_name = $_POST['name'];

    // Handle file upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/uploads/students/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $new_file_name = uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $new_file_name;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
            $photo_path = '/BMC-SMS/uploads/students/' . $new_file_name;
        } else {
            throw new Exception("Failed to upload student photo.");
        }
    }

    // 1. Insert into main 'users' table
    $stmt_user = $conn->prepare('INSERT INTO "users" ("role", "email", "password", "account_status") VALUES (?, ?, ?, ?)');
    $stmt_user->execute([$user_role, $_POST['email'], $hashed_password, 'active']);
    $user_id = $conn->lastInsertId();

    // 2. Insert/update student record
    $stmt = $conn->prepare("
        INSERT INTO student (
            id, student_name, roll_number, class, school_id, 
            contact_number, email, address, student_image, 
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            NOW()
        )
    ");

    $stmt->execute([
        $user_id,
        $_POST['name'],
        $_POST['roll_number'],
        $_POST['class'],
        $school_id,
        $_POST['contact_number'] ?? null,
        $_POST['email'],
        $_POST['address'] ?? null,
        $photo_path,
    ]);

    // Commit transaction
    $conn->commit();
    
    // ⭐ LOGGING: Log the successful student enrollment
    log_interaction($enrolling_role, $enrolling_user_id, "ENROLLMENT SUCCESS: Enrolled new student: {$student_name} (Roll: {$_POST['roll_number']}, ID: {$user_id})", $enrolling_user_name);

    // TODO: Send email with temporary password
    // For now, we'll include it in the response
    $response_data = ['temp_password' => $temp_password];

    // Send success response
    Response::send([
        'success' => true,
        'message' => 'Student enrolled successfully',
        'data' => $response_data,
        'redirect' => '../student/student_list.php'
    ]);

} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // If file was uploaded, attempt to delete it
    if (isset($target_path) && file_exists($target_path)) {
        unlink($target_path);
    }
    
    // Handle specific constraint errors (e.g., duplicate email)
    $error_message = $e->getMessage();
    if (strpos($error_message, 'duplicate key') !== false || strpos($error_message, 'email') !== false) {
        $error_message = "A user with this email or roll number already exists.";
    } elseif (strpos($error_message, 'required') !== false) {
         $error_message = "Please fill in all required fields.";
    } else {
        error_log("Student Enrollment Error: " . $error_message);
        $error_message = "A system error occurred. Please try again later.";
    }

    Response::send([
        'success' => false,
        'message' => $error_message
    ], 500);
}
?>