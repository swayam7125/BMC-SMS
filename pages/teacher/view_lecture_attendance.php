<?php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Authorization: Ensure the user is a logged-in teacher
$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');

if ($role !== 'teacher') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
$teacher_lectures = [];
$attendance_records = [];

// Get the school ID for the logged-in teacher
$stmt_school = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
$stmt_school->bind_param("i", $userId);
$stmt_school->execute();
$school_id_result = $stmt_school->get_result()->fetch_assoc();
if ($school_id_result) {
    $school_id = $school_id_result['school_id'];
}
$stmt_school->close();

// Get the filter values from the URL, with defaults
$view_date = $_GET['view_date'] ?? date('Y-m-d');
$selected_lecture_id = $_GET['lecture_id'] ?? null;

// Fetch all unique lectures for the teacher to populate the filter dropdown
$stmt_lectures = $conn->prepare(
    "SELECT id, standard, period_number, subject_name 
     FROM school_timetable 
     WHERE teacher_id = ? 
     GROUP BY standard, period_number, subject_name 
     ORDER BY standard, period_number"
);
$stmt_lectures->bind_param("i", $userId);
$stmt_lectures->execute();
$teacher_lectures = $stmt_lectures->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_lectures->close();

// If filters are set, fetch the attendance records
if ($selected_lecture_id) {
    // First, get the details of the selected lecture
    $stmt_lecture_details = $conn->prepare("SELECT standard, period_number FROM school_timetable WHERE id = ?");
    $stmt_lecture_details->bind_param("i", $selected_lecture_id);
    $stmt_lecture_details->execute();
    $lecture_details = $stmt_lecture_details->get_result()->fetch_assoc();
    $stmt_lecture_details->close();

    if ($lecture_details) {
        $stmt_att = $conn->prepare(
            "SELECT s.rollno, s.student_name, a.status 
             FROM attendance a
             JOIN student s ON a.student_id = s.id
             WHERE a.teacher_id = ? AND a.attendance_date = ? AND a.standard = ? AND a.period_number = ?
             ORDER BY s.rollno ASC"
        );
        $stmt_att->bind_param("isss", $userId, $view_date, $lecture_details['standard'], $lecture_details['period_number']);
        $stmt_att->execute();
        $attendance_records = $stmt_att->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_att->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>View Lecture Attendance</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">View Lecture Attendance</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Attendance Records</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="view_date" class="mr-2">Date:</label>
                                    <input type="date" id="view_date" name="view_date" class="form-control" value="<?php echo htmlspecialchars($view_date); ?>">
                                </div>
                                <div class="form-group mr-3">
                                    <label for="lecture_id" class="mr-2">Lecture:</label>
                                    <select id="lecture_id" name="lecture_id" class="form-control" required>
                                        <option value="">-- Select a Lecture --</option>
                                        <?php foreach ($teacher_lectures as $lec): ?>
                                            <option value="<?php echo $lec['id']; ?>" <?php if ($selected_lecture_id == $lec['id']) echo 'selected'; ?>>
                                                Std <?php echo htmlspecialchars($lec['standard']); ?> -
                                                Period <?php echo htmlspecialchars($lec['period_number']); ?>
                                                (<?php echo htmlspecialchars($lec['subject_name']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">View Records</button>
                            </form>
                        </div>
                    </div>

                    <?php if ($selected_lecture_id): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Displaying Records for <?php echo htmlspecialchars($view_date); ?></h6>
                        </div>
                        <div class="card-body">
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
                                        <?php if (!empty($attendance_records)): ?>
                                            <?php foreach ($attendance_records as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['rollno']); ?></td>
                                                <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                                <td>
                                                    <?php
                                                    $status = htmlspecialchars($record['status']);
                                                    $badge_class = ($status == 'Present') ? 'badge-success' : 'badge-danger';
                                                    echo "<span class='badge {$badge_class}'>{$status}</span>";
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No attendance records found for the selected lecture and date.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
 <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>