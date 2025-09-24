<?php
require_once "../connect.php";
require_once "../../encryption.php";

header('Content-Type: application/json');

$response = ['success' => false, 'counts' => []];

$user_id = null;
$role = null;

// Get user info from cookies
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$user_id || !$role) {
    echo json_encode($response);
    exit;
}

try {
    $counts = [];
    
    switch ($role) {
        case 'student':
            $sql = "SELECT
                        COUNT(*) FILTER (WHERE type = 'new_assignment' AND is_read = false) AS assignments,
                        COUNT(*) FILTER (WHERE type = 'marks_uploaded' AND is_read = false) AS results,
                        COUNT(*) FILTER (WHERE type = 'school_notice' AND is_read = false) AS notices,
                        COUNT(*) FILTER (WHERE type = 'new_notes' AND is_read = false) AS notes,
                        COUNT(*) FILTER (WHERE type = 'exam_timetable' AND is_read = false) AS exam_timetables,
                        COUNT(*) FILTER (WHERE type = 'borrow_status' AND is_read = false) AS library_status
                    FROM notifications WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $counts['new_assignment'] = (int) ($result['assignments'] ?? 0);
                $counts['marks_uploaded'] = (int) ($result['results'] ?? 0);
                $counts['school_notice'] = (int) ($result['notices'] ?? 0);
                $counts['new_notes'] = (int) ($result['notes'] ?? 0);
                $counts['exam_timetable'] = (int) ($result['exam_timetables'] ?? 0);
                $counts['borrow_status'] = (int) ($result['library_status'] ?? 0);
            }
            break;

        case 'teacher':
            $sql = "SELECT
                        COUNT(*) FILTER (WHERE type = 'school_notice' AND is_read = false) AS teacher_notices,
                        COUNT(*) FILTER (WHERE type = 'assignment_submission' AND is_read = false) AS submissions,
                        COUNT(*) FILTER (WHERE type = 'leave_status' AND is_read = false) AS leave_status,
                        COUNT(*) FILTER (WHERE type = 'exam_timetable' AND is_read = false) AS exam_timetables,
                        COUNT(*) FILTER (WHERE type = 'borrow_status' AND is_read = false) AS library_status,
                        COUNT(*) FILTER (WHERE type = 'salary' AND is_read = false) AS salary_notifs
                    FROM notifications WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $counts['school_notice'] = (int) ($result['teacher_notices'] ?? 0);
                $counts['assignment_submission'] = (int) ($result['submissions'] ?? 0);
                $counts['leave_status'] = (int) ($result['leave_status'] ?? 0);
                $counts['exam_timetable'] = (int) ($result['exam_timetables'] ?? 0);
                $counts['borrow_status'] = (int) ($result['library_status'] ?? 0);
                $counts['salary'] = (int) ($result['salary_notifs'] ?? 0);
            }
            break;

        // Add other roles as needed...
    }

    $response['success'] = true;
    $response['counts'] = $counts;

} catch (PDOException $e) {
    error_log("Notification count error: " . $e->getMessage());
}

echo json_encode($response);
?>
