<?php
// Include necessary files for database connection, encryption, and AJAX helpers.
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/ajax_helpers.php';

// Decrypt the user role from the cookie.
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
// Decrypt the user ID from the cookie.
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

// If the user is not a hr user or if the user ID is not set, redirect to the login page.
if ($role !== 'hr' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// Initialize an array to store attendance records.
$records = []; 
try {
    // Prepare a query to fetch all attendance records for the specified hr user.
    $query = "SELECT attendance_date, status FROM hr_attendance WHERE hr_id = ? ORDER BY attendance_date DESC";

    // Prepare and execute the statement.
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    // Fetch all attendance records.
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log any database errors and display a generic error message.
    error_log("Error fetching HR attendance records: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

// Set the page title.
$page_title = "My Attendance Report";

// If the request is not an AJAX request, include the HTML header.
if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/sidebar.php'; ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/header.php'; ?>
<?php
}
?>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800"><?php echo htmlspecialchars($page_title); ?></h1>
                    
                    <!-- Attendance Records Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">My Records</h6>
                        </div>
                        <div class="card-body">
                             <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($records)): ?>
                                            <tr><td colspan="2" class="text-center">No attendance records found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($records as $record): ?>
                                            <tr>
                                                <td><?php echo date('d M, Y', strtotime($record['attendance_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                        // Display a colored badge based on the attendance status.
                                                        if ($record['status'] == 'Present') echo '<span class="badge badge-success">Present</span>';
                                                        elseif ($record['status'] == 'Absent') echo '<span class="badge badge-danger">Absent</span>';
                                                        elseif ($record['status'] == 'Leave') echo '<span class="badge badge-warning">Leave</span>';
                                                        elseif ($record['status'] == 'Half Day') echo '<span class="badge badge-info">Half Day</span>';
                                                    ?>
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
                <!-- /.container-fluid -->
<?php
// If the request is not an AJAX request, include the HTML footer and scripts.
if (!is_ajax_request()) {
?>
            </div>
            <!-- End of Main Content -->
            <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/footer.php'; ?>
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <!-- Logout Modal-->
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/logout_modal.php'; ?>

    <!-- Core JavaScript-->
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Easing Plugin JavaScript-->
    <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <!-- Page level plugins -->
    <script src="/BMC-SMS/assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <!-- Page level custom scripts -->
    <script>
        // Initialize the DataTable with no default ordering.
        $(document).ready(function() { 
            $('#dataTable').DataTable({"order": []}); 
        });
    </script>
</body>

</html>
<?php
}
?>
