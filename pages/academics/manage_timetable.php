<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');

if ($role !== 'principal') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
$teachers = [];
$subjects = [];
$standards = [];
$timetable_data = [];

// Get the school_id for the logged-in principal
$stmt_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
$stmt_school->bind_param("i", $userId);
$stmt_school->execute();
$school_id = $stmt_school->get_result()->fetch_assoc()['school_id'];
$stmt_school->close();

if ($school_id) {
    // Fetch all teachers for the school to populate dropdowns
    $teachers_stmt = $conn->prepare("SELECT id, teacher_name FROM teacher WHERE school_id = ? ORDER BY teacher_name");
    $teachers_stmt->bind_param("i", $school_id);
    $teachers_stmt->execute();
    $teachers = $teachers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch all unique standards in the school for the main selector
    $standards_stmt = $conn->prepare("SELECT DISTINCT std FROM student WHERE school_id = ? ORDER BY CAST(std AS UNSIGNED)");
    $standards_stmt->bind_param("i", $school_id);
    $standards_stmt->execute();
    $standards = $standards_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get filter values from URL
$selected_std = $_GET['standard'] ?? null;
$total_periods = isset($_GET['periods']) ? (int)$_GET['periods'] : 8; // Default to 8 periods

// Handle form submission to save the timetable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_timetable'])) {
    $timetable_entries = $_POST['timetable'];
    $standard_to_save = $_POST['standard'];

    $stmt = $conn->prepare("
        INSERT INTO school_timetable (school_id, standard, day_of_week, period_number, subject_name, teacher_id, start_time, end_time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name), teacher_id = VALUES(teacher_id), start_time = VALUES(start_time), end_time = VALUES(end_time)
    ");

    foreach ($timetable_entries as $day => $periods) {
        foreach ($periods as $period_num => $details) {
            if (!empty($details['subject']) && !empty($details['teacher'])) {
                $stmt->bind_param("issisiss", $school_id, $standard_to_save, $day, $period_num, $details['subject'], $details['teacher'], $details['start_time'], $details['end_time']);
                $stmt->execute();
            }
        }
    }
    $stmt->close();
    $successMessage = "Timetable for Standard $standard_to_save has been saved!";
    $selected_std = $standard_to_save;
}

// Fetch existing timetable data and relevant subjects IF a standard is selected
if ($selected_std) {
    $subjects_stmt = $conn->prepare("
        SELECT s.subject_name 
        FROM standard_subjects ss
        JOIN subjects s ON ss.subject_id = s.subject_id
        WHERE ss.standard = ?
        ORDER BY s.subject_name ASC
    ");
    $subjects_stmt->bind_param("s", $selected_std);
    $subjects_stmt->execute();
    $subjects = $subjects_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $subjects_stmt->close();

    $existing_stmt = $conn->prepare("SELECT * FROM school_timetable WHERE school_id = ? AND standard = ?");
    $existing_stmt->bind_param("is", $school_id, $selected_std);
    $existing_stmt->execute();
    $result = $existing_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $timetable_data[$row['day_of_week']][$row['period_number']] = $row;
    }
    $existing_stmt->close();
}

$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage Timetable</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <!-- <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet"> -->
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">

    <style>
    .table-responsive {
        overflow-x: auto;
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
                    <h1 class="h3 mb-4 text-gray-800">Manage School Timetable</h1>

                    <?php if (isset($successMessage)) echo "<div class='alert alert-success'>$successMessage</div>"; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Select Options</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="standard" class="mr-2">Standard:</label>
                                    <select name="standard" id="standard" class="form-control">
                                        <option value="">-- Select --</option>
                                        <?php foreach ($standards as $standard): ?>
                                        <option value="<?php echo $standard['std']; ?>"
                                            <?php if ($selected_std == $standard['std']) echo 'selected'; ?>>
                                            Standard <?php echo $standard['std']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-3">
                                    <label for="periods" class="mr-2">Periods per Day:</label>
                                    <input type="number" name="periods" id="periods" class="form-control" 
                                           value="<?php echo htmlspecialchars($total_periods); ?>" min="1" max="12">
                                </div>
                                <button type="submit" class="btn btn-primary">Generate Timetable</button>
                            </form>
                        </div>
                    </div>

                    <?php if ($selected_std): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Timetable for Standard
                                <?php echo htmlspecialchars($selected_std); ?></h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="standard" value="<?php echo $selected_std; ?>">
                                <div class="table-responsive">
                                    <table class="table table-bordered text-center">
                                        <thead>
                                            <tr>
                                                <th>Period</th>
                                                <?php foreach ($days_of_week as $day) echo "<th>$day</th>"; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php for ($p = 1; $p <= $total_periods; $p++): ?>
                                            <tr>
                                                <td><strong>Period <?php echo $p; ?></strong></td>
                                                <?php foreach ($days_of_week as $day): 
                                                    $entry = $timetable_data[$day][$p] ?? null;
                                                ?>
                                                <td>
                                                    <div class="form-group mb-2">
                                                        <select
                                                            name="timetable[<?php echo $day; ?>][<?php echo $p; ?>][subject]"
                                                            class="form-control form-control-sm">
                                                            <option value="">- Subject -</option>
                                                            <?php foreach ($subjects as $subject) {
                                                                    $selected = ($entry && $entry['subject_name'] == $subject['subject_name']) ? 'selected' : '';
                                                                    echo "<option value='{$subject['subject_name']}' $selected>{$subject['subject_name']}</option>";
                                                                } ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group mb-2">
                                                        <select
                                                            name="timetable[<?php echo $day; ?>][<?php echo $p; ?>][teacher]"
                                                            class="form-control form-control-sm">
                                                            <option value="">- Teacher -</option>
                                                            <?php foreach ($teachers as $teacher) {
                                                                    $selected = ($entry && $entry['teacher_id'] == $teacher['id']) ? 'selected' : '';
                                                                    echo "<option value='{$teacher['id']}' $selected>{$teacher['teacher_name']}</option>";
                                                                } ?>
                                                        </select>
                                                    </div>
                                                    <div class="input-group input-group-sm">
                                                        <input type="time"
                                                            name="timetable[<?php echo $day; ?>][<?php echo $p; ?>][start_time]"
                                                            class="form-control"
                                                            value="<?php echo $entry['start_time'] ?? ''; ?>">
                                                        <input type="time"
                                                            name="timetable[<?php echo $day; ?>][<?php echo $p; ?>][end_time]"
                                                            class="form-control"
                                                            value="<?php echo $entry['end_time'] ?? ''; ?>">
                                                    </div>
                                                </td>
                                                <?php endforeach; ?>
                                            </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" name="save_timetable" class="btn btn-success mt-3">Save
                                    Timetable</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
        <?php include_once "../../includes/logout_modal.php"?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>