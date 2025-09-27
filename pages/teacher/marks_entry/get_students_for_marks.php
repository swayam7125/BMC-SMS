<?php
include_once '../../../includes/connect.php';
include_once '../../../encryption.php';
include_once '../../../includes/log_system.php'; // Log system included

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'teacher') {
    http_response_code(403);
    echo "Unauthorized access.";
    exit;
}

$teacher_id = $userId;
$school_id = null;
$error_msg = '';

try {
    // Get school_id for the teacher
    $stmt_school = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
    $stmt_school->execute([$teacher_id]);
    $school_id = $stmt_school->fetchColumn();

    if (!$school_id) {
        throw new Exception("Could not determine teacher's school.");
    }

    $academic_year = isset($_POST['academic_year']) ? $_POST['academic_year'] : '';
    $standard = isset($_POST['standard']) ? $_POST['standard'] : '';
    $exam_type = isset($_POST['exam_type']) ? $_POST['exam_type'] : '';
    $subject = isset($_POST['subject']) ? $_POST['subject'] : '';

    if (empty($academic_year) || empty($standard) || empty($exam_type) || empty($subject)) {
        echo "<div class='alert alert-warning'>Please select all fields to fetch students.</div>";
        exit;
    }

    // Fetch students for the selected standard
    $stmt_students = $conn->prepare("SELECT id, student_name, rollno FROM student WHERE school_id = ? AND std = ? ORDER BY rollno");
    $stmt_students->execute([$school_id, $standard]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    if (empty($students)) {
        echo "<div class='alert alert-info'>No students found for the selected standard.</div>";
        exit;
    }

    // Fetch existing marks to pre-fill the form
    $student_ids = array_column($students, 'id');
    $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
    
    $stmt_marks = $conn->prepare("
        SELECT student_id, marks_obtained, total_marks 
        FROM student_marks 
        WHERE student_id IN ($placeholders) 
          AND academic_year = ? 
          AND std = ? 
          AND exam_type = ? 
          AND subject_name = ?
    ");
    $params = array_merge($student_ids, [$academic_year, $standard, $exam_type, $subject]);
    $stmt_marks->execute($params);
    $existing_marks = $stmt_marks->fetchAll(PDO::FETCH_KEY_PAIR);

    // Log the action of loading students
    log_interaction($role, $userId, "MARKS ENTRY: Loaded students for marks entry. Standard: {$standard}, Year: {$academic_year}, Exam: {$exam_type}, Subject: {$subject}.", $userName);

    // Generate the student list table
    $output = '<table class="table table-bordered"><thead><tr><th>Roll No</th><th>Student Name</th><th>Marks Obtained</th><th>Total Marks</th></tr></thead><tbody>';
    foreach ($students as $student) {
        $marks_obtained = $existing_marks[$student['id']]['marks_obtained'] ?? '';
        $total_marks = $existing_marks[$student['id']]['total_marks'] ?? '100'; // Default to 100
        $output .= '<tr>';
        $output .= '<td>' . htmlspecialchars($student['rollno']) . '</td>';
        $output .= '<td>' . htmlspecialchars($student['student_name']) . '</td>';
        $output .= '<td><input type="number" name="marks[' . $student['id'] . '][obtained]" class="form-control" value="' . htmlspecialchars($marks_obtained) . '" min="0" required></td>';
        $output .= '<td><input type="number" name="marks[' . $student['id'] . '][total]" class="form-control" value="' . htmlspecialchars($total_marks) . '" min="0" required></td>';
        $output .= '</tr>';
    }
    $output .= '</tbody></table>';
    $output .= '<button type="submit" class="btn btn-primary mt-3">Save Marks</button>';
    echo $output;

} catch (Exception $e) {
    http_response_code(500);
    // Log any errors
    log_interaction($role, $userId, "MARKS ENTRY ERROR: Failed to load students for marks entry. Error: " . $e->getMessage(), $userName);
    echo "<div class='alert alert-danger'>An error occurred: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>