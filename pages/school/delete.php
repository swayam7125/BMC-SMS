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
    header("Location: school_list.php?error=Invalid ID provided");
    exit;
}

$school_id = intval($_GET['id']);

// Start transaction
mysqli_autocommit($conn, false);

try {
    // First, delete related records in principal table
    $delete_principal = "DELETE FROM principal WHERE school_id = ?";
    $stmt_principal = mysqli_prepare($conn, $delete_principal);
    mysqli_stmt_bind_param($stmt_principal, "i", $school_id);

    if (!mysqli_stmt_execute($stmt_principal)) {
        throw new Exception("Error deleting principal record: " . mysqli_stmt_error($stmt_principal));
    }

    // Then delete the school record
    $delete_school = "DELETE FROM school WHERE id = ?";
    $stmt_school = mysqli_prepare($conn, $delete_school);
    mysqli_stmt_bind_param($stmt_school, "i", $school_id);

    if (!mysqli_stmt_execute($stmt_school)) {
        throw new Exception("Error deleting school record: " . mysqli_stmt_error($stmt_school));
    }

    // Check if any row was actually deleted
    if (mysqli_stmt_affected_rows($stmt_school) === 0) {
        throw new Exception("No school found with the provided ID");
    }

    // Commit transaction
    mysqli_commit($conn);

    // Close statements
    mysqli_stmt_close($stmt_principal);
    mysqli_stmt_close($stmt_school);

    // Correct redirect path for success
    header("Location: ../school/school_list.php?success=School deleted successfully");
    exit;
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);

    // Close statements if they exist
    if (isset($stmt_principal)) mysqli_stmt_close($stmt_principal);
    if (isset($stmt_school)) mysqli_stmt_close($stmt_school);

    // Correct redirect path for error
    header("Location: ../school/school_list.php?error=" . urlencode($e->getMessage()));
    exit;
}

// Close connection
mysqli_close($conn);
?>