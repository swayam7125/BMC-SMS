<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if this is an AJAX request
if (is_ajax_request()) {
    // Start output buffering to capture the HTML
    ob_start();
}

date_default_timezone_set('Asia/Kolkata');

$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');

if ($role !== 'teacher') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$today_day_name = date('l');
$current_date = date('Y-m-d'); // Use current date for holiday check
$current_time = date('H:i:s');
$lectures_today = [];
$students = [];
$selected_lecture = null;
$school_id = null;
$is_holiday = false; // NEW: Flag to check for holiday
$holiday_description = ''; // NEW: To store the holiday description
$errorMessage = '';

try {
    // PDO Change: Converted all queries to PDO
    $stmt_school = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
    $stmt_school->execute([$userId]);
    $school_data = $stmt_school->fetch(PDO::FETCH_ASSOC);
    $school_id = $school_data['school_id'] ?? null;

    if (!$school_id) {
        throw new Exception("Could not determine school for the teacher.");
    }

    // NEW: Check if today is a holiday
    $holiday_stmt = $conn->prepare("SELECT description FROM holidays WHERE holiday_date = ? AND school_id = ?");
    $holiday_stmt->execute([$current_date, $school_id]);
    $holiday = $holiday_stmt->fetch(PDO::FETCH_ASSOC);
    if ($holiday) {
        $is_holiday = true;
        $holiday_description = $holiday['description'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance']) && !$is_holiday) {
        $attendance_date = $_POST['attendance_date'];
        $period_number = $_POST['period_number'];
        $standard = $_POST['standard'];
        $subject = $_POST['subject'];
        $attendance_data = $_POST['attendance'];

        $conn->beginTransaction();

        $stmt = $conn->prepare("
            INSERT INTO attendance (student_id, teacher_id, school_id, subject, period_number, attendance_date, status, standard) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT (student_id, attendance_date, period_number) 
            DO UPDATE SET status = EXCLUDED.status, teacher_id = EXCLUDED.teacher_id
        ");

        foreach ($attendance_data as $student_id => $status) {
            $stmt->execute([$student_id, $userId, $school_id, $subject, $period_number, $attendance_date, $status, $standard]);
        }
        $conn->commit();
        $successMessage = "Attendance saved successfully for Period $period_number!";
    }

    // Only fetch lectures if it's not a holiday
    if (!$is_holiday) {
        $lecture_query = "SELECT id, standard, period_number, subject_name, start_time, end_time 
                          FROM school_timetable 
                          WHERE teacher_id = ? AND day_of_week = ? 
                          ORDER BY period_number ASC";
        $stmt_lectures = $conn->prepare($lecture_query);
        $stmt_lectures->execute([$userId, $today_day_name]);
        $lectures_today = $stmt_lectures->fetchAll(PDO::FETCH_ASSOC);
    
        if (isset($_GET['lecture_id'])) {
            $lecture_id = $_GET['lecture_id'];
            $stmt_lecture_details = $conn->prepare("SELECT * FROM school_timetable WHERE id = ? AND teacher_id = ?");
            $stmt_lecture_details->execute([$lecture_id, $userId]);
            $selected_lecture = $stmt_lecture_details->fetch(PDO::FETCH_ASSOC);
    
            if ($selected_lecture) {
                $stmt_students = $conn->prepare("SELECT id, rollno, student_name FROM student WHERE school_id = ? AND std = ? ORDER BY rollno ASC");
                $stmt_students->execute([$school_id, $selected_lecture['standard']]);
                $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    $errorMessage = "A database error occurred: " . $e->getMessage();
    error_log("Add Lecture Attendance Error: " . $e->getMessage());
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Take Lecture Attendance</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
        <?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?> <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?><?php
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">My Lectures for Today (<?php echo $today_day_name; ?>)</h1>

                    <?php if (isset($successMessage)) echo "<div class='alert alert-success'>$successMessage</div>"; ?>
                    <?php if (!empty($errorMessage)): ?>
                    <div class='alert alert-danger'><?php echo $errorMessage; ?></div>
                    <?php endif; ?>
                    <?php if ($is_holiday): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Attendance on
                                <?php echo htmlspecialchars($current_date); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h4 class="alert-heading"><i class="fas fa-calendar-check"></i> Public Holiday</h4>
                                <p>You cannot mark attendance for this day because it is a public holiday:
                                    <strong><?php echo htmlspecialchars($holiday_description); ?></strong>.</p>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <?php if (empty($lectures_today)): ?>
                        <div class="col-12">
                            <p>You have no lectures scheduled for today.</p>
                        </div>
                        <?php else: foreach ($lectures_today as $lecture): ?>
                        <?php $is_active = ($current_time >= $lecture['start_time'] && $current_time <= $lecture['end_time']);
                                    $card_class = $is_active ? '' : 'disabled-card'; ?>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card shadow h-100 <?php echo $card_class; ?>">
                                <div class="card-body">
                                    <div class="font-weight-bold text-primary text-uppercase mb-1">Period
                                        <?php echo htmlspecialchars($lecture['period_number']); ?></div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Std
                                        <?php echo htmlspecialchars($lecture['standard']); ?> -
                                        <?php echo htmlspecialchars($lecture['subject_name']); ?></div>
                                    <div class="text-muted small">
                                        <?php echo date('h:i A', strtotime($lecture['start_time'])) . ' - ' . date('h:i A', strtotime($lecture['end_time'])); ?>
                                    </div>
                                    <?php if ($is_active): ?>
                                    <a href="add_lecture_attendance.php?lecture_id=<?php echo $lecture['id']; ?>"
                                        class="btn btn-primary btn-sm mt-3"><i class="fas fa-check-circle"></i> Take
                                        Attendance</a>
                                    <?php else: ?>
                                    <button class="btn btn-secondary btn-sm mt-3" disabled><i class="fas fa-clock"></i>
                                        Not Lecture Time</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach;
                            endif; ?>
                    </div>

                    <?php if ($selected_lecture && !empty($students)): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Taking Attendance for: Std
                                <?php echo htmlspecialchars($selected_lecture['standard']); ?> | Period
                                <?php echo htmlspecialchars($selected_lecture['period_number']); ?> | Subject:
                                <?php echo htmlspecialchars($selected_lecture['subject_name']); ?></h6>
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
                                                    <div class="form-check form-check-inline"><input
                                                            class="form-check-input" type="radio"
                                                            name="attendance[<?php echo $student['id']; ?>]"
                                                            value="Present" checked><label
                                                            class="form-check-label">Present</label></div>
                                                    <div class="form-check form-check-inline"><input
                                                            class="form-check-input" type="radio"
                                                            name="attendance[<?php echo $student['id']; ?>]"
                                                            value="Absent"><label
                                                            class="form-check-label">Absent</label></div>
                                                    <div class="form-check form-check-inline"><input
                                                            class="form-check-input" type="radio"
                                                            name="attendance[<?php echo $student['id']; ?>]"
                                                            value="Leave"><label class="form-check-label">Leave</label>
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
                    <?php endif; ?>
                </div>
            </div>
            <?php
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
<?php
// Add this block at the very end of the file
if (is_ajax_request()) {
    // Get the captured HTML
    $content = ob_get_clean();
    
    // Extract just the main content area for the AJAX response
    if (preg_match('/<div class="container-fluid".*?>(.*?)<\ /div>/s', $content, $matches)) {
    echo '<div class="container-fluid">' . $matches[1] . '</div>';
    } else {
    // Fallback if the main container isn't found
    echo $content;
    }
    // Stop the script for AJAX requests
    exit;
    }
    ?>

</html>