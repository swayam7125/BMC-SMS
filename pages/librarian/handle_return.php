<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Security: Check if the user is a logged-in librarian
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
if ($role !== 'librarian') {
    $error_message = "You are not authorized to perform this action.";
    header("Location: issue_return.php?error=" . urlencode($error_message));
    exit;
}

// Validate the record_id from the URL
if (!isset($_GET['record_id']) || !filter_var($_GET['record_id'], FILTER_VALIDATE_INT)) {
    $error_message = "Invalid record ID provided.";
    header("Location: issue_return.php?error=" . urlencode($error_message));
    exit;
}
$record_id = (int)$_GET['record_id'];

try {
    $conn->beginTransaction();

    $stmt_get_book = $conn->prepare("SELECT book_id FROM borrowing_records WHERE record_id = ? AND is_returned = false");
    $stmt_get_book->execute([$record_id]);
    $record = $stmt_get_book->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new Exception("Record not found or the book has already been returned.");
    }
    $book_id = $record['book_id'];

    $stmt_return = $conn->prepare("UPDATE borrowing_records SET is_returned = true, return_date = CURRENT_DATE WHERE record_id = ?");
    $stmt_return->execute([$record_id]);

    $stmt_update_qty = $conn->prepare("UPDATE books SET quantity_available = quantity_available + 1 WHERE book_id = ?");
    $stmt_update_qty->execute([$book_id]);

    $conn->commit();
    $success_message = "Book marked as returned successfully.";
    header("Location: issue_return.php?success=" . urlencode($success_message));
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $error_message = "Failed to return the book. Error: " . $e->getMessage();
    header("Location: issue_return.php?error=" . urlencode($error_message));
    exit;
}
?>