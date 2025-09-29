<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Set the content type to JSON for API responses
header('Content-Type: application/json');

// --- Authorization Check ---
// Decrypt user role and ID from cookies.
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
$user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

// Ensure the user is a logged-in librarian.
if ($role !== 'librarian' || !$user_id) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'You do not have permission to access this resource.']);
    exit;
}

try {
    // --- Get Librarian's School ID ---
    // A librarian can only see borrowers from their own school.
    $stmt_school = $conn->prepare('SELECT "school_id" FROM "librarian" WHERE "id" = ?');
    $stmt_school->execute([$user_id]);
    $school_id = $stmt_school->fetchColumn();

    if (!$school_id) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Could not find the school associated with this librarian.']);
        exit;
    }

    // --- Fetch Borrowers Based on Role ---
    $borrower_role = $_GET['role'] ?? '';
    $borrowers = [];

    // Use a switch statement for better organization if more roles are added in the future.
    switch ($borrower_role) {
        case 'student':
            $query = 'SELECT "id", "student_name" as "name", "email" FROM "student" WHERE "school_id" = ? ORDER BY "name"';
            $stmt = $conn->prepare($query);
            $stmt->execute([$school_id]);
            $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'teacher':
            $query = 'SELECT "id", "teacher_name" as "name", "email" FROM "teacher" WHERE "school_id" = ? ORDER BY "name"';
            $stmt = $conn->prepare($query);
            $stmt->execute([$school_id]);
            $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }

    // Return the borrowers as a JSON array.
    echo json_encode($borrowers);
} catch (PDOException $e) {
    // Generic error for database issues, logged for administrator.
    error_log("Database error in get_borrowers.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'A database error occurred. Please try again later.']);
}

// Close the database connection.
$conn = null;
