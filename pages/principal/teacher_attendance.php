<?php
// Add debugging for development
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

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
$allow_past_attendance = true; // Flag to allow filling past attendance

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

    // Check if we're trying to fill a past date (not today)
    $is_past_date = $attendance_date_display < $current_date;
    
    if (empty($errorMessage)) {
        $holiday_stmt = $conn->prepare("SELECT description FROM holidays WHERE holiday_date = ? AND school_id = ?");
        $holiday_stmt->execute([$attendance_date_display, $principalDetails['school_id']]);
        $holiday = $holiday_stmt->fetch(PDO::FETCH_ASSOC);
        if ($holiday) {
            $is_holiday = true;
            $holiday_description = $holiday['description'];
        }
    }

    // --- Enhanced Past Attendance Check Logic ---
    if (empty($errorMessage) && !$is_holiday) {
        $target_date = new DateTime($attendance_date_display);
        $current_datetime = new DateTime($current_date);

        // Get the earliest joining date
        $first_joining_stmt = $conn->prepare("SELECT MIN(date_of_joining) FROM teacher WHERE school_id = ? AND date_of_joining IS NOT NULL AND date_of_joining <= ?");
        $first_joining_stmt->execute([$principalDetails['school_id'], $current_date]);
        $first_joining_date = $first_joining_stmt->fetchColumn();

        // Start checking from the beginning of the current month OR the earliest joining date, whichever is later
        $start_of_month = new DateTime($target_date->format('Y-m-01'));
        
        if ($first_joining_date) {
            $first_joining_datetime = new DateTime($first_joining_date);
            $start_date = ($first_joining_datetime > $start_of_month) ? $first_joining_datetime : $start_of_month;
        } else {
            $start_date = $start_of_month;
        }

        // If filling today's attendance, check for ALL missing past dates
if (!$is_past_date) {
    // Check for missing dates from start_date to yesterday
    $yesterday = new DateTime($current_date);
    $yesterday->sub(new DateInterval('P1D'));

    if ($start_date <= $yesterday) {
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start_date, $interval, $current_datetime);

        $att_count_stmt = $conn->prepare("SELECT COUNT(teacher_id) FROM teacher_attendance WHERE school_id = ? AND attendance_date = ?");
        $holiday_check_stmt = $conn->prepare("SELECT COUNT(*) FROM holidays WHERE school_id = ? AND holiday_date = ?");
        $teacher_expected_stmt = $conn->prepare("SELECT COUNT(id) FROM teacher WHERE school_id = ? AND (date_of_joining IS NULL OR date_of_joining <= ?)");

        foreach ($period as $date) {
            // Skip Sundays (day 7)
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
}  else {
            // If filling a past date, allow it (this enables filling missing attendance)
            $allow_past_attendance = true;
        }
    }

    // POST processing - allow if no missing dates OR if filling past attendance
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_holiday && (empty($all_missing_dates) || $allow_past_attendance)) {
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
        
        // Check if it's an AJAX request
        if (is_ajax_request()) {
            echo json_encode(['success' => true, 'message' => $success_message]);
            exit();
        } else {
            // After saving past attendance, redirect back to today if there are no more missing dates
            if ($is_past_date) {
                // Check if there are still missing dates after this save
                $recheck_stmt = $conn->prepare("SELECT COUNT(teacher_id) FROM teacher_attendance WHERE school_id = ? AND attendance_date = ?");
                $recheck_stmt->execute([$principalDetails['school_id'], $attendance_date]);
                $saved_count = $recheck_stmt->fetchColumn();
                
                $teacher_count_stmt = $conn->prepare("SELECT COUNT(id) FROM teacher WHERE school_id = ? AND (date_of_joining IS NULL OR date_of_joining <= ?)");
                $teacher_count_stmt->execute([$principalDetails['school_id'], $attendance_date]);
                $expected_count = $teacher_count_stmt->fetchColumn();
                
                if ($saved_count >= $expected_count) {
                    // This date is now complete, check if we should redirect to next missing date or today
                    $next_missing_date = null;
                    foreach ($all_missing_dates as $missing_date) {
                        if ($missing_date > $attendance_date) {
                            $next_missing_date = $missing_date;
                            break;
                        }
                    }
                    
                    if ($next_missing_date) {
                        header("Location: teacher_attendance.php?attendance_date=" . urlencode($next_missing_date) . "&success=" . urlencode($success_message));
                    } else {
                        header("Location: teacher_attendance.php?success=" . urlencode($success_message . " You can now fill today's attendance."));
                    }
                    exit();
                }
            }
            
            header("Location: view_teacher_attendence.php?date=" . urlencode($attendance_date_display) . "&success=" . urlencode($success_message));
            exit();
        }
    }

    // Load teachers data if no errors and (no missing dates OR filling past attendance)
    if (empty($errorMessage) && !$is_holiday && (empty($all_missing_dates) || $allow_past_attendance)) {
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
    error_log("Teacher Attendance Error: " . $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getFile());
    
    // If debug mode is on, show detailed error
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo "<pre>Error Details:\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "Trace:\n" . $e->getTraceAsString();
        echo "</pre>";
        exit();
    }
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
    <link rel="stylesheet" href="../../assets/css/responsive.css" />

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
                        <h1 class="h3 mb-0 text-gray-800">Update Teacher Attendance</h1>
                        <a href="view_teacher_attendence.php?date=<?php echo htmlspecialchars($attendance_date_display); ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-eye fa-sm text-white-50"></i> View History</a>
                    </div>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                        <div class="alert alert-info">
                            <h5>Debug Information:</h5>
                            <p><strong>Current Date:</strong> <?php echo $current_date; ?></p>
                            <p><strong>Attendance Date:</strong> <?php echo $attendance_date_display; ?></p>
                            <p><strong>Is Past Date:</strong> <?php echo $is_past_date ? 'Yes' : 'No'; ?></p>
                            <p><strong>Is Holiday:</strong> <?php echo $is_holiday ? 'Yes' : 'No'; ?></p>
                            <p><strong>Allow Past Attendance:</strong> <?php echo $allow_past_attendance ? 'Yes' : 'No'; ?></p>
                            <p><strong>Missing Dates Count:</strong> <?php echo count($all_missing_dates); ?></p>
                            <?php if (!empty($all_missing_dates)): ?>
                                <p><strong>Missing Dates:</strong> <?php echo implode(', ', $all_missing_dates); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_holiday): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Attendance for <?php echo htmlspecialchars($attendance_date_display); ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h4 class="alert-heading"><i class="fas fa-calendar-check"></i> Public Holiday</h4>
                                    <p>You cannot mark attendance for this day because it is a public holiday: <strong><?php echo htmlspecialchars($holiday_description); ?></strong>.</p>
                                </div>
                                <div class="mt-3">
                                    <a href="teacher_attendance.php" class="btn btn-primary">
                                        <i class="fas fa-arrow-left"></i> Go to Today's Attendance
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php elseif (!empty($all_missing_dates) && !$is_past_date): ?>                         <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-warning">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i class="fas fa-exclamation-triangle"></i> Action Required - Missing Past Attendance
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning mb-4">
                                    <h4 class="alert-heading"><i class="fas fa-calendar-times"></i> Complete Past Attendance First</h4>
                                    <p class="mb-3">You cannot mark attendance for <strong><?php echo htmlspecialchars($attendance_date_display); ?></strong> because teacher attendance for the following past date(s) is incomplete:</p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="list-unstyled">
                                                <?php 
                                                $half = ceil(count($all_missing_dates) / 2);
                                                for ($i = 0; $i < $half; $i++): 
                                                ?>
                                                    <li class="mb-2">
                                                        <span class="badge badge-danger mr-2"><?php echo ($i + 1); ?></span>
                                                        <strong><?php echo date('d M Y (D)', strtotime($all_missing_dates[$i])); ?></strong>
                                                    </li>
                                                <?php endfor; ?>
                                            </ul>
                                        </div>
                                        <?php if (count($all_missing_dates) > 1): ?>
                                        <div class="col-md-6">
                                            <ul class="list-unstyled">
                                                <?php for ($i = $half; $i < count($all_missing_dates); $i++): ?>
                                                    <li class="mb-2">
                                                        <span class="badge badge-danger mr-2"><?php echo ($i + 1); ?></span>
                                                        <strong><?php echo date('d M Y (D)', strtotime($all_missing_dates[$i])); ?></strong>
                                                    </li>
                                                <?php endfor; ?>
                                            </ul>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <p class="mb-0">
                                            <i class="fas fa-info-circle text-info"></i> 
                                            <strong>Next Step:</strong> Please start by filling attendance for <strong><?php echo date('d M Y', strtotime($all_missing_dates[0])); ?></strong>
                                        </p>
                                        <div>
                                            <span class="badge badge-secondary">
                                                <?php echo count($all_missing_dates); ?> date(s) pending
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="teacher_attendance.php?attendance_date=<?php echo htmlspecialchars($all_missing_dates[0]); ?>" 
                                       class="btn btn-danger btn-lg">
                                        <i class="fas fa-calendar-plus"></i> Fill Attendance for <?php echo date('d M Y', strtotime($all_missing_dates[0])); ?>
                                    </a>
                                    
                                    <?php if (count($all_missing_dates) > 1): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="fas fa-list"></i> Or Choose Another Date
                                        </button>
                                        <div class="dropdown-menu">
                                            <?php foreach ($all_missing_dates as $missing_date): ?>
                                                <a class="dropdown-item" href="teacher_attendance.php?attendance_date=<?php echo htmlspecialchars($missing_date); ?>">
                                                    <?php echo date('d M Y (D)', strtotime($missing_date)); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-calendar-check"></i> Attendance for Teachers on <?php echo htmlspecialchars($attendance_date_display); ?>
                                <?php if ($is_past_date): ?>
                                    <span class="badge badge-warning ml-2">Past Date</span>
                                <?php endif; ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if ($is_past_date): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    You are filling attendance for a past date: <strong><?php echo date('d M Y (D)', strtotime($attendance_date_display)); ?></strong>
                                </div>
                            <?php endif; ?>
                            
                            <p class="text-info mb-3">
                                    <?php echo $edit_teacher_id ? 'Editing a single teacher\'s attendance.' : 'Bulk Edit Mode: All teachers are editable.'; ?>
                                </p>
                            
                                <form method="POST" action="">
                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <div class="form-inline">
                                            <div class="form-group">
                                                <label for="attendance_date" class="mr-2"><i class="fas fa-calendar"></i> Date:</label>
                                                <input type="date" id="attendance_date" name="attendance_date" class="form-control" value="<?php echo htmlspecialchars($attendance_date_display); ?>">
                                            <?php 
                                            // Set minimum date as the earliest joining date or beginning of current month, whichever is later
                                            $min_date = '';
                                            if ($earliest_joining_date_school) {
                                                $current_month_start = new DateTime(date('Y-m-01'));
                                                $min_date = ($earliest_joining_date_school > $current_month_start) ? 
                                                           $earliest_joining_date_school->format('Y-m-d') : 
                                                           $current_month_start->format('Y-m-d');
                                            } else {
                                                $min_date = date('Y-m-01'); // Start of current month if no teachers found
                                            }
                                            echo 'min="' . $min_date . '"';
                                            ?> 
                                            max="<?php echo $current_date; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" id="customSearchBox" class="form-control" placeholder="Search teachers...">
                                        </div>
                                    </div>
                                
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th><i class="fas fa-user"></i> Teacher Name</th>
                                                    <th><i class="fas fa-clock"></i> Batch</th>
                                                    <th><i class="fas fa-check-square"></i> Status</th>
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
                                                                <span class='badge badge-secondary p-2'>
                                                                <i class="fas fa-user-plus"></i> Joined on <?php echo date('d M, Y', strtotime($teacher['date_of_joining'])); ?>
                                                            </span>
                                                                <input type="hidden" name="attendance[<?php echo $teacher['id']; ?>]" value="Not Applicable">
                                                            <?php else:
                                                                $current_status = $teacher['status'];
                                                            ?>
                                                                <div class="<?php echo $is_disabled ? 'disabled-row-content' : ''; ?>">
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Present" <?php if ($current_status == 'Present') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                        <label class="form-check-label text-success">
                                                                        <i class="fas fa-check-circle"></i> Present
                                                                    </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Absent" <?php if ($current_status == 'Absent') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                        <label class="form-check-label text-danger">
                                                                        <i class="fas fa-times-circle"></i> Absent
                                                                    </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Half Day" <?php if ($current_status == 'Half Day') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                        <label class="form-check-label text-warning">
                                                                        <i class="fas fa-clock"></i> Half Day
                                                                    </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="radio" name="attendance[<?php echo $teacher['id']; ?>]" value="Leave" <?php if ($current_status == 'Leave') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                        <label class="form-check-label text-info">
                                                                        <i class="fas fa-calendar-alt"></i> Leave
                                                                    </label>
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
                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                            <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-save"></i> Save Attendance for <?php echo date('d M Y', strtotime($attendance_date_display)); ?>
                                        </button>
                                        
                                        <?php if ($is_past_date): ?>
                                            <a href="teacher_attendance.php" class="btn btn-outline-primary">
                                                <i class="fas fa-arrow-right"></i> Go to Today's Attendance
                                            </a>
                                        <?php endif; ?>
                                    </div>
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
        .disabled-text {
            color: #6c757d;
        }

        .disabled-row-content {
            opacity: 0.6;
            pointer-events: none;
            user-select: none;
        }
        .thead-light th {
            background-color: #f8f9fc;
            border-color: #e3e6f0;
        }
        .badge {
            font-size: 0.875rem;
        }
    </style>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>

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
                var redirectUrl = 'teacher_attendance.php?attendance_date=' + selectedDate;
                var editId = '<?php echo $edit_teacher_id; ?>';
                if (editId) {
                    redirectUrl += '&edit_teacher_id=' + editId;
                }
                
                // Show loading indicator
                $('body').append('<div class="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; justify-content: center; align-items: center; color: white;"><div><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading...</div></div>');
                
                window.location.href = redirectUrl;
            });

            // Auto-refresh success message
            <?php if (isset($_GET['success'])): ?>
            setTimeout(function() {
                $('.alert-success').fadeOut('slow');
            }, 5000);
            <?php endif; ?>
        });
    </script>
</body>
</html>
<?php
// AJAX response handling
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