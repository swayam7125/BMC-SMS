<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // ADDED: Log system dependency

// --- Authorization ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$librarian_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'Librarian'; // ADDED: Retrieve acting user name

if ($role !== 'librarian' || !$librarian_user_id) {
    header("Location: ../../login.php");
    exit;
}

$redirect_url = 'borrow_requests.php';

try {
    // === ACTION 1: REJECT a Request (via POST) ===
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {

        // --- Input Validation for Rejection ---
        $request_id = filter_var($_POST['request_id'], FILTER_VALIDATE_INT);
        $rejection_reason = trim($_POST['rejection_reason']);

        if (!$request_id || empty($rejection_reason)) {
            throw new Exception("Rejection reason is required.");
        }

        // Fetch request info for the notification message.
        $stmt_info = $conn->prepare('SELECT br.borrower_id, br.borrower_role, b.title FROM "borrow_requests" br JOIN "books" b ON br.book_id = b.book_id WHERE br.request_id = ?');
        $stmt_info->execute([$request_id]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
        $book_title = $info['title'] ?? 'Unknown Book'; // Capture title for log

        // Update the request status to 'Rejected'.
        $stmt_reject = $conn->prepare("UPDATE \"borrow_requests\" SET status = 'Rejected', librarian_id = ?, action_date = CURRENT_TIMESTAMP, rejection_reason = ? WHERE request_id = ? AND status = 'Pending'");
        $stmt_reject->execute([$librarian_user_id, $rejection_reason, $request_id]);

        if ($stmt_reject->rowCount() > 0) {
            // Create a notification for the user if the update was successful.
            if ($info) {
                $message = "Your request for '" . htmlspecialchars($info['title']) . "' was rejected. Reason: " . htmlspecialchars($rejection_reason);
                $link = ($info['borrower_role'] === 'student') ? 'pages/student/my_library_record.php' : 'pages/teacher/my_library_record.php';
                $stmt_notify = $conn->prepare('INSERT INTO "notifications" (user_id, message, link, type) VALUES (?, ?, ?, ?)');
                $stmt_notify->execute([$info['borrower_id'], $message, $link, "borrow_status"]);
                
                // ⭐ LOGGING: Log the rejection
                $log_message = "LIBRARY ACTION: Rejected borrow request (ID: {$request_id}) for book '{$book_title}'. Reason: {$rejection_reason}";
                log_interaction($role, $librarian_user_id, $log_message, $acting_user_name);
            }
            header("Location: $redirect_url?success=" . urlencode("Request successfully rejected."));
            exit;
        } else {
            throw new Exception("Failed to reject request. It might have been processed already.");
        }

        // === ACTION 2: APPROVE a Request (via GET) ===
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'approve') {

        // --- Input Validation for Approval ---
        $request_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$request_id) {
            throw new Exception("Invalid request ID provided.");
        }

        $conn->beginTransaction();

        // Step 1: Fetch the request and lock the row to prevent other actions.
        $stmt_req = $conn->prepare('SELECT * FROM "borrow_requests" WHERE "request_id" = ? FOR UPDATE');
        $stmt_req->execute([$request_id]);
        $request = $stmt_req->fetch(PDO::FETCH_ASSOC);

        if (!$request) throw new Exception("This request does not exist.");
        if ($request['status'] !== 'Pending') throw new Exception("This request has already been processed.");

        // Step 2: Check book availability and lock the book row.
        $stmt_book = $conn->prepare('SELECT "title", "quantity_available" FROM "books" WHERE "book_id" = ? FOR UPDATE');
        $stmt_book->execute([$request['book_id']]);
        $book = $stmt_book->fetch(PDO::FETCH_ASSOC);

        if (!$book || $book['quantity_available'] < 1) {
            throw new Exception("Approval failed: This book is no longer available.");
        }
        $book_title = $book['title']; // Capture title for log
        $borrower_id = $request['borrower_id'];
        $borrower_role = $request['borrower_role'];

        // Step 3: Update book quantity.
        $stmt_update_book = $conn->prepare('UPDATE "books" SET "quantity_available" = "quantity_available" - 1 WHERE "book_id" = ?');
        $stmt_update_book->execute([$request['book_id']]);

        // Step 4: Update the request status to 'Approved'.
        $due_date = $request['requested_due_date'];
        $stmt_update_req = $conn->prepare("UPDATE \"borrow_requests\" SET status = 'Approved', librarian_id = ?, action_date = CURRENT_TIMESTAMP, due_date = ? WHERE request_id = ?");
        $stmt_update_req->execute([$librarian_user_id, $due_date, $request_id]);

        // Step 5: Create a new record in the main borrowing_records table.
        $stmt_insert_br = $conn->prepare('INSERT INTO "borrowing_records" (book_id, borrower_id, borrower_role, checkout_date, due_date) VALUES (?, ?, ?, CURRENT_DATE, ?)');
        $stmt_insert_br->execute([$request['book_id'], $request['borrower_id'], $request['borrower_role'], $due_date]);

        // Step 6: Create a notification for the user.
        $message = "Your request for '" . htmlspecialchars($book['title']) . "' has been approved. Please collect it from the library.";
        $link = ($request['borrower_role'] === 'student') ? 'pages/student/my_library_record.php' : 'pages/teacher/my_library_record.php';
        $stmt_notify = $conn->prepare('INSERT INTO "notifications" (user_id, message, link, type) VALUES (?, ?, ?, ?)');
        $stmt_notify->execute([$request['borrower_id'], $message, $link, "borrow_status"]);

        $conn->commit();
        
        // ⭐ LOGGING: Log the approval
        $log_message = "LIBRARY ACTION: Approved borrow request (ID: {$request_id}) for book '{$book_title}'. Issued to {$borrower_role} ID: {$borrower_id}.";
        log_interaction($role, $librarian_user_id, $log_message, $acting_user_name);
        
        header("Location: $redirect_url?success=" . urlencode("Request approved and book issued successfully."));
        exit;
    }
} catch (Exception $e) {
    // If any error occurs in either action, roll back the transaction if one was started.
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Borrow request handling failed: " . $e->getMessage());
    header("Location: $redirect_url?error=" . urlencode($e->getMessage()));
    exit;
}

// Default redirect if no action is matched.
header("Location: $redirect_url");
exit;