<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';

$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
if ($role !== 'principal') {
    header("Location: /BMC-SMS/login.php");
    exit();
}
$principal_id = decrypt_id($_COOKIE['encrypted_user_id']);

$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');
$attendance_records = [];
$principal_name = 'Principal';
$school_name = 'Your School';

try {
    $info_stmt = $conn->prepare("SELECT p.principal_name, s.school_name FROM principal p JOIN school s ON p.school_id = s.id WHERE p.id = ?");
    $info_stmt->execute([$principal_id]);
    if ($principal_info = $info_stmt->fetch(PDO::FETCH_ASSOC)) {
        $principal_name = $principal_info['principal_name'];
        $school_name = $principal_info['school_name'];
    }

    $query = "
        SELECT 
            pa.attendance_date, pa.status, pa.login_time, pa.login_latitude, pa.login_longitude
        FROM principal_attendance pa
        WHERE pa.principal_id = ? 
          AND EXTRACT(MONTH FROM pa.attendance_date) = ? 
          AND EXTRACT(YEAR FROM pa.attendance_date) = ?
        ORDER BY pa.attendance_date DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$principal_id, $selected_month, $selected_year]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Principal My Attendance Error: " . $e->getMessage());
    die("A database error occurred.");
}

$page_title = "My Attendance Report";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800"><?php echo htmlspecialchars($page_title); ?></h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter by Month and Year</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group"><label for="month">Month</label><select name="month" id="month" class="form-control"><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?php echo $m; ?>" <?php echo ($selected_month == $m) ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 10)); ?></option><?php endfor; ?></select></div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group"><label for="year">Year</label><select name="year" id="year" class="form-control"><?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?><option value="<?php echo $y; ?>" <?php echo ($selected_year == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option><?php endfor; ?></select></div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group"><label>&nbsp;</label><button type="submit" class="btn btn-primary btn-block">Filter</button></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Login Time</th>
                                            <th>Login Location</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($attendance_records)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No attendance records found for the selected period.</td>
                                            </tr>
                                            <?php else: foreach ($attendance_records as $record): ?>
                                                <tr>
                                                    <td><?php echo date('d M, Y', strtotime($record['attendance_date'])); ?></td>
                                                    <td><span class="badge badge-<?php echo ($record['status'] == 'Present') ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                                    <td><?php echo $record['login_time'] ? date('h:i A', strtotime($record['login_time'])) : 'N/A'; ?></td>
                                                    <td>
                                                        <?php if ($record['login_latitude'] && $record['login_longitude']): ?>
                                                            <a href="https://maps.google.com/?q=<?php echo htmlspecialchars($record['login_latitude']); ?>,<?php echo htmlspecialchars($record['login_longitude']); ?>" target="_blank">View on Map</a>
                                                        <?php else: ?> N/A <?php endif; ?>
                                                    </td>
                                                </tr>
                                        <?php endforeach;
                                        endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/logout_modal.php'; ?>
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": []
            });
        });
    </script>
</body>

</html>