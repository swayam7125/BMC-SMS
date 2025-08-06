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
            $response['message'] = "No subjects assigned to this standard.";
            echo json_encode($response);
            exit;
        }
        $response['subjects'] = $subjects;

        $student_query = "SELECT id, student_name, rollno, school_id FROM student WHERE std = ? ORDER BY rollno";
        $stmt_students = $conn->prepare($student_query);
        $stmt_students->execute([$class_std]);

        $students_result = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
        $students = [];
        $school_id = null;
        foreach ($students_result as $student_row) {
            if (!$school_id) {
                $school_id = $student_row['school_id'];
            }
            $students[$student_row['id']] = [
                'id' => $student_row['id'],
                'student_name' => $student_row['student_name'],
                'rollno' => $student_row['rollno'],
                'marks' => [],
                'total_obtained' => 0,
                'total_possible' => 0,
                'percentage' => 0,
                'status' => 'N/A'
            ];
        }

        $passing_percentage = 33.00; // Default
        if ($school_id) {
            $stmt_settings = $conn->prepare("SELECT passing_percentage FROM school WHERE id = ?");
            $stmt_settings->execute([$school_id]);
            if ($settings_row = $stmt_settings->fetch(PDO::FETCH_ASSOC)) {
                $passing_percentage = (float)$settings_row['passing_percentage'];
            }
        }

        if (!empty($students)) {
            $student_ids = array_keys($students);
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));

            $marks_query = "SELECT student_id, subject_name, marks_obtained, total_marks 
                            FROM student_marks 
                            WHERE exam_type = ? AND academic_year = ? AND student_id IN ($placeholders)";

            $stmt_marks = $conn->prepare($marks_query);
            $params = array_merge([$exam_type, $academic_year], $student_ids);
            $stmt_marks->execute($params);

            while ($mark_row = $stmt_marks->fetch(PDO::FETCH_ASSOC)) {
                if (isset($students[$mark_row['student_id']])) {
                    $students[$mark_row['student_id']]['marks'][$mark_row['subject_name']] = $mark_row['marks_obtained'];
                    $students[$mark_row['student_id']]['total_obtained'] += $mark_row['marks_obtained'];
                    $students[$mark_row['student_id']]['total_possible'] += $mark_row['total_marks'];
                }
            }

            foreach ($students as &$student_data) { // Use reference to modify array directly
                if ($student_data['total_possible'] > 0) {
                    $percentage = ($student_data['total_obtained'] / $student_data['total_possible']) * 100;
                    $student_data['percentage'] = round($percentage, 2);
                    $student_data['status'] = ($percentage >= $passing_percentage) ? 'Pass' : 'Fail';
                }
            }
            unset($student_data); // Unset reference
        }

        $response['success'] = true;
        $response['students'] = array_values($students);
        $response['message'] = 'Marks report loaded successfully.';
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log("Get Marks Report Error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Required parameters are missing.';
}

echo json_encode($response);
