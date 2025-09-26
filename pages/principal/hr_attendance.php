<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

date_default_timezone_set('Asia/Kolkata');

$role = null;
$userId = null;
$errorMessage = '';
$principalDetails = null;
$school_id = null;
$all_missing_dates = [];
$is_holiday = false;
$holiday_description = '';
$edit_payroll_id = isset($_GET['edit_payroll_id']) ? $_GET['edit_payroll_id'] : null;
$earliest_joining_date_school = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
if ($role !== 'principal' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

try {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $principalDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $school_id = $principalDetails['school_id'] ?? null;

    if (!$school_id) {
        throw new Exception("Access Denied: You are not assigned to a school.");
    }

    $current_date = date('Y-m-d');
    $attendance_date_display = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : $current_date;

    if ($attendance_date_display > $current_date) {
        $attendance_date_display = $current_date;
        $errorMessage = "You cannot mark attendance for a future date. The date has been reset to today.";
    }

    if (empty($errorMessage)) {
        $holiday_stmt = $conn->prepare("SELECT description FROM holidays WHERE holiday_date = ? AND school_id = ?");
        $holiday_stmt->execute([$attendance_date_display, $school_id]);
        $holiday = $holiday_stmt->fetch(PDO::FETCH_ASSOC);
        if ($holiday) {
            $is_holiday = true;
            $holiday_description = $holiday['description'];
        }
    }

    // --- Mandatory Past Attendance Check ---
    if (empty($errorMessage) && !$is_holiday) {
        $target_date = new DateTime($attendance_date_display);

        $payroll_joining_stmt = $conn->prepare("SELECT MIN(date_of_joining) FROM hr WHERE school_id = ? AND date_of_joining IS NOT NULL");
        $payroll_joining_stmt->execute([$school_id]);
        $first_joining_date = $payroll_joining_stmt->fetchColumn();

        $start_of_month = new DateTime($target_date->format('Y-m-01'));
        $start_date = ($first_joining_date && new DateTime($first_joining_date) > $start_of_month) ? new DateTime($first_joining_date) : $start_of_month;

        if ($start_date < $target_date) {
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start_date, $interval, $target_date);

            $att_count_stmt = $conn->prepare("SELECT COUNT(hr_id) FROM hr_attendance WHERE school_id = ? AND attendance_date = ?");
            $holiday_check_stmt = $conn->prepare("SELECT COUNT(*) FROM holidays WHERE school_id = ? AND holiday_date = ?");
            $payroll_expected_stmt = $conn->prepare("SELECT COUNT(id) FROM hr WHERE school_id = ? AND (date_of_joining IS NULL OR date_of_joining <= ?)");

            foreach ($period as $date) {
                if (date('N', $date->getTimestamp()) < 7) {
                    $date_to_check = $date->format('Y-m-d');

                    $holiday_check_stmt->execute([$school_id, $date_to_check]);
                    if ($holiday_check_stmt->fetchColumn() > 0) {
                        continue;
                    }

                    $payroll_expected_stmt->execute([$school_id, $date_to_check]);
                    $expected_payroll = $payroll_expected_stmt->fetchColumn();

                    if ($expected_payroll == 0) {
                        continue;
                    }

                    $att_count_stmt->execute([$school_id, $date_to_check]);
                    $recorded_payroll = $att_count_stmt->fetchColumn();

                    if ($recorded_payroll < $expected_payroll) {
                        $all_missing_dates[] = $date_to_check;
                    }
                }
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($all_missing_dates) && $school_id && !$is_holiday) {
        $attendance_date = $_POST['attendance_date'];
        if ($attendance_date > $current_date) $attendance_date = $current_date;

        try {
            $conn->beginTransaction();
            $upsert_sql = "INSERT INTO hr_attendance (hr_id, school_id, attendance_date, status, marked_by_user_id)
                           VALUES (?, ?, ?, ?, ?)
                           ON CONFLICT (hr_id, attendance_date)
                           DO UPDATE SET status = EXCLUDED.status, marked_by_user_id = EXCLUDED.marked_by_user_id";
            $stmt_upsert = $conn->prepare($upsert_sql);

            $success_message = '';
            if (isset($_POST['attendance'])) {
                foreach ($_POST['attendance'] as $payroll_id => $details) {
                    $status = $details['status'];

                    // *** FIX: This condition prevents the "Not Applicable" value from being sent to the database ***
                    if ($status !== 'Not Applicable') {
                        $stmt_upsert->execute([$payroll_id, $school_id, $attendance_date, $status, $userId]);
                    }
                }
                $success_message = "Attendance for " . htmlspecialchars($attendance_date) . " saved!";
            }

            $conn->commit();
            header("Location: view_hr_attendance.php?date=" . urlencode($attendance_date) . "&success=" . urlencode($success_message));
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            // Display the specific SQL error for debugging
            $errorMessage = "Failed to update attendance: " . $e->getMessage();
        }
    }

    $payroll_staff_with_details = [];
    $earliest_joining_date_school = null;

    if (empty($errorMessage) && empty($all_missing_dates) && $school_id && !$is_holiday) {
        try {
            // Updated query to get hr staff with joining date.
            $sql = "SELECT p.id, p.hr_name, p.date_of_joining, pa.status
                    FROM hr p
                    LEFT JOIN hr_attendance pa ON p.id = pa.hr_id AND pa.attendance_date = ?
                    WHERE p.school_id = ?
                    ORDER BY p.hr_name ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$attendance_date_display, $school_id]);
            $payroll_staff_with_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Determine the earliest joining date for the date picker minimum attribute.
            $earliest_joining_date_school = new DateTime();
            $found_first = false;
            foreach ($payroll_staff_with_details as $staff) {
                if (!empty($staff['date_of_joining']) && (!$found_first || new DateTime($staff['date_of_joining']) < $earliest_joining_date_school)) {
                    $earliest_joining_date_school = new DateTime($staff['date_of_joining']);
                    $found_first = true;
                }
            }
        } catch (PDOException $e) {
            $errorMessage = "Failed to load attendance data: " . $e->getMessage();
        }
    }
} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Update HR Attendance - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php
        if (!$is_ajax_request) {
            include '../../includes/sidebar.php';
        }
        ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
                if (!$is_ajax_request) {
                    include '../../includes/header.php';
                }
                ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Update HR Attendance</h1>
                        <a href="view_hr_attendance.php?date=<?php echo $attendance_date_display; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-eye fa-sm text-white-50"></i> View History</a>
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
                            <p>You cannot mark attendance for <strong><?php echo htmlspecialchars($attendance_date_display); ?></strong> because HR staff attendance for the following past date(s) is incomplete:</p>
                            <ul>
                                <?php foreach ($all_missing_dates as $missing_date): ?>
                                    <li><strong><?php echo htmlspecialchars($missing_date); ?></strong></li>
                                <?php endforeach; ?>
                            </ul>
                            <hr>
                            <p class="mb-0">Please start by filling the attendance for <strong><?php echo htmlspecialchars($all_missing_dates[0]); ?></strong>.</p>
                            <a href="hr_attendance.php?attendance_date=<?php echo htmlspecialchars($all_missing_dates[0]); ?>" class="btn btn-primary mt-3">Go to First Pending Attendance Sheet</a>
                        </div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    Attendance for HR Staff on <?php echo date('d F, Y', strtotime($attendance_date_display)); ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="text-info">
                                    <?php echo $edit_payroll_id ? 'Editing a single HR staff member\'s attendance.' : 'Bulk Edit Mode: All HR staff members are editable.'; ?>
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
                                            <input type="text" id="customSearchBox" class="form-control" placeholder="Search HR staff...">
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>HR Staff Name</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($payroll_staff_with_details)): ?>
                                                    <tr>
                                                        <td colspan="2" class="text-center">No HR staff found for this school.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($payroll_staff_with_details as $staff):
                                                        $is_pre_joining = !empty($staff['date_of_joining']) && $attendance_date_display < $staff['date_of_joining'];
                                                        $is_disabled = ($edit_payroll_id && $staff['id'] != $edit_payroll_id) || $is_pre_joining;
                                                    ?>
                                                        <tr <?php echo $is_disabled ? 'class="blurred-row"' : ''; ?>>
                                                            <td><?php echo htmlspecialchars($staff['hr_name']); ?></td>
                                                            <td>
                                                                <?php if ($is_pre_joining): ?>
                                                                    <span class='badge badge-secondary p-2'>Joined on <?php echo date('d M, Y', strtotime($staff['date_of_joining'])); ?></span>
                                                                    <input type="hidden" name="attendance[<?php echo $staff['id']; ?>][status]" value="Not Applicable">
                                                                <?php else: ?>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="radio" name="attendance[<?php echo $staff['id']; ?>][status]" value="Present" <?php if ($staff['status'] == 'Present' || !$staff['status']) echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                        <label class="form-check-label">Present</label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="radio" name="attendance[<?php echo $staff['id']; ?>][status]" value="Absent" <?php if ($staff['status'] == 'Absent') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                        <label class="form-check-label">Absent</label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="radio" name="attendance[<?php echo $staff['id']; ?>][status]" value="Leave" <?php if ($staff['status'] == 'Leave') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                        <label class="form-check-label">Leave</label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="radio" name="attendance[<?php echo $staff['id']; ?>][status]" value="Half Day" <?php if ($staff['status'] == 'Half Day') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                        <label class="form-check-label">Half Day</label>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if (!empty($payroll_staff_with_details)): ?>
                                        <button type="submit" name="submit_attendance" class="btn btn-success mt-3"><i class="fas fa-save"></i> Save Attendance</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            if (!$is_ajax_request) {
                include '../../includes/footer.php';
            }
            ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <style>
        .blurred-row {
            filter: blur(1px);
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
                var redirectUrl = 'hr_attendance.php?attendance_date=' + selectedDate;
                var editId = '<?php echo $edit_payroll_id; ?>';
                if (editId) {
                    redirectUrl += '&edit_payroll_id=' + editId;
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