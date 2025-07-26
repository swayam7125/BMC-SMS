<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Security Check: Ensure user is a logged-in schooladmin
session_start();
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
if ($role !== 'schooladmin') {
    die("Access Denied.");
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $leave_id = (int)$_GET['id'];
    $action = $_GET['action'];
    $new_status = '';

    if ($action == 'approve') {
        $new_status = 'Approved';
    } elseif ($action == 'reject') {
        $new_status = 'Rejected';
    }

    if (!empty($new_status)) {
        // Use a prepared statement to prevent SQL injection
        $stmt = $conn->prepare("UPDATE leave_applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $leave_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Redirect back to the requests page
header("Location: principal_leave_requests.php");
exit;
