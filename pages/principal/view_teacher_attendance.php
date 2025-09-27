<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
require_once __DIR__ . '/../../includes/ajax_helpers.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

$role = null;
$userId = null;
$errorMessage = '';
$principalDetails = null;
$attendance_records = [];
$is_holiday_for_date = false;
$holiday_description_for_date = '';
$earliest_joining_date = null;

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
    } else {
        // Fetch the earliest joining date for a teacher in this school
        $joining_stmt = $conn->prepare("SELECT MIN(date_of_joining) FROM teacher WHERE school_id = ? AND date_of_joining IS NOT NULL");
        $joining_stmt->execute([$principalDetails['school_id']]);
        $earliest_joining_date = $joining_stmt->fetchColumn();
    }

    $current_date = date('Y-m-d');
    $attendance_date_display = $_GET['date'] ?? $current_date;

    if ($attendance_date_display > $current_date) {
        $attendance_date_display = $current_date;
        $errorMessage = "You cannot view attendance for a future date. The date has been reset to today.";
    }

    if (empty($errorMessage)) {
        $holiday_check_stmt = $conn->prepare("SELECT description FROM holidays WHERE holiday_date = ? AND school_id = ?");
        $holiday_check_stmt->execute([$attendance_date_display, $principalDetails['school_id']]);
        $holiday_info = $holiday_check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($holiday_info) {
            $is_holiday_for_date = true;
            $holiday_description_for_date = $holiday_info['description'];
        }

        $stmt_att = $conn->prepare("
            SELECT 
                t.id AS teacher_id, t.teacher_name, t.batch, t.class_teacher, t.class_teacher_std, t.date_of_joining, ta.status 
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
                                            <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($attendance_date_display); ?>" min="<?php echo htmlspecialchars($earliest_joining_date); ?>" max="<?php echo $current_date; ?>">
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
                                                            $is_pre_joining = $record['date_of_joining'] && $attendance_date_display < $record['date_of_joining'];
                                                            $is_editable = true;

                                                            if ($is_pre_joining) {
                                                                $status = 'Joined on ' . date('d M, Y', strtotime($record['date_of_joining']));
                                                                $badge_class = 'badge-secondary';
                                                                $is_editable = false;
                                                            } elseif ($is_holiday_for_date) {
                                                                $status = 'Holiday';
                                                                $badge_class = 'badge-primary';
                                                                $is_editable = false;
                                                            } else {
                                                                $status = $record['status'] ?? 'Not Marked';
                                                                $badge_class = 'badge-secondary';
                                                                if ($status == 'Present') $badge_class = 'badge-success';
                                                                if ($status == 'Absent') $badge_class = 'badge-danger';
                                                                if ($status == 'Leave') $badge_class = 'badge-warning';
                                                                if ($status == 'Half Day') $badge_class = 'badge-info';
                                                            }
                                                            echo "<span class='badge {$badge_class} p-2'>" . htmlspecialchars($status) . "</span>";
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <a href="teacher_attendence.php?attendance_date=<?php echo htmlspecialchars($attendance_date_display); ?>&edit_teacher_id=<?php echo $record['teacher_id']; ?>" class="btn btn-sm btn-warning <?php echo $is_editable ? '' : 'disabled'; ?>">
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
    <script src="/BMC-SMS/assets/js/global-ajax-filters.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": []
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