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

$student_id = intval($_GET['id']);
$student_email = null;

// Begin a transaction to ensure atomicity
mysqli_begin_transaction($conn);

try {
    // Step 1: Find the student's email from the 'student' table before deletion.
    $query_email = "SELECT email FROM student WHERE id = ?";
    $stmt_email = mysqli_prepare($conn, $query_email);
    mysqli_stmt_bind_param($stmt_email, "i", $student_id);
    mysqli_stmt_execute($stmt_email);
    $result_email = mysqli_stmt_get_result($stmt_email);

    if ($row = mysqli_fetch_assoc($result_email)) {
        $student_email = $row['email'];
    }
    mysqli_stmt_close($stmt_email);

    // If no student was found, throw an error.
    if (!$student_email) {
        throw new Exception("No student found with the provided ID.");
    }

    // Step 2: Delete the corresponding user from the 'users' table.
    // The role check ensures we only delete the student user account.
    $delete_user = "DELETE FROM users WHERE email = ? AND role = 'student'";
    $stmt_user = mysqli_prepare($conn, $delete_user);
    mysqli_stmt_bind_param($stmt_user, "s", $student_email);
    if (!mysqli_stmt_execute($stmt_user)) {
        throw new Exception("Error deleting from users table: " . mysqli_stmt_error($stmt_user));
    }
    mysqli_stmt_close($stmt_user);

    // Step 3: Delete the student record from the 'student' table.
    $delete_student = "DELETE FROM student WHERE id = ?";
    $stmt_student = mysqli_prepare($conn, $delete_student);
    mysqli_stmt_bind_param($stmt_student, "i", $student_id);
    if (!mysqli_stmt_execute($stmt_student)) {
        throw new Exception("Error deleting from student table: " . mysqli_stmt_error($stmt_student));
    }
    
    // Confirm that the student record was actually deleted.
    if (mysqli_stmt_affected_rows($stmt_student) === 0) {
        throw new Exception("Student record could not be deleted (it may have been removed already).");
    }
    mysqli_stmt_close($stmt_student);

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