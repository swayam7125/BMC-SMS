<?php
// Include necessary files
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Initialize variables
$role = null;

// Retrieve and decrypt user role from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Authorization Check: Only allow 'schooladmin'
if (!$role || $role !== 'schooladmin') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// Fetch students for the principal's school
$students = [];
// Assuming the principal's school_id is available in the session or can be retrieved
// For demonstration, let's assume a function get_principal_school_id() exists
$principal_school_id = 1; // Example school_id
$stmt_students = $conn->prepare("SELECT id, student_name FROM students WHERE school_id = ? ORDER BY student_name ASC");
$stmt_students->bind_param("i", $principal_school_id);
$stmt_students->execute();
$result_students = $stmt_students->get_result();
$students = $result_students->fetch_all(MYSQLI_ASSOC);
$stmt_students->close();

$attendance_records = [];
$summary = ['Present' => 0, 'Absent' => 0, 'Leave' => 0];
$selected_student_id = null;

// Get filter values
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
if (isset($_GET['student_id'])) {
    $selected_student_id = intval($_GET['student_id']);

    // Fetch attendance records for the selected student for the filtered month/year
    $stmt_attendance = $conn->prepare(
        "SELECT attendance_date, status 
         FROM attendance 
         WHERE student_id = ? AND YEAR(attendance_date) = ? AND MONTH(attendance_date) = ?
         ORDER BY attendance_date ASC"
    );
    $stmt_attendance->bind_param("iii", $selected_student_id, $filter_year, $filter_month);
    $stmt_attendance->execute();
    $result_attendance = $stmt_attendance->get_result();
    $attendance_records = $result_attendance->fetch_all(MYSQLI_ASSOC);
    $stmt_attendance->close();

    // Calculate summary
    foreach ($attendance_records as $record) {
        if (isset($summary[$record['status']])) {
            $summary[$record['status']]++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Student Attendance - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

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
                    <h1 class="h3 mb-2 text-gray-800">View Student Attendance</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Student and Period</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline mb-4">
                                <div class="form-group mr-2">
                                    <label for="student_id" class="mr-2">Student:</label>
                                    <select name="student_id" id="student_id" class="form-control" required>
                                        <option value="">Select a Student</option>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?php echo $student['id']; ?>" <?php echo ($student['id'] == $selected_student_id) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($student['student_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <label for="month" class="mr-2">Month:</label>
                                    <select name="month" id="month" class="form-control">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo $m; ?>" <?php echo ($m == $filter_month) ? 'selected' : ''; ?>>
                                                <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <label for="year" class="mr-2">Year:</label>
                                    <select name="year" id="year" class="form-control">
                                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y == $filter_year) ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                        </div>
                    </div>

                    <?php if ($selected_student_id): ?>
                    <div class="row mb-4">
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Present</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['Present']; ?> Days</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Absent</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['Absent']; ?> Days</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Leave</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['Leave']; ?> Days</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Attendance History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($attendance_records)): ?>
                                            <?php foreach ($attendance_records as $record): ?>
                                                <tr>
                                                    <td><?php echo date("l, F j, Y", strtotime($record['attendance_date'])); ?></td>
                                                    <td>
                                                        <?php
                                                        $status = htmlspecialchars($record['status']);
                                                        $badge_class = 'badge-secondary';
                                                        if ($status == 'Present') $badge_class = 'badge-success';
                                                        if ($status == 'Absent') $badge_class = 'badge-danger';
                                                        if ($status == 'Leave') $badge_class = 'badge-warning';
                                                        echo "<span class='badge {$badge_class}'>{$status}</span>";
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="2" class="text-center">No attendance records found for the selected period.</td>
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
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
</body>
</html>