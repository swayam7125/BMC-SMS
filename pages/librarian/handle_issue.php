<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// --- Authorization & Security ---
// Ensure the user is a librarian and the request is a POST request from a form.
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
if ($role !== 'librarian' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../dashboard.php");
    exit;
}

// --- Input Validation ---
$book_id = isset($_POST['book_id']) ? filter_var($_POST['book_id'], FILTER_VALIDATE_INT) : false;
$borrower_id = isset($_POST['borrower_id']) ? filter_var($_POST['borrower_id'], FILTER_VALIDATE_INT) : false;
$borrower_role = $_POST['borrower_role'] ?? '';
$redirect_url = 'issue_return.php';

if (!$book_id || !$borrower_id || !in_array($borrower_role, ['student', 'teacher'])) {
    header("Location: $redirect_url?error=" . urlencode("Invalid data provided. Please fill out all fields correctly."));
    exit;
}

try {
    // --- Database Transaction ---
    $conn->beginTransaction();

    // Step 1: Check book availability and lock the row to prevent race conditions.
    // 'FOR UPDATE' ensures that no two librarians can issue the last copy of a book simultaneously.
    $stmt_book = $conn->prepare('SELECT "quantity_available" FROM "books" WHERE "book_id" = ? FOR UPDATE');
    $stmt_book->execute([$book_id]);
    $book = $stmt_book->fetch(PDO::FETCH_ASSOC);

    if (!$book || $book['quantity_available'] < 1) {
        throw new Exception("This book is currently not available for issue.");
    }

    // Step 2: Decrement the book's available quantity.
    $stmt_update_book = $conn->prepare('UPDATE "books" SET "quantity_available" = "quantity_available" - 1 WHERE "book_id" = ?');
    $stmt_update_book->execute([$book_id]);

    // Step 3: Create a new borrowing record.
    $due_date = date('Y-m-d', strtotime('+14 days'));
    $stmt_insert_br = $conn->prepare('INSERT INTO "borrowing_records" (book_id, borrower_id, borrower_role, checkout_date, due_date) VALUES (?, ?, ?, CURRENT_DATE, ?)');
    $stmt_insert_br->execute([$book_id, $borrower_id, $borrower_role, $due_date]);

    // If all steps were successful, commit the transaction.
    $conn->commit();
    $success_message = "Book issued successfully! Due date is " . date('d-m-Y', strtotime($due_date)) . ".";
    header("Location: $redirect_url?success=" . urlencode($success_message));
    exit;
} catch (Exception $e) {
    // If any step failed, roll back all changes.
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Book issue failed: " . $e->getMessage());
    header("Location: $redirect_url?error=" . urlencode("An error occurred during issuing: " . $e->getMessage()));
    exit;
}
