<?php
// --- ADDED: Include necessary files for decryption and logging ---
require_once "encryption.php";
require_once "./includes/log_system.php";

// 1. Retrieve user information from cookies before clearing them
$user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : 'Unknown ID';
$user_role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : 'Unknown Role';
$user_name = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'Unknown User';

// 2. Log the interaction
log_interaction($user_role, $user_id, 'User logged out successfully.', $user_name);

// 3. Destroy session (if any)
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}

// Clear all cookies
setcookie("encrypted_user_id", "", time() - 3600, "/");
setcookie("encrypted_user_role", "", time() - 3600, "/");
setcookie("encrypted_profile_image", "", time() - 3600, "/");
setcookie("encrypted_user_name", "", time() - 3600, "/");

// Redirect to the login page
header("Location: login.php");
exit(); 
?>