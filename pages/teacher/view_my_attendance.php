<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/ajax_helpers.php';

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if this is an AJAX request
if (is_ajax_request()) {
    // Start output buffering to capture the HTML
    ob_start();
}

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'teacher' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$records = []; // Initialize to an empty array
try {
    // This query is fine for PDO, using '?' as the placeholder
    $query = "SELECT attendance_date, status FROM teacher_attendance WHERE teacher_id = ? ORDER BY attendance_date DESC";

    // --- CORRECTED PDO LOGIC ---
    $stmt = $conn->prepare($query);
    // Pass parameters directly into the execute() method
    $stmt->execute([$userId]);
    // Fetch all results using the correct PDO method
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log the error or show a friendly message
    die("Error fetching attendance records: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Attendance Report</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>
<body id="page-top">
    <div id="wrapper">
<?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?>        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
<?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">My Attendance Report</h1>
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
                                                        if ($record['status'] == 'Present') echo '<span class="badge badge-success">Present</span>';
                                                        elseif ($record['status'] == 'Absent') echo '<span class="badge badge-danger">Absent</span>';
                                                        elseif ($record['status'] == 'Leave') echo '<span class="badge badge-warning">Leave</span>';
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
            </div>
<?php
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?>        </div>
    </div>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/logout_modal.php'; ?>
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>$(document).ready(function() { $('#dataTable').DataTable({"order": []}); });</script>
</body>
<?php
// Add this block at the very end of the file
if (is_ajax_request()) {
    // Get the captured HTML
    $content = ob_get_clean();
    
    // Extract just the main content area for the AJAX response
    if (preg_match('/<div class="container-fluid".*?>(.*?)<\/div>/s', $content, $matches)) {
        echo '<div class="container-fluid">' . $matches[1] . '</div>';
    } else {
        // Fallback if the main container isn't found
        echo $content;
    }
    // Stop the script for AJAX requests
    exit;
}
?>
</html>