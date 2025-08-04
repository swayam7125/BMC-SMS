<?php
// This script handles the returning of a book by the librarian.
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// --- 1. AUTHENTICATION ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$librarian_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'librarian' || !$librarian_user_id) {
    header("Location: ../../login.php");
    exit;
}

$redirect_url = 'issue_return.php';

// --- 2. INPUT VALIDATION ---
if (!isset($_GET['record_id']) || !filter_var($_GET['record_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "Invalid record ID specified.";
    header("Location: $redirect_url");
    exit;
}
$record_id = $_GET['record_id'];

// --- 3. DATABASE TRANSACTION ---
$conn->begin_transaction();
try {
    // A) Fetch the borrowing record and lock it for update
    $stmt_record = $conn->prepare("SELECT * FROM borrowing_records WHERE record_id = ? AND is_returned = 0 FOR UPDATE");
    $stmt_record->bind_param("i", $record_id);
    $stmt_record->execute();
    $record = $stmt_record->get_result()->fetch_assoc();
    $stmt_record->close();

    if (!$record) {
        throw new Exception("Record not found or this book has already been returned.");
    }
    $book_id = $record['book_id'];

    // B) Update the borrowing record to mark it as returned
    $stmt_update_record = $conn->prepare("UPDATE borrowing_records SET is_returned = 1, return_date = CURDATE() WHERE record_id = ?");
    $stmt_update_record->bind_param("i", $record_id);
    $stmt_update_record->execute();
    $stmt_update_record->close();

    // C) Increment the book's available quantity
    $stmt_update_book = $conn->prepare("UPDATE books SET quantity_available = quantity_available + 1 WHERE book_id = ?");
    $stmt_update_book->bind_param("i", $book_id);
    $stmt_update_book->execute();
    $stmt_update_book->close();

    // D) Commit the transaction
    $conn->commit();
    $_SESSION['success_message'] = "Book has been successfully marked as returned.";

} catch (Exception $e) {
    // If anything fails, roll back the entire transaction
    $conn->rollback();
    $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
}

// --- 4. REDIRECT BACK ---
header("Location: $redirect_url");
exit;
?>