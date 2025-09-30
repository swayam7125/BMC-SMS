<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // Log system included

session_start();

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';


if ($role !== 'librarian') {
    // Redirect to login if not a librarian
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = $_POST['book_id'] ?? null;
    $borrower_id = $_POST['borrower_id'] ?? null;
    $borrower_type = $_POST['borrower_type'] ?? null;
    $due_date = $_POST['due_date'] ?? null;

    if (!$book_id || !$borrower_id || !$borrower_type || !$due_date) {
        $_SESSION['error'] = "Missing required information.";
        header("Location: issue_return.php");
        exit;
    }

    try {
        $conn->beginTransaction();

        // 1. Check if the book is available
        $stmt_check = $conn->prepare("SELECT available_copies FROM books WHERE id = ? AND available_copies > 0 FOR UPDATE");
        $stmt_check->execute([$book_id]);
        $book = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            throw new Exception("Book not available or does not exist.");
        }

        // 2. Insert into borrowing_records
        $stmt_insert = $conn->prepare(
            "INSERT INTO borrowing_records (book_id, user_id, user_role, due_date, status) VALUES (?, ?, ?, ?, 'issued')"
        );
        $stmt_insert->execute([$book_id, $borrower_id, $borrower_type, $due_date]);

        // 3. Decrement available_copies in books table
        $stmt_update = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
        $stmt_update->execute([$book_id]);

        $conn->commit();
        $_SESSION['success'] = "Book issued successfully!";

        // Log the successful action
        log_interaction($role, $userId, "BOOK ISSUE: Issued Book ID {$book_id} to {$borrower_type} ID {$borrower_id}. Due date: {$due_date}", $userName);


    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
        // Log the error
        log_interaction($role, $userId, "BOOK ISSUE ERROR: Failed to issue Book ID {$book_id}. Error: " . $e->getMessage(), $userName);
    }

    header("Location: issue_return.php");
    exit;
} else {
    // Redirect if accessed directly without POST
    header("Location: issue_return.php");
    exit;
}
?>