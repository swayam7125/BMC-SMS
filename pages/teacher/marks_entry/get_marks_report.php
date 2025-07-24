<?php
header('Content-Type: application/json');
include_once "../../../includes/connect.php";

$response = ['success' => false, 'students' => [], 'subjects' => [], 'message' => 'An error occurred.'];

if (isset($_POST['class_std']) && isset($_POST['exam_type']) && isset($_POST['academic_year'])) {
    $class_std = $_POST['class_std'];
    $exam_type = $_POST['exam_type'];
    $academic_year = $_POST['academic_year'];

    try {
        // --- FIX: Fetch subjects for the specific standard first ---
        $subjects_query = "SELECT s.subject_name 
                           FROM standard_subjects ss
                           JOIN subjects s ON ss.subject_id = s.subject_id
                           WHERE ss.standard = ?
                           ORDER BY s.subject_name";
        $stmt_subjects = mysqli_prepare($conn, $subjects_query);
        mysqli_stmt_bind_param($stmt_subjects, "s", $class_std);
        mysqli_stmt_execute($stmt_subjects);
        $subjects_result = mysqli_stmt_get_result($stmt_subjects);
        
        $subjects = [];
        while ($row = mysqli_fetch_assoc($subjects_result)) {
            $subjects[] = $row['subject_name'];
        }
        mysqli_stmt_close($stmt_subjects);

        if (empty($subjects)) {
            $response['message'] = "No subjects have been assigned to this standard. Please contact the administrator.";
            echo json_encode($response);
            exit;
        }
        $response['subjects'] = $subjects;

        // First, get all students for the class to ensure everyone is listed
        $student_query = "SELECT id, student_name, rollno FROM student WHERE std = ? ORDER BY rollno";
        $stmt_students = mysqli_prepare($conn, $student_query);
        mysqli_stmt_bind_param($stmt_students, "s", $class_std);
        mysqli_stmt_execute($stmt_students);
        $students_result = mysqli_stmt_get_result($stmt_students);
        
        $students = [];
        while ($student_row = mysqli_fetch_assoc($students_result)) {
            $students[$student_row['id']] = [
                'id' => $student_row['id'],
                'student_name' => $student_row['student_name'],
                'rollno' => $student_row['rollno'],
                'marks' => [] // Initialize marks array
            ];
        }
        mysqli_stmt_close($stmt_students);

        if (!empty($students)) {
            $student_ids = array_keys($students);
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
            
            // Now, fetch the marks for these students based on the criteria
            $marks_query = "SELECT student_id, subject_name, marks_obtained FROM student_marks WHERE exam_type = ? AND academic_year = ? AND student_id IN ($placeholders)";
            $stmt_marks = mysqli_prepare($conn, $marks_query);
            
            $types = 'ss' . str_repeat('i', count($student_ids));
            $params = array_merge([$exam_type, $academic_year], $student_ids);
            mysqli_stmt_bind_param($stmt_marks, $types, ...$params);
            
            mysqli_stmt_execute($stmt_marks);
            $marks_result = mysqli_stmt_get_result($stmt_marks);

            while ($mark_row = mysqli_fetch_assoc($marks_result)) {
                if (isset($students[$mark_row['student_id']])) {
                    $students[$mark_row['student_id']]['marks'][$mark_row['subject_name']] = $mark_row['marks_obtained'];
                }
            }
            mysqli_stmt_close($stmt_marks);
        }

        $response['success'] = true;
        $response['students'] = array_values($students);
        $response['message'] = 'Marks report loaded successfully.';

    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Required parameters are missing.';
}

echo json_encode($response);
?>
