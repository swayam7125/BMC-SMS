<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // ADDED: Log system dependency

// Check if the user is a logged-in librarian
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'Librarian'; // ADDED: Acting user name

if ($role !== 'librarian' || !$user_id) {
    header("Location: ../../login.php");
    exit;
}

// Get the action ('approve' or 'reject') and the request ID from the URL
$action = $_GET['action'] ?? null;
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate the input and redirect if invalid
if (!in_array($action, ['approve', 'reject']) || $request_id <= 0) {
    header("Location: book_requests.php");
    exit;
}

try {
    // Start a transaction to ensure all database operations succeed or fail together
    $conn->beginTransaction();

    // Fetch request details for the notification and logging
    $stmt_fetch = $conn->prepare("SELECT requester_id, book_title FROM book_requests WHERE request_id = ? AND status = 'Pending'");
    $stmt_fetch->execute([$request_id]);
    $request = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if ($request) {
        $requester_id = $request['requester_id'];
        $book_title = $request['book_title'];
        $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';

        // Update the status of the book request in the database
        $stmt_update = $conn->prepare("UPDATE book_requests SET status = ? WHERE request_id = ?");
        $stmt_update->execute([$new_status, $request_id]);

        // Create a notification for the user who made the request
        $notification_msg = "Your book request for \"" . htmlspecialchars($book_title) . "\" has been " . strtolower($new_status) . ".";
        $notification_link = 'pages/user/my_book_requests.php';
        $notification_type = 'acquisition_status';

        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
        $stmt_notify->execute([$requester_id, $notification_msg, $notification_link, $notification_type]);
        
        // ⭐ LOGGING: Log the critical acquisition action
        $log_message = "ASSET ACTION: Acquisition request for '{$book_title}' (Request ID: {$request_id}) was {$new_status} by Librarian.";
        log_interaction($role, $user_id, $log_message, $acting_user_name);
    }

    // If all queries were successful, commit the changes
    $conn->commit();

} catch (Exception $e) {
    // If any error occurred, roll back all changes
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Log the error for the administrator to review
    error_log("Handle Acquisition Request Error: " . $e->getMessage());
}

// Redirect the librarian back to the book requests page
header("Location: book_requests.php");
exit;
