<?php
// This script handles the book borrowing request from both students and teachers.

include_once '../connect.php';
include_once '../../encryption.php';

// --- 1. AUTHENTICATION AND DATA GATHERING ---

$role = null;
$user_id = null;
$school_id = null;

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

// --- 2. VALIDATE INPUT ---

if (!isset($_POST['book_id']) || !filter_var($_POST['book_id'], FILTER_VALIDATE_INT) || !isset($_POST['requested_due_date'])) {
    header("Location: " . $redirect_path . "?error=Invalid data submitted.");
    exit;
}
$book_id = $_POST['book_id'];
$requested_due_date = $_POST['requested_due_date'];

// Validate the date format and ensure it's not in the past
$today = date('Y-m-d');
if ($requested_due_date < $today) {
    header("Location: " . $redirect_path . "?error=The desired return date cannot be in the past.");
    exit;
}

// --- 3. FETCH USER & BOOK DATA ---

$table = ($role === 'student') ? 'student' : 'teacher';
$stmt_user = $conn->prepare("SELECT school_id FROM $table WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($user_data = $result_user->fetch_assoc()) {
    $school_id = $user_data['school_id'];
}
$stmt_user->close();

if (!$school_id) {
    header("Location: " . $redirect_path . "?error=Could not verify your school information.");
    exit;
}

// --- 4. PERFORM BUSINESS LOGIC CHECKS ---

$stmt_book = $conn->prepare("SELECT quantity_available FROM books WHERE book_id = ? AND school_id = ?");
$stmt_book->bind_param("ii", $book_id, $school_id);
$stmt_book->execute();
$result_book = $stmt_book->get_result();
$book_data = $result_book->fetch_assoc();
$stmt_book->close();

if (!$book_data || $book_data['quantity_available'] <= 0) {
    header("Location: " . $redirect_path . "?error=Sorry, this book is no longer available.");
    exit;
}

$stmt_check = $conn->prepare("SELECT request_id FROM borrow_requests WHERE book_id = ? AND borrower_id = ? AND status = 'Pending'");
$stmt_check->bind_param("ii", $book_id, $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows > 0) {
    header("Location: " . $redirect_path . "?error=You already have a pending request for this book.");
    exit;
}
$stmt_check->close();

// --- 5. EXECUTE DATABASE INSERTION ---

$stmt_insert = $conn->prepare("INSERT INTO borrow_requests (book_id, school_id, borrower_id, borrower_role, requested_due_date) VALUES (?, ?, ?, ?, ?)");
$stmt_insert->bind_param("iisss", $book_id, $school_id, $user_id, $role, $requested_due_date);

if ($stmt_insert->execute()) {
    header("Location: " . $redirect_path . "?success=Your request has been sent to the librarian!");
} else {
    header("Location: " . $redirect_path . "?error=An unexpected error occurred. Please try again.");
}

$stmt_insert->close();
$conn->close();
exit;
?>