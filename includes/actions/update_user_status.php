<?php
// --- ADDED FOR DEBUGGING ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../connect.php";
include_once "../../encryption.php";

// Get current user's role to check permissions
$current_user_role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;

if (!$current_user_role) {
    header("Location: ../../login.php");
    exit;
}

// Get the user ID and desired status from the URL
$user_id_to_update = isset($_GET['id']) ? intval($_GET['id']) : 0;
$new_status = isset($_GET['status']) && in_array($_GET['status'], ['active', 'suspended']) ? $_GET['status'] : null;
$return_page = isset($_GET['return']) ? $_GET['return'] : '../../dashboard.php';

if ($user_id_to_update <= 0 || !$new_status) {
    header("Location: $return_page?error=Invalid request");
    exit;
}

// Fetch the role of the user being updated
$query_target_user = "SELECT role FROM users WHERE id = ?";
$stmt_target = mysqli_prepare($conn, $query_target_user);
mysqli_stmt_bind_param($stmt_target, "i", $user_id_to_update);
mysqli_stmt_execute($stmt_target);
$result_target = mysqli_stmt_get_result($stmt_target);
$target_user = mysqli_fetch_assoc($result_target);
$target_role = $target_user ? $target_user['role'] : null;
mysqli_stmt_close($stmt_target);

if (!$target_role) {
    header("Location: $return_page?error=User not found");
    exit;
}

// --- PERMISSION LOGIC UPDATED ---
$is_authorized = false;
switch ($current_user_role) {
    case 'superadmin':
        if ($target_role === 'principal') $is_authorized = true;
        break;
    case 'principal':
        // A Principal can manage teachers, students, AND librarians
        if ($target_role === 'teacher' || $target_role === 'student' || $target_role === 'librarian') $is_authorized = true;
        break;
    // The 'teacher' case has been removed. Teachers are no longer authorized.
}

if (!$is_authorized) {
    header("Location: $return_page?error=You are not authorized to perform this action.");
    exit;
}

// --- Update the Database ---
$query_update = "UPDATE users SET account_status = ? WHERE id = ?";
$stmt_update = mysqli_prepare($conn, $query_update);
mysqli_stmt_bind_param($stmt_update, "si", $new_status, $user_id_to_update);

if (mysqli_stmt_execute($stmt_update)) {
    $action = ($new_status === 'suspended') ? 'suspended' : 'reactivated';
    header("Location: $return_page?success=User has been successfully {$action}.");
} else {
    header("Location: $return_page?error=Failed to update user status.");
}
mysqli_stmt_close($stmt_update);
mysqli_close($conn);
?>