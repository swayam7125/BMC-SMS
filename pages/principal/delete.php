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
    header("Location: principal_list.php?error=Invalid ID provided");
    exit;
}

$principal_id = intval($_GET['id']);

// Start transaction
mysqli_autocommit($conn, false);

try {
    // Delete the principal record
    $delete_principal = "DELETE FROM principal WHERE id = ?";
    $stmt_principal = mysqli_prepare($conn, $delete_principal);
    mysqli_stmt_bind_param($stmt_principal, "i", $principal_id);

    if (!mysqli_stmt_execute($stmt_principal)) {
        throw new Exception("Error deleting principal record: " . mysqli_stmt_error($stmt_principal));
    }

    // Check if any row was actually deleted
    if (mysqli_stmt_affected_rows($stmt_principal) === 0) {
        throw new Exception("No principal found with the provided ID");
    }

    // Commit transaction
    mysqli_commit($conn);

    // Close statement
    mysqli_stmt_close($stmt_principal);

    // Redirect with success message
    header("Location: principal_list.php?success=Principal deleted successfully");
    exit;
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);

    // Close statement if it exists
    if (isset($stmt_principal)) {
        mysqli_stmt_close($stmt_principal);
    }

    // Redirect with error message
    header("Location: principal_list.php?error=" . urlencode($e->getMessage()));
    exit;
}

// Close connection
mysqli_close($conn);