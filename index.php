<?php
include_once "encryption.php";

// List of valid roles
$allowed_roles = ['student', 'teacher', 'schooladmin', 'bmc'];

// Initialize role
$role = null;

// Check if encrypted role cookie exists
if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted = decrypt_id($_COOKIE['encrypted_user_role']);

    // Validate role
    if (in_array($decrypted, $allowed_roles)) {
        $role = $decrypted;
    }
}

// If valid role, redirect to corresponding dashboard
if ($role) {
    switch ($role) {
        case 'student':
            header("Location: dashboard.php");
            break;
        case 'teacher':
            header("Location: dashboard.php");
            break;
        case 'schooladmin':
            header("Location: dashboard.php");
            break;
        case 'bmc':
            header("Location: dashboard.php");
            break;
    }
    exit;
}

// If no valid role, redirect to login
header("Location: login.php");
exit;
?>