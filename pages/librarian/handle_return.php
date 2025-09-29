<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// --- Authorization ---
// Ensure that only a logged-in librarian can access this functionality.
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
if ($role !== 'librarian') {
    header("Location: issue_return.php?error=" . urlencode("Unauthorized action."));
    exit;
}

// --- Input Validation ---
// Validate that the record_id from the URL is a positive integer.
if (!isset($_GET['record_id']) || !filter_var($_GET['record_id'], FILTER_VALIDATE_INT) || $_GET['record_id'] <= 0) {
    header("Location: issue_return.php?error=" . urlencode("Invalid record ID provided."));
    exit;
}
$record_id = (int)$_GET['record_id'];

try {
    // --- Database Transaction ---
    // A transaction ensures both updates (return status and book quantity) succeed or fail together.
    $conn->beginTransaction();

    // Step 1: Find the book_id from the borrowing record and ensure it hasn't been returned yet.
    $stmt_get_book = $conn->prepare("SELECT book_id FROM borrowing_records WHERE record_id = ? AND is_returned = false");
    $stmt_get_book->execute([$record_id]);
    $record = $stmt_get_book->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new Exception("Record not found or this book has already been marked as returned.");
    }
    $book_id = $record['book_id'];

    // Step 2: Update the borrowing record to mark the book as returned.
    $stmt_return = $conn->prepare("UPDATE borrowing_records SET is_returned = true, return_date = CURRENT_DATE WHERE record_id = ?");
    $stmt_return->execute([$record_id]);

    // Step 3: Increment the available quantity of the book.
    $stmt_update_qty = $conn->prepare("UPDATE books SET quantity_available = quantity_available + 1 WHERE book_id = ?");
    $stmt_update_qty->execute([$book_id]);

    // If all steps were successful, commit the changes to the database.
    $conn->commit();

    // Redirect with a success message.
    header("Location: issue_return.php?success=" . urlencode("Book marked as returned successfully."));
    exit;
} catch (Exception $e) {
    // If any step failed, roll back the entire transaction.
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Log the detailed error for administrators and redirect the user with a generic message.
    error_log("Book return failed: " . $e->getMessage());
    header("Location: issue_return.php?error=" . urlencode("Failed to process the return. Please try again."));
    exit;
}
