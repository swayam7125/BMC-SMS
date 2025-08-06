<?php
header('Content-Type: application/json');
include_once "../../../includes/connect.php";

$response = ['success' => false, 'students' => [], 'subjects' => [], 'message' => 'An error occurred.'];

if (isset($_POST['class_std']) && isset($_POST['exam_type']) && isset($_POST['academic_year'])) {
    $class_std = $_POST['class_std'];
    $exam_type = $_POST['exam_type'];
    $academic_year = $_POST['academic_year'];

    try {
        // PDO Change: Converted all mysqli queries to PDO
        $subjects_query = "SELECT s.subject_name 
                           FROM standard_subjects ss
                           JOIN subjects s ON ss.subject_id = s.subject_id
                           WHERE ss.standard = ?
                           ORDER BY s.subject_name";
        $stmt_subjects = $conn->prepare($subjects_query);
        $stmt_subjects->execute([$class_std]);
        $subjects = $stmt_subjects->fetchAll(PDO::FETCH_COLUMN, 0);

        if (empty($subjects)) {
            $response['message'] = "No subjects have been assigned to this standard. Please contact the administrator.";
            echo json_encode($response);
            exit;
        }
        $response['subjects'] = $subjects;

        $student_query = "SELECT id, student_name, rollno FROM student WHERE std = ? ORDER BY rollno";
        $stmt_students = $conn->prepare($student_query);
        $stmt_students->execute([$class_std]);

        $students_result = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
        $students = [];
        foreach ($students_result as $student_row) {
            $students[$student_row['id']] = [
                'id' => $student_row['id'],
                'student_name' => $student_row['student_name'],
                'rollno' => $student_row['rollno'],
                'marks' => []
            ];
        }

        if (!empty($students)) {
            $student_ids = array_keys($students);
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));

            $marks_query = "SELECT student_id, subject_name, marks_obtained FROM student_marks WHERE exam_type = ? AND academic_year = ? AND student_id IN ($placeholders)";
            $stmt_marks = $conn->prepare($marks_query);

            $params = array_merge([$exam_type, $academic_year], $student_ids);
            $stmt_marks->execute($params);

            while ($mark_row = $stmt_marks->fetch(PDO::FETCH_ASSOC)) {
                if (isset($students[$mark_row['student_id']])) {
                    $students[$mark_row['student_id']]['marks'][$mark_row['subject_name']] = $mark_row['marks_obtained'];
                }
            }
        }

        $response['success'] = true;
        $response['students'] = array_values($students);
        $response['message'] = 'Students and subjects loaded successfully.';
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log("Get Students for Marks Error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Required parameters are missing.';
}

echo json_encode($response);
