<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_COOKIE['encrypted_user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated.']);
    exit;
}

$notification_type = $_POST['type'] ?? null;
$user_id = decrypt_id($_COOKIE['encrypted_user_id']);

if (empty($notification_type) || !$user_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = true WHERE user_id = ? AND type = ? AND is_read = false");
    $stmt->execute([$user_id, $notification_type]);
    
    echo json_encode(['status' => 'success', 'message' => 'Notifications marked as read.']);

} catch (PDOException $e) {
    error_log("Mark by type error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
} finally {
    $conn = null;
}
?>