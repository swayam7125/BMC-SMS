<?php
// Include necessary files
include_once '../../includes/connect.php'; // Database connection
include_once '../../encryption.php';    // Encryption functions

// Initialize variables
$role = null;
$userId = null;
$errorMessage = '';
$teacherDetails = null;
$students = [];

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
    // PDO Change: Converted to PDO. Note B'1' for boolean TRUE in PostgreSQL.
    $stmt = $conn->prepare("SELECT class_teacher, class_teacher_std, school_id FROM teacher WHERE id = ? AND class_teacher = B'1'");
    $stmt->execute([$userId]);
    $teacherDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacherDetails || empty($teacherDetails['class_teacher_std'])) {
        $errorMessage = "Access Denied: You are not assigned as a class teacher and cannot add attendance.";
    }

    // Handle form submission to save attendance
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errorMessage)) {
        $attendance_date = $_POST['attendance_date'];
        $attendance_data = $_POST['attendance'];
        $class_std = $teacherDetails['class_teacher_std'];
        $school_id = $teacherDetails['school_id'];

        $conn->beginTransaction();

        // PostgreSQL Change: Using ON CONFLICT for INSERT/UPDATE logic
        // Assumes a UNIQUE constraint exists on (student_id, attendance_date)
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

    // Fetch students for the form
    $attendance_date_display = $_GET['attendance_date'] ?? date('Y-m-d');

    if (empty($errorMessage)) {
        // Fetch students
        $student_stmt = $conn->prepare("SELECT id, rollno, student_name FROM student WHERE std = ? AND school_id = ? ORDER BY rollno ASC");
        $student_stmt->execute([$teacherDetails['class_teacher_std'], $teacherDetails['school_id']]);
        $students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch existing attendance records
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
    if ($conn->inTransaction()) {
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