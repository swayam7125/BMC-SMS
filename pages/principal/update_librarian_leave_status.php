<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // Log system included

header('Content-Type: application/json');

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'principal') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized action.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$leave_id = $_POST['leave_id'];
$status = $_POST['status'];
$rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : null;

try {
    // Fetch Librarian ID and name for notification and logging
    $stmt_info = $conn->prepare("SELECT la.librarian_id, l.librarian_name FROM librarian_leave_applications la JOIN librarian l ON la.librarian_id = l.id WHERE la.id = ?");
    $stmt_info->execute([$leave_id]);
    $leave_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    if (!$leave_info) {
        throw new Exception("Leave application not found.");
    }
    $librarian_id = $leave_info['librarian_id'];
    $librarian_name = $leave_info['librarian_name'];

    // Update the leave status
    $stmt = $conn->prepare("UPDATE librarian_leave_applications SET status = ?, rejection_reason = ? WHERE id = ?");
    $stmt->execute([$status, $rejection_reason, $leave_id]);

    // Notify the librarian
    $notification_msg = "Your leave application has been " . htmlspecialchars($status) . ".";
    $notification_link = "pages/librarian/my_leave_management.php";
    $notification_type = "librarian_leave_status";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
    $stmt_notify->execute([$librarian_id, $notification_msg, $notification_link, $notification_type]);
    
    // Log the action
    $log_message = "LEAVE MGMT (Librarian): {$status} leave application ID {$leave_id} for {$librarian_name}.";
    if ($status === 'Rejected' && $rejection_reason) {
        $log_message .= " Reason: {$rejection_reason}";
    }
    log_interaction($role, $userId, $log_message, $userName);

    echo json_encode(['status' => 'success', 'message' => 'Leave status updated successfully.']);

} catch (Exception $e) {
    // Log the error
    log_interaction($role, $userId, "LEAVE MGMT (Librarian) ERROR: Failed to update leave status for application ID {$leave_id}. Error: " . $e->getMessage(), $userName);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>