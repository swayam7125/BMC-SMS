<?php
session_start();
include_once '../connect.php';
include_once '../../encryption.php';
include_once '../log_system.php'; // Log system included

header('Content-Type: application/json');

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$book_id = $_POST['book_id'] ?? null;
$borrower_id = $_POST['borrower_id'] ?? null;
$borrower_role = $_POST['borrower_role'] ?? null;
$school_id = $_POST['school_id'] ?? null;
$requested_due_date = $_POST['requested_due_date'] ?? null;

if (empty($book_id) || empty($borrower_id) || empty($borrower_role) || empty($school_id) || empty($requested_due_date)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

try {
    // Check for existing pending requests by the same user for the same book
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM borrow_requests WHERE book_id = ? AND borrower_id = ? AND borrower_role = ? AND status = 'Pending'");
    $stmt_check->execute([$book_id, $borrower_id, $borrower_role]);
    if ($stmt_check->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'You already have a pending request for this book.']);
        exit;
    }

    // Insert the new borrow request
    $stmt_insert = $conn->prepare("INSERT INTO borrow_requests (book_id, school_id, borrower_id, borrower_role, requested_due_date) VALUES (?, ?, ?, ?::borrow_requester_role, ?)");
    $stmt_insert->execute([$book_id, $school_id, $borrower_id, $borrower_role, $requested_due_date]);

    // Notify the librarian(s)
    $stmt_librarians = $conn->prepare("SELECT id FROM librarian WHERE school_id = ?");
    $stmt_librarians->execute([$school_id]);
    $librarian_ids = $stmt_librarians->fetchAll(PDO::FETCH_COLUMN, 0);
    
    if (!empty($librarian_ids)) {
        $notification_msg = htmlspecialchars($userName) . " has requested to borrow a book.";
        $notification_link = "pages/librarian/borrow_requests.php";
        $notification_type = "borrow_request";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
        
        foreach ($librarian_ids as $librarian_id) {
            $stmt_notify->execute([$librarian_id, $notification_msg, $notification_link, $notification_type]);
        }
    }
    
    // Log the action
    log_interaction($role, $userId, "LIBRARY: Submitted a request to borrow book ID {$book_id}.", $userName);
    
    echo json_encode(['status' => 'success', 'message' => 'Your request to borrow the book has been sent successfully.']);

} catch (PDOException $e) {
    // Log the error
    log_interaction($role, $userId, "LIBRARY ERROR: Failed to request book ID {$book_id}. DB Error: " . $e->getMessage(), $userName);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>