<?php
require_once "../connect.php";
require_once "../../encryption.php";

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$user_id = null;
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$user_id) {
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    $notification_type = $_POST['type'];
    
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = true WHERE user_id = ? AND type = ? AND is_read = false");
        if ($stmt->execute([$user_id, $notification_type])) {
            $response['success'] = true;
            $response['message'] = 'Notifications marked as read';
        }
    } catch (PDOException $e) {
        error_log("Mark notifications read error: " . $e->getMessage());
        $response['message'] = 'Failed to mark notifications as read';
    }
}

echo json_encode($response);
?>
