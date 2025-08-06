<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php?error=Invalid request method.");
    exit;
}

if (!isset($_COOKIE['encrypted_user_id']) || !isset($_COOKIE['encrypted_user_role'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = decrypt_id($_COOKIE['encrypted_user_id']);
$user_role = decrypt_id($_COOKIE['encrypted_user_role']);

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

try {
    // Fetch from the primary 'users' table for password verification
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() === 0) {
        header("Location: profile.php?error=User not found.");
        exit;
    }

    $user_record = $stmt->fetch(PDO::FETCH_ASSOC);
    $stored_password_hash = $user_record['password'];

    if (!password_verify($current_password, $stored_password_hash)) {
        header("Location: profile.php?error=Invalid current password.");
        exit;
    }

    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    $conn->beginTransaction();

    // Update both tables within a transaction
    $stmt_users = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt_users->execute([$new_password_hash, $user_id]);

    $stmt_role = $conn->prepare("UPDATE {$table_name} SET password = ? WHERE id = ?");
    $stmt_role->execute([$new_password_hash, $user_id]);

    $conn->commit();

    header("Location: profile.php?success=Password updated successfully.");
    exit;
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Password change error: " . $e->getMessage());
    header("Location: profile.php?error=An unexpected error occurred. Please try again.");
    exit;
}
