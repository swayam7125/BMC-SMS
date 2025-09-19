<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

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
            header("Location: hr_leave_management.php?error=reason_required");
            exit;
        }

        $stmt = $conn->prepare("UPDATE hr_leave_applications SET status = 'Rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$rejection_reason, $leave_id]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'approve') {
        $leave_id = (int) $_GET['id'];
        $action_status_message = 'Approved';

        $stmt = $conn->prepare("UPDATE hr_leave_applications SET status = 'Approved', rejection_reason = NULL WHERE id = ?");
        $stmt->execute([$leave_id]);
    }

    if ($leave_id > 0 && !empty($action_status_message)) {
        $stmt_get_hr = $conn->prepare(
            "SELECT l.hr_id, h.hr_name, h.email, l.from_date, l.to_date 
             FROM hr_leave_applications l 
             JOIN hr h ON l.hr_id = h.id 
             WHERE l.id = ?"
        );
        $stmt_get_hr->execute([$leave_id]);

        if ($leave_data = $stmt_get_hr->fetch(PDO::FETCH_ASSOC)) {
            $hr_id = $leave_data['hr_id'];
            $hr_name = $leave_data['hr_name'];
            $hr_email = $leave_data['email'];
            $from_date = $leave_data['from_date'];
            $to_date = $leave_data['to_date'];

            $notification_message = "Your leave application has been " . $action_status_message . ".";
            $notification_link = "pages/hr/my_leave_management.php";
            $notification_type = "hr_leave_status";

            $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
            $stmt_notify->execute([$hr_id, $notification_message, $notification_link, $notification_type]);

            // Assuming that single-day approved leave automatically marks attendance as 'Leave'
            if ($action_status_message === 'Approved' && $from_date === $to_date) {
                // To insert or update attendance, we need the school_id. We can get this from the hr table.
                $stmt_get_school_id = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
                $stmt_get_school_id->execute([$hr_id]);
                $school_id = $stmt_get_school_id->fetchColumn();
                
                if ($school_id) {
                     // Check if an attendance record already exists for the HR for that date
                    $stmt_check_attendance = $conn->prepare("SELECT COUNT(*) FROM hr_attendance WHERE hr_id = ? AND attendance_date = ?");
                    $stmt_check_attendance->execute([$hr_id, $from_date]);
                    $record_exists = $stmt_check_attendance->fetchColumn();

                    if ($record_exists > 0) {
                        // Update the existing record
                        $stmt_update_attendance = $conn->prepare("UPDATE hr_attendance SET status = 'Leave', marked_by_user_id = ?, updated_at = NOW() WHERE hr_id = ? AND attendance_date = ?");
                        $stmt_update_attendance->execute([$current_user_id, $hr_id, $from_date]);
                    } else {
                        // Insert a new record
                        $stmt_insert_attendance = $conn->prepare("INSERT INTO hr_attendance (hr_id, school_id, attendance_date, status, marked_by_user_id) VALUES (?, ?, ?, 'Leave', ?)");
                        $stmt_insert_attendance->execute([$hr_id, $school_id, $from_date, $current_user_id]);
                    }
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log("Update HR Leave Status Error: " . $e->getMessage());
    header("Location: hr_leave_management.php?error=db_error");
    exit;
}

header("Location: hr_leave_management.php?status=updated");
exit;
?>