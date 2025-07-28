<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Security Check: Ensure user is a logged-in schooladmin
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
if ($role !== 'schooladmin') {
    header("Location: /BMC-SMS/login.php?error=unauthorized");
    exit;
}

$leave_id = 0;
$action_status_message = '';

// Handle rejection submitted from the modal form (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    $leave_id = (int)$_POST['leave_id'];
    $rejection_reason = trim($_POST['rejection_reason']);
    $action_status_message = 'Rejected';

    if (empty($rejection_reason)) {
        header("Location: principal_leave_requests.php?error=reason_required");
        exit;
    }

    $stmt = $conn->prepare("UPDATE leave_applications SET status = 'Rejected', rejection_reason = ? WHERE id = ?");
    $stmt->bind_param("si", $rejection_reason, $leave_id);
    $stmt->execute();
    $stmt->close();

// Handle approval from a direct link (GET request)
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'approve') {
    $leave_id = (int)$_GET['id'];
    $action_status_message = 'Approved';
    
    $stmt = $conn->prepare("UPDATE leave_applications SET status = 'Approved', rejection_reason = NULL WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->close();
}

// If a leave application was processed, send a notification
if ($leave_id > 0 && !empty($action_status_message)) {
    // Get the teacher_id to send the notification to
    $stmt_get_teacher = $conn->prepare("SELECT teacher_id FROM leave_applications WHERE id = ?");
    $stmt_get_teacher->bind_param("i", $leave_id);
    $stmt_get_teacher->execute();
    $result = $stmt_get_teacher->get_result();

    if ($leave_data = $result->fetch_assoc()) {
        $teacher_id = $leave_data['teacher_id'];
        
        $notification_message = "Your leave application has been " . $action_status_message . ".";
        $notification_link = "/pages/teacher/teacher_leave_history.php";
        $notification_type = "leave_status";

        // Insert the notification for the teacher
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
        $stmt_notify->bind_param("isss", $teacher_id, $notification_message, $notification_link, $notification_type);
        $stmt_notify->execute();
        $stmt_notify->close();
    }
    $stmt_get_teacher->close();
}

$conn->close();

// Redirect back to the requests page after processing
header("Location: principal_leave_requests.php?status=updated");
exit;
?>