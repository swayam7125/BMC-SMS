<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Security Check: Ensure user is a logged-in schooladmin
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
if ($role !== 'schooladmin') {
    header("Location: /BMC-SMS/login.php?error=unauthorized");
    exit;
}

// Handle rejection submitted from the modal form (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    $leave_id = (int)$_POST['leave_id'];
    $rejection_reason = trim($_POST['rejection_reason']);

    // Ensure a reason is provided for rejection
    if (empty($rejection_reason)) {
        header("Location: principal_leave_requests.php?error=reason_required");
        exit;
    }

    // Prepare and execute the update statement for rejection
    $stmt = $conn->prepare("UPDATE leave_applications SET status = 'Rejected', rejection_reason = ? WHERE id = ?");
    $stmt->bind_param("si", $rejection_reason, $leave_id);
    $stmt->execute();
    $stmt->close();

// Handle approval from a direct link (GET request)
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'approve') {
    $leave_id = (int)$_GET['id'];
    
    // Prepare and execute the update statement for approval
    // Set rejection_reason to NULL to clear it in case it was previously rejected.
    $stmt = $conn->prepare("UPDATE leave_applications SET status = 'Approved', rejection_reason = NULL WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();

// Redirect back to the requests page after processing
header("Location: principal_leave_requests.php?status=updated");
exit;