<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if not logged in
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: teacher_list.php?error=Invalid ID provided");
    exit;
}

$teacher_id = intval($_GET['id']);
$user_id_to_delete = $teacher_id; // Now, teacher_id IS the user_id

// Start transaction to ensure both deletions succeed or fail together
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

    if (!$user_record || $user_record['role'] !== 'teacher') {
        throw new Exception("User not found or role mismatch for deletion.");
    }

    // 2. Fetch teacher_image path for file deletion before the record is cascaded
    $query_teacher_image = "SELECT teacher_image FROM teacher WHERE id = ?";
    $stmt_image = mysqli_prepare($conn, $query_teacher_image);
    mysqli_stmt_bind_param($stmt_image, "i", $teacher_id);
    mysqli_stmt_execute($stmt_image);
    $result_image = mysqli_stmt_get_result($stmt_image);
    $image_data = mysqli_fetch_assoc($result_image);
    $image_path = $image_data['teacher_image'] ?? null;
    mysqli_stmt_close($stmt_image);

    // 3. Delete the user record from the 'users' table.
    // Due to `ON DELETE CASCADE` foreign key on `teacher.id` referencing `users.id`,
    // the corresponding record in the `teacher` table will be automatically deleted.
    $delete_user_query = "DELETE FROM users WHERE id = ?";
    $stmt_user = mysqli_prepare($conn, $delete_user_query);
    mysqli_stmt_bind_param($stmt_user, "i", $user_id_to_delete);
    if (!mysqli_stmt_execute($stmt_user)) {
        throw new Exception("Error deleting user from users table: " . mysqli_stmt_error($stmt_user));
    }

    if (mysqli_stmt_affected_rows($stmt_user) === 0) {
        throw new Exception("User record could not be deleted (already removed?).");
    }
    mysqli_stmt_close($stmt_user);

    // 4. Delete the uploaded image file, if it exists (only if not handled by your DB triggers or another system)
    if (!empty($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }

    // If all deletions were successful, commit the transaction
    mysqli_commit($conn);

    // Redirect with success message
    header("Location: teacher_list.php?success=Teacher deleted successfully");
    exit;

} catch (Exception $e) {
    // If any step failed, roll back the entire transaction
    mysqli_rollback($conn);

    // Redirect with a detailed error message
    header("Location: teacher_list.php?error=" . urlencode($e->getMessage()));
    exit;

} finally {
    // Always close the connection
    mysqli_close($conn);
}
?>