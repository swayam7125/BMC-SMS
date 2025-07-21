<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: principal_list.php?error=Invalid ID provided");
    exit;
}

$principal_id = intval($_GET['id']);

// Start transaction
mysqli_autocommit($conn, false);

try {
    // 1. Get the principal's email and image path BEFORE deleting
    $query_principal = "SELECT email, principal_image FROM principal WHERE id = ?";
    $stmt_fetch = mysqli_prepare($conn, $query_principal);
    mysqli_stmt_bind_param($stmt_fetch, "i", $principal_id);
    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);

    if (mysqli_num_rows($result) === 0) {
        throw new Exception("No principal found with the provided ID");
    }
    $principal_data = mysqli_fetch_assoc($result);
    $principal_email = $principal_data['email'];
    $image_path = $principal_data['principal_image'];
    mysqli_stmt_close($stmt_fetch);

    // 2. Delete the principal record
    $delete_principal = "DELETE FROM principal WHERE id = ?";
    $stmt_principal = mysqli_prepare($conn, $delete_principal);
    mysqli_stmt_bind_param($stmt_principal, "i", $principal_id);

    if (!mysqli_stmt_execute($stmt_principal)) {
        throw new Exception("Error deleting principal record.");
    }
    mysqli_stmt_close($stmt_principal);

    // 3. Delete the corresponding user record
    $delete_user = "DELETE FROM users WHERE email = ?";
    $stmt_user = mysqli_prepare($conn, $delete_user);
    mysqli_stmt_bind_param($stmt_user, "s", $principal_email);

    if (!mysqli_stmt_execute($stmt_user)) {
        throw new Exception("Error deleting user record.");
    }
    mysqli_stmt_close($stmt_user);

    // 4. Delete the uploaded image file, if it exists
    if (!empty($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }

    // If all successful, commit the transaction
    mysqli_commit($conn);

    header("Location: principal_list.php?success=Principal deleted successfully");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: principal_list.php?error=" . urlencode($e->getMessage()));
    exit;
}

mysqli_close($conn);
?>