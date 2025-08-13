<?php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// IMPROVEMENT: Set a consistent timezone for all date operations
date_default_timezone_set('Asia/Kolkata');

$role = null;
$userId = null;
$errorMessage = '';
$principalDetails = null;
$teachers_with_details = [];
$all_missing_dates = []; // To hold ALL dates with incomplete attendance

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
    
    $current_date = date('Y-m-d');
    $attendance_date_display = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : $current_date;

    // --- REVISED: Mandatory Past Attendance Check for ALL missing dates ---
    if (empty($errorMessage) && !$edit_teacher_id) { // Only check in bulk mode
        $target_date = new DateTime($attendance_date_display);
        $start_date = new DateTime($target_date->format('Y-m-01'));
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start_date, $interval, $target_date);

        $teacher_count_stmt = $conn->prepare("SELECT COUNT(id) FROM teacher WHERE school_id = ?");
        $teacher_count_stmt->execute([$principalDetails['school_id']]);
        $total_teachers = $teacher_count_stmt->fetchColumn();

        if ($total_teachers > 0) {
            $att_count_stmt = $conn->prepare("SELECT COUNT(teacher_id) FROM teacher_attendance WHERE school_id = ? AND attendance_date = ?");
            foreach ($period as $date) {
                if ($date->format('N') < 7) { // Mon-Sat
                    $date_to_check = $date->format('Y-m-d');
                    $att_count_stmt->execute([$principalDetails['school_id'], $date_to_check]);
                    $recorded_teachers = $att_count_stmt->fetchColumn();
                    if ($recorded_teachers < $total_teachers) {
                        $all_missing_dates[] = $date_to_check;
                    }
                }
            }
        }
    }
    // --- END: Mandatory Past Attendance Check ---

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($all_missing_dates)) {
        $conn->beginTransaction();

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

    if (empty($errorMessage) && empty($all_missing_dates)) {
        if ($edit_teacher_id) {
            $teacher_stmt = $conn->prepare("SELECT id, teacher_name, std, batch FROM teacher WHERE id = ? AND school_id = ?");
            $teacher_stmt->execute([$edit_teacher_id, $principalDetails['school_id']]);
        } else {
            $teacher_stmt = $conn->prepare("SELECT id, teacher_name, std, batch FROM teacher WHERE school_id = ? ORDER BY teacher_name ASC");
            $teacher_stmt->execute([$principalDetails['school_id']]);
        }
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
                    <?php elseif (!empty($all_missing_dates)): ?>
                         <div class="alert alert-warning">
                            <h4 class="alert-heading">Action Required</h4>
                            <p>You cannot mark attendance for <strong><?php echo htmlspecialchars($attendance_date_display); ?></strong> because teacher attendance for the following past date(s) is incomplete:</p>
                            <ul>
                                <?php foreach ($all_missing_dates as $missing_date): ?>
                                    <li><strong><?php echo htmlspecialchars($missing_date); ?></strong></li>
                                <?php endforeach; ?>
                            </ul>
                            <hr>
                            <p class="mb-0">Please start by filling the attendance for <strong><?php echo htmlspecialchars($all_missing_dates[0]); ?></strong>.</p>
                            <a href="teacher_attendence.php?attendance_date=<?php echo htmlspecialchars($all_missing_dates[0]); ?>" class="btn btn-primary mt-3">Go to First Pending Attendance Sheet</a>
                        </div>
                    <?php else: ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                Attendance for 
                                <?php echo ($edit_teacher_id && count($teachers_with_details) > 0) ? htmlspecialchars($teachers_with_details[0]['teacher_name']) : 'Teachers'; ?>
                                on <?php echo htmlspecialchars($attendance_date_display); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                             <?php if ($edit_teacher_id): ?>
                                <p class="text-info">Single Edit Mode: Only one teacher is editable.</p>
                            <?php else: ?>
                                <p class="text-info">Bulk Edit Mode: All teachers are editable.</p>
                            <?php endif; ?>
                            <form method="POST" action="">
                                <div class="form-group row">
                                    <label for="attendanceDateInput" class="col-sm-2 col-form-label">Select Date:</label>
                                    <div class="col-sm-4">
                                        <input type="date" class="form-control" id="attendanceDateInput" name="attendance_date" value="<?php echo htmlspecialchars($attendance_date_display); ?>" max="<?php echo $current_date; ?>">
                                    </div>
                                    <div class="col-sm-6">
                                        <button type="button" id="changeDateBtn" class="btn btn-primary">Change Date</button>
                                    </div>
                                </div>
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
                                                            <label class="form-check-label">Present</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Absent" <?php if ($current_status == 'Absent') echo 'checked'; ?>>
                                                            <label class="form-check-label">Absent</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Half Day" <?php if ($current_status == 'Half Day') echo 'checked'; ?>>
                                                            <label class="form-check-label">Half Day</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Leave" <?php if ($current_status == 'Leave') echo 'checked'; ?>>
                                                            <label class="form-check-label">Leave</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (!empty($teachers_with_details)): ?>
                                    <button type="submit" class="btn btn-success mt-3"><i class="fas fa-save"></i> Save Attendance</button>
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

            $('#changeDateBtn').on('click', function() {
                var selectedDate = $('#attendanceDateInput').val();
                var editTeacherId = '<?php echo $edit_teacher_id; ?>';
                var redirectUrl = 'teacher_attendence.php?attendance_date=' + selectedDate;
                if (editTeacherId) {
                    redirectUrl += '&edit_teacher_id=' + editTeacherId;
                }
                window.location.href = redirectUrl;
            });
        });
    </script>
</body>
</html>