<?php
require_once '../../includes/connect.php';
require_once '../../includes/functions.php';
require_once '../../includes/ajax_helpers.php';
require_once '../../encryption.php';

// Authentication
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'librarian') {
    Response::error('Access denied', url('login.php'));
}

// Get book ID from either POST (AJAX) or GET (direct)
$book_id = 0;
if (is_ajax_request()) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        Response::error('Invalid request');
    }
    $book_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
} else {
    $book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

if ($book_id <= 0) {
    Response::error('Invalid book ID provided', url('pages/librarian/book_list.php'));
}

try {
    // Verify the book belongs to the librarian's school
    $stmt = $conn->prepare('
        SELECT b.* 
        FROM books b
        JOIN librarian l ON b.school_id = l.school_id
        WHERE b.book_id = ? AND l.id = ?
    ');
    $stmt->execute([$book_id, $user_id]);
    $book_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book_data) {
        Response::error('Book not found or access denied', url('pages/librarian/book_list.php'));
    }

    // Begin transaction
    $conn->beginTransaction();

    // Check if book can be deleted
    $stmt = $conn->prepare('
        SELECT COUNT(*) 
        FROM book_loans 
        WHERE book_id = ? AND return_date IS NULL
    ');
    $stmt->execute([$book_id]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Cannot delete: Book is currently loaned out');
    }

    // Archive the book data
    $stmt_archive = $conn->prepare('
        INSERT INTO "deleted_books" (
            original_book_id, title, author, isbn, 
            quantity_total, school_id, is_digital, 
            deleted_by_role
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt_archive->execute([
        (int)$book_data['book_id'],
        $book_data['title'],
        $book_data['author'],
        $book_data['isbn'],
        (int)$book_data['quantity_total'],
        (int)$book_data['school_id'],
        !empty($book_data['is_digital']) ? 'true' : 'false',
        $role
    ]);

    // Delete the book
    $stmt = $conn->prepare('DELETE FROM "books" WHERE "book_id" = ?');
    $stmt->execute([$book_id]);

    // Verify deletion
    if ($stmt->rowCount() === 0) {
        throw new Exception("Book could not be deleted (it may have already been removed).");
    }

    // Commit transaction
    $conn->commit();

    // Send response based on request type
    if (is_ajax_request()) {
        Response::success('Book deleted successfully', url('pages/librarian/book_list.php'));
    } else {
        header('Location: ' . url('pages/librarian/book_list.php') . '?success=Book deleted successfully');
        exit;
    }

} catch (Exception $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    if (is_ajax_request()) {
        Response::error($e->getMessage());
    } else {
        header('Location: ' . url('pages/librarian/book_list.php') . '?error=' . urlencode($e->getMessage()));
        exit;
    }
} finally {
    $conn = null;
}