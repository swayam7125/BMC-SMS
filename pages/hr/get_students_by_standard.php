<?php
require_once __DIR__ . "/../../includes/connect.php";
require_once __DIR__ . "/../../encryption.php";

// Authorization: Ensure the user is an HR staff member
if (!isset($_COOKIE['encrypted_user_id']) || !isset($_COOKIE['encrypted_user_role'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}
$hr_id = decrypt_id($_COOKIE['encrypted_user_id']);
$role = decrypt_id($_COOKIE['encrypted_user_role']);
if ($role !== 'hr') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

// Get the HR user's school ID
$stmt = $conn->prepare("SELECT school_id FROM hr WHERE id = :hr_id");
$stmt->bindParam(':hr_id', $hr_id);
$stmt->execute();
$school_id = $stmt->fetchColumn();

// Validate input
if (!$school_id || !isset($_GET['standard']) || !filter_var($_GET['standard'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request parameters.']);
    exit;
}

$standard = $_GET['standard'];

// Fetch students for the given standard and school
try {
    $stmt = $conn->prepare("SELECT id, student_name, rollno FROM student WHERE school_id = :school_id AND std = :standard ORDER BY student_name");
    $stmt->execute([':school_id' => $school_id, ':standard' => $standard]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($students);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.']);
}
?>