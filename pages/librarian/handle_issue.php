<?php
// This script handles the direct issuing of a book from the form in issue_return.php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// --- 1. AUTHENTICATION & INPUT VALIDATION ---
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

// --- 2. DATABASE TRANSACTION ---
$conn->begin_transaction();
try {
    // A) Check book availability and lock rows
    $stmt_book = $conn->prepare("SELECT quantity_available FROM books WHERE book_id = ? FOR UPDATE");
    $stmt_book->bind_param("i", $book_id);
    $stmt_book->execute();
    $book = $stmt_book->get_result()->fetch_assoc();
    $stmt_book->close();

    if (!$book || $book['quantity_available'] < 1) {
        throw new Exception("Issuing failed: This book is currently not available.");
    }
    
    // B) Decrement book quantity
    $stmt_update_book = $conn->prepare("UPDATE books SET quantity_available = quantity_available - 1 WHERE book_id = ?");
    $stmt_update_book->bind_param("i", $book_id);
    $stmt_update_book->execute();
    $stmt_update_book->close();

    // C) Insert into borrowing records with an automatic due date (14 days from now)
    $due_date = date('Y-m-d', strtotime('+14 days'));
    $stmt_insert_br = $conn->prepare("INSERT INTO borrowing_records (book_id, borrower_id, borrower_role, checkout_date, due_date) VALUES (?, ?, ?, CURDATE(), ?)");
    $stmt_insert_br->bind_param("iiss", $book_id, $borrower_id, $borrower_role, $due_date);
    $stmt_insert_br->execute();
    $stmt_insert_br->close();

    // D) Commit the transaction
    $conn->commit();
    $_SESSION['success_message'] = "Book issued successfully! Due date is " . date('d-m-Y', strtotime($due_date)) . ".";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
}

// --- 3. REDIRECT BACK ---
header("Location: $redirect_url");
exit;
?>