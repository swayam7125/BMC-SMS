<?php
header('Content-Type: application/json');
include_once "../../../includes/connect.php";
include_once "../../../encryption.php";

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

    $stmt_school = mysqli_prepare($conn, "SELECT school_id FROM teacher WHERE id = ?");
    mysqli_stmt_bind_param($stmt_school, "i", $teacher_id);
    mysqli_stmt_execute($stmt_school);
    $school_result = mysqli_stmt_get_result($stmt_school);
    
    if (mysqli_num_rows($school_result) == 0) {
        $response['message'] = 'Could not identify your school.';
        echo json_encode($response);
        exit;
    }
    
    $school_data = mysqli_fetch_assoc($school_result);
    $school_id = $school_data['school_id'];
    mysqli_stmt_close($stmt_school);

    mysqli_begin_transaction($conn);
    try {
        // FIXED: Removed division column and corrected parameter binding for subject name
        $query = "INSERT INTO student_marks (student_id, school_id, academic_year, std, exam_type, subject_name, marks_obtained, entered_by_user_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                  marks_obtained = VALUES(marks_obtained), 
                  entered_by_user_id = VALUES(entered_by_user_id)";

        $stmt = mysqli_prepare($conn, $query);

        $saved_count = 0;
        $error_details = [];

        foreach ($marks_data as $student_id => $subjects) {
            foreach ($subjects as $subject => $marks) {
                if (is_numeric($marks) && $marks >= 0 && $marks <= 100) {
                    // FIXED: Corrected binding - subject name should be 's' (string), not 'i' (integer)
                    // Parameters: student_id(i), school_id(i), academic_year(s), std(s), exam_type(s), subject_name(s), marks_obtained(d), teacher_id(i)
                    mysqli_stmt_bind_param($stmt, "iissssdi", 
                        $student_id, $school_id, $academic_year, $class_std, $exam_type, $subject, $marks, $teacher_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $saved_count++;
                    } else {
                        $error_details[] = "Failed to save marks for student ID {$student_id}, subject {$subject}: " . mysqli_stmt_error($stmt);
                    }
                }
            }
        }
        
        mysqli_stmt_close($stmt);
        
        if ($saved_count > 0 && empty($error_details)) {
            mysqli_commit($conn);
            $response['success'] = true;
            $response['message'] = "Successfully saved {$saved_count} marks entries!";
        } else if ($saved_count > 0 && !empty($error_details)) {
            mysqli_commit($conn);
            $response['success'] = true;
            $response['message'] = "Partially successful: {$saved_count} marks saved, but some errors occurred.";
            $response['errors'] = $error_details;
        } else {
            mysqli_rollback($conn);
            $response['message'] = 'Failed to save marks: ' . implode(', ', $error_details);
        }

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'No marks data received or required fields are missing.';
}

echo json_encode($response);
?>