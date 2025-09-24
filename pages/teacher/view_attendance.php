<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

// Check if this is an AJAX request
if (is_ajax_request()) {
    // Start output buffering to capture the HTML
    ob_start();
}

$role = null;
$userId = null;
$errorMessage = '';
$attendance_records = [];
$teacherDetails = null;
$view_date = date('Y-m-d');

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$role || $role !== 'teacher') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

try {
    $stmt = $conn->prepare("SELECT class_teacher_std, school_id FROM teacher WHERE id = ?");
    $stmt->execute([$userId]);
    $teacherDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacherDetails || empty($teacherDetails['class_teacher_std'])) {
        $errorMessage = "Access Denied: You are not assigned as a class teacher.";
    } else {
        $view_date = $_GET['view_date'] ?? date('Y-m-d');

        $att_stmt = $conn->prepare(
            "SELECT s.rollno, s.student_name, a.status 
             FROM attendance a
             JOIN student s ON a.student_id = s.id
             WHERE a.school_id = ? AND a.std = ? AND a.attendance_date = ?
             ORDER BY s.rollno ASC"
        );
        $att_stmt->execute([$teacherDetails['school_id'], $teacherDetails['class_teacher_std'], $view_date]);
        $attendance_records = $att_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $errorMessage = "A database error occurred.";
    error_log("View Teacher Attendance Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>View Class Attendance - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">View Class Attendance</h1>
                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Attendance Records for Class: <?php echo htmlspecialchars($teacherDetails['class_teacher_std']); ?></h6>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="" class="form-inline mb-4">
                                    <div class="form-group"><label for="view_date" class="mr-2">Select Date:</label><input type="date" id="view_date" name="view_date" class="form-control" value="<?php echo htmlspecialchars($view_date); ?>"></div>
                                    <button type="submit" class="btn btn-primary ml-2">View Records</button>
                                </form>
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
                                                            <?php $status = htmlspecialchars($record['status']);
                                                            $badge_class = 'badge-secondary';
                                                            if ($status == 'Present') $badge_class = 'badge-success';
                                                            if ($status == 'Absent') $badge_class = 'badge-danger';
                                                            if ($status == 'Leave') $badge_class = 'badge-warning';
                                                            echo "<span class='badge {$badge_class}'>{$status}</span>"; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach;
                                            else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No attendance records found for <?php echo htmlspecialchars($view_date); ?>.</td>
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
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#attendanceTable').DataTable();
        });
    </script>
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