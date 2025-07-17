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
    header("Location: ../../extra/student_tables.php?error=Invalid ID provided");
    exit;
}

$student_id = intval($_GET['id']);

// Start transaction
mysqli_autocommit($conn, false);

try {
    // Delete the student record
    $delete_student = "DELETE FROM student WHERE id = ?";
    $stmt_student = mysqli_prepare($conn, $delete_student);
    mysqli_stmt_bind_param($stmt_student, "i", $student_id);
    
    if (!mysqli_stmt_execute($stmt_student)) {
        throw new Exception("Error deleting student record: " . mysqli_stmt_error($stmt_student));
    }
    
    // Check if any row was actually deleted
    if (mysqli_stmt_affected_rows($stmt_student) === 0) {
        throw new Exception("No student found with the provided ID");
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Close statement
    mysqli_stmt_close($stmt_student);
    
    // Redirect with success message
    header("Location: ../../extra/student_tables.php?success=Student deleted successfully");
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    // Close statement if it exists
    if (isset($stmt_student)) {
        mysqli_stmt_close($stmt_student);
    }
    
    // Redirect with error message
    header("Location: ../../extra/student_tables.php?error=" . urlencode($e->getMessage()));
    exit;
}

// Close connection
mysqli_close($conn);
?>