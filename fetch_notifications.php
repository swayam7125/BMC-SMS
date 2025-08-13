<?php
date_default_timezone_set('Asia/Kolkata');

// NOTE: These paths assume fetch_notifications.php is in the root directory.
// Adjust the paths if this file is located elsewhere.
include_once __DIR__ . "/encryption.php";
include_once __DIR__ . "/includes/connect.php";

header('Content-Type: application/json');

$userId = null;
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$userId) {
    echo json_encode(['error' => 'User not authenticated.']);
    exit;
}

$notifications = [];

try {
    // This query correctly fetches ALL notifications (read and unread) for the dashboard
    $stmt = $conn->prepare("SELECT id, message, link, is_read, created_at, type FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Fetch Notifications Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
    // Close the connection before exiting
    $conn = null;
    exit;
}

// Close the connection
$conn = null;


// --- Categorize Notifications ---
$categorized = [
    'Acquisition Requests' => [], // FIX: Added a new category for Acquisition Requests
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
        'id' => htmlspecialchars($notification['id']),
        'message' => htmlspecialchars($notification['message']),
        'link' => htmlspecialchars($notification['link'] ?? '#'),
        'is_read' => (bool)$notification['is_read'],
        'time_ago' => time_ago($notification['created_at']),
        'raw_date' => $notification['created_at']
    ];

    // FIX: Added logic to categorize acquisition_request notifications
    if (strpos($type, 'acquisition_request') !== false) {
        $categorized['Acquisition Requests'][] = $formatted_notification;
    } elseif (strpos($type, 'assignment') !== false) {
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

echo json_encode($categorized);
?>
