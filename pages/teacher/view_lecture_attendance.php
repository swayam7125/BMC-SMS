<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

date_default_timezone_set('Asia/Kolkata');

$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');

if ($role !== 'teacher') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
$teacher_lectures = [];
$attendance_records = [];
$errorMessage = '';
$is_holiday = false;
$holiday_description = '';

try {
    $stmt_school = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
    $stmt_school->execute([$userId]);
    $school_id_result = $stmt_school->fetch(PDO::FETCH_ASSOC);
    if ($school_id_result) {
        $school_id = $school_id_result['school_id'];
    }

    if (!$school_id) {
        throw new Exception("Could not determine school for teacher.");
    }

    $view_date = $_GET['view_date'] ?? date('Y-m-d');
    $selected_lecture_id = $_GET['lecture_id'] ?? null;

    $holiday_stmt = $conn->prepare("SELECT description FROM holidays WHERE holiday_date = ? AND school_id = ?");
    $holiday_stmt->execute([$view_date, $school_id]);
    $holiday = $holiday_stmt->fetch(PDO::FETCH_ASSOC);
    if ($holiday) {
        $is_holiday = true;
        $holiday_description = $holiday['description'];
    }

    $stmt_lectures = $conn->prepare(
        "SELECT MIN(id) AS id, standard, period_number, subject_name 
         FROM school_timetable 
         WHERE teacher_id = ? 
         GROUP BY standard, period_number, subject_name 
         ORDER BY standard, period_number"
    );
    $stmt_lectures->execute([$userId]);
    $teacher_lectures = $stmt_lectures->fetchAll(PDO::FETCH_ASSOC);

    if ($selected_lecture_id && !$is_holiday) {
        $stmt_lecture_details = $conn->prepare("SELECT standard, period_number FROM school_timetable WHERE id = ?");
        $stmt_lecture_details->execute([$selected_lecture_id]);
        $lecture_details = $stmt_lecture_details->fetch(PDO::FETCH_ASSOC);

        if ($lecture_details) {
            $stmt_att = $conn->prepare(
                "SELECT s.rollno, s.student_name, a.status 
                 FROM attendance a
                 JOIN student s ON a.student_id = s.id
                 WHERE a.teacher_id = ? AND a.attendance_date = ? AND a.standard = ? AND a.period_number = ?
                 ORDER BY s.rollno ASC"
            );
            $stmt_att->execute([$userId, $view_date, $lecture_details['standard'], $lecture_details['period_number']]);
            $attendance_records = $stmt_att->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    $errorMessage = "A database error occurred: " . $e->getMessage();
    error_log("View Lecture Attendance Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>View Lecture Attendance</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                    <?php if ($errorMessage): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Attendance Records</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline" id="attendanceFilterForm">
                                <div class="form-group mr-3">
                                    <label for="view_date" class="mr-2">Date:</label>
                                    <input type="date" id="view_date" name="view_date" class="form-control"
                                        value="<?php echo htmlspecialchars($view_date); ?>">
                                </div>
                                <div class="form-group mr-3">
                                    <label for="lecture_id" class="mr-2">Lecture:</label>
                                    <select id="lecture_id" name="lecture_id" class="form-control" <?php echo $is_holiday ? 'disabled' : 'required'; ?>>
                                        <option value="">-- Select a Lecture --</option>
                                        <?php foreach ($teacher_lectures as $lec): ?>
                                        <option value="<?php echo $lec['id']; ?>"
                                            <?php if ($selected_lecture_id == $lec['id']) echo 'selected'; ?>>
                                            Std <?php echo htmlspecialchars($lec['standard']); ?> -
                                            Period <?php echo htmlspecialchars($lec['period_number']); ?>
                                            (<?php echo htmlspecialchars($lec['subject_name']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary" <?php echo $is_holiday ? 'disabled' : ''; ?>>View Records</button>
                            </form>
                        </div>
                    </div>

                    <?php if ($is_holiday): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Attendance Records for
                                <?php echo htmlspecialchars($view_date); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h4 class="alert-heading"><i class="fas fa-calendar-check"></i> Public Holiday</h4>
                                <p>No attendance records are available for this day because it is a public holiday:
                                    <strong><?php echo htmlspecialchars($holiday_description); ?></strong>.
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($selected_lecture_id): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Displaying Records for
                                <?php echo htmlspecialchars($view_date); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Roll No</th>
                                            <th>Student Name</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($attendance_records)): foreach ($attendance_records as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['rollno']); ?></td>
                                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                            <td>
                                                <?php
                                                            $status = htmlspecialchars($record['status']);
                                                            $badge_class = 'badge-secondary';
                                                            if ($status == 'Present') $badge_class = 'badge-success';
                                                            elseif ($status == 'Absent') $badge_class = 'badge-danger';
                                                            elseif ($status == 'Leave') $badge_class = 'badge-warning';
                                                            echo "<span class='badge {$badge_class}'>{$status}</span>";
                                                            ?>
                                            </td>
                                        </tr>
                                        <?php endforeach;
                                            else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No attendance records found for the
                                                selected lecture and date.</td>
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
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#attendanceTable').DataTable();
            
            // Set max date for the date picker
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('view_date').setAttribute('max', today);

            // Handle date change event
            $('#view_date').on('change', function() {
                // Get the current URL and the selected date
                var url = new URL(window.location.href);
                var selectedDate = $(this).val();

                // Set the new date as a URL parameter
                url.searchParams.set('view_date', selectedDate);
                
                // Clear the lecture_id parameter to ensure the holiday check is performed first
                url.searchParams.delete('lecture_id');

                // Reload the page
                window.location.href = url.toString();
            });
        });
    </script>
</body>

</html> 