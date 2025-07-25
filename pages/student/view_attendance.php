<?php
// Include necessary files
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Initialize variables
$role = null;
$userId = null;
$attendance_records = [];
$summary = ['Present' => 0, 'Absent' => 0, 'Leave' => 0];

// Retrieve and decrypt user role and ID from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Authorization Check
if (!$role || $role !== 'student') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// Get filter values, default to current month and year
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

// Fetch attendance records for the logged-in student for the filtered month/year
$stmt = $conn->prepare(
    "SELECT attendance_date, status 
     FROM attendance 
     WHERE student_id = ? AND YEAR(attendance_date) = ? AND MONTH(attendance_date) = ?
     ORDER BY attendance_date ASC"
);
$stmt->bind_param("iii", $userId, $filter_year, $filter_month);
$stmt->execute();
$result = $stmt->get_result();
$attendance_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate summary
foreach ($attendance_records as $record) {
    if (isset($summary[$record['status']])) {
        $summary[$record['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Attendance - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
            <link rel="stylesheet" href="../../assets/css/sidebar.css">

</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">My Attendance</h1>

                    <div class="row mb-4">
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Present</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['Present']; ?> Days</div></div></div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Absent</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['Absent']; ?> Days</div></div></div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Leave</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['Leave']; ?> Days</div></div></div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Attendance History</h6></div>
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline mb-4">
                                <div class="form-group mr-2">
                                    <label for="month" class="mr-2">Month:</label>
                                    <select name="month" id="month" class="form-control">
                                        <?php for ($m = 1; $m <= 12; $m++): ?><option value="<?php echo $m; ?>" <?php echo ($m == $filter_month) ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 10)); ?></option><?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <label for="year" class="mr-2">Year:</label>
                                    <select name="year" id="year" class="form-control">
                                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?><option value="<?php echo $y; ?>" <?php echo ($y == $filter_year) ? 'selected' : ''; ?>><?php echo $y; ?></option><?php endfor; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead><tr><th>Date</th><th>Status</th></tr></thead>
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
                                            <tr><td colspan="2" class="text-center">No attendance records found for the selected period.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
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
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [[ 0, "desc" ]] // Order by date descending
            });
        });
    </script>
</body>
</html>