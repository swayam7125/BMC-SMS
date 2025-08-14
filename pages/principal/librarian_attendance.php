<?php
session_start();
// Include necessary files
include_once '../../includes/connect.php'; // This must create a PDO object $conn
include_once '../../encryption.php';

// IMPROVEMENT: Set a consistent timezone for all date operations
date_default_timezone_set('Asia/Kolkata');

// Initialize variables
$role = null;
$userId = null;
$errorMessage = '';
$principalDetails = null;
$school_id = null;
$all_missing_dates = []; // To hold ALL dates with incomplete attendance
$is_holiday = false; // NEW: Holiday flag
$holiday_description = ''; // NEW: Holiday description

// Cookie and Authorization
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (!$role || $role !== 'principal' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

try {
    // Fetch principal's school_id
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

    // NEW: Check if the selected date is a holiday
    if(empty($errorMessage)){
        $holiday_stmt = $conn->prepare("SELECT description FROM holidays WHERE holiday_date = ? AND school_id = ?");
        $holiday_stmt->execute([$attendance_date_display, $school_id]);
        $holiday = $holiday_stmt->fetch(PDO::FETCH_ASSOC);
        if ($holiday) {
            $is_holiday = true;
            $holiday_description = $holiday['description'];
        }
    }

    // --- REVISED: Mandatory Past Attendance Check for ALL missing dates (only if not a holiday) ---
    if (empty($errorMessage) && !$is_holiday) {
        $target_date = new DateTime($attendance_date_display);
        $start_date = new DateTime($target_date->format('Y-m-01'));
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start_date, $interval, $target_date);

        $lib_count_stmt = $conn->prepare("SELECT COUNT(id) FROM librarian WHERE school_id = ?");
        $lib_count_stmt->execute([$school_id]);
        $total_librarians = $lib_count_stmt->fetchColumn();

        if ($total_librarians > 0) {
            $att_count_stmt = $conn->prepare("SELECT COUNT(librarian_id) FROM librarian_attendance WHERE school_id = ? AND attendance_date = ?");
            foreach ($period as $date) {
                if (date('N', $date->getTimestamp()) < 7) { // Mon-Sat
                    $date_to_check = $date->format('Y-m-d');
                    $att_count_stmt->execute([$school_id, $date_to_check]);
                    $recorded_librarians = $att_count_stmt->fetchColumn();
                    if ($recorded_librarians < $total_librarians) {
                        $all_missing_dates[] = $date_to_check;
                    }
                }
            }
        }
    }
    // --- END: Mandatory Past Attendance Check ---
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($all_missing_dates) && $school_id && !$is_holiday) {
        $attendance_date = $_POST['attendance_date'];
        if ($attendance_date > $current_date) $attendance_date = $current_date;

        try {
            $conn->beginTransaction();
            $upsert_sql = "INSERT INTO librarian_attendance (librarian_id, school_id, attendance_date, status, marked_by_user_id)
                           VALUES (?, ?, ?, ?, ?)
                           ON CONFLICT (librarian_id, attendance_date)
                           DO UPDATE SET status = EXCLUDED.status, marked_by_user_id = EXCLUDED.marked_by_user_id";
            $stmt_upsert = $conn->prepare($upsert_sql);

            $success_message = '';
            if (isset($_POST['attendance'])) { // Bulk update
                foreach ($_POST['attendance'] as $librarian_id => $status) {
                    $stmt_upsert->execute([$librarian_id, $school_id, $attendance_date, $status, $userId]);
                }
                // MODIFIED: Prepare success message for GET parameter
                $success_message = "Bulk attendance for " . htmlspecialchars($attendance_date) . " saved!";
            }
            
            $conn->commit();
            // MODIFIED: Redirect with GET parameter instead of SESSION
            header("Location: view_librarian_attendance.php?date=" . urlencode($attendance_date) . "&success=" . urlencode($success_message));
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $errorMessage = "Failed to update attendance: " . $e->getMessage();
        }
    }
    
    $librarians_with_details = [];
    if (empty($errorMessage) && empty($all_missing_dates) && $school_id && !$is_holiday) {
        try {
            $sql = "SELECT id, librarian_name FROM librarian WHERE school_id = ? ORDER BY librarian_name ASC";
            $lib_stmt = $conn->prepare($sql);
            $lib_stmt->execute([$school_id]);
            $all_librarians = $lib_stmt->fetchAll(PDO::FETCH_ASSOC);

            $att_stmt = $conn->prepare("SELECT librarian_id, status FROM librarian_attendance WHERE school_id = ? AND attendance_date = ?");
            $att_stmt->execute([$school_id, $attendance_date_display]);
            $attendance_records_raw = $att_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            foreach ($all_librarians as $librarian) {
                $librarian['status'] = $attendance_records_raw[$librarian['id']] ?? 'Present';
                $librarians_with_details[] = $librarian;
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
    <title>Update Librarian Attendance - School Management System</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Update Librarian Attendance</h1>
                        <a href="view_librarian_attendance.php?date=<?php echo htmlspecialchars($attendance_date_display); ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-eye fa-sm text-white-50"></i> View History</a>
                    </div>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php // NEW: Logic to show holiday message or attendance form ?>
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
                            <p>You cannot mark attendance for <strong><?php echo htmlspecialchars($attendance_date_display); ?></strong> because librarian attendance for the following past date(s) is incomplete:</p>
                            <ul>
                                <?php foreach ($all_missing_dates as $missing_date): ?>
                                    <li><strong><?php echo htmlspecialchars($missing_date); ?></strong></li>
                                <?php endforeach; ?>
                            </ul>
                            <hr>
                            <p class="mb-0">Please start by filling the attendance for <strong><?php echo htmlspecialchars($all_missing_dates[0]); ?></strong>.</p>
                            <a href="librarian_attendance.php?attendance_date=<?php echo htmlspecialchars($all_missing_dates[0]); ?>" class="btn btn-primary mt-3">Go to First Pending Attendance Sheet</a>
                        </div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    Attendance for Librarians on <?php echo htmlspecialchars($attendance_date_display); ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="text-info">Bulk Edit Mode: All librarians are editable.</p>
                                <form method="POST" action="">
                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <div class="form-inline">
                                            <div class="form-group">
                                                <label for="attendance_date" class="mr-2">Date:</label>
                                                <input type="date" id="attendance_date" name="attendance_date" class="form-control" value="<?php echo htmlspecialchars($attendance_date_display); ?>" max="<?php echo $current_date; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" id="customSearchBox" class="form-control" placeholder="Search librarians...">
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead><tr><th>Librarian Name</th><th>Status</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($librarians_with_details as $librarian): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($librarian['librarian_name']); ?></td>
                                                        <td>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $librarian['id']; ?>]" value="Present" <?php if ($librarian['status'] == 'Present') echo 'checked'; ?>><label class="form-check-label">Present</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $librarian['id']; ?>]" value="Absent" <?php if ($librarian['status'] == 'Absent') echo 'checked'; ?>><label class="form-check-label">Absent</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $librarian['id']; ?>]" value="Half Day" <?php if ($librarian['status'] == 'Half Day') echo 'checked'; ?>><label class="form-check-label">Half Day</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $librarian['id']; ?>]" value="Leave" <?php if ($librarian['status'] == 'Leave') echo 'checked'; ?>><label class="form-check-label">Leave</label>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if (!empty($librarians_with_details)): ?>
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
    <?php include_once "../../includes/logout_modal.php"?>
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
            $('#customSearchBox').on('keyup', function(){
                table.search(this.value).draw();
            });

            $('#attendance_date').on('change', function() {
                var selectedDate = $(this).val();
                var redirectUrl = 'librarian_attendance.php?attendance_date=' + selectedDate;
                window.location.href = redirectUrl;
            });
        });
    </script>
</body>
</html>