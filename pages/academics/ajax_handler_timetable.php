<?php
header('Content-Type: application/json');
include_once "../../includes/connect.php";
include_once "../../includes/log_system.php";
include_once "../../encryption.php";

// Get user info for logging
$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');
$userName = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?: 'Unknown User';

$response = ['success' => false, 'subjects' => [], 'message' => 'Invalid Request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_teacher_subjects') {
    $teacher_id = $_POST['teacher_id'] ?? null;

    if ($teacher_id) {
        try {
            $stmt = $conn->prepare('SELECT "subject" FROM "teacher" WHERE "id" = ?');
            $stmt->execute([$teacher_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && !empty($result['subject'])) {
                // The 'subject' column is a comma-separated string, so we split it into an array
                $subjects_array = array_map('trim', explode(',', $result['subject']));
                $response['success'] = true;
                $response['subjects'] = $subjects_array;
                $response['message'] = 'Subjects fetched successfully.';
            } else {
                $response['message'] = 'No subjects found for this teacher.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database Error: ' . $e->getMessage();
            log_interaction($role, $userId, "TIMETABLE_FETCH_SUBJECTS_ERROR: Database error while fetching subjects - " . $e->getMessage(), $userName);
        }
    } else {
        $response['message'] = 'Teacher ID not provided.';
        log_interaction($role, $userId, "TIMETABLE_FETCH_SUBJECTS_ERROR: Missing teacher ID in request", $userName);
    }
}

echo json_encode($response);
$conn = null;
?>