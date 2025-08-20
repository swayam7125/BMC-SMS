<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/ajax_helpers.php";

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
$total_periods = 0;
$teacher_timings = [];

// NEW VARIABLES FOR TEACHER ROLE
$is_class_teacher = false;
$class_teacher_std = null;
$teacher_id = null;

try {
    if (isset($_GET['notif_id']) && is_numeric($_GET['notif_id'])) {
        $notification_id = $_GET['notif_id'];
        $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt_mark_read->execute([$notification_id, $userId]);
    }

    switch ($role) {
        case 'student':
            $stmt = $conn->prepare("SELECT school_id, std FROM student WHERE id = ?");
            $stmt->execute([$userId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $schoolId = $row['school_id'];
                $studentStd = $row['std'];
                $selected_std = $studentStd;
            }
            break;

        case 'teacher':
            $stmt = $conn->prepare("SELECT school_id, class_teacher, class_teacher_std FROM teacher WHERE id = ?");
            $stmt->execute([$userId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $schoolId = $row['school_id'];
                $is_class_teacher = (bool)$row['class_teacher'];
                $class_teacher_std = $row['class_teacher_std'];
                $teacher_id = $userId;
            }
            $query_timings = "SELECT * FROM teacher_timings WHERE teacher_id = ?";
            $stmt_timings = $conn->prepare($query_timings);
            $stmt_timings->execute([$userId]);
            while ($row_timing = $stmt_timings->fetch(PDO::FETCH_ASSOC)) {
                $teacher_timings[$row_timing['day_of_week']] = $row_timing;
            }
        case 'principal':
            $tableName = ($role === 'teacher') ? 'teacher' : 'principal';
            $stmt = $conn->prepare("SELECT school_id FROM $tableName WHERE id = ?");
            $stmt->execute([$userId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $schoolId = $row['school_id'];
            }

            if ($schoolId) {
                $sql_standards = "
                    SELECT std FROM student 
                    WHERE school_id = ? 
                    GROUP BY std 
                    ORDER BY 
                        CASE WHEN std ~ '^[0-9]+$' THEN 1 ELSE 2 END, 
                        CASE WHEN std ~ '^[0-9]+$' THEN CAST(std AS INTEGER) ELSE NULL END, 
                        std
                ";
                $standards_stmt = $conn->prepare($sql_standards);
                $standards_stmt->execute([$schoolId]);
                while ($row = $standards_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $availableStandards[] = $row['std'];
                }
            }
            
            if (isset($_GET['standard'])) {
                $selected_std = $_GET['standard'];
            } elseif ($role === 'teacher' && $is_class_teacher && $class_teacher_std) {
                $selected_std = $class_teacher_std;
            } else {
                $selected_std = $_GET['standard'] ?? null;
            }

            break;
    }

    if ($schoolId && $selected_std) {
        $query = "
            SELECT 
                stt.day_of_week, stt.period_number, stt.subject_name, stt.start_time, stt.end_time, t.teacher_name, stt.teacher_id
            FROM school_timetable stt
            JOIN teacher t ON stt.teacher_id = t.id
            WHERE stt.school_id = ? AND stt.standard = ?
            ORDER BY stt.period_number, 
                     CASE stt.day_of_week 
                         WHEN 'Monday' THEN 1 WHEN 'Tuesday' THEN 2 WHEN 'Wednesday' THEN 3 
                         WHEN 'Thursday' THEN 4 WHEN 'Friday' THEN 5 WHEN 'Saturday' THEN 6 
                         ELSE 7 
                     END
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([$schoolId, $selected_std]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $timetable_grid[$row['period_number']][$row['day_of_week']] = $row;
            if ($row['period_number'] > $total_periods) {
                $total_periods = $row['period_number'];
            }
        }
    }
} catch (PDOException $e) {
    error_log("DB Error in view_timetable.php: " . $e->getMessage());
    die("A database error has occurred. Please check the logs.");
}

$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$pageTitle = 'View Timetable';

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
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
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .timetable-table .lecture-block .subject {
            font-weight: bold;
            color: #0056b3;
        }

        .table-timings th {
            width: 30%;
        }

        .table-timings td {
            width: 70%;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <?php
                }
                ?>
                <div class="container-fluid">
                    <?php if (in_array($role, ['teacher', 'principal'])): ?>
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <form method="GET" class="form-inline">
                                    <label for="standard" class="mr-2">View Class Timetable for Standard:</label>
                                    <select name="standard" id="standard" class="form-control mr-2"
                                        onchange="this.form.submit()">
                                        <option value="">-- Select --</option>
                                        <?php foreach ($availableStandards as $standard): ?>
                                            <option value="<?php echo htmlspecialchars($standard); ?>"
                                                <?php if ($selected_std == $standard) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($standard); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($selected_std && !empty($timetable_grid)): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Weekly Class Schedule for Standard
                                    <?php echo htmlspecialchars($selected_std); ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered timetable-table" id="timetableTable" width="100%" cellspacing="0">
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

                                                                if ($role === 'teacher'):
                                                                    if (($is_class_teacher && $selected_std == $class_teacher_std) || ($lecture['teacher_id'] == $teacher_id)): ?>
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
                                                                <?php else: ?>
                                                                    <div class="lecture-block">
                                                                        <div class="subject">
                                                                            <?php echo htmlspecialchars($lecture['subject_name']); ?></div>
                                                                        <div class="teacher small text-muted">
                                                                            <?php echo htmlspecialchars($lecture['teacher_name']); ?></div>
                                                                        <div class="time small font-italic mt-1">
                                                                            <?php echo date('h:i A', strtotime($lecture['start_time'])) . ' - ' . date('h:i A', strtotime($lecture['end_time'])); ?>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
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
                    <?php endif; ?>
                    
                    <?php if ($role === 'teacher' && !isset($_GET['standard'])): ?>
                        <div class="alert alert-info">Please select a standard to view its class timetable.</div>
                        <h1 class="h3 mb-4 text-gray-800">Timetable</h1>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-clock mr-2"></i>Your Personal Weekly Schedule</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered table-striped table-timings">
                                    <tbody>
                                        <?php
                                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                        foreach ($days as $day):
                                            $day_timing = $teacher_timings[$day] ?? null;
                                        ?>
                                            <tr>
                                                <th><?php echo $day; ?></th>
                                                <td>
                                                    <?php if ($day_timing && !empty($day_timing['is_closed'])): ?>
                                                        <span class="badge badge-danger">Closed</span>
                                                    <?php elseif ($day_timing && !empty($day_timing['opens_at'])): ?>
                                                        <?php echo date("g:i A", strtotime($day_timing['opens_at'])); ?> - <?php echo date("g:i A", strtotime($day_timing['closes_at'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not Set</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php elseif ($role === 'principal' && !$selected_std): ?>
                         <div class="alert alert-info">Please select a standard to view its class timetable.</div>
                    <?php elseif (!$selected_std): ?>
                        <div class="alert alert-warning">The timetable has not been set for your class yet.</div>
                    <?php endif; ?>
                </div>
                <?php
                if (!is_ajax_request()) {
                ?>
                </div>
                <?php include '../../includes/footer.php'; ?>
            </div>
        </div>

        <?php include_once "../../includes/logout_modal.php" ?>

        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>
        <script src="../../assets/js/custom_student_scripts.js"></script>
    </body>

</html>
<?php
                }
?>