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

try {
    // Get school_id for the teacher
    $stmt_school = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
    $stmt_school->execute([$teacher_id]);
    $school_id = $stmt_school->fetchColumn();
} catch (PDOException $e) {
    http_response_code(500);
    log_interaction($role, $userId, "MARKS REPORT ERROR: Failed to get school ID. DB Error: " . $e->getMessage(), $userName);
    die("Database error fetching school ID: " . $e->getMessage());
}

if (!$school_id) {
    http_response_code(400);
    die("Could not determine teacher's school.");
}

$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$standard = isset($_GET['standard']) ? $_GET['standard'] : '';
$exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';

if (empty($academic_year) || empty($standard) || empty($exam_type)) {
    echo "<div class='alert alert-warning'>Please select academic year, standard, and exam type to generate the report.</div>";
    exit;
}

try {
    // Fetch student marks for the selected criteria
    $stmt = $conn->prepare("
        SELECT 
            s.rollno, 
            s.student_name, 
            sm.subject_name, 
            sm.marks_obtained, 
            sm.total_marks
        FROM student_marks sm
        JOIN student s ON sm.student_id = s.id
        WHERE sm.school_id = ? 
          AND sm.academic_year = ? 
          AND sm.std = ? 
          AND sm.exam_type = ?
        ORDER BY s.rollno, sm.subject_name
    ");
    $stmt->execute([$school_id, $academic_year, $standard, $exam_type]);
    $marks_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($marks_data)) {
        echo "<div class='alert alert-info'>No marks found for the selected criteria.</div>";
        exit;
    }

    // Log the successful report generation
    log_interaction($role, $userId, "MARKS REPORT: Generated marks report for Standard {$standard}, Year {$academic_year}, Exam {$exam_type}.", $userName);


    // Process data for the report table
    $students = [];
    $subjects = [];
    foreach ($marks_data as $row) {
        $students[$row['rollno']]['student_name'] = $row['student_name'];
        $students[$row['rollno']]['marks'][$row['subject_name']] = [
            'obtained' => $row['marks_obtained'],
            'total' => $row['total_marks']
        ];
        if (!in_array($row['subject_name'], $subjects)) {
            $subjects[] = $row['subject_name'];
        }
    }
    sort($subjects);

?>
    <h4 class="mb-3">Marks Report</h4>
    <p><strong>Standard:</strong> <?php echo htmlspecialchars($standard); ?> | <strong>Academic Year:</strong> <?php echo htmlspecialchars($academic_year); ?> | <strong>Exam:</strong> <?php echo htmlspecialchars($exam_type); ?></p>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Roll No</th>
                    <th>Student Name</th>
                    <?php foreach ($subjects as $subject): ?>
                        <th><?php echo htmlspecialchars($subject); ?></th>
                    <?php endforeach; ?>
                    <th>Total Obtained</th>
                    <th>Total Marks</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $rollno => $student_data):
                    $total_obtained = 0;
                    $total_marks = 0;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rollno); ?></td>
                        <td><?php echo htmlspecialchars($student_data['student_name']); ?></td>
                        <?php 
                        foreach ($subjects as $subject) {
                            $marks = $student_data['marks'][$subject] ?? ['obtained' => 'N/A', 'total' => 0];
                            echo "<td>" . htmlspecialchars($marks['obtained']) . " / " . htmlspecialchars($marks['total']) . "</td>";
                            if (is_numeric($marks['obtained'])) {
                                $total_obtained += $marks['obtained'];
                            }
                            if (is_numeric($marks['total'])) {
                                $total_marks += $marks['total'];
                            }
                        }
                        $percentage = ($total_marks > 0) ? round(($total_obtained / $total_marks) * 100, 2) : 0;
                        ?>
                        <td><strong><?php echo htmlspecialchars($total_obtained); ?></strong></td>
                        <td><strong><?php echo htmlspecialchars($total_marks); ?></strong></td>
                        <td><strong><?php echo htmlspecialchars($percentage); ?>%</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <button class="btn btn-secondary mt-3" onclick="window.print();"><i class="fas fa-print"></i> Print Report</button>

<?php
} catch (PDOException $e) {
    http_response_code(500);
    // Log the database error
    log_interaction($role, $userId, "MARKS REPORT ERROR: Failed to generate report. DB Error: " . $e->getMessage(), $userName);
    echo "<div class='alert alert-danger'>Database Error: Could not generate the report. " . $e->getMessage() . "</div>";
}
?>