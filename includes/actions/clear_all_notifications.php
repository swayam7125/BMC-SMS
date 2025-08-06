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
    if ($current_user_id && isset($conn)) {
        try {
            // --- CORRECTED: Use a PDO prepared statement ---
            $stmt = $conn->prepare('UPDATE "notifications" SET "is_read" = true WHERE "user_id" = ? AND "is_read" = false');
            
            if ($stmt->execute([$current_user_id])) {
                $response = ['status' => 'success', 'message' => 'Notifications cleared successfully.'];
            } else {
                $response['message'] = 'Database query failed to execute.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid user session or database connection error.';
    }
}

// Send the final JSON response
echo json_encode($response);

// Close the PDO connection
$conn = null;
?>
