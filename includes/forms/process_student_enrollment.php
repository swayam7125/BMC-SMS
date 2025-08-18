<?php
require_once '../../includes/connect.php';
require_once '../../includes/ajax_helpers.php';
require_once '../../encryption.php';

// Check authentication
check_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (is_ajax_request()) {
        echo get_json_response(false, 'Invalid request method');
    } else {
        header('Location: student_enrollment.php');
    }
    exit;
}

try {
    // Validate required fields
    $required_fields = ['name', 'roll_number', 'class', 'school_id'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("$field is required");
        }
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

    // Prepare and execute SQL
    $stmt = $conn->prepare("
        INSERT INTO students (
            name, roll_number, class, school_id, contact_number, 
            email, address, photo, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, NOW()
        )
    ");

    $stmt->execute([
        $_POST['name'],
        $_POST['roll_number'],
        $_POST['class'],
        $school_id,
        $_POST['contact_number'] ?? null,
        $_POST['email'] ?? null,
        $_POST['address'] ?? null,
        $photo_path
    ]);

    // Send success response
    if (is_ajax_request()) {
        echo get_json_response(true, 'Student enrolled successfully', [], '../student/student_list.php');
    } else {
        header('Location: ../student/student_list.php');
    }

} catch (Exception $e) {
    // If file was uploaded, delete it
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }

    if (is_ajax_request()) {
        echo get_json_response(false, $e->getMessage());
    } else {
        header('Location: student_enrollment.php?error=' . urlencode($e->getMessage()));
    }
}
?>
