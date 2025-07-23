<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in and has a valid role
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if not logged in
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// Check if a student ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: student_list.php?error=Invalid ID provided");
    exit;
}

$student_id = intval($_GET['id']); // This student_id is now also the user_id
$user_id_to_delete = $student_id; // The ID is directly the user ID

// Begin a transaction to ensure atomicity
mysqli_begin_transaction($conn);

try {
    // 1. Verify the user's role before deleting (security check)
    // This is crucial to prevent deleting the wrong user if an ID is tampered with.
    $check_user_role_query = "SELECT role FROM users WHERE id = ?";
    $stmt_check = mysqli_prepare($conn, $check_user_role_query);
    mysqli_stmt_bind_param($stmt_check, "i", $user_id_to_delete);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $user_record = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);

    if (!$user_record || $user_record['role'] !== 'student') {
        throw new Exception("User not found or role mismatch for deletion.");
    }

    // 2. Fetch student_image path for file deletion before the record is cascaded
    $query_student_image = "SELECT student_image FROM student WHERE id = ?";
    $stmt_image = mysqli_prepare($conn, $query_student_image);
    mysqli_stmt_bind_param($stmt_image, "i", $student_id);
    mysqli_stmt_execute($stmt_image);
    $result_image = mysqli_stmt_get_result($stmt_image);
    $image_data = mysqli_fetch_assoc($result_image);
    $image_path = $image_data['student_image'] ?? null;
    mysqli_stmt_close($stmt_image);

    // 3. Delete the user record from the 'users' table.
    // Due to `ON DELETE CASCADE` foreign key on `student.id` referencing `users.id`,
    // the corresponding record in the `student` table will be automatically deleted.
    $delete_user = "DELETE FROM users WHERE id = ?";
    $stmt_user = mysqli_prepare($conn, $delete_user);
    mysqli_stmt_bind_param($stmt_user, "i", $user_id_to_delete);
    if (!mysqli_stmt_execute($stmt_user)) {
        throw new Exception("Error deleting user from users table: " . mysqli_stmt_error($stmt_user));
    }
    
    // Confirm that a row was actually affected in the users table
    if (mysqli_stmt_affected_rows($stmt_user) === 0) {
        throw new Exception("User record could not be deleted (it may have been removed already).");
    }
    mysqli_stmt_close($stmt_user);

    // 4. Delete the physical image file, if it exists
    if (!empty($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }

    // If both deletions were successful, commit the changes to the database.
    mysqli_commit($conn);

    // Redirect to the student list with a success message.
    header("Location: student_list.php?success=Student deleted successfully");
    exit;

} catch (Exception $e) {
    // If any part of the process fails, roll back all database changes.
    mysqli_rollback($conn);

    // Redirect to the student list with a detailed error message.
    header("Location: student_list.php?error=" . urlencode($e->getMessage()));
    exit;

} finally {
    // Ensure the database connection is always closed.
    mysqli_close($conn);
}
?>