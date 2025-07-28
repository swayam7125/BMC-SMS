<?php
session_start();

// Include necessary files
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Initialize variables
$role = null;
$userId = null;
$errorMessage = '';
$principalDetails = null;

$edit_teacher_id = isset($_GET['edit_teacher_id']) ? (int)$_GET['edit_teacher_id'] : null;

// Cookie and Authorization
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (!$role || $role !== 'schooladmin') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// Fetch principal details
$stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$principalDetails = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$principalDetails || empty($principalDetails['school_id'])) {
    $errorMessage = "Access Denied: You are not assigned to a school.";
}

// === POST HANDLERS for both scenarios ===

// Handle BULK update (from sidebar workflow)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    $attendance_data = $_POST['attendance'];
    $attendance_date = $_POST['attendance_date'];
    $conn->begin_transaction();
    try {
        foreach ($attendance_data as $teacher_id => $status) {
            $check_stmt = $conn->prepare("SELECT attendance_id FROM teacher_attendance WHERE teacher_id = ? AND attendance_date = ?");
            $check_stmt->bind_param("is", $teacher_id, $attendance_date);
            $check_stmt->execute();
            $existing_record = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ($existing_record) {
                $update_stmt = $conn->prepare("UPDATE teacher_attendance SET status = ?, marked_by_user_id = ? WHERE attendance_id = ?");
                $update_stmt->bind_param("sii", $status, $userId, $existing_record['attendance_id']);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                $insert_stmt = $conn->prepare("INSERT INTO teacher_attendance (teacher_id, school_id, attendance_date, status, marked_by_user_id) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("iissi", $teacher_id, $principalDetails['school_id'], $attendance_date, $status, $userId);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
        }
        $conn->commit();
        $_SESSION['success_message'] = "Attendance for " . htmlspecialchars($attendance_date) . " has been successfully saved!";
        header("Location: view_teacher_attendence.php?date=" . urlencode($attendance_date));
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $errorMessage = "Failed to update attendance: " . $e->getMessage();
    }
}

// Handle SINGLE teacher update (from "Edit" button workflow)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['teacher_id'])) {
    $teacher_id_to_update = $_POST['teacher_id'];
    $status = $_POST['status'];
    $attendance_date = $_POST['attendance_date'];
    try {
        $check_stmt = $conn->prepare("SELECT attendance_id FROM teacher_attendance WHERE teacher_id = ? AND attendance_date = ?");
        $check_stmt->bind_param("is", $teacher_id_to_update, $attendance_date);
        $check_stmt->execute();
        $existing_record = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($existing_record) {
            $update_stmt = $conn->prepare("UPDATE teacher_attendance SET status = ?, marked_by_user_id = ? WHERE attendance_id = ?");
            $update_stmt->bind_param("sii", $status, $userId, $existing_record['attendance_id']);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO teacher_attendance (teacher_id, school_id, attendance_date, status, marked_by_user_id) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("iissi", $teacher_id_to_update, $principalDetails['school_id'], $attendance_date, $status, $userId);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        $_SESSION['success_message'] = "Attendance for the teacher has been updated successfully!";
        header("Location: view_teacher_attendence.php?date=" . urlencode($attendance_date));
        exit();
    } catch (Exception $e) {
        $errorMessage = "Failed to update attendance: " . $e->getMessage();
    }
}

// --- Data Fetching for Display ---
$teachers_with_details = [];
$attendance_date_display = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : date('Y-m-d');

if (empty($errorMessage)) {
    $teacher_stmt = $conn->prepare("SELECT id, teacher_name, std, batch FROM teacher WHERE school_id = ? ORDER BY teacher_name ASC");
    $teacher_stmt->bind_param("i", $principalDetails['school_id']);
    $teacher_stmt->execute();
    $teachers_result = $teacher_stmt->get_result();

    $att_stmt = $conn->prepare("SELECT status FROM teacher_attendance WHERE teacher_id = ? AND attendance_date = ?");

    while ($teacher = $teachers_result->fetch_assoc()) {
        $att_stmt->bind_param("is", $teacher['id'], $attendance_date_display);
        $att_stmt->execute();
        $att_result = $att_stmt->get_result()->fetch_assoc();
        $teacher['status'] = $att_result['status'] ?? 'Present';
        $teachers_with_details[] = $teacher;
    }
    $teacher_stmt->close();
    $att_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Update Teacher Attendance - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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

                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div class="d-flex align-items-center">
                                        <form method="GET" action="" class="form-inline mr-3">
                                            <div class="form-group">
                                                <label for="attendance_date" class="mr-2">Date:</label>
                                                <input type="date" id="attendance_date" name="attendance_date" class="form-control" value="<?php echo htmlspecialchars($attendance_date_display); ?>">
                                            </div>
                                            <button type="submit" class="btn btn-primary ml-2">Load</button>
                                        </form>

                                        <div class="form-group">
                                            <label for="batchFilter" class="mr-2">Batch:</label>
                                            <select id="batchFilter" class="form-control">
                                                <option value="">All</option>
                                                <option value="Morning">Morning</option>
                                                <option value="Evening">Evening</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="customSearchBox" class="mr-2">Search:</label>
                                        <input type="text" id="customSearchBox" class="form-control" placeholder="Search teachers...">
                                    </div>
                                </div>


                                <?php if ($edit_teacher_id): // --- SINGLE EDIT MODE --- 
                                ?>
                                    <p class="text-info">Single Edit Mode: Only one teacher is editable.</p>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Teacher Name</th>
                                                    <th>Batch</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($teachers_with_details as $teacher):
                                                    $is_editable = ($teacher['id'] == $edit_teacher_id);
                                                    $row_class = $is_editable ? '' : 'opacity-50 pe-none';
                                                ?>
                                                    <tr class="<?php echo $row_class; ?>">
                                                        <form method="POST" action="">
                                                            <td><?php echo htmlspecialchars($teacher['teacher_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($teacher['batch']); ?></td>
                                                            <td>
                                                                <?php $current_status = $teacher['status']; ?>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="status" value="Present" <?php if ($current_status == 'Present') echo 'checked'; ?> <?php if (!$is_editable) echo 'disabled'; ?>>
                                                                    <label class="form-check-label">Present</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="status" value="Absent" <?php if ($current_status == 'Absent') echo 'checked'; ?> <?php if (!$is_editable) echo 'disabled'; ?>>
                                                                    <label class="form-check-label">Absent</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="status" value="Leave" <?php if ($current_status == 'Leave') echo 'checked'; ?> <?php if (!$is_editable) echo 'disabled'; ?>>
                                                                    <label class="form-check-label">Leave</label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <?php if ($is_editable): ?>
                                                                    <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                                                    <input type="hidden" name="attendance_date" value="<?php echo $attendance_date_display; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-success">Update</button>
                                                                    <a href="view_teacher_attendence.php?date=<?php echo $attendance_date_display; ?>" class="btn btn-sm btn-secondary">Cancel</a>
                                                                <?php endif; ?>
                                                            </td>
                                                        </form>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: // --- BULK EDIT MODE --- 
                                ?>
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
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
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
            var table = $('#dataTable').DataTable({
                "paging": false,
                "info": false,
                "dom": '<"table-responsive"t>'
            });

            $('#customSearchBox').on('keyup', function() {
                table.search(this.value).draw();
            });

            $('#batchFilter').on('change', function() {
                var selectedBatch = $(this).val();
                table.column(1).search(selectedBatch).draw();
            });
        });
    </script>
</body>

</html>