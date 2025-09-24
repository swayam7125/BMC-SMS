<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

// Check if this is an AJAX request
if (is_ajax_request()) {
    // Start output buffering to capture the HTML
    ob_start();
}

// Set a consistent timezone for all date operations
date_default_timezone_set('Asia/Kolkata');

$role = null;
$userId = null;
$errorMessage = '';
$principalDetails = null;
$teachers_with_details = [];
$all_missing_dates = [];
$is_holiday = false;
$holiday_description = '';
$edit_teacher_id = isset($_GET['edit_teacher_id']) ? $_GET['edit_teacher_id'] : null;
$earliest_joining_date_school = null;

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

    if ($attendance_date_display > $current_date) {
        $attendance_date_display = $current_date;
        $errorMessage = "You cannot mark attendance for a future date. The date has been reset to today.";
    }

    if (empty($errorMessage)) {
        $holiday_stmt = $conn->prepare("SELECT description FROM holidays WHERE holiday_date = ? AND school_id = ?");
        $holiday_stmt->execute([$attendance_date_display, $principalDetails['school_id']]);
        $holiday = $holiday_stmt->fetch(PDO::FETCH_ASSOC);
        if ($holiday) {
            $is_holiday = true;
            $holiday_description = $holiday['description'];
        }
    }

    // --- Mandatory Past Attendance Check ---
    if (empty($errorMessage) && !$is_holiday) {
        $target_date = new DateTime($attendance_date_display);

        // Corrected query to prevent future dates from breaking the min attribute
        $first_joining_stmt = $conn->prepare("SELECT MIN(date_of_joining) FROM teacher WHERE school_id = ? AND date_of_joining IS NOT NULL AND date_of_joining <= ?");
        $first_joining_stmt->execute([$principalDetails['school_id'], $current_date]);
        $first_joining_date = $first_joining_stmt->fetchColumn();

        $start_of_month = new DateTime($target_date->format('Y-m-01'));
        $start_date = ($first_joining_date && new DateTime($first_joining_date) > $start_of_month) ? new DateTime($first_joining_date) : $start_of_month;

        if ($start_date < $target_date) {
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start_date, $interval, $target_date);

            $att_count_stmt = $conn->prepare("SELECT COUNT(teacher_id) FROM teacher_attendance WHERE school_id = ? AND attendance_date = ?");
            $holiday_check_stmt = $conn->prepare("SELECT COUNT(*) FROM holidays WHERE school_id = ? AND holiday_date = ?");
            $teacher_expected_stmt = $conn->prepare("SELECT COUNT(id) FROM teacher WHERE school_id = ? AND (date_of_joining IS NULL OR date_of_joining <= ?)");

            foreach ($period as $date) {
                if (date('N', $date->getTimestamp()) < 7) {
                    $date_to_check = $date->format('Y-m-d');

                    $holiday_check_stmt->execute([$principalDetails['school_id'], $date_to_check]);
                    if ($holiday_check_stmt->fetchColumn() > 0) {
                        continue;
                    }

                    $teacher_expected_stmt->execute([$principalDetails['school_id'], $date_to_check]);
                    $expected_teachers = $teacher_expected_stmt->fetchColumn();

                    if ($expected_teachers == 0) {
                        continue;
                    }

                    $att_count_stmt->execute([$principalDetails['school_id'], $date_to_check]);
                    $recorded_teachers = $att_count_stmt->fetchColumn();

                    if ($recorded_teachers < $expected_teachers) {
                        $all_missing_dates[] = $date_to_check;
                    }
                }
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($all_missing_dates) && !$is_holiday) {
        $conn->beginTransaction();

        $upsert_sql = "INSERT INTO teacher_attendance (teacher_id, school_id, attendance_date, status, marked_by_user_id)
                       VALUES (?, ?, ?, ?, ?)
                       ON CONFLICT (teacher_id, attendance_date)
                       DO UPDATE SET status = EXCLUDED.status, marked_by_user_id = EXCLUDED.marked_by_user_id";
        $stmt_upsert = $conn->prepare($upsert_sql);

        $success_message = '';
        if (isset($_POST['attendance'])) {
            $attendance_data = $_POST['attendance'];
            $attendance_date = $_POST['attendance_date'];
            foreach ($attendance_data as $teacher_id => $status) {
                // Ensure all five parameters are always passed
                $stmt_upsert->execute([$teacher_id, $principalDetails['school_id'], $attendance_date, $status, $userId]);
            }
            $success_message = "Attendance for " . htmlspecialchars($attendance_date) . " has been successfully saved!";
        }

        $conn->commit();
        header("Location: view_teacher_attendence.php?date=" . urlencode($attendance_date_display) . "&success=" . urlencode($success_message));
        exit();
    }

    if (empty($errorMessage) && empty($all_missing_dates) && !$is_holiday) {
        $school_id_param = $principalDetails['school_id'];
        $attendance_date_param = $attendance_date_display;

        $teacher_query = "SELECT id, teacher_name, batch, class_teacher, class_teacher_std, date_of_joining FROM teacher WHERE school_id = ? ORDER BY teacher_name ASC";
        $teacher_stmt = $conn->prepare($teacher_query);
        $teacher_stmt->execute([$school_id_param]);
        $teachers_result = $teacher_stmt->fetchAll(PDO::FETCH_ASSOC);

        $earliest_joining_date_school = new DateTime();
        $found_first = false;
        foreach ($teachers_result as $teacher) {
            if ($teacher['date_of_joining'] && (!$found_first || new DateTime($teacher['date_of_joining']) < $earliest_joining_date_school)) {
                $earliest_joining_date_school = new DateTime($teacher['date_of_joining']);
                $found_first = true;
            }
        }

        $att_stmt = $conn->prepare("SELECT status FROM teacher_attendance WHERE teacher_id = ? AND attendance_date = ?");
        foreach ($teachers_result as $teacher) {
            $att_stmt->execute([$teacher['id'], $attendance_date_param]);
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

if (!is_ajax_request()) {
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
                <?php
                }
                ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Update Teacher Attendance</h1>
                        <a href="view_teacher_attendence.php?date=<?php echo htmlspecialchars($attendance_date_display); ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-eye fa-sm text-white-50"></i> View History</a>
                    </div>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php elseif ($is_holiday): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Attendance for <?php echo htmlspecialchars($attendance_date_display); ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h4 class="alert-heading"><i class="fas fa-calendar-check"></i> Public Holiday</h4>
                                    <p>You cannot mark attendance for this day because it is a public holiday: <strong><?php echo htmlspecialchars($holiday_description); ?></strong>.</p>
                                </div>
                            </div>
                        </div>
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
                                Attendance for Teachers on <?php echo htmlspecialchars($attendance_date_display); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="text-info">
                                <?php echo $edit_teacher_id ? 'Editing a single teacher\'s attendance.' : 'Bulk Edit Mode: All teachers are editable.'; ?>
                            </p>
                            <form method="POST" action="">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div class="form-inline">
                                        <div class="form-group">
                                            <label for="attendance_date" class="mr-2">Date:</label>
                                            <input type="date" id="attendance_date" name="attendance_date" class="form-control" value="<?php echo htmlspecialchars($attendance_date_display); ?>" min="<?php echo $earliest_joining_date_school ? $earliest_joining_date_school->format('Y-m-d') : ''; ?>" max="<?php echo $current_date; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <input type="text" id="customSearchBox" class="form-control" placeholder="Search teachers...">
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
                                            <?php foreach ($teachers_with_details as $teacher):
                                                $is_pre_joining = $teacher['date_of_joining'] && $attendance_date_display < $teacher['date_of_joining'];
                                                $is_disabled = ($edit_teacher_id && $teacher['id'] != $edit_teacher_id) || $is_pre_joining;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <span class="<?php echo $is_disabled ? 'disabled-text' : ''; ?>"><?php echo htmlspecialchars($teacher['teacher_name']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="<?php echo $is_disabled ? 'disabled-text' : ''; ?>"><?php echo htmlspecialchars($teacher['batch']); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($is_pre_joining): ?>
                                                            <span class='badge badge-secondary p-2'>Joined on <?php echo date('d M, Y', strtotime($teacher['date_of_joining'])); ?></span>
                                                            <input type="hidden" name="attendance[<?php echo $teacher['id']; ?>]" value="Not Applicable">
                                                        <?php else:
                                                            $current_status = $teacher['status'];
                                                        ?>
                                                            <div class="<?php echo $is_disabled ? 'disabled-row-content' : ''; ?>">
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Present" <?php if ($current_status == 'Present') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                    <label class="form-check-label">Present</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Absent" <?php if ($current_status == 'Absent') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                    <label class="form-check-label">Absent</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Half Day" <?php if ($current_status == 'Half Day') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                    <label class="form-check-label">Half Day</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Leave" <?php if ($current_status == 'Leave') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                    <label class="form-check-label">Leave</label>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
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
<?php
if (!is_ajax_request()) {
?>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <style>
        .disabled-text {
            color: #6c757d;
        }
        .disabled-row-content {
            opacity: 0.6;
            pointer-events: none;
            user-select: none;
        }
    </style>
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

            $('#attendance_date').on('change', function() {
                var selectedDate = $(this).val();
                var redirectUrl = 'teacher_attendence.php?attendance_date=' + selectedDate;
                var editId = '<?php echo $edit_teacher_id; ?>';
                if (editId) {
                    redirectUrl += '&edit_teacher_id=' + editId;
                }
                window.location.href = redirectUrl;
            });
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
<?php
}
?>