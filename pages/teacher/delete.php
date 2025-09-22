<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/log_system.php"; // ADDED: Log system dependency

// Check user role
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'Admin';

if (!$role) {
    header("Location: ../../login.php?error=Unauthorized");
    exit;
}

// Validate input
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: teacher_list.php?error=Invalid teacher ID provided");
    exit;
}
$teacher_id = intval($_GET['id']);
$teacher_name = 'Unknown Teacher';

// Start database transaction
try {
    $conn->beginTransaction();

    // 1. Fetch the teacher's data
    $stmt_fetch = $conn->prepare("SELECT * FROM teacher WHERE id = ?");
    $stmt_fetch->execute([$teacher_id]);
    $teacher_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$teacher_data) {
        throw new Exception("Teacher with ID $teacher_id not found.");
    }
    $teacher_name = $teacher_data['teacher_name']; // Captured name for log

    // 2. Archive the teacher's data
    $query_archive_teacher = "INSERT INTO deleted_teachers 
                                (id, teacher_name, email, phone, gender, dob, blood_group, address, school_id, 
                                 qualification, subject, language_known, salary, std, experience, batch, 
                                 class_teacher, class_teacher_std, deleted_by_role) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_archive = $conn->prepare($query_archive_teacher);

    // FINAL FIX: Explicitly convert the boolean value to a string 'true' or 'false'
    $class_teacher_value = !empty($teacher_data['class_teacher']) ? 'true' : 'false'; 

    $stmt_archive->execute([
        $teacher_data['id'],
        $teacher_data['teacher_name'],
        $teacher_data['email'],
        $teacher_data['phone'],
        $teacher_data['gender'],
        $teacher_data['dob'],
        $teacher_data['blood_group'],
        $teacher_data['address'],
        $teacher_data['school_id'],
        $teacher_data['qualification'],
        $teacher_data['subject'],
        $teacher_data['language_known'],
        $teacher_data['salary'],
        $teacher_data['std'],
        $teacher_data['experience'],
        $teacher_data['batch'],
        $class_teacher_value, // Use the new explicit string value
        $teacher_data['class_teacher_std'],
        $role
    ]);

    // 3. Delete the user record
    $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete->execute([$teacher_id]);

    if ($stmt_delete->rowCount() === 0) {
        throw new Exception("User record could not be deleted.");
    }

    // 4. Delete the image file
    $image_path = $teacher_data['teacher_image'];
    if (!empty($image_path) && file_exists($image_path)) {
        @unlink($image_path);
    }

    // Commit changes
    $conn->commit();
    
    // ⭐ LOGGING: Log the critical deletion action
    $log_message = "DELETION: Teacher '{$teacher_name}' (ID: {$teacher_id}) was successfully deleted and archived.";
    log_interaction($role, $userId, $log_message, $acting_user_name);
    
    header("Location: teacher_list.php?success=Teacher was successfully deleted and archived.");
    exit;

} catch (Exception $e) {
    // Roll back on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Teacher Deletion Error: " . $e->getMessage());
    header("Location: teacher_list.php?error=" . urlencode("An error occurred during deletion."));
    exit;
}