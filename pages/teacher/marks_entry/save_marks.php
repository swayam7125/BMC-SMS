<?php
header('Content-Type: application/json');
include_once "../../../includes/connect.php";
include_once "../../../encryption.php";
// include_once "../../../includes/email_functions.php"; // Uncomment if you have email sending setup

$response = ['success' => false, 'message' => 'An error occurred.'];

$role = null;
$teacher_id = null;
if (isset($_COOKIE['encrypted_user_role'])) $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id'])) $teacher_id = decrypt_id($_COOKIE['encrypted_user_id']);

if ($role !== 'teacher' || !$teacher_id) {
    $response['message'] = 'Authentication failed.';
    echo json_encode($response);
    exit;
}

if (isset($_POST['marks']) && isset($_POST['class_std']) && isset($_POST['exam_type_hidden']) && isset($_POST['academic_year_hidden'])) {
    $marks_data = $_POST['marks'];
    $class_std = $_POST['class_std'];
    $exam_type = $_POST['exam_type_hidden'];
    $academic_year = $_POST['academic_year_hidden'];

    try {
        $stmt_school = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
        $stmt_school->execute([$teacher_id]);
        $school_data = $stmt_school->fetch(PDO::FETCH_ASSOC);

        if (!$school_data) {
            $response['message'] = 'Could not identify your school.';
            echo json_encode($response);
            exit;
        }
        $school_id = $school_data['school_id'];

        $conn->beginTransaction();

        // PostgreSQL Change: Replaced ON DUPLICATE KEY UPDATE with ON CONFLICT
        // NOTE: This assumes you have a UNIQUE constraint on (student_id, academic_year, exam_type, subject_name)
        $query = "INSERT INTO student_marks (student_id, school_id, academic_year, std, exam_type, subject_name, marks_obtained, entered_by_user_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                  ON CONFLICT (student_id, academic_year, exam_type, subject_name) 
                  DO UPDATE SET 
                    marks_obtained = EXCLUDED.marks_obtained, 
                    entered_by_user_id = EXCLUDED.entered_by_user_id";

        $stmt = $conn->prepare($query);
        $saved_count = 0;

        foreach ($marks_data as $student_id => $subjects) {
            foreach ($subjects as $subject => $marks) {
                if (is_numeric($marks) && $marks >= 0 && $marks <= 100) {
                    $stmt->execute([
                        $student_id,
                        $school_id,
                        $academic_year,
                        $class_std,
                        $exam_type,
                        $subject,
                        $marks,
                        $teacher_id
                    ]);
                    $saved_count += $stmt->rowCount();
                }
            }
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Successfully saved/updated {$saved_count} marks entries!";

        // Notification logic can be placed here if needed, after successful commit.

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log("Save Marks Error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'No marks data received or required fields are missing.';
}

echo json_encode($response);
