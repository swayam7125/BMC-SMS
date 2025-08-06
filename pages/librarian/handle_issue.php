<?php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
if ($role !== 'librarian') {
    header("Location: ../../login.php");
    exit;
}

$redirect_url = 'issue_return.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../dashboard.php");
    exit;
}

$book_id = filter_var($_POST['book_id'], FILTER_VALIDATE_INT);
$borrower_id = filter_var($_POST['borrower_id'], FILTER_VALIDATE_INT);
$borrower_role = $_POST['borrower_role'];

if (!$book_id || !$borrower_id || !in_array($borrower_role, ['student', 'teacher'])) {
    $_SESSION['error_message'] = "Invalid data provided. Please fill out all fields.";
    header("Location: $redirect_url");
    exit;
}

try {
    // --- CORRECTED: Using PDO Transaction ---
    $conn->beginTransaction();

    $stmt_book = $conn->prepare('SELECT "quantity_available" FROM "books" WHERE "book_id" = ? FOR UPDATE');
    $stmt_book->execute([$book_id]);
    $book = $stmt_book->fetch(PDO::FETCH_ASSOC);

    if (!$book || $book['quantity_available'] < 1) {
        throw new Exception("Issuing failed: This book is currently not available.");
    }
    
    $stmt_update_book = $conn->prepare('UPDATE "books" SET "quantity_available" = "quantity_available" - 1 WHERE "book_id" = ?');
    $stmt_update_book->execute([$book_id]);

    $due_date = date('Y-m-d', strtotime('+14 days'));
    $stmt_insert_br = $conn->prepare('INSERT INTO "borrowing_records" (book_id, borrower_id, borrower_role, checkout_date, due_date) VALUES (?, ?, ?, CURRENT_DATE, ?)');
    $stmt_insert_br->execute([$book_id, $borrower_id, $borrower_role, $due_date]);

    $conn->commit();
    $_SESSION['success_message'] = "Book issued successfully! Due date is " . date('d-m-Y', strtotime($due_date)) . ".";

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
}

header("Location: $redirect_url");
exit;
?>
