<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Decrypt user role and ID from cookies, handle cases where they might not be set.
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : '';

// Ensure the user is a student, otherwise redirect to login.
if ($role !== 'student') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// Get filter parameters or use current month/year as default.
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$attendance_records = [];

// Prepare the SQL query to fetch attendance records for the logged-in student.
$query = "SELECT attendance_date, period_number, subject, status 
          FROM attendance 
          WHERE student_id = ? 
            AND EXTRACT(YEAR FROM attendance_date) = ? 
            AND EXTRACT(MONTH FROM attendance_date) = ?
          ORDER BY attendance_date DESC, period_number ASC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId, $filter_year, $filter_month]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log database errors instead of displaying them to the user.
    error_log("DB Error in view_lecture_attendance.php: " . $e->getMessage());
    // Optionally, set an error message to display to the user.
    $page_error = "Could not retrieve attendance data. Please try again later.";
}

// Calculate attendance summary statistics.
$total_lectures = count($attendance_records);
$present_count = 0;
foreach ($attendance_records as $record) {
    if (strtolower($record['status']) === 'present') { // Use strtolower for case-insensitive comparison
        $present_count++;
    }
}
$attendance_percentage = ($total_lectures > 0) ? round(($present_count / $total_lectures) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Lecture Attendance</title>

    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-2 text-gray-800">My Lecture Attendance</h1>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Lectures Held</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_lectures; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Lectures Attended</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $present_count; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Attendance Percentage</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $attendance_percentage; ?>%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Log Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Attendance Log</h6>
                        </div>
                        <div class="card-body">
                            <!-- Filter Form (can be enhanced) -->
                            <form method="GET" class="form-inline mb-4">
                                <!-- Add month and year dropdowns for filtering -->
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Period</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // The foreach loop will simply not output any rows if the array is empty.
                                        // DataTables will handle showing the "No records" message.
                                        foreach ($attendance_records as $record):
                                        ?>
                                            <tr>
                                                <td><?php echo date("d-m-Y", strtotime($record['attendance_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['period_number']); ?></td>
                                                <td><?php echo htmlspecialchars($record['subject']); ?></td>
                                                <td>
                                                    <?php
                                                    $status = htmlspecialchars($record['status']);
                                                    $badge_class = 'badge-secondary'; // Default badge
                                                    if (strtolower($status) == 'present') $badge_class = 'badge-success';
                                                    if (strtolower($status) == 'absent') $badge_class = 'badge-danger';
                                                    echo "<span class='badge {$badge_class}'>{$status}</span>";
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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
        // Initialize the DataTable with custom settings
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [
                    [0, "desc"] // Default order by date descending
                ],
                // FIX: Let DataTables handle the empty table message
                "language": {
                    "emptyTable": "No attendance records found for the selected filter."
                }
            });
        });
    </script>
</body>

</html>