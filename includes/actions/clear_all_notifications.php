<?php
// Set the content type to JSON for the response
header('Content-Type: application/json');

// Essential includes for database and encryption functions
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';

// Default error response
$response = ['status' => 'error', 'message' => 'Authentication failed.'];

// Check if a user is logged in by verifying the cookie
if (isset($_COOKIE['encrypted_user_id'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);

    // Ensure the user ID is valid and the database connection is active
    if ($current_user_id && isset($conn) && $conn->ping()) {
        
        // Use a prepared statement to mark all unread notifications as read for the current user
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $current_user_id);
        
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Notifications cleared successfully.'];
        } else {
            $response['message'] = 'Database query failed to execute.';
        }
        
        $stmt->close();
        $conn->close();

    } else {
        $response['message'] = 'Invalid user session or database connection error.';
    }
}

// Send the final JSON response back to the JavaScript Fetch call
echo json_encode($response);
?>