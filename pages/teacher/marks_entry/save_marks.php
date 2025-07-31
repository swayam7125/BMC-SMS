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

    // --- START: Notification and Email Logic ---
    if ($response['success']) {
        // Get teacher's name
        $teacher_name = 'Your Teacher';
        $stmt_teacher = mysqli_prepare($conn, "SELECT teacher_name FROM teacher WHERE id = ?");
        mysqli_stmt_bind_param($stmt_teacher, "i", $teacher_id);
        mysqli_stmt_execute($stmt_teacher);
        $teacher_result = mysqli_stmt_get_result($stmt_teacher);
        if ($teacher_row = mysqli_fetch_assoc($teacher_result)) {
            $teacher_name = $teacher_row['teacher_name'];
        }
        mysqli_stmt_close($stmt_teacher);

        $student_ids = array_keys($marks_data);
        if (!empty($student_ids)) {
            // Get student info (name, email)
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
            $sql_students = "SELECT id, student_name, email FROM student WHERE id IN ($placeholders)";
            $stmt_students = mysqli_prepare($conn, $sql_students);
            mysqli_stmt_bind_param($stmt_students, str_repeat('i', count($student_ids)), ...$student_ids);
            mysqli_stmt_execute($stmt_students);
            $result_students = mysqli_stmt_get_result($stmt_students);
            $students_info = [];
            while ($student_row = mysqli_fetch_assoc($result_students)) {
                $students_info[$student_row['id']] = ['name' => $student_row['student_name'], 'email' => $student_row['email']];
            }
            mysqli_stmt_close($stmt_students);

            // --- NEW: Calculate percentage for each student ---
            $student_percentages = [];
            $sql_agg = "SELECT student_id, SUM(marks_obtained) AS total_obtained, SUM(total_marks) AS total_possible
                        FROM student_marks
                        WHERE exam_type = ? AND academic_year = ? AND student_id IN ($placeholders)
                        GROUP BY student_id";
            $stmt_agg = mysqli_prepare($conn, $sql_agg);
            mysqli_stmt_bind_param($stmt_agg, 'ss' . str_repeat('i', count($student_ids)), $exam_type, $academic_year, ...$student_ids);
            mysqli_stmt_execute($stmt_agg);
            $result_agg = mysqli_stmt_get_result($stmt_agg);
            while ($agg_row = mysqli_fetch_assoc($result_agg)) {
                if ($agg_row['total_possible'] > 0) {
                    $percentage = ($agg_row['total_obtained'] / $agg_row['total_possible']) * 100;
                    $student_percentages[$agg_row['student_id']] = round($percentage, 2);
                }
            }
            mysqli_stmt_close($stmt_agg);
            // --- END: NEW ---

            $exam_type_formatted = ucwords(str_replace('_', ' ', $exam_type));
            $stmt_notification = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message, link, type, created_at) VALUES (?, ?, ?, 'result_published', NOW())");

            // Loop through each student to send notifications and personalized emails
            foreach ($marks_data as $student_id => $subjects) {
                if (isset($students_info[$student_id])) {
                    $student = $students_info[$student_id];

                    // Create and send notification
                    $notification_message = "Your results for the {$exam_type_formatted} have been published.";
                    $notification_link = "/pages/student/view_my_marks.php?exam_type={$exam_type}&academic_year={$academic_year}";
                    mysqli_stmt_bind_param($stmt_notification, "iss", $student_id, $notification_message, $notification_link);
                    mysqli_stmt_execute($stmt_notification);

                    // Send email
                    if (!empty($student['email'])) {
                        $email_subject = "Marks Published for {$exam_type_formatted} - {$academic_year}";

                        $marks_table = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 80%; margin-top: 15px;">
                                            <thead style="background-color: #f2f2f2;"><tr><th>Subject</th><th>Marks Obtained</th></tr></thead><tbody>';
                        foreach ($subjects as $subject_name => $marks) {
                            if (is_numeric($marks)) {
                                $marks_table .= '<tr><td>' . htmlspecialchars($subject_name) . '</td><td>' . htmlspecialchars($marks) . '</td></tr>';
                            }
                        }
                        $marks_table .= '</tbody></table>';

                        // --- MODIFIED: Add percentage to the email body ---
                        $percentage_html = '';
                        if (isset($student_percentages[$student_id])) {
                            $percentage_val = $student_percentages[$student_id];
                            $percentage_html = '<p style="font-size: 1.1em;">Your overall percentage is: <strong>' . $percentage_val . '%</strong></p>';
                        }

                        $email_body = "
                            <p>Dear " . htmlspecialchars($student['name']) . ",</p>
                            <p>Your marks for the <strong>{$exam_type_formatted}</strong> of the academic year <strong>{$academic_year}</strong> have been published by your teacher, " . htmlspecialchars($teacher_name) . ".</p>
                            <p>Here are your results:</p>
                            {$marks_table}
                            {$percentage_html}
                            <p>You can view your full report card by logging into the school portal.</p>
                            <p>Best regards,<br>School Administration</p>
                        ";
                        
                        send_email($student['email'], $email_subject, $email_body);
                    }
                }
            }
            mysqli_stmt_close($stmt_notification);
        }
    }
    // --- END: Notification and Email Logic ---

} else {
    $response['message'] = 'No marks data received or required fields are missing.';
}

echo json_encode($response);
?>