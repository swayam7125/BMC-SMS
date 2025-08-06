<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set timezone to ensure 'time_ago' function is accurate
date_default_timezone_set('Asia/Kolkata');

// Include necessary files
include_once "encryption.php";
include_once "./includes/connect.php";

header('Content-Type: application/json');

$userId = null;
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$userId) {
    echo json_encode(['error' => 'User not authenticated.']);
    exit;
}

try {
    // --- FIXED: Converted to PDO syntax ---
    $stmt = $conn->prepare('SELECT "message", "link", "is_read", "created_at", "type" FROM "notifications" WHERE "user_id" = ? ORDER BY "created_at" DESC');
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Categorize Notifications ---
    $categorized = [
        'Assignments' => [],
        'Leave Status' => [],
        'Notices' => [],
        'Timetables' => [],
        'Other' => []
    ];

    foreach ($notifications as $notification) {
        $type = $notification['type'];
        // Sanitize and format the notification data
        $formatted_notification = [
            'message' => htmlspecialchars($notification['message']),
            'link' => htmlspecialchars($notification['link'] ?? '#'),
            'is_read' => (bool)$notification['is_read'],
            'time_ago' => time_ago($notification['created_at']),
            'raw_date' => $notification['created_at']
        ];

        // Categorization logic based on the 'type' column
        if (strpos($type, 'assignment') !== false) {
            $categorized['Assignments'][] = $formatted_notification;
        } elseif (strpos($type, 'leave') !== false) {
            $categorized['Leave Status'][] = $formatted_notification;
        } elseif (strpos($type, 'notice') !== false) {
            $categorized['Notices'][] = $formatted_notification;
        } elseif (strpos($type, 'timetable') !== false) {
            $categorized['Timetables'][] = $formatted_notification;
        } else {
            $categorized['Other'][] = $formatted_notification;
        }
    }

    echo json_encode($categorized);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}

// Function to calculate "time ago" from a timestamp
function time_ago($timestamp)
{
    $time_diff = time() - strtotime($timestamp);
    if ($time_diff < 60) {
        return 'just now';
    }
    $intervals = [
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute'
    ];
    foreach ($intervals as $secs => $str) {
        $d = $time_diff / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
        }
    }
    return 'a moment ago';
}

// Close the connection
$conn = null;
?>
