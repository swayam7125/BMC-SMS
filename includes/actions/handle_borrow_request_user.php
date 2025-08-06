<?php
// /includes/actions/handle_borrow_request_user.php

include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = null;
$user_id = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (($role !== 'student' && $role !== 'teacher') || !$user_id) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../dashboard.php");
    exit;
}

$redirect_path = ($role === 'student') ? '/BMC-SMS/pages/student/browse_books.php' : '/BMC-SMS/pages/teacher/browse_books.php';

// Validate input
if (!isset($_POST['book_id']) || !filter_var($_POST['book_id'], FILTER_VALIDATE_INT) || !isset($_POST['requested_due_date'])) {
    header("Location: " . $redirect_path . "?error=Invalid data submitted.");
    exit;
}
$book_id = $_POST['book_id'];
$requested_due_date = $_POST['requested_due_date'];

$today = date('Y-m-d');
if ($requested_due_date < $today) {
    header("Location: " . $redirect_path . "?error=The desired return date cannot be in the past.");
    exit;
}

try {
    // --- CORRECTED: Using PDO to fetch user and book data ---
    $table = ($role === 'student') ? '"student"' : '"teacher"';
    $stmt_user = $conn->prepare("SELECT \"school_id\" FROM $table WHERE \"id\" = ?");
    $stmt_user->execute([$user_id]);
    $school_id = $stmt_user->fetchColumn();

    if (!$school_id) {
        header("Location: " . $redirect_path . "?error=Could not verify your school information.");
        exit;
    }

    $stmt_book = $conn->prepare('SELECT "quantity_available" FROM "books" WHERE "book_id" = ? AND "school_id" = ?');
    $stmt_book->execute([$book_id, $school_id]);
    $book_data = $stmt_book->fetch(PDO::FETCH_ASSOC);

    if (!$book_data || $book_data['quantity_available'] <= 0) {
        header("Location: " . $redirect_path . "?error=Sorry, this book is no longer available.");
        exit;
    }

    $stmt_check = $conn->prepare('SELECT "request_id" FROM "borrow_requests" WHERE "book_id" = ? AND "borrower_id" = ? AND "status" = \'Pending\'');
    $stmt_check->execute([$book_id, $user_id]);
    if ($stmt_check->fetch()) {
        header("Location: " . $redirect_path . "?error=You already have a pending request for this book.");
        exit;
    }

    $stmt_insert = $conn->prepare('INSERT INTO "borrow_requests" (book_id, school_id, borrower_id, borrower_role, requested_due_date) VALUES (?, ?, ?, ?, ?)');
    if ($stmt_insert->execute([$book_id, $school_id, $user_id, $role, $requested_due_date])) {
        
        // --- NOTIFICATION LOGIC ---
        $stmt_librarian = $conn->prepare('SELECT "id" FROM "librarian" WHERE "school_id" = ?');
        $stmt_librarian->execute([$school_id]);
        $librarian_id = $stmt_librarian->fetchColumn();

        if ($librarian_id) {
            $user_name = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'A user';
            $message = htmlspecialchars($user_name) . " has requested to borrow a book.";
            $link = "/pages/librarian/borrow_requests.php";
            $type = "borrow_request";

            $stmt_notify = $conn->prepare('INSERT INTO "notifications" (user_id, message, link, type) VALUES (?, ?, ?, ?)');
            $stmt_notify->execute([$librarian_id, $message, $link, $type]);
        }
        
        header("Location: " . $redirect_path . "?success=Your request has been sent to the librarian!");
    } else {
        header("Location: " . $redirect_path . "?error=An unexpected error occurred. Please try again.");
    }

} catch (PDOException $e) {
    header("Location: " . $redirect_path . "?error=Database error: " . $e->getMessage());
}

$conn = null;
exit;
?>
