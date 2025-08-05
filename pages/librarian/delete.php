<?php
session_start();
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// 1. Authenticate and authorize user (must be a librarian)
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$role || $role !== 'librarian') {
    header("Location: ../../login.php");
    exit;
}

// 2. Validate book ID from GET parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: book_list.php?error=Invalid book ID provided.");
    exit;
}
$book_id = intval($_GET['id']);

// 3. Begin a transaction for data safety
mysqli_begin_transaction($conn);

try {
    // 4. Fetch the book data that needs to be archived
    $query_fetch_book = "SELECT * FROM books WHERE book_id = ?";
    $stmt_fetch = mysqli_prepare($conn, $query_fetch_book);
    mysqli_stmt_bind_param($stmt_fetch, "i", $book_id);
    mysqli_stmt_execute($stmt_fetch);
    $result_book = mysqli_stmt_get_result($stmt_fetch);
    $book_data = mysqli_fetch_assoc($result_book);
    mysqli_stmt_close($stmt_fetch);

    if (!$book_data) {
        throw new Exception("Book with ID $book_id not found.");
    }

    // 5. Archive the book data into the `deleted_books` table
    $query_archive_book = "INSERT INTO deleted_books 
                                (original_book_id, title, author, isbn, quantity_total, school_id, is_digital, deleted_by_role) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_archive = mysqli_prepare($conn, $query_archive_book);
    mysqli_stmt_bind_param(
        $stmt_archive,
        "isssiiss",
        $book_data['book_id'],
        $book_data['title'],
        $book_data['author'],
        $book_data['isbn'],
        $book_data['quantity_total'],
        $book_data['school_id'],
        $book_data['is_digital'],
        $role
    );

    if (!mysqli_stmt_execute($stmt_archive)) {
        throw new Exception("Failed to archive the book: " . mysqli_stmt_error($stmt_archive));
    }
    mysqli_stmt_close($stmt_archive);

    // 6. Finally, delete the book from the active `books` table
    $query_delete_book = "DELETE FROM books WHERE book_id = ?";
    $stmt_delete = mysqli_prepare($conn, $query_delete_book);
    mysqli_stmt_bind_param($stmt_delete, "i", $book_id);

    if (!mysqli_stmt_execute($stmt_delete)) {
        throw new Exception("Failed to delete the book record: " . mysqli_stmt_error($stmt_delete));
    }

    if (mysqli_stmt_affected_rows($stmt_delete) === 0) {
        throw new Exception("Book could not be deleted (it may have already been removed).");
    }
    mysqli_stmt_close($stmt_delete);

    // 7. If all went well, commit the changes
    mysqli_commit($conn);
    header("Location: book_list.php?success=Book was successfully deleted and archived.");
    exit;

} catch (Exception $e) {
    // 8. If any step failed, roll back the transaction
    mysqli_rollback($conn);
    header("Location: book_list.php?error=" . urlencode($e->getMessage()));
    exit;
    
} finally {
    mysqli_close($conn);
}
?>