<?php
// --- START: CORRECTED CORE FILE INCLUDES ---
// Using absolute paths is more reliable than relative paths like ../../
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';

// For debugging - It's good practice to have this on new pages
error_reporting(E_ALL);
ini_set('display_errors', 1);
// --- END: CORRECTED CORE FILE INCLUDES ---


// Check if the user is a Super Admin admin
if (!isset($_COOKIE['encrypted_user_role']) || decrypt_id($_COOKIE['encrypted_user_role']) !== 'superadmin') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// --- FILTERING LOGIC ---
$selected_school_id = $_GET['school_id'] ?? '';
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

// Fetch all schools for the filter dropdown
$schools_query = "SELECT id, school_name FROM school ORDER BY school_name ASC";
$schools_result = mysqli_query($conn, $schools_query);
$schools = mysqli_fetch_all($schools_result, MYSQLI_ASSOC);

// --- DATA FETCHING LOGIC ---
$query = "
    SELECT 
        pa.attendance_date, 
        pa.status, 
        pa.login_time, 
        pa.login_latitude, 
        pa.login_longitude,
        p.principal_name,
        s.school_name
    FROM principal_attendance pa
    JOIN principal p ON pa.principal_id = p.id
    JOIN school s ON pa.school_id = s.id
    WHERE MONTH(pa.attendance_date) = ? AND YEAR(pa.attendance_date) = ?
";

$params = [$selected_month, $selected_year];
$param_types = "ii";

if (!empty($selected_school_id)) {
    $query .= " AND pa.school_id = ?";
    $params[] = $selected_school_id;
    $param_types .= "i";
}

$query .= " ORDER BY pa.attendance_date DESC, s.school_name ASC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance_records = mysqli_fetch_all($result, MYSQLI_ASSOC);

$page_title = "Principal Attendance Records";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Using absolute paths for assets as well for consistency -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <!-- <link href="/BMC-SMS/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css"> -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css"> <!-- If you have custom styles -->

</head>

<body id="page-top">

    <div id="wrapper">
        <!-- Sidebar -->
        <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/header.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800"><?php echo htmlspecialchars($page_title); ?></h1>

                    <!-- Filter Form Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Attendance</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="school_id">School</label>
                                            <select name="school_id" id="school_id" class="form-control">
                                                <option value="">All Schools</option>
                                                <?php foreach ($schools as $school): ?>
                                                <option value="<?php echo $school['id']; ?>" <?php echo ($selected_school_id == $school['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($school['school_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="month">Month</label>
                                            <select name="month" id="month" class="form-control">
                                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <option value="<?php echo $m; ?>" <?php echo ($selected_month == $m) ? 'selected' : ''; ?>>
                                                    <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="year">Year</label>
                                            <select name="year" id="year" class="form-control">
                                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                                <option value="<?php echo $y; ?>" <?php echo ($selected_year == $y) ? 'selected' : ''; ?>>
                                                    <?php echo $y; ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                        <label>&nbsp;</label><button type="submit" class="btn btn-primary btn-block">Filter</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Attendance Table Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Principal Name</th>
                                            <th>School Name</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Login Time</th>
                                            <th>Login Location</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($attendance_records)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No attendance records found for the selected filters.</td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($attendance_records as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['principal_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['school_name']); ?></td>
                                            <td><?php echo date('d M, Y', strtotime($record['attendance_date'])); ?></td>
                                            <td>
                                                <?php if ($record['status'] == 'Present'): ?>
                                                <span class="badge badge-success">Present</span>
                                                <?php else: ?>
                                                <span class="badge badge-danger">Absent</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('h:i A', strtotime($record['login_time'])); ?></td>
                                            <td>
                                                <?php if ($record['login_latitude'] && $record['login_longitude']): ?>
                                                <a href="https://www.google.com/maps?q=<?php echo htmlspecialchars($record['login_latitude']); ?>,<?php echo htmlspecialchars($record['login_longitude']); ?>" target="_blank">View on Map</a>
                                                <?php else: ?>
                                                N/A
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/footer.php'; ?>
        </div>
    </div>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal (if you have one) -->
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/logout_modal.php'; ?>

    <!-- Core JavaScript-->
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <!-- Page level plugins -->
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
