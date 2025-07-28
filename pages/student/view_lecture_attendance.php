<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');

if ($role !== 'student') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$filter_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

// Fetch detailed lecture attendance records
$stmt = $conn->prepare(
    "SELECT attendance_date, period_number, subject, status 
     FROM attendance 
     WHERE student_id = ? AND YEAR(attendance_date) = ? AND MONTH(attendance_date) = ?
     ORDER BY attendance_date DESC, period_number ASC"
);
$stmt->bind_param("iii", $userId, $filter_year, $filter_month);
$stmt->execute();
$attendance_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate percentage-based summary
$total_lectures = count($attendance_records);
$present_count = 0;
foreach ($attendance_records as $record) {
    if ($record['status'] === 'Present') {
        $present_count++;
    }
}
$attendance_percentage = ($total_lectures > 0) ? round(($present_count / $total_lectures) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Lecture Attendance</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <!-- Corrected Font Awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">My Lecture Attendance</h1>

                    <div class="row mb-4">
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Lectures
                                        Held</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_lectures; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Lectures
                                        Attended</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $present_count; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Attendance
                                        Percentage</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $attendance_percentage; ?>%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Attendance Log</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="form-inline mb-4">
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
                                        <?php if (!empty($attendance_records)): ?>
                                        <?php foreach ($attendance_records as $record): ?>
                                        <tr>
                                            <td><?php echo date("d-m-Y", strtotime($record['attendance_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($record['period_number']); ?></td>
                                            <td><?php echo htmlspecialchars($record['subject']); ?></td>
                                            <td>
                                                <?php
                                                        $status = htmlspecialchars($record['status']);
                                                        $badge = 'badge-secondary';
                                                        if ($status == 'Present') $badge = 'badge-success';
                                                        if ($status == 'Absent') $badge = 'badge-danger';
                                                        echo "<span class='badge {$badge}'>{$status}</span>";
                                                        ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No records found.</td>
                                        </tr>
                                        <?php endif; ?>
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
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "order": [
                [0, "desc"]
            ]
        });
    });
    </script>
</body>

</html>