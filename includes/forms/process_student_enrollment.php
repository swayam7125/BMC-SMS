<?php
require_once '../../includes/connect.php';
require_once '../../includes/ajax_helpers.php';
require_once '../../includes/layout.php';
require_once '../../encryption.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'school') {
    Response::send([
        'success' => false,
        'message' => 'Unauthorized access',
        'redirect' => '../../login.php'
    ], 403);
    exit;
}

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
        throw new Exception("Invalid email format");
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    if ($stmt->fetch()) {
        throw new Exception("Email already registered");
    }

    // Handle file upload
    $photo_path = null;
    if (!empty($_FILES['photo']['name'])) {
        $photo = $_FILES['photo'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png'];
        if (!in_array($photo['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG and PNG are allowed.');
        }
        
        // Validate file size (2MB max)
        if ($photo['size'] > 2 * 1024 * 1024) {
            throw new Exception('File is too large. Maximum size is 2MB.');
        }
        
        // Generate unique filename
        $ext = pathinfo($photo['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $upload_path = '../../uploads/students/' . $filename;
        
        // Create directory if it doesn't exist
        if (!file_exists('../../uploads/students')) {
            mkdir('../../uploads/students', 0777, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($photo['tmp_name'], $upload_path)) {
            throw new Exception('Failed to upload file');
        }
        
        $photo_path = $filename;
    }

    // Decrypt school_id
    $school_id = decrypt_id($_POST['school_id']);

    // Generate temporary password
    $temp_password = bin2hex(random_bytes(8));
    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

    // Create user account
    $stmt = $conn->prepare("
        INSERT INTO users (email, password, role, account_status, created_at)
        VALUES (?, ?, 'student', 'active', NOW())
    ");
    $stmt->execute([$_POST['email'], $hashed_password]);
    $user_id = $conn->lastInsertId();

    // Create student record
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

    // If file was uploaded, delete it
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }

    Response::send([
        'success' => false,
        'message' => $e->getMessage(),
        'redirect' => 'student_enrollment.php'
    ], 400);
}
?>
