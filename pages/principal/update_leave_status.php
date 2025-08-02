<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/email_functions.php'; // Include the email functions

// Security Check: Ensure user is a logged-in principal
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
if ($role !== 'principal') {
    header("Location: /BMC-SMS/login.php?error=unauthorized");
    exit;
}

$leave_id = 0;
$action_status_message = '';
$rejection_reason = ''; // Initialize rejection reason

// Handle rejection submitted from the modal form (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    $leave_id = (int) $_POST['leave_id'];
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
    $leave_id = (int) $_GET['id'];
    $action_status_message = 'Approved';

    $stmt = $conn->prepare("UPDATE leave_applications SET status = 'Approved', rejection_reason = NULL WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->close();
}

// If a leave application was processed, send a notification and an email
if ($leave_id > 0 && !empty($action_status_message)) {
    // Get the teacher's ID, email, and name for the notification
    $stmt_get_teacher = $conn->prepare(
        "SELECT l.teacher_id, t.teacher_name, t.email, l.from_date, l.to_date 
         FROM leave_applications l 
         JOIN teacher t ON l.teacher_id = t.id 
         WHERE l.id = ?"
    );
    $stmt_get_teacher->bind_param("i", $leave_id);
    $stmt_get_teacher->execute();
    $result = $stmt_get_teacher->get_result();

    if ($leave_data = $result->fetch_assoc()) {
        $teacher_id = $leave_data['teacher_id'];
        $teacher_name = $leave_data['teacher_name'];
        $teacher_email = $leave_data['email'];
        $from_date = $leave_data['from_date'];
        $to_date = $leave_data['to_date'];

        // 1. Send In-App Notification
        $notification_message = "Your leave application has been " . $action_status_message . ".";
        $notification_link = "/pages/teacher/teacher_leave_management.php"; // Updated link
        $notification_type = "leave_status";

        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
        $stmt_notify->bind_param("isss", $teacher_id, $notification_message, $notification_link, $notification_type);
        $stmt_notify->execute();
        $stmt_notify->close();

        // 2. Send Email Notification
        $email_subject = "Your Leave Application has been " . $action_status_message;
        $email_body = "
            <p>Dear " . htmlspecialchars($teacher_name) . ",</p>
            <p>Your leave application from <strong>" . htmlspecialchars($from_date) . "</strong> to <strong>" . htmlspecialchars($to_date) . "</strong> has been <strong>" . $action_status_message . "</strong>.</p>";

        if ($action_status_message === 'Rejected' && !empty($rejection_reason)) {
            $email_body .= "<p><strong>Reason for Rejection:</strong> " . htmlspecialchars($rejection_reason) . "</p>";
        }

        $email_body .= "<p>You can view your leave history by logging into the portal.</p><p>Thank you.</p>";

        send_email($teacher_email, $email_subject, $email_body);
    }
    $stmt_get_teacher->close();
}

$conn->close();

// Redirect back to the requests page after processing
header("Location: principal_leave_requests.php?status=updated");
exit;
?>