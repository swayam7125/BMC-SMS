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

$principal_id = intval($_GET['id']); // This principal_id is now also the user_id
$user_id_to_delete = $principal_id; // The ID is directly the user ID

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Verify the user's role before deleting (security check)
    // This is crucial to prevent deleting the wrong user if an ID is tampered with.
    $check_user_role_query = "SELECT role, email, password FROM users WHERE id = ?"; // Fetch email for image path and password for audit (if needed)
    $stmt_check = mysqli_prepare($conn, $check_user_role_query);
    mysqli_stmt_bind_param($stmt_check, "i", $user_id_to_delete);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $user_record = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);

    if (!$user_record || $user_record['role'] !== 'schooladmin') { // Principal is 'schooladmin' role
        throw new Exception("User not found or role mismatch for deletion.");
    }
    
    $principal_email = $user_record['email']; // Get email from users table
    // Fetch principal_image for deletion
    $query_principal_image = "SELECT principal_image FROM principal WHERE id = ?";
    $stmt_image = mysqli_prepare($conn, $query_principal_image);
    mysqli_stmt_bind_param($stmt_image, "i", $principal_id);
    mysqli_stmt_execute($stmt_image);
    $result_image = mysqli_stmt_get_result($stmt_image);
    $image_data = mysqli_fetch_assoc($result_image);
    $image_path = $image_data['principal_image'] ?? null;
    mysqli_stmt_close($stmt_image);


    // 2. Delete the user record from the 'users' table.
    // Due to `ON DELETE CASCADE` foreign key on `principal.id` referencing `users.id`,
    // the corresponding record in the `principal` table will be automatically deleted.
    $delete_user_query = "DELETE FROM users WHERE id = ?";
    $stmt_user = mysqli_prepare($conn, $delete_user_query);
    mysqli_stmt_bind_param($stmt_user, "i", $user_id_to_delete);
    if (!mysqli_stmt_execute($stmt_user)) {
        throw new Exception("Error deleting user from users table: " . mysqli_stmt_error($stmt_user));
    }
    
    // Check if a row was actually deleted from users (implies cascade worked if FK is set)
    if (mysqli_stmt_affected_rows($stmt_user) === 0) {
        throw new Exception("User record could not be deleted (already removed?).");
    }
    mysqli_stmt_close($stmt_user);

    // 3. Delete the uploaded image file, if it exists (only if not handled by your DB triggers or another system)
    // This part is crucial as ON DELETE CASCADE does not delete actual files.
    if (!empty($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }

    // If all successful, commit the transaction
    mysqli_commit($conn);

    header("Location: principal_list.php?success=Principal deleted successfully");
    exit;

} catch (Exception $e) {
    // If any step failed, roll back the entire transaction
    mysqli_rollback($conn);

    // Redirect with a detailed error message
    header("Location: principal_list.php?error=" . urlencode($e->getMessage()));
    exit;

} finally {
    // Always close the connection
    mysqli_close($conn);
}
?>