<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;

if (!$role) { // Simplified check, might need to be more specific (e.g., principal only)
    header("Location: ../../login.php?error=Unauthorized");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: teacher_list.php?error=Invalid teacher ID provided");
    exit;
}

$teacher_id = intval($_GET['id']);

try {
    $conn->beginTransaction();

    $stmt_fetch = $conn->prepare("SELECT * FROM teacher WHERE id = ?");
    $stmt_fetch->execute([$teacher_id]);
    $teacher_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$teacher_data) {
        throw new Exception("Teacher with ID $teacher_id not found.");
    }

    $query_archive_teacher = "INSERT INTO deleted_teachers 
                                (id, teacher_name, email, phone, gender, dob, blood_group, address, school_id, 
                                 qualification, subject, language_known, salary, std, experience, batch, 
                                 class_teacher, class_teacher_std, deleted_by_role) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_archive = $conn->prepare($query_archive_teacher);
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
        $teacher_data['class_teacher'],
        $teacher_data['class_teacher_std'],
        $role
    ]);

    // This assumes an ON DELETE CASCADE constraint from 'users' to 'teacher'
    $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete->execute([$teacher_id]);

    if ($stmt_delete->rowCount() === 0) {
        throw new Exception("User record could not be deleted.");
    }

    $image_path = $teacher_data['teacher_image'];
    if (!empty($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }

    $conn->commit();
    header("Location: teacher_list.php?success=Teacher was successfully deleted and archived.");
    exit;
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Teacher Deletion Error: " . $e->getMessage());
    header("Location: teacher_list.php?error=" . urlencode("An error occurred during deletion."));
    exit;
}
