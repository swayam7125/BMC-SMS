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
    // --- NEW: Add status to the response ---
    'status' => 'N/A' 
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
        $student_query = "SELECT student_name FROM student WHERE id = ?";
        $stmt_student = mysqli_prepare($conn, $student_query);
        mysqli_stmt_bind_param($stmt_student, "i", $student_id);
        mysqli_stmt_execute($stmt_student);
        $student_result = mysqli_stmt_get_result($stmt_student);
        $student_data = mysqli_fetch_assoc($student_result);
        $response['student_name'] = $student_data ? $student_data['student_name'] : 'Unknown Student';
        mysqli_stmt_close($stmt_student);

        $marks_query = "SELECT subject_name, marks_obtained, total_marks 
                        FROM student_marks 
                        WHERE student_id = ? 
                        AND exam_type = ? 
                        AND academic_year = ?
                        ORDER BY subject_name";

        $stmt_marks = mysqli_prepare($conn, $marks_query);
        mysqli_stmt_bind_param($stmt_marks, "iss", $student_id, $exam_type, $academic_year);
        mysqli_stmt_execute($stmt_marks);
        $marks_result = mysqli_stmt_get_result($stmt_marks);

        $marks = [];
        $total_obtained = 0;
        $total_possible = 0;

        while ($mark_row = mysqli_fetch_assoc($marks_result)) {
            $marks[$mark_row['subject_name']] = [
                'marks_obtained' => $mark_row['marks_obtained'],
                'total_marks' => $mark_row['total_marks']
            ];
            $total_obtained += $mark_row['marks_obtained'];
            $total_possible += $mark_row['total_marks'];
        }
        mysqli_stmt_close($stmt_marks);

        $percentage = 0;
        if ($total_possible > 0) {
            $percentage = ($total_obtained / $total_possible) * 100;
            // --- NEW: Determine Pass/Fail status based on percentage ---
            $response['status'] = ($percentage >= 33) ? 'Pass' : 'Fail';
        }

        $response['success'] = true;
        $response['marks'] = $marks;
        $response['total_obtained'] = $total_obtained;
        $response['total_possible'] = $total_possible;
        $response['percentage'] = round($percentage, 2);
        $response['message'] = 'Marks loaded successfully.';

    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Required parameters are missing.';
}

echo json_encode($response);
?>