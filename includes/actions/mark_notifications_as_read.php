<?php
/**
 * API Endpoint to mark notifications as read.
 * This handles both single notifications (by ID) and bulk updates (by type).
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Corrected absolute paths for reliability
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';

$response = ['status' => 'error', 'message' => 'Invalid request.'];

// Check for user authentication
if (!isset($_COOKIE['encrypted_user_id'])) {
    $response['message'] = 'User not authenticated.';
    echo json_encode($response);
    exit;
}

$userId = decrypt_id($_COOKIE['encrypted_user_id']);

// Validate user ID and database connection
if (!$userId || !isset($conn)) {
    $response['message'] = 'Database connection or user ID failed.';
    echo json_encode($response);
    exit;
}

try {
    // Case 1: Mark a single notification by its ID
    if (isset($_POST['notif_id'])) {
        $notifId = filter_var($_POST['notif_id'], FILTER_VALIDATE_INT);
        if ($notifId) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = true WHERE id = ? AND user_id = ?");
            $stmt->execute([$notifId, $userId]);
            if ($stmt->rowCount() > 0) {
                $response = ['status' => 'success', 'message' => 'Notification marked as read.'];
            } else {
                $response['message'] = 'Notification not found or already read.';
            }
        }
    } 
    // Case 2: Mark all notifications of a specific type as read
    elseif (isset($_POST['type'])) {
        $type = htmlspecialchars($_POST['type'], ENT_QUOTES, 'UTF-8');
        if ($type) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = true WHERE type = ? AND user_id = ? AND is_read = false");
            $stmt->execute([$type, $userId]);
            $updated_count = $stmt->rowCount();
            $response = [
                'status' => 'success', 
                'message' => "$updated_count notifications of type '$type' marked as read.",
                'updated_count' => $updated_count
            ];
        }
    }

} catch (PDOException $e) {
    error_log("Mark as read error: " . $e->getMessage());
    $response['message'] = 'A database error occurred.';
}

echo json_encode($response);
?>