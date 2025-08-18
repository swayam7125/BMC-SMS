<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = null;
$userId = null;
$errorMessage = '';
$principalDetails = null;
$attendance_records = [];

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$role || $role !== 'principal') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

try {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $principalDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$principalDetails || empty($principalDetails['school_id'])) {
        $errorMessage = "Access Denied: You are not assigned to a school.";
    }

    $current_date = date('Y-m-d');
    
    $attendance_date_display = $_GET['date'] ?? $current_date;

    // Server-side check to prevent future dates
    if ($attendance_date_display > $current_date) {
        $attendance_date_display = $current_date;
        $errorMessage = "You cannot view attendance for a future date. The date has been reset to today.";
    }

    if (empty($errorMessage)) {
        $stmt_att = $conn->prepare("
            SELECT 
                t.id AS teacher_id, t.teacher_name, t.batch, t.class_teacher, t.class_teacher_std, ta.status 
            FROM teacher t
            LEFT JOIN teacher_attendance ta ON t.id = ta.teacher_id AND ta.attendance_date = ?
            WHERE t.school_id = ?
            ORDER BY t.teacher_name ASC
        ");
        $stmt_att->execute([$attendance_date_display, $principalDetails['school_id']]);
        $attendance_records = $stmt_att->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $errorMessage = "A database error occurred.";
    error_log("View Teacher Attendance (Principal) Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>View Teacher Attendance - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
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
                    <h1 class="h3 mb-2 text-gray-800">Teacher Attendance History</h1>

                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">View Attendance Records</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                                    <form method="GET" action="" class="form-inline">
                                        <div class="form-group">
                                            <label for="date" class="mr-2">Date:</label>
                                            <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($attendance_date_display); ?>" max="<?php echo $current_date; ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary ml-2"><i class="fas fa-search fa-sm"></i> View</button>
                                    </form>
                                    <a href="teacher_attendence.php?attendance_date=<?php echo htmlspecialchars($attendance_date_display); ?>" class="btn btn-info">
                                        <i class="fas fa-edit"></i> Update Attendance
                                    </a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Teacher Name</th>
                                                <th>Batch</th>
                                                <th>Class Teacher</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($attendance_records)): foreach ($attendance_records as $record): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($record['teacher_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($record['batch']); ?></td>
                                                        <td><?php if ($record['class_teacher']): ?>Yes (Std: <?php echo htmlspecialchars($record['class_teacher_std']); ?>)<?php else: ?>No<?php endif; ?></td>
                                                        <td>
                                                            <?php 
                                                                $status = $record['status'] ?? 'Not Marked';
                                                                $badge_class = 'badge-secondary';
                                                                if ($status == 'Present') $badge_class = 'badge-success';
                                                                if ($status == 'Absent') $badge_class = 'badge-danger';
                                                                if ($status == 'Leave') $badge_class = 'badge-warning';
                                                                if ($status == 'Half Day') $badge_class = 'badge-info';
                                                                echo "<span class='badge {$badge_class} p-2'>" . htmlspecialchars($status) . "</span>"; 
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <a href="teacher_attendence.php?attendance_date=<?php echo htmlspecialchars($attendance_date_display); ?>&edit_teacher_id=<?php echo $record['teacher_id']; ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach;
                                            else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No teachers found for this school.</td>
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
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [] // Disable initial sorting
            });
        });
    </script>
</body>
</html>