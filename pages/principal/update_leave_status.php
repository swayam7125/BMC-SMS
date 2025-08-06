<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
// include_once '../../includes/email_functions.php'; // Uncomment if email is set up

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
if ($role !== 'principal') {
    header("Location: /BMC-SMS/login.php?error=unauthorized");
    exit;
}

$leave_id = 0;
$action_status_message = '';
$rejection_reason = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
        $leave_id = (int) $_POST['leave_id'];
        $rejection_reason = trim($_POST['rejection_reason']);
        $action_status_message = 'Rejected';

        if (empty($rejection_reason)) {
            header("Location: principal_leave_requests.php?error=reason_required");
            exit;
        }

        $stmt = $conn->prepare("UPDATE leave_applications SET status = 'Rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$rejection_reason, $leave_id]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'approve') {
        $leave_id = (int) $_GET['id'];
        $action_status_message = 'Approved';

        $stmt = $conn->prepare("UPDATE leave_applications SET status = 'Approved', rejection_reason = NULL WHERE id = ?");
        $stmt->execute([$leave_id]);
    }

    if ($leave_id > 0 && !empty($action_status_message)) {
        $stmt_get_teacher = $conn->prepare(
            "SELECT l.teacher_id, t.teacher_name, t.email, l.from_date, l.to_date 
             FROM leave_applications l 
             JOIN teacher t ON l.teacher_id = t.id 
             WHERE l.id = ?"
        );
        $stmt_get_teacher->execute([$leave_id]);

        if ($leave_data = $stmt_get_teacher->fetch(PDO::FETCH_ASSOC)) {
            $teacher_id = $leave_data['teacher_id'];
            $teacher_name = $leave_data['teacher_name'];
            $teacher_email = $leave_data['email'];
            $from_date = $leave_data['from_date'];
            $to_date = $leave_data['to_date'];

            $notification_message = "Your leave application has been " . $action_status_message . ".";
            $notification_link = "/BMC-SMS/pages/teacher/teacher_leave_management.php";
            $notification_type = "leave_status";

            $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
            $stmt_notify->execute([$teacher_id, $notification_message, $notification_link, $notification_type]);

            // Email sending logic can be added here
        }
    }
} catch (PDOException $e) {
    error_log("Update Leave Status Error: " . $e->getMessage());
    header("Location: principal_leave_requests.php?error=db_error");
    exit;
}

header("Location: principal_leave_requests.php?status=updated");
exit;
