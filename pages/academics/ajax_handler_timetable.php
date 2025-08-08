<?php
header('Content-Type: application/json');
include_once "../../includes/connect.php";

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
        }
    } else {
        $response['message'] = 'Teacher ID not provided.';
    }
}

echo json_encode($response);
$conn = null;
?>