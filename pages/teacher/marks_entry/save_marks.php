<?php
header('Content-Type: application/json');
include_once "../../../includes/connect.php";
include_once "../../../encryption.php";
include_once "../../../includes/email_functions.php"; // Include the email functions file

$response = ['success' => false, 'message' => 'An error occurred.'];

$role = null;
$teacher_id = null;
if (isset($_COOKIE['encrypted_user_role']))
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id']))
    $teacher_id = decrypt_id($_COOKIE['encrypted_user_id']);

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
                    mysqli_stmt_bind_param(
                        $stmt,
                        "iissssdi",
                        $student_id,
                        $school_id,
                        $academic_year,
                        $class_std,
                        $exam_type,
                        $subject,
                        $marks,
                        $teacher_id
                    );

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

    // --- START: Email Notification Logic ---
    // Send emails only if the marks were successfully saved to the database.
    if ($response['success']) {
        // 1. Get the teacher's name for the email body
        $teacher_name = 'Your Teacher'; // Fallback name
        $stmt_teacher = mysqli_prepare($conn, "SELECT teacher_name FROM teacher WHERE id = ?");
        mysqli_stmt_bind_param($stmt_teacher, "i", $teacher_id);
        mysqli_stmt_execute($stmt_teacher);
        $teacher_result = mysqli_stmt_get_result($stmt_teacher);
        if ($teacher_row = mysqli_fetch_assoc($teacher_result)) {
            $teacher_name = $teacher_row['teacher_name'];
        }
        mysqli_stmt_close($stmt_teacher);

        // 2. Get details (name and email) for all students whose marks were updated
        $student_ids = array_keys($marks_data);
        if (!empty($student_ids)) {
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
            $sql_students = "SELECT id, student_name, email FROM student WHERE id IN ($placeholders)";
            $stmt_students = mysqli_prepare($conn, $sql_students);
            $types = str_repeat('i', count($student_ids));
            mysqli_stmt_bind_param($stmt_students, $types, ...$student_ids);
            mysqli_stmt_execute($stmt_students);
            $result_students = mysqli_stmt_get_result($stmt_students);

            $students_info = [];
            while ($student_row = mysqli_fetch_assoc($result_students)) {
                $students_info[$student_row['id']] = [
                    'name' => $student_row['student_name'],
                    'email' => $student_row['email']
                ];
            }
            mysqli_stmt_close($stmt_students);

            // 3. Loop through each student and send them a personalized email
            $exam_type_formatted = ucwords(str_replace('_', ' ', $exam_type));

            foreach ($marks_data as $student_id => $subjects) {
                if (isset($students_info[$student_id]) && !empty($students_info[$student_id]['email'])) {
                    $student = $students_info[$student_id];

                    $email_subject = "Marks Published for {$exam_type_formatted} - {$academic_year}";

                    // Create an HTML table for the marks
                    $marks_table = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 80%; margin-top: 15px;">
                                        <thead style="background-color: #f2f2f2;">
                                            <tr>
                                                <th>Subject</th>
                                                <th>Marks Obtained</th>
                                            </tr>
                                        </thead>
                                        <tbody>';
                    foreach ($subjects as $subject_name => $marks) {
                        if (is_numeric($marks)) { // Only include subjects where marks were entered
                            $marks_table .= '<tr><td>' . htmlspecialchars($subject_name) . '</td><td>' . htmlspecialchars($marks) . '</td></tr>';
                        }
                    }
                    $marks_table .= '</tbody></table>';

                    $email_body = "
                        <p>Dear " . htmlspecialchars($student['name']) . ",</p>
                        <p>Your marks for the <strong>{$exam_type_formatted}</strong> of the academic year <strong>{$academic_year}</strong> have been published by your teacher, " . htmlspecialchars($teacher_name) . ".</p>
                        <p>Here are your results:</p>
                        {$marks_table}
                        <p>You can view your full report card by logging into the school portal.</p>
                        <p>Best regards,<br>School Administration</p>
                    ";

                    // Call the central email function
                    send_email($student['email'], $email_subject, $email_body);
                }
            }
        }
    }
    // --- END: Email Notification Logic ---

} else {
    $response['message'] = 'No marks data received or required fields are missing.';
}

echo json_encode($response);
?>