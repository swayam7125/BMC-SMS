<?php
// Include necessary files
include_once '../../includes/connect.php'; // Database connection
include_once '../../encryption.php';    // Encryption functions

// IMPROVEMENT: Set a consistent timezone for all date operations
date_default_timezone_set('Asia/Kolkata');

// Initialize variables
$role = null;
$userId = null;
$errorMessage = '';
$teacherDetails = null;
$students = [];
$all_missing_dates = []; // To hold ALL dates with incomplete attendance
$is_holiday = false; // NEW: Holiday flag
$holiday_description = ''; // NEW: Holiday description

// Retrieve and decrypt user role and ID from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Authorization Check: Ensure user is a logged-in teacher
if (!$role || $role !== 'teacher') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

try {
    // Fetch teacher details
    $stmt = $conn->prepare("SELECT class_teacher, class_teacher_std, school_id FROM teacher WHERE id = ? AND class_teacher = B'1'");
    $stmt->execute([$userId]);
    $teacherDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacherDetails || empty($teacherDetails['class_teacher_std'])) {
        $errorMessage = "Access Denied: You are not assigned as a class teacher and cannot add attendance.";
    }

    $attendance_date_display = $_GET['attendance_date'] ?? date('Y-m-d');

    // NEW: Check if the selected date is a holiday
    if (empty($errorMessage)) {
        $holiday_stmt = $conn->prepare("SELECT description FROM holidays WHERE holiday_date = ? AND school_id = ?");
        $holiday_stmt->execute([$attendance_date_display, $teacherDetails['school_id']]);
        $holiday = $holiday_stmt->fetch(PDO::FETCH_ASSOC);
        if ($holiday) {
            $is_holiday = true;
            $holiday_description = $holiday['description'];
        }
    }

    // --- REVISED: Mandatory Past Attendance Check for ALL missing dates (only if not a holiday) ---
    if (empty($errorMessage) && !$is_holiday) {
        $target_date = new DateTime($attendance_date_display);
        $start_date = new DateTime($target_date->format('Y-m-01')); // Check from the 1st of the month
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start_date, $interval, $target_date);

        // Get the total number of students in the class
        $student_count_stmt = $conn->prepare("SELECT COUNT(id) FROM student WHERE std = ? AND school_id = ?");
        $student_count_stmt->execute([$teacherDetails['class_teacher_std'], $teacherDetails['school_id']]);
        $total_students = $student_count_stmt->fetchColumn();

        if ($total_students > 0) {
            $att_count_stmt = $conn->prepare("SELECT COUNT(student_id) FROM attendance WHERE std = ? AND school_id = ? AND attendance_date = ?");
            foreach ($period as $date) {
                // Check only for working days (Mon-Sat)
                if ($date->format('N') < 7) {
                    $date_to_check = $date->format('Y-m-d');
                    $att_count_stmt->execute([$teacherDetails['class_teacher_std'], $teacherDetails['school_id'], $date_to_check]);
                    $recorded_students = $att_count_stmt->fetchColumn();

                    if ($recorded_students < $total_students) {
                        $all_missing_dates[] = $date_to_check; // Add date to the list
                    }
                }
            }
        }
    }
    // --- END: Mandatory Past Attendance Check ---

    // Handle form submission to save attendance (only if not a holiday)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errorMessage) && empty($all_missing_dates) && !$is_holiday) {
        $attendance_date = $_POST['attendance_date'];
        $attendance_data = $_POST['attendance'];
        $class_std = $teacherDetails['class_teacher_std'];
        $school_id = $teacherDetails['school_id'];

        $conn->beginTransaction();

        $upsert_sql = "INSERT INTO attendance (student_id, teacher_id, school_id, std, attendance_date, status) 
                       VALUES (?, ?, ?, ?, ?, ?)
                       ON CONFLICT (student_id, attendance_date) 
                       DO UPDATE SET status = EXCLUDED.status, teacher_id = EXCLUDED.teacher_id";

        $stmt_upsert = $conn->prepare($upsert_sql);

        foreach ($attendance_data as $student_id => $status) {
            $stmt_upsert->execute([$student_id, $userId, $school_id, $class_std, $attendance_date, $status]);
        }

        $conn->commit();
        $successMessage = "Attendance for " . htmlspecialchars($attendance_date) . " has been saved successfully!";
    }

    // Fetch students for the form (only if not a holiday and no missing past attendance)
    if (empty($errorMessage) && empty($all_missing_dates) && !$is_holiday) {
        $student_stmt = $conn->prepare("SELECT id, rollno, student_name FROM student WHERE std = ? AND school_id = ? ORDER BY rollno ASC");
        $student_stmt->execute([$teacherDetails['class_teacher_std'], $teacherDetails['school_id']]);
        $students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);

        $existing_attendance = [];
        if (!empty($students)) {
            $att_stmt = $conn->prepare("SELECT student_id, status FROM attendance WHERE std = ? AND school_id = ? AND attendance_date = ?");
            $att_stmt->execute([$teacherDetails['class_teacher_std'], $teacherDetails['school_id'], $attendance_date_display]);
            while ($row = $att_stmt->fetch(PDO::FETCH_ASSOC)) {
                $existing_attendance[$row['student_id']] = $row['status'];
            }
        }
    }
} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    $errorMessage = "A database error occurred. Please try again. Details: " . $e->getMessage();
    error_log("Add Attendance Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Add Attendance - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Add/Update Attendance</h1>

                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $successMessage; ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                    <?php endif; ?>
                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    
                    <?php // NEW: Logic to show holiday message, missing dates, or attendance form ?>
                    <?php elseif ($is_holiday): ?>
                         <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Attendance for <?php echo htmlspecialchars($teacherDetails['class_teacher_std']); ?> on <?php echo htmlspecialchars($attendance_date_display); ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h4 class="alert-heading"><i class="fas fa-calendar-check"></i> Public Holiday</h4>
                                    <p>You cannot mark attendance for this day because it is a public holiday: <strong><?php echo htmlspecialchars($holiday_description); ?></strong>.</p>
                                </div>
                            </div>
                        </div>
                    <?php elseif (!empty($all_missing_dates)): ?>
                        <div class="alert alert-warning">
                            <h4 class="alert-heading">Action Required</h4>
                            <p>You cannot mark attendance for <strong><?php echo htmlspecialchars($attendance_date_display); ?></strong> because attendance for the following past date(s) is incomplete:</p>
                            <ul>
                                <?php foreach ($all_missing_dates as $missing_date): ?>
                                    <li><strong><?php echo htmlspecialchars($missing_date); ?></strong></li>
                                <?php endforeach; ?>
                            </ul>
                            <hr>
                            <p class="mb-0">Please start by filling the attendance for <strong><?php echo htmlspecialchars($all_missing_dates[0]); ?></strong>.</p>
                            <a href="add_attendance.php?attendance_date=<?php echo htmlspecialchars($all_missing_dates[0]); ?>" class="btn btn-primary mt-3">Go to First Pending Attendance Sheet</a>
                        </div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Attendance for Class: <?php echo htmlspecialchars($teacherDetails['class_teacher_std']); ?></h6>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="" class="form-inline mb-4">
                                    <div class="form-group"><label for="attendance_date" class="mr-2">Select Date:</label><input type="date" id="attendance_date" name="attendance_date" class="form-control" value="<?php echo $attendance_date_display; ?>"></div>
                                    <button type="submit" class="btn btn-primary ml-2">Load Sheet</button>
                                </form>

                                <?php if (!empty($students)): ?>
                                    <form method="POST" action="add_attendance.php?attendance_date=<?php echo $attendance_date_display; ?>">
                                        <input type="hidden" name="attendance_date" value="<?php echo $attendance_date_display; ?>">
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="addAttendanceTable" width="100%" cellspacing="0">
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
                                                                <?php $current_status = $existing_attendance[$student['id']] ?? 'Present'; ?>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $student['id']; ?>]" id="present_<?php echo $student['id']; ?>" value="Present" <?php if ($current_status == 'Present') echo 'checked'; ?>>
                                                                    <label class="form-check-label" for="present_<?php echo $student['id']; ?>">Present</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $student['id']; ?>]" id="absent_<?php echo $student['id']; ?>" value="Absent" <?php if ($current_status == 'Absent') echo 'checked'; ?>>
                                                                    <label class="form-check-label" for="absent_<?php echo $student['id']; ?>">Absent</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $student['id']; ?>]" id="leave_<?php echo $student['id']; ?>" value="Leave" <?php if ($current_status == 'Leave') echo 'checked'; ?>>
                                                                    <label class="form-check-label" for="leave_<?php echo $student['id']; ?>">Leave</label>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <button type="submit" class="btn btn-success mt-3">Save Attendance</button>
                                    </form>
                                <?php else: ?>
                                    <p>No students found for your class.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>