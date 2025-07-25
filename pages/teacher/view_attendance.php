<?php
// Include necessary files
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Initialize variables
$role = null;
$userId = null;
$errorMessage = '';
$attendance_records = [];

// Retrieve and decrypt user role and ID from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Authorization Check
if (!$role || $role !== 'teacher') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// Fetch teacher details to find their class
$stmt = $conn->prepare("SELECT class_teacher_std, school_id FROM teacher WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$teacherDetails = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacherDetails || empty($teacherDetails['class_teacher_std'])) {
    $errorMessage = "Access Denied: You are not assigned as a class teacher.";
} else {
    $view_date = isset($_GET['view_date']) ? $_GET['view_date'] : date('Y-m-d');
    
    $att_stmt = $conn->prepare(
        "SELECT s.rollno, s.student_name, a.status 
         FROM attendance a
         JOIN student s ON a.student_id = s.id
         WHERE a.school_id = ? AND a.std = ? AND a.attendance_date = ?
         ORDER BY s.rollno ASC"
    );
    $att_stmt->bind_param("iss", $teacherDetails['school_id'], $teacherDetails['class_teacher_std'], $view_date);
    $att_stmt->execute();
    $attendance_records = $att_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $att_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Class Attendance - School Management System</title>
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
                                    <div class="form-group">
                                        <label for="view_date" class="mr-2">Select Date:</label>
                                        <input type="date" id="view_date" name="view_date" class="form-control" value="<?php echo htmlspecialchars($view_date); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary ml-2">View Records</button>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Roll No</th>
                                                <th>Student Name</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($attendance_records)): ?>
                                                <?php foreach ($attendance_records as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['rollno']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['student_name']); ?></td>
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
            $('#dataTable').DataTable();
        });
    </script>
</body>
</html>