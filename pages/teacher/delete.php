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
$teacher_email = null;

// Start transaction to ensure both deletions succeed or fail together
mysqli_begin_transaction($conn);

try {
    // 1. Get the teacher's email before deleting the record
    $query_email = "SELECT email FROM teacher WHERE id = ?";
    $stmt_email = mysqli_prepare($conn, $query_email);
    mysqli_stmt_bind_param($stmt_email, "i", $teacher_id);
    mysqli_stmt_execute($stmt_email);
    $result_email = mysqli_stmt_get_result($stmt_email);

    if ($row = mysqli_fetch_assoc($result_email)) {
        $teacher_email = $row['email'];
    }
    mysqli_stmt_close($stmt_email);

    if (!$teacher_email) {
        throw new Exception("No teacher found with the provided ID.");
    }

    // 2. Delete the corresponding user from the 'users' table
    $delete_user = "DELETE FROM users WHERE email = ? AND role = 'teacher'";
    $stmt_user = mysqli_prepare($conn, $delete_user);
    mysqli_stmt_bind_param($stmt_user, "s", $teacher_email);
    if (!mysqli_stmt_execute($stmt_user)) {
        throw new Exception("Error deleting from users table: " . mysqli_stmt_error($stmt_user));
    }
    mysqli_stmt_close($stmt_user);

    // 3. Delete the teacher record from the 'teacher' table
    $delete_teacher = "DELETE FROM teacher WHERE id = ?";
    $stmt_teacher = mysqli_prepare($conn, $delete_teacher);
    mysqli_stmt_bind_param($stmt_teacher, "i", $teacher_id);
    if (!mysqli_stmt_execute($stmt_teacher)) {
        throw new Exception("Error deleting from teacher table: " . mysqli_stmt_error($stmt_teacher));
    }
    
    // Check if a row was actually deleted
    if (mysqli_stmt_affected_rows($stmt_teacher) === 0) {
        throw new Exception("Teacher record could not be deleted (already removed?).");
    }
    mysqli_stmt_close($stmt_teacher);

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