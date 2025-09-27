<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set timezone to ensure 'time_ago' function is accurate
date_default_timezone_set('Asia/Kolkata');

// NOTE: These paths assume fetch_notifications.php is in the root directory.
// Adjust the paths if this file is located elsewhere.
include_once __DIR__ . "/encryption.php";
include_once __DIR__ . "/includes/connect.php";
include_once __DIR__ . "/includes/log_system.php"; 

header('Content-Type: application/json');

$userId = null;
$role = null; 
$userName = 'Guest'; 

if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_name'])) {
    $userName = decrypt_id($_COOKIE['encrypted_user_name']);
}


if (!$userId) {
    echo json_encode(['error' => 'User not authenticated.']);
    exit;
}

$notifications = [];
$unread_count = 0;
$total_count = 0;

try {
    // This query correctly fetches ALL notifications (read and unread) for the dashboard
    $stmt = $conn->prepare("SELECT id, message, link, is_read, created_at, type FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get counts for logging
    $total_count = count($notifications);
    $unread_count = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
    
    $notification_summary = [];
    foreach ($notifications as $notification) {
        $type = $notification['type'];
        if (!isset($notification_summary[$type])) {
            $notification_summary[$type] = 0;
        }
        $notification_summary[$type]++;
    }

    $summary_parts = [];
    foreach ($notification_summary as $type => $count) {
        $summary_parts[] = "$type ($count)";
    }
    $summary_string = !empty($summary_parts) ? " Types: " . implode(', ', $summary_parts) . "." : "";

    // Log the action with more details
    $log_message = "API: User fetched notifications. Total found: {$total_count}, Unread: {$unread_count}." . $summary_string;
    log_interaction($role, $userId, $log_message, $userName);


} catch (PDOException $e) {
    error_log("Fetch Notifications Error: " . $e->getMessage());
    // Log the error
    log_interaction($role, $userId, "API ERROR: Failed to fetch notifications. DB Error: " . $e->getMessage(), $userName);
    echo json_encode(['error' => 'A database error occurred.']);
    // Close the connection before exiting
    $conn = null;
    exit;
}

// Close the connection
$conn = null;


// --- Categorize Notifications ---
$categorized = [
    'Acquisition Requests' => [], 
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