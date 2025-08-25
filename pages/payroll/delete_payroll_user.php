<?php
// Include necessary files with corrected paths
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Get user role and ID from cookies
$role = null;
$userId = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security: Only principals can perform this action
if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

// Get the payroll user ID to delete from the URL
$payroll_user_id_to_delete = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
if (!$payroll_user_id_to_delete) {
    header("Location: payroll_list.php?error=Invalid user ID.");
    exit;
}

// Get the principal's school ID for security
$admin_school_id = null;
if ($userId) {
    $stmt = $conn->prepare('SELECT "school_id" FROM "principal" WHERE "id" = ?');
    $stmt->execute([$userId]);
    $admin_school_id = $stmt->fetchColumn();
}

try {
    // Security Check: Verify the user being deleted belongs to the principal's school
    $stmt_check = $conn->prepare('SELECT school_id FROM payroll WHERE id = ?');
    $stmt_check->execute([$payroll_user_id_to_delete]);
    $user_school_id = $stmt_check->fetchColumn();

    if ($user_school_id != $admin_school_id) {
        header("Location: payroll_list.php?error=You do not have permission to delete this user.");
        exit;
    }

    // Begin transaction
    $conn->beginTransaction();

    // Delete from the 'users' table. The 'ON DELETE CASCADE' constraint on the 'payroll'
    // table will automatically delete the corresponding record there.
    $stmt_delete = $conn->prepare('DELETE FROM users WHERE id = ? AND role = \'payroll\'');
    $stmt_delete->execute([$payroll_user_id_to_delete]);

    // Commit the transaction
    $conn->commit();

    header("Location: payroll_list.php?success=Payroll user deleted successfully.");
    exit();

} catch (PDOException $e) {
    // If something goes wrong, roll back the transaction
    $conn->rollBack();
    error_log("Error deleting payroll user: " . $e->getMessage());
    header("Location: payroll_list.php?error=An error occurred during deletion.");
    exit;
}
?>
