<?php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = null;
$userId = null;
$errorMessage = '';
$principalDetails = null;
$teachers_with_details = [];

$edit_teacher_id = isset($_GET['edit_teacher_id']) ? (int)$_GET['edit_teacher_id'] : null;

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

    $attendance_date_display = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : date('Y-m-d');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->beginTransaction();

        // PostgreSQL Change: Using ON CONFLICT for INSERT/UPDATE
        $upsert_sql = "INSERT INTO teacher_attendance (teacher_id, school_id, attendance_date, status, marked_by_user_id) 
                       VALUES (?, ?, ?, ?, ?)
                       ON CONFLICT (teacher_id, attendance_date) 
                       DO UPDATE SET status = EXCLUDED.status, marked_by_user_id = EXCLUDED.marked_by_user_id";
        $stmt_upsert = $conn->prepare($upsert_sql);

        if (isset($_POST['attendance'])) { // Bulk update
            $attendance_data = $_POST['attendance'];
            $attendance_date = $_POST['attendance_date'];
            foreach ($attendance_data as $teacher_id => $status) {
                $stmt_upsert->execute([$teacher_id, $principalDetails['school_id'], $attendance_date, $status, $userId]);
            }
            $_SESSION['success_message'] = "Attendance for " . htmlspecialchars($attendance_date) . " has been successfully saved!";
        } elseif (isset($_POST['teacher_id'])) { // Single update
            $teacher_id_to_update = $_POST['teacher_id'];
            $status = $_POST['status'];
            $attendance_date = $_POST['attendance_date'];
            $stmt_upsert->execute([$teacher_id_to_update, $principalDetails['school_id'], $attendance_date, $status, $userId]);
            $_SESSION['success_message'] = "Attendance for the teacher has been updated successfully!";
        }

        $conn->commit();
        header("Location: view_teacher_attendence.php?date=" . urlencode($attendance_date_display));
        exit();
    }

    if (empty($errorMessage)) {
        $teacher_stmt = $conn->prepare("SELECT id, teacher_name, std, batch FROM teacher WHERE school_id = ? ORDER BY teacher_name ASC");
        $teacher_stmt->execute([$principalDetails['school_id']]);
        $teachers_result = $teacher_stmt->fetchAll(PDO::FETCH_ASSOC);

        $att_stmt = $conn->prepare("SELECT status FROM teacher_attendance WHERE teacher_id = ? AND attendance_date = ?");
        foreach ($teachers_result as $teacher) {
            $att_stmt->execute([$teacher['id'], $attendance_date_display]);
            $att_result = $att_stmt->fetch(PDO::FETCH_ASSOC);
            $teacher['status'] = $att_result['status'] ?? 'Present';
            $teachers_with_details[] = $teacher;
        }
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    $errorMessage = "Failed to update attendance: " . $e->getMessage();
    error_log("Teacher Attendance Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Update Teacher Attendance - School Management System</title>
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Update Teacher Attendance</h1>
                        <a href="view_teacher_attendence.php?date=<?php echo $attendance_date_display; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-eye fa-sm text-white-50"></i> View History</a>
                    </div>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Attendance for Teachers on <?php echo htmlspecialchars($attendance_date_display); ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="text-info">Bulk Edit Mode: All teachers are editable.</p>
                                <form method="POST" action="">
                                    <input type="hidden" name="attendance_date" value="<?php echo $attendance_date_display; ?>">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Teacher Name</th>
                                                    <th>Batch</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($teachers_with_details as $teacher): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($teacher['teacher_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($teacher['batch']); ?></td>
                                                        <td>
                                                            <?php $current_status = $teacher['status']; ?>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Present" <?php if ($current_status == 'Present') echo 'checked'; ?>>
                                                                <label>Present</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Absent" <?php if ($current_status == 'Absent') echo 'checked'; ?>>
                                                                <label>Absent</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Leave" <?php if ($current_status == 'Leave') echo 'checked'; ?>>
                                                                <label>Leave</label>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if (!empty($teachers_with_details)): ?>
                                        <button type="submit" class="btn btn-success mt-3"><i class="fas fa-save"></i> Save All Attendance</button>
                                    <?php endif; ?>
                                </form>
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
            var table = $('#dataTable').DataTable({
                "paging": false,
                "info": false,
                "dom": '<"table-responsive"t>'
            });
            $('#customSearchBox').on('keyup', function() {
                table.search(this.value).draw();
            });
            $('#batchFilter').on('change', function() {
                table.column(1).search($(this).val()).draw();
            });
        });
    </script>
</body>

</html>