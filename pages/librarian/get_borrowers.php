<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

header('Content-Type: application/json');

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
$user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'librarian' || !$user_id) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

try {
    // --- CORRECTED: Using PDO ---
    $stmt_school = $conn->prepare('SELECT "school_id" FROM "librarian" WHERE "id" = ?');
    $stmt_school->execute([$user_id]);
    $school_id = $stmt_school->fetchColumn();

    if (!$school_id) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not determine school.']);
        exit;
    }

    $borrower_role = $_GET['role'] ?? '';
    $borrowers = [];

    if ($borrower_role === 'student') {
        $query = 'SELECT "id", "student_name" as "name", "email" FROM "student" WHERE "school_id" = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$school_id]);
        $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($borrower_role === 'teacher') {
        $query = 'SELECT "id", "teacher_name" as "name", "email" FROM "teacher" WHERE "school_id" = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$school_id]);
        $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($borrowers);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn = null;
?>
