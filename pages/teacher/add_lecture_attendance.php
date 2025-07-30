<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// ADDED: Set the timezone to match your location
date_default_timezone_set('Asia/Kolkata');

$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');

if ($role !== 'teacher') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$today_day_name = date('l'); // e.g., 'Tuesday'
$current_time = date('H:i:s'); // Get current time in HH:MM:SS format
$lectures_today = [];
$students = [];
$selected_lecture = null;
$school_id = null;

// Get the school ID for the logged-in teacher
$stmt_school = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
$stmt_school->bind_param("i", $userId);
$stmt_school->execute();
$school_id = $stmt_school->get_result()->fetch_assoc()['school_id'];
$stmt_school->close();

// Handle form submission to SAVE attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attendance_date = $_POST['attendance_date'];
    $period_number = $_POST['period_number'];
    $standard = $_POST['standard'];
    $subject = $_POST['subject'];
    $attendance_data = $_POST['attendance'];

    $conn->begin_transaction();
    try {
        foreach ($attendance_data as $student_id => $status) {
            $stmt = $conn->prepare("
                INSERT INTO attendance (student_id, teacher_id, school_id, standard, subject, period_number, attendance_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), teacher_id = VALUES(teacher_id)
            ");
            $stmt->bind_param("iiississ", $student_id, $userId, $school_id, $standard, $subject, $period_number, $attendance_date, $status);
            $stmt->execute();
        }
        $conn->commit();
        $successMessage = "Attendance saved successfully for Period $period_number!";
    } catch (Exception $e) {
        $conn->rollback();
        $errorMessage = "Failed to save attendance: " . $e->getMessage();
    }
}

// Fetch today's lectures for the teacher
$stmt_lectures = $conn->prepare("SELECT id, standard, period_number, subject_name, start_time, end_time FROM school_timetable WHERE teacher_id = ? AND day_of_week = ? ORDER BY period_number ASC");
$stmt_lectures->bind_param("is", $userId, $today_day_name);
$stmt_lectures->execute();
$lectures_today = $stmt_lectures->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_lectures->close();

// If a specific lecture is selected to take attendance for
if (isset($_GET['lecture_id'])) {
    $lecture_id = $_GET['lecture_id'];
    $stmt_lecture_details = $conn->prepare("SELECT * FROM school_timetable WHERE id = ? AND teacher_id = ?");
    $stmt_lecture_details->bind_param("ii", $lecture_id, $userId);
    $stmt_lecture_details->execute();
    $selected_lecture = $stmt_lecture_details->get_result()->fetch_assoc();
    
    if ($selected_lecture) {
        // Fetch students for the selected lecture's standard
        $stmt_students = $conn->prepare("SELECT id, rollno, student_name FROM student WHERE school_id = ? AND std = ? ORDER BY rollno ASC");
        $stmt_students->bind_param("is", $school_id, $selected_lecture['standard']);
        $stmt_students->execute();
        $students = $stmt_students->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Take Lecture Attendance</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <style>
        .disabled-card {
            opacity: 0.65;
            background-color: #f8f9fc;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">My Lectures for Today (<?php echo $today_day_name; ?>)</h1>

                    <?php if (isset($successMessage)) echo "<div class='alert alert-success'>$successMessage</div>"; ?>
                    <?php if (isset($errorMessage)) echo "<div class='alert alert-danger'>$errorMessage</div>"; ?>

                    <div class="row">
                        <?php if (empty($lectures_today)): ?>
                        <div class="col-12">
                            <p>You have no lectures scheduled for today.</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($lectures_today as $lecture): ?>
                        <?php
                            // Check if the current time is within the lecture's start and end time
                            $is_active = ($current_time >= $lecture['start_time'] && $current_time <= $lecture['end_time']);
                            $card_class = $is_active ? '' : 'disabled-card';
                        ?>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card shadow h-100 <?php echo $card_class; ?>">
                                <div class="card-body">
                                    <div class="font-weight-bold text-primary text-uppercase mb-1">Period
                                        <?php echo htmlspecialchars($lecture['period_number']); ?></div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Std
                                        <?php echo htmlspecialchars($lecture['standard']); ?> - <?php echo htmlspecialchars($lecture['subject_name']); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo date('h:i A', strtotime($lecture['start_time'])) . ' - ' . date('h:i A', strtotime($lecture['end_time'])); ?>
                                    </div>
                                    
                                    <?php if ($is_active): ?>
                                        <a href="add_lecture_attendance.php?lecture_id=<?php echo $lecture['id']; ?>" class="btn btn-primary btn-sm mt-3">
                                            <i class="fas fa-check-circle"></i> Take Attendance
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm mt-3" disabled>
                                            <i class="fas fa-clock"></i> Not Lecture Time
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($selected_lecture && !empty($students)): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                Taking Attendance for: Std <?php echo htmlspecialchars($selected_lecture['standard']); ?> | Period
                                <?php echo htmlspecialchars($selected_lecture['period_number']); ?> | Subject:
                                <?php echo htmlspecialchars($selected_lecture['subject_name']); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="add_lecture_attendance.php">
                                <input type="hidden" name="attendance_date" value="<?php echo date('Y-m-d'); ?>">
                                <input type="hidden" name="period_number"
                                    value="<?php echo htmlspecialchars($selected_lecture['period_number']); ?>">
                                <input type="hidden" name="standard"
                                    value="<?php echo htmlspecialchars($selected_lecture['standard']); ?>">
                                <input type="hidden" name="subject"
                                    value="<?php echo htmlspecialchars($selected_lecture['subject_name']); ?>">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Roll No</th>
                                                <th>Student Name</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['rollno']); ?></td>
                                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                <td>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                            name="attendance[<?php echo $student['id']; ?>]"
                                                            value="Present" checked>
                                                        <label class="form-check-label">Present</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                            name="attendance[<?php echo $student['id']; ?>]"
                                                            value="Absent">
                                                        <label class="form-check-label">Absent</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                            name="attendance[<?php echo $student['id']; ?>]"
                                                            value="Leave">
                                                        <label class="form-check-label">Leave</label>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" name="save_attendance" class="btn btn-success">Save
                                    Attendance</button>
                            </form>
                        </div>
                    </div>
                    <?php elseif (isset($_GET['lecture_id'])): ?>
                    <div class="alert alert-warning">No students found for this class.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>