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
$edit_librarian_id = isset($_GET['edit_librarian_id']) ? $_GET['edit_librarian_id'] : null;
$earliest_joining_date_school = null;

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
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $principalDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $school_id = $principalDetails['school_id'] ?? null;

    if (!$school_id) {
        throw new Exception("Access Denied: You are not assigned to a school.");
    }

    $current_date = date('Y-m-d');
    $attendance_date_display = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : $current_date;
    $is_past_date = $attendance_date_display < $current_date;

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

    // --- Mandatory Past Attendance Check (only for today's view) ---
    if (empty($errorMessage) && !$is_holiday && !$is_past_date) {
        $current_datetime = new DateTime(); // Check up to today

        $first_joining_stmt = $conn->prepare("SELECT MIN(date_of_joining) FROM librarian WHERE school_id = ? AND date_of_joining IS NOT NULL");
        $first_joining_stmt->execute([$school_id]);
        $first_joining_date = $first_joining_stmt->fetchColumn();

        $start_of_month = new DateTime($current_datetime->format('Y-m-01'));
        $start_date = ($first_joining_date && new DateTime($first_joining_date) > $start_of_month) ? new DateTime($first_joining_date) : $start_of_month;

        if ($start_date < $current_datetime) {
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start_date, $interval, $current_datetime); // Check up to yesterday

            $att_count_stmt = $conn->prepare("SELECT COUNT(librarian_id) FROM librarian_attendance WHERE school_id = ? AND attendance_date = ?");
            $holiday_check_stmt = $conn->prepare("SELECT COUNT(*) FROM holidays WHERE school_id = ? AND holiday_date = ?");
            $lib_expected_stmt = $conn->prepare("SELECT COUNT(id) FROM librarian WHERE school_id = ? AND (date_of_joining IS NULL OR date_of_joining <= ?)");

            foreach ($period as $date) {
                if (date('N', $date->getTimestamp()) < 7) { // Skip Sundays
                    $date_to_check = $date->format('Y-m-d');

                    $holiday_check_stmt->execute([$school_id, $date_to_check]);
                    if ($holiday_check_stmt->fetchColumn() > 0) {
                        continue;
                    }

                    $lib_expected_stmt->execute([$school_id, $date_to_check]);
                    $expected_librarians = $lib_expected_stmt->fetchColumn();

                    if ($expected_librarians == 0) {
                        continue;
                    }

                    $att_count_stmt->execute([$school_id, $date_to_check]);
                    $recorded_librarians = $att_count_stmt->fetchColumn();

                    if ($recorded_librarians < $expected_librarians) {
                        $all_missing_dates[] = $date_to_check;
                    }
                }
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($is_past_date || empty($all_missing_dates)) && $school_id && !$is_holiday) {
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
            if (isset($_POST['attendance'])) {
                foreach ($_POST['attendance'] as $librarian_id => $status) {
                    $stmt_upsert->execute([$librarian_id, $school_id, $attendance_date, $status, $userId]);
                }
                $success_message = "Bulk attendance for " . htmlspecialchars($attendance_date) . " saved!";
            }

            $conn->commit();

            // After saving a past date, find the next missing date or redirect to today
            if ($attendance_date < $current_date) {
                $next_missing_date = null;
                $start_next_check = new DateTime($attendance_date);
                $start_next_check->add(new DateInterval('P1D'));
                $end_check = new DateTime($current_date);

                if ($start_next_check < $end_check) {
                    $period = new DatePeriod($start_next_check, new DateInterval('P1D'), $end_check);
                    $att_count_stmt = $conn->prepare("SELECT COUNT(librarian_id) FROM librarian_attendance WHERE school_id = ? AND attendance_date = ?");
                    $holiday_check_stmt = $conn->prepare("SELECT COUNT(*) FROM holidays WHERE school_id = ? AND holiday_date = ?");
                    $lib_expected_stmt = $conn->prepare("SELECT COUNT(id) FROM librarian WHERE school_id = ? AND (date_of_joining IS NULL OR date_of_joining <= ?)");

                    foreach ($period as $date) {
                        if (date('N', $date->getTimestamp()) < 7) {
                            $date_to_check = $date->format('Y-m-d');
                            $holiday_check_stmt->execute([$school_id, $date_to_check]);
                            if ($holiday_check_stmt->fetchColumn() > 0) continue;

                            $lib_expected_stmt->execute([$school_id, $date_to_check]);
                            if ($lib_expected_stmt->fetchColumn() == 0) continue;

                            $att_count_stmt->execute([$school_id, $date_to_check]);
                            if ($att_count_stmt->fetchColumn() < $lib_expected_stmt->fetchColumn()) {
                                $next_missing_date = $date_to_check;
                                break;
                            }
                        }
                    }
                }

                if ($next_missing_date) {
                    header("Location: librarian_attendance.php?attendance_date=" . urlencode($next_missing_date) . "&success=" . urlencode($success_message));
                } else {
                    header("Location: librarian_attendance.php?success=" . urlencode($success_message . " All past attendance is complete. You can now fill today's attendance."));
                }
                exit();
            }

            // Default behavior for today's attendance
            header("Location: view_librarian_attendance.php?date=" . urlencode($attendance_date) . "&success=" . urlencode($success_message));
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $errorMessage = "Failed to update attendance: " . $e->getMessage();
        }
    }

    $librarians_with_details = [];
    $earliest_joining_date_school = null;

    if (empty($errorMessage) && !$is_holiday && ($is_past_date || empty($all_missing_dates))) {
        try {
            $sql = "SELECT id, librarian_name, date_of_joining FROM librarian WHERE school_id = ? ORDER BY librarian_name ASC";
            $lib_stmt = $conn->prepare($sql);
            $lib_stmt->execute([$school_id]);
            $all_librarians = $lib_stmt->fetchAll(PDO::FETCH_ASSOC);

            $att_stmt = $conn->prepare("SELECT librarian_id, status FROM librarian_attendance WHERE school_id = ? AND attendance_date = ?");
            $att_stmt->execute([$school_id, $attendance_date_display]);
            $attendance_records_raw = $att_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $earliest_joining_date_school = new DateTime();
            $found_first = false;
            foreach ($all_librarians as $librarian) {
                if ($librarian['date_of_joining'] && (!$found_first || new DateTime($librarian['date_of_joining']) < $earliest_joining_date_school)) {
                    $earliest_joining_date_school = new DateTime($librarian['date_of_joining']);
                    $found_first = true;
                }
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
                        <h1 class="h3 mb-0 text-gray-800">Update Librarian Attendance</h1>
                        <a href="view_librarian_attendance.php?date=<?php echo htmlspecialchars($attendance_date_display); ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-eye fa-sm text-white-50"></i> View History</a>
                    </div>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
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
                            </div>
                        </div>
                    <?php elseif (!empty($all_missing_dates) && !$is_past_date): ?>
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
                                    <i class="fas fa-calendar-check"></i> Attendance for Librarians on <?php echo htmlspecialchars($attendance_date_display); ?>
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
                                <p class="text-info">
                                    <?php echo $edit_librarian_id ? 'Editing a single librarian\'s attendance.' : 'Bulk Edit Mode: All librarians are editable.'; ?>
                                </p>
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
                                            <thead>
                                                <tr>
                                                    <th>Librarian Name</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($librarians_with_details as $librarian):
                                                    $is_pre_joining = $librarian['date_of_joining'] && $attendance_date_display < $librarian['date_of_joining'];
                                                    $is_disabled = ($edit_librarian_id && $librarian['id'] != $edit_librarian_id) || $is_pre_joining;
                                                ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($librarian['librarian_name']); ?></td>
                                                        <td>
                                                            <?php if ($is_pre_joining): ?>
                                                                <span class='badge badge-secondary p-2'>Joined on <?php echo date('d M, Y', strtotime($librarian['date_of_joining'])); ?></span>
                                                                <input type="hidden" name="attendance[<?php echo $librarian['id']; ?>]" value="Not Applicable">
                                                            <?php else: ?>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $librarian['id']; ?>]" value="Present" <?php if ($librarian['status'] == 'Present') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                    <label class="form-check-label">Present</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $librarian['id']; ?>]" value="Absent" <?php if ($librarian['status'] == 'Absent') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                    <label class="form-check-label">Absent</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $librarian['id']; ?>]" value="Half Day" <?php if ($librarian['status'] == 'Half Day') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                    <label class="form-check-label">Half Day</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $librarian['id']; ?>]" value="Leave" <?php if ($librarian['status'] == 'Leave') echo 'checked'; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                                                    <label class="form-check-label">Leave</label>
                                                                </div>
                                                            <?php endif; ?>
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
            <?php
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?> 
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
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
                var redirectUrl = 'librarian_attendance.php?attendance_date=' + selectedDate;
                var editId = '<?php echo $edit_librarian_id; ?>';
                if (editId) {
                    redirectUrl += '&edit_librarian_id=' + editId;
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
