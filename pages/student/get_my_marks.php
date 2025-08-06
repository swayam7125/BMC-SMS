<?php
header('Content-Type: application/json');
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$response = [
    'success' => false,
    'marks' => [],
    'message' => 'An error occurred.',
    'percentage' => 0,
    'total_obtained' => 0,
    'total_possible' => 0,
    'status' => 'N/A',
    'student_name' => 'Unknown Student'
];

$role = null;
$student_id = null;

if (isset($_COOKIE['encrypted_user_role'])) $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id'])) $student_id = decrypt_id($_COOKIE['encrypted_user_id']);

if ($role !== 'student' || !$student_id) {
    $response['message'] = 'Authentication Error. Please log in again.';
    echo json_encode($response);
    exit;
}

if (isset($_POST['exam_type']) && isset($_POST['academic_year'])) {
    $exam_type = $_POST['exam_type'];
    $academic_year = $_POST['academic_year'];

    try {
        // PDO Change: Converted to PDO
        $stmt_student = $conn->prepare("SELECT student_name FROM student WHERE id = ?");
        $stmt_student->execute([$student_id]);
        $student_data = $stmt_student->fetch(PDO::FETCH_ASSOC);
        if ($student_data) {
            $response['student_name'] = $student_data['student_name'];
        }

        $marks_query = "SELECT subject_name, marks_obtained, total_marks 
                        FROM student_marks 
                        WHERE student_id = ? 
                        AND exam_type = ? 
                        AND academic_year = ?
                        ORDER BY subject_name";

        $stmt_marks = $conn->prepare($marks_query);
        $stmt_marks->execute([$student_id, $exam_type, $academic_year]);

        $marks = [];
        $total_obtained = 0;
        $total_possible = 0;

        while ($mark_row = $stmt_marks->fetch(PDO::FETCH_ASSOC)) {
            $marks[$mark_row['subject_name']] = [
                'marks_obtained' => $mark_row['marks_obtained'],
                'total_marks' => $mark_row['total_marks']
            ];
            $total_obtained += $mark_row['marks_obtained'];
            $total_possible += $mark_row['total_marks'];
        }

        $percentage = 0;
        if ($total_possible > 0) {
            $percentage = ($total_obtained / $total_possible) * 100;
            $response['status'] = ($percentage >= 33) ? 'Pass' : 'Fail';
        }

        $response['success'] = true;
        $response['marks'] = $marks;
        $response['total_obtained'] = $total_obtained;
        $response['total_possible'] = $total_possible;
        $response['percentage'] = round($percentage, 2);
        $response['message'] = 'Marks loaded successfully.';
    } catch (PDOException $e) {
        $response['message'] = 'Database error. Could not retrieve marks.';
        error_log("Get Marks Error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Required parameters are missing.';
}

echo json_encode($response);
