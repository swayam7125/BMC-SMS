<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";

// --- START: MARK AS READ LOGIC (Kept from original file) ---
if (isset($_GET['notif_id']) && is_numeric($_GET['notif_id'])) {
    $notification_id = $_GET['notif_id'];
    $current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
    if ($current_user_id) {
        $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt_mark_read->bind_param("ii", $notification_id, $current_user_id);
        $stmt_mark_read->execute();
        $stmt_mark_read->close();
    }
}
// --- END: MARK AS READ LOGIC ---

// --- USER AUTHENTICATION & DATA FETCHING ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if (!$role || !$userId) {
    header("Location: ./login.php");
    exit;
}

$schoolId = null;
$studentStd = null;
$availableStandards = [];
$selected_std = null;
$timetable_grid = [];

switch ($role) {
    case 'student':
        $stmt = $conn->prepare("SELECT school_id, std FROM student WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $schoolId = $row['school_id'];
            $studentStd = $row['std'];
            $selected_std = $studentStd; // For student, standard is fixed
        }
        $stmt->close();
        break;
    case 'teacher':
    case 'schooladmin':
        // Fetch school ID for teacher/admin
        $tableName = ($role === 'teacher') ? 'teacher' : 'principal';
        $stmt = $conn->prepare("SELECT school_id FROM $tableName WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $schoolId = $row['school_id'];
        }
        $stmt->close();

        // Fetch all available standards in the school for the dropdown
        if ($schoolId) {
            $standards_stmt = $conn->prepare("SELECT DISTINCT std FROM student WHERE school_id = ? ORDER BY CAST(std AS UNSIGNED)");
            $standards_stmt->bind_param("i", $schoolId);
            $standards_stmt->execute();
            $result = $standards_stmt->get_result();
            while($row = $result->fetch_assoc()){
                $availableStandards[] = $row['std'];
            }
            $standards_stmt->close();
        }
        // Set selected standard from GET request for teacher/admin
        $selected_std = $_GET['standard'] ?? null;
        break;
}

// --- FETCH TIMETABLE DATA FROM THE NEW `school_timetable` TABLE ---
if ($schoolId && $selected_std) {
    $stmt = $conn->prepare("
        SELECT 
            stt.day_of_week, stt.period_number, stt.subject_name, stt.start_time, stt.end_time, t.teacher_name
        FROM school_timetable stt
        JOIN teacher t ON stt.teacher_id = t.id
        WHERE stt.school_id = ? AND stt.standard = ?
        ORDER BY stt.period_number, FIELD(stt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
    ");
    $stmt->bind_param("is", $schoolId, $selected_std);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Organize data into a grid format for easy display
        $timetable_grid[$row['period_number']][$row['day_of_week']] = $row;
    }
    $stmt->close();
}

$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$total_periods = 1; // Adjust if your school has more/less periods
$pageTitle = 'View Timetable';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <!-- Corrected Font Awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <style>
    .timetable-table th,
    .timetable-table td {
        vertical-align: middle;
        text-align: center;
        min-width: 150px;
    }

    .timetable-table .period-cell {
        font-weight: bold;
        background-color: #f8f9fc;
    }

    .timetable-table .lecture-block {
        padding: 10px;
        border-radius: 5px;
        background-color: #e9f5ff;
        border: 1px solid #bde0ff;
    }

    .timetable-table .lecture-block .subject {
        font-weight: bold;
        color: #0056b3;
    }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Class Timetable</h1>

                    <?php if (in_array($role, ['teacher', 'schooladmin'])): ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="GET" class="form-inline">
                                <label for="standard" class="mr-2">View Timetable for Standard:</label>
                                <select name="standard" id="standard" class="form-control mr-2"
                                    onchange="this.form.submit()">
                                    <option value="">-- Select --</option>
                                    <?php foreach ($availableStandards as $standard): ?>
                                    <option value="<?php echo $standard; ?>"
                                        <?php if ($selected_std == $standard) echo 'selected'; ?>>
                                        <?php echo $standard; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($selected_std): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Weekly Schedule for Standard
                                <?php echo htmlspecialchars($selected_std); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered timetable-table">
                                    <thead>
                                        <tr class="bg-primary text-white">
                                            <th>Period</th>
                                            <?php foreach ($days_of_week as $day) echo "<th>$day</th>"; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($p = 1; $p <= $total_periods; $p++): ?>
                                        <tr>
                                            <td class="period-cell">Period <?php echo $p; ?></td>
                                            <?php foreach ($days_of_week as $day): ?>
                                            <td>
                                                <?php if (isset($timetable_grid[$p][$day])): 
                                                        $lecture = $timetable_grid[$p][$day];
                                                    ?>
                                                <div class="lecture-block">
                                                    <div class="subject">
                                                        <?php echo htmlspecialchars($lecture['subject_name']); ?></div>
                                                    <div class="teacher small text-muted">
                                                        <?php echo htmlspecialchars($lecture['teacher_name']); ?></div>
                                                    <div class="time small font-italic mt-1">
                                                        <?php echo date('h:i A', strtotime($lecture['start_time'])) . ' - ' . date('h:i A', strtotime($lecture['end_time'])); ?>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php elseif($role !== 'student'): ?>
                    <div class="alert alert-info">Please select a standard to view its timetable.</div>
                    <?php else: ?>
                    <div class="alert alert-warning">The timetable has not been set for your class yet.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>