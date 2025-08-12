<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

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

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
        $request_id = filter_var($_POST['request_id'], FILTER_VALIDATE_INT);
        $rejection_reason = trim($_POST['rejection_reason']);

        if (!$request_id || empty($rejection_reason)) {
            $error_message = "Invalid data. Rejection reason is required.";
            header("Location: $redirect_url?error=" . urlencode($error_message));
            exit;
        }

        $stmt_info = $conn->prepare('SELECT br.borrower_id, br.borrower_role, b.title FROM "borrow_requests" br JOIN "books" b ON br.book_id = b.book_id WHERE br.request_id = ?');
        $stmt_info->execute([$request_id]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        $stmt_reject = $conn->prepare("UPDATE \"borrow_requests\" SET status = 'Rejected', librarian_id = ?, action_date = CURRENT_TIMESTAMP, rejection_reason = ? WHERE request_id = ? AND status = 'Pending'");
        if ($stmt_reject->execute([$librarian_user_id, $rejection_reason, $request_id]) && $stmt_reject->rowCount() > 0) {
            if ($info) {
                $message = "Your request for '" . htmlspecialchars($info['title']) . "' was rejected. Reason: " . htmlspecialchars($rejection_reason);
                $link = ($info['borrower_role'] === 'student') ? 'pages/student/my_library_record.php' : 'pages/teacher/my_library_record.php';
                $type = "borrow_status";
                $stmt_notify = $conn->prepare('INSERT INTO "notifications" (user_id, message, link, type) VALUES (?, ?, ?, ?)');
                $stmt_notify->execute([$info['borrower_id'], $message, $link, $type]);
            }
            $success_message = "Request has been successfully rejected.";
            header("Location: $redirect_url?success=" . urlencode($success_message));
            exit;
        } else {
            $error_message = "Failed to reject request. It might have been already processed.";
            header("Location: $redirect_url?error=" . urlencode($error_message));
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'approve') {
        $request_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$request_id) {
            $error_message = "Invalid request ID.";
            header("Location: $redirect_url?error=" . urlencode($error_message));
            exit;
        }

        $conn->beginTransaction();
        
        $stmt_req = $conn->prepare('SELECT * FROM "borrow_requests" WHERE "request_id" = ? FOR UPDATE');
        $stmt_req->execute([$request_id]);
        $request = $stmt_req->fetch(PDO::FETCH_ASSOC);

        if (!$request) throw new Exception("This request does not exist.");
        if ($request['status'] !== 'Pending') throw new Exception("This request has already been processed.");

        $stmt_book = $conn->prepare('SELECT "title", "quantity_available" FROM "books" WHERE "book_id" = ? FOR UPDATE');
        $stmt_book->execute([$request['book_id']]);
        $book = $stmt_book->fetch(PDO::FETCH_ASSOC);

        if (!$book || $book['quantity_available'] < 1) throw new Exception("Approval failed: This book is no longer available.");

        $stmt_update_book = $conn->prepare('UPDATE "books" SET "quantity_available" = "quantity_available" - 1 WHERE "book_id" = ?');
        $stmt_update_book->execute([$request['book_id']]);

        // === FIX: Use the 'requested_due_date' from the original request ===
        $due_date = $request['requested_due_date'];
        
        $stmt_update_req = $conn->prepare("UPDATE \"borrow_requests\" SET status = 'Approved', librarian_id = ?, action_date = CURRENT_TIMESTAMP, due_date = ? WHERE request_id = ?");
        $stmt_update_req->execute([$librarian_user_id, $due_date, $request_id]);

        $stmt_insert_br = $conn->prepare('INSERT INTO "borrowing_records" (book_id, borrower_id, borrower_role, checkout_date, due_date) VALUES (?, ?, ?, CURRENT_DATE, ?)');
        $stmt_insert_br->execute([$request['book_id'], $request['borrower_id'], $request['borrower_role'], $due_date]);
        
        $message = "Your request for '" . htmlspecialchars($book['title']) . "' has been approved. Please collect it from the library.";
        $link = ($request['borrower_role'] === 'student') ? 'pages/student/my_library_record.php' : '/pages/teacher/my_library_record.php';
        $type = "borrow_status";
        $stmt_notify = $conn->prepare('INSERT INTO "notifications" (user_id, message, link, type) VALUES (?, ?, ?, ?)');
        $stmt_notify->execute([$request['borrower_id'], $message, $link, $type]);

        $conn->commit();
        $success_message = "Request approved successfully. The book has been issued.";
        header("Location: $redirect_url?success=" . urlencode($success_message));
        exit;
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $error_message = $e->getMessage();
    header("Location: $redirect_url?error=" . urlencode($error_message));
    exit;
}

header("Location: $redirect_url");
exit;
?>