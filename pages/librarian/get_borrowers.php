<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
$user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

// Only allow access for logged-in librarians
if ($role !== 'librarian' || !$user_id) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Get the librarian's school_id
$school_id = null;
$stmt_school = $conn->prepare("SELECT school_id FROM librarian WHERE id = ?");
if ($stmt_school) {
    $stmt_school->bind_param("i", $user_id);
    $stmt_school->execute();
    $result_school = $stmt_school->get_result();
    if ($librarian_data = $result_school->fetch_assoc()) {
        $school_id = $librarian_data['school_id'];
    }
    $stmt_school->close();
}

if (!$school_id) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not determine school.']);
    exit;
}

$borrower_role = $_GET['role'] ?? '';
$borrowers = [];

if ($borrower_role === 'student') {
    $query = "SELECT id, student_name as name, email FROM student WHERE school_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $school_id);
} elseif ($borrower_role === 'teacher') {
    $query = "SELECT id, teacher_name as name, email FROM teacher WHERE school_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $school_id);
} else {
    echo json_encode([]);
    exit;
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $borrowers[] = $row;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($borrowers);
?>