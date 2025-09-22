<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once "../../includes/log_system.php"; // ADDED: Log system dependency

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : 'N/A';
$principal_name_acting = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'Principal'; // ADDED: Acting Principal's name

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
            header("Location: librarian_leave_management.php?error=reason_required");
            exit;
        }

        $stmt = $conn->prepare("UPDATE librarian_leave_applications SET status = 'Rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$rejection_reason, $leave_id]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'approve') {
        $leave_id = (int) $_GET['id'];
        $action_status_message = 'Approved';

        $stmt = $conn->prepare("UPDATE librarian_leave_applications SET status = 'Approved', rejection_reason = NULL WHERE id = ?");
        $stmt->execute([$leave_id]);
    }

    if ($leave_id > 0 && !empty($action_status_message)) {
        $stmt_get_librarian = $conn->prepare(
            "SELECT l.librarian_id, li.librarian_name, li.email, l.from_date, l.to_date 
             FROM librarian_leave_applications l 
             JOIN librarian li ON l.librarian_id = li.id 
             WHERE l.id = ?"
        );
        $stmt_get_librarian->execute([$leave_id]);

        if ($leave_data = $stmt_get_librarian->fetch(PDO::FETCH_ASSOC)) {
            $librarian_id = $leave_data['librarian_id'];
            $librarian_name = $leave_data['librarian_name'];
            $librarian_email = $leave_data['email'];
            $from_date = $leave_data['from_date'];
            $to_date = $leave_data['to_date'];

            // ⭐ LOGGING: Log the leave action
            $log_message = "Librarian leave application (ID: {$leave_id}, Librarian: {$librarian_name}) was {$action_status_message} by Principal.";
            log_interaction($role, $userId, $log_message, $principal_name_acting);

            $notification_message = "Your leave application has been " . $action_status_message . ".";
            // Correct the notification link here
            $notification_link = "pages/librarian/my_leave_management.php";
            $notification_type = "librarian_leave_status";

            $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
            $stmt_notify->execute([$librarian_id, $notification_message, $notification_link, $notification_type]);

            // Email sending logic can be added here
        }
    }
} catch (PDOException $e) {
    error_log("Update Librarian Leave Status Error: " . $e->getMessage());
    header("Location: librarian_leave_management.php?error=db_error");
    exit;
}

header("Location: librarian_leave_management.php?status=updated");
exit;