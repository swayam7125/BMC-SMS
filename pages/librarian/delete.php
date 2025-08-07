<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$role || $role !== 'librarian') {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: book_list.php?error=Invalid book ID provided.");
    exit;
}
$book_id = intval($_GET['id']);

try {
    // Using a PDO Transaction to ensure data integrity
    $conn->beginTransaction();

    // Step 1: Fetch the full record of the book to be deleted
    $stmt_fetch = $conn->prepare('SELECT * FROM "books" WHERE "book_id" = ?');
    $stmt_fetch->execute([$book_id]);
    $book_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$book_data) {
        throw new Exception("Book with ID $book_id not found.");
    }

    // === FIXES APPLIED HERE ===
    // 1. Explicitly cast IDs to integers to match the 'deleted_books' table schema.
    $original_book_id_int = (int)$book_data['book_id'];
    $school_id_int = (int)$book_data['school_id'];
    // 2. Explicitly convert the PHP boolean to a PostgreSQL-compatible string ('true'/'false').
    $is_digital_pg = !empty($book_data['is_digital']) ? 'true' : 'false';


    // Step 2: Archive the book's data into the 'deleted_books' table
    $query_archive_book = 'INSERT INTO "deleted_books" (original_book_id, title, author, isbn, quantity_total, school_id, is_digital, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt_archive = $conn->prepare($query_archive_book);
    $stmt_archive->execute([
        $original_book_id_int,
        $book_data['title'],
        $book_data['author'],
        $book_data['isbn'],
        $book_data['quantity_total'],
        $school_id_int,
        $is_digital_pg,
        $role
    ]);

    // Step 3: Delete the book from the main 'books' table
    $stmt_delete = $conn->prepare('DELETE FROM "books" WHERE "book_id" = ?');
    $stmt_delete->execute([$book_id]);

    if ($stmt_delete->rowCount() === 0) {
        throw new Exception("Book could not be deleted (it may have already been removed).");
    }

    // Step 4: If all steps succeeded, commit the changes
    $conn->commit();
    header("Location: book_list.php?success=Book was successfully deleted and archived.");
    exit;

} catch (Exception $e) {
    // If any step failed, roll back all changes
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header("Location: book_list.php?error=" . urlencode($e->getMessage()));
    exit;
} finally {
    $conn = null;
}