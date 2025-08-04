<?php
// This script handles the librarian's approval or rejection of a book borrowing request.

include_once '../../includes/connect.php';
include_once '../../encryption.php';

session_start(); // Using session for flash messages (success/error feedback)

// --- 1. AUTHENTICATION AND INITIALIZATION ---

$role = null;
$librarian_user_id = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $librarian_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'librarian' || !$librarian_user_id) {
    header("Location: ../../login.php");
    exit;
}

$redirect_url = 'borrow_requests.php';

// --- 2. HANDLE REJECTION (POST REQUEST FROM MODAL) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'reject') {
        $request_id = filter_var($_POST['request_id'], FILTER_VALIDATE_INT);
        $rejection_reason = trim($_POST['rejection_reason']);

        if (!$request_id || empty($rejection_reason)) {
            $_SESSION['error_message'] = "Invalid data. Rejection reason is required.";
            header("Location: $redirect_url");
            exit;
        }

        // Update the borrow request status to Rejected with a reason
        $stmt_reject = $conn->prepare("UPDATE borrow_requests SET status = 'Rejected', librarian_id = ?, action_date = NOW(), rejection_reason = ? WHERE request_id = ? AND status = 'Pending'");
        $stmt_reject->bind_param("isi", $librarian_user_id, $rejection_reason, $request_id);
        
        if ($stmt_reject->execute() && $stmt_reject->affected_rows > 0) {
            $_SESSION['success_message'] = "Request has been successfully rejected.";
        } else {
            $_SESSION['error_message'] = "Failed to reject request. It might have been already processed.";
        }
        $stmt_reject->close();
        header("Location: $redirect_url");
        exit;
    }
}


// --- 3. HANDLE APPROVAL (GET REQUEST FROM LINK) ---

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['id']) || !isset($_GET['action']) || $_GET['action'] !== 'approve') {
        $_SESSION['error_message'] = "Invalid action specified.";
        header("Location: $redirect_url");
        exit;
    }

    $request_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$request_id) {
        $_SESSION['error_message'] = "Invalid request ID.";
        header("Location: $redirect_url");
        exit;
    }

    $conn->begin_transaction();
    try {
        // Lock the row to prevent race conditions
        $stmt_req = $conn->prepare("SELECT * FROM borrow_requests WHERE request_id = ? FOR UPDATE");
        $stmt_req->bind_param("i", $request_id);
        $stmt_req->execute();
        $request = $stmt_req->get_result()->fetch_assoc();
        $stmt_req->close();

        if (!$request) throw new Exception("This request does not exist.");
        if ($request['status'] !== 'Pending') throw new Exception("This request has already been processed.");

        // Check book availability
        $stmt_book = $conn->prepare("SELECT quantity_available FROM books WHERE book_id = ? FOR UPDATE");
        $stmt_book->bind_param("i", $request['book_id']);
        $stmt_book->execute();
        $book = $stmt_book->get_result()->fetch_assoc();
        $stmt_book->close();

        if (!$book || $book['quantity_available'] < 1) throw new Exception("Approval failed: This book is no longer available.");

        // Decrement book quantity
        $stmt_update_book = $conn->prepare("UPDATE books SET quantity_available = quantity_available - 1 WHERE book_id = ?");
        $stmt_update_book->bind_param("i", $request['book_id']);
        $stmt_update_book->execute();
        $stmt_update_book->close();

        // Update borrow request status to 'Approved'
        $due_date = date('Y-m-d', strtotime('+14 days'));
        $stmt_update_req = $conn->prepare("UPDATE borrow_requests SET status = 'Approved', librarian_id = ?, action_date = NOW(), due_date = ? WHERE request_id = ?");
        $stmt_update_req->bind_param("isi", $librarian_user_id, $due_date, $request_id);
        $stmt_update_req->execute();
        $stmt_update_req->close();

        // Insert into main borrowing records
        $stmt_insert_br = $conn->prepare("INSERT INTO borrowing_records (book_id, borrower_id, borrower_role, checkout_date, due_date) VALUES (?, ?, ?, CURDATE(), ?)");
        $stmt_insert_br->bind_param("iiss", $request['book_id'], $request['borrower_id'], $request['borrower_role'], $due_date);
        $stmt_insert_br->execute();
        $stmt_insert_br->close();
        
        $conn->commit();
        $_SESSION['success_message'] = "Request approved successfully. The book has been issued.";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header("Location: $redirect_url");
    exit;
}

// Fallback redirect if accessed incorrectly
header("Location: $redirect_url");
exit;
?>