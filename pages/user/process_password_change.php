<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php?error=Invalid request method.");
    exit;
}

// Check if user is logged in
if (!isset($_COOKIE['encrypted_user_id']) || !isset($_COOKIE['encrypted_user_role'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = decrypt_id($_COOKIE['encrypted_user_id']);
$user_role = decrypt_id($_COOKIE['encrypted_user_role']);

// Basic input validation
$current_password = trim($_POST['current_password'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header("Location: profile.php?error=All password fields are required.");
    exit;
}

if ($new_password !== $confirm_password) {
    header("Location: profile.php?error=New passwords do not match.");
    exit;
}

// Determine the table based on the user's role
$table_name = '';
switch ($user_role) {
    case 'teacher':
        $table_name = 'teacher';
        break;
    case 'student':
        $table_name = 'student';
        break;
    case 'principal':
        $table_name = 'principal';
        break;
    case 'librarian':
        $table_name = 'librarian';
        break;
    default:
        header("Location: profile.php?error=Invalid user role.");
        exit;
}

// Fetch the user's current password hash from the database
try {
    $query = "SELECT password FROM {$table_name} WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        header("Location: profile.php?error=User not found.");
        exit;
    }

    $user_record = mysqli_fetch_assoc($result);
    $stored_password_hash = $user_record['password'];

    // Securely verify the current password
    if (!password_verify($current_password, $stored_password_hash)) {
        header("Location: profile.php?error=Invalid current password.");
        exit;
    }

    // Hash the new password securely
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    // The 'users' table is the primary source for login credentials, so update both 'users' and the specific role table.
    $update_users_table = "UPDATE users SET password = ? WHERE id = ?";
    $update_role_table = "UPDATE {$table_name} SET password = ? WHERE id = ?";

    $update_stmt_users = mysqli_prepare($conn, $update_users_table);
    $update_stmt_role = mysqli_prepare($conn, $update_role_table);

    mysqli_stmt_bind_param($update_stmt_users, "si", $new_password_hash, $user_id);
    mysqli_stmt_bind_param($update_stmt_role, "si", $new_password_hash, $user_id);

    if (mysqli_stmt_execute($update_stmt_users) && mysqli_stmt_execute($update_stmt_role)) {
        header("Location: profile.php?success=Password updated successfully.");
        exit;
    } else {
        throw new Exception("Password update failed: " . mysqli_stmt_error($conn));
    }
} catch (Exception $e) {
    error_log("Password change error: " . $e->getMessage());
    header("Location: profile.php?error=An unexpected error occurred. Please try again.");
    exit;
}
?>