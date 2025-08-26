<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
|
| This section handles all server-side operations:
| 1. Includes necessary files for database connection and security.
| 2. Authenticates and authorizes the user (ensures they are a principal).
| 3. Processes the form submission for saving the timetable.
| 4. Fetches all required data from the database (standards, teachers, subjects, etc.).
|
*/

// Core Includes
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

// --- Authorization ---
$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');

if ($role !== 'principal') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// --- Initialization ---
$school_id = null;
$teachers = [];
$subjects = [];
$standards = [];
$timetable_data = [];
$errorMessage = '';
$successMessage = '';
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

try {
    // --- Data Fetching ---
    $stmt_school = $conn->prepare('SELECT "school_id" FROM "principal" WHERE "id" = ?');
    $stmt_school->execute([$userId]);
    $school_id = $stmt_school->fetchColumn();

    if ($school_id) {
        // Prepare the SQL statement
        $standards_stmt = $conn->prepare("
        SELECT \"std\"
        FROM \"student\"
        WHERE \"school_id\" = ?
        GROUP BY \"std\"
        ORDER BY
            CASE
                WHEN \"std\" ~ '^\\d+$' THEN CAST(\"std\" AS INTEGER)
                ELSE 0
            END,
        \"std\"
    ");

        // Execute and fetch the results
        $standards_stmt->execute([$school_id]);
        $standards = $standards_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $selected_std = $_GET['standard'] ?? null;
    $total_periods = isset($_GET['periods']) ? (int)$_GET['periods'] : 8;

    // --- Form Submission Handling ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_timetable'])) {
        $timetable_entries = $_POST['timetable'];
        $standard_to_save = $_POST['standard'];

        $conn->beginTransaction();

        $delete_stmt = $conn->prepare('DELETE FROM "school_timetable" WHERE "school_id" = ? AND "standard" = ?');
        $delete_stmt->execute([$school_id, $standard_to_save]);

        $insert_stmt = $conn->prepare('INSERT INTO "school_timetable" (school_id, standard, day_of_week, period_number, subject_name, teacher_id, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        
        foreach ($timetable_entries as $day => $periods) {
            foreach ($periods as $period_num => $details) {
                if (!empty($details['subject']) && !empty($details['teacher'])) {
                    $start_time = !empty($details['start_time']) ? $details['start_time'] : null;
                    $end_time = !empty($details['end_time']) ? $details['end_time'] : null;
                    $insert_stmt->execute([$school_id, $standard_to_save, $day, $period_num, $details['subject'], $details['teacher'], $start_time, $end_time]);
                }
            }
        }
        $conn->commit();
        $successMessage = "Timetable for Standard $standard_to_save has been saved!";
        $selected_std = $standard_to_save; // Ensure the selected standard persists after saving
    }

    // --- Data Fetching for Display ---
    if ($selected_std) {
        // Fetch teachers who teach the selected standard
        $teachers_stmt = $conn->prepare('SELECT "id", "teacher_name", "subject" FROM "teacher" WHERE "school_id" = ? AND ? = ANY("std") ORDER BY "teacher_name"');
        $teachers_stmt->execute([$school_id, $selected_std]);
        $teachers = $teachers_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch subjects assigned to the selected standard
        $subjects_stmt = $conn->prepare('SELECT s."subject_name" FROM "standard_subjects" ss JOIN "subjects" s ON ss."subject_id" = s."subject_id" WHERE ss."standard" = ? ORDER BY s."subject_name" ASC');
        $subjects_stmt->execute([$selected_std]);
        $subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch existing timetable data to pre-fill the form
        $existing_stmt = $conn->prepare('SELECT * FROM "school_timetable" WHERE "school_id" = ? AND "standard" = ?');
        $existing_stmt->execute([$school_id, $selected_std]);
        $result = $existing_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $timetable_data[$row['day_of_week']][$row['period_number']] = $row;
        }
    }
} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    $errorMessage = "Database Error: " . $e->getMessage();
}

// The PHP logic ends here. The HTML part will now render the page.
?>

<?php
/*
|--------------------------------------------------------------------------
| RESPONSIVE FRONTEND (VIEW)
|--------------------------------------------------------------------------
|
| This section contains the complete HTML structure for the page.
| - It uses Bootstrap's grid system for a responsive layout.
| - The main timetable table is wrapped in `.table-responsive` to prevent layout breaking.
|
*/
if (!is_ajax_request()): // Prevents HTML from being sent on AJAX calls
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Timetable - School Management System</title>

    <!-- Professional Font & Icons -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <!-- Core CSS -->
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage School Timetable</h1>

                    <!-- Display Success or Error Messages -->
                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>

                    <!-- Selection Controls Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Options</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET">
                                <!-- Responsive Row for Form Controls -->
                                <div class="form-row align-items-end">
                                    <div class="col-md-4 col-sm-12 mb-2">
                                        <label for="standard">Standard:</label>
                                        <select name="standard" id="standard" class="form-control">
                                            <option value="">-- Select a Standard --</option>
                                            <?php foreach ($standards as $standard): ?>
                                                <option value="<?php echo htmlspecialchars($standard['std']); ?>" <?php if ($selected_std == $standard['std']) echo 'selected'; ?>>
                                                    Standard <?php echo htmlspecialchars($standard['std']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-sm-12 mb-2">
                                        <label for="periods">Periods per Day:</label>
                                        <input type="number" name="periods" id="periods" class="form-control" value="<?php echo htmlspecialchars($total_periods); ?>" min="1" max="12">
                                    </div>
                                    <div class="col-md-4 col-sm-12 mb-2">
                                        <button type="submit" class="btn btn-primary btn-block">Generate Timetable</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Timetable Grid Card (only shown if a standard is selected) -->
                    <?php if ($selected_std): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Timetable for Standard <?php echo htmlspecialchars($selected_std); ?></h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="standard" value="<?php echo htmlspecialchars($selected_std); ?>">
                                    <!-- This div makes the table scroll horizontally on small screens -->
                                    <div class="table-responsive">
                                        <table class="table table-bordered text-center" style="min-width: 800px;">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Period</th>
                                                    <?php foreach ($days_of_week as $day) echo "<th>$day</th>"; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php for ($p = 1; $p <= $total_periods; $p++): ?>
                                                    <tr>
                                                        <td class="align-middle"><strong>Period <?php echo $p; ?></strong></td>
                                                        <?php foreach ($days_of_week as $day): 
                                                            $entry = $timetable_data[$day][$p] ?? null;
                                                        ?>
                                                            <td>
                                                                <div class="form-group mb-2">
                                                                    <select name="timetable[<?php echo $day; ?>][<?php echo $p; ?>][teacher]" class="form-control form-control-sm teacher-select" data-day="<?php echo $day; ?>" data-period-id="<?php echo $p; ?>">
                                                                        <option value="">- Teacher -</option>
                                                                        <?php foreach ($teachers as $teacher): ?>
                                                                            <option value="<?php echo htmlspecialchars($teacher['id']); ?>" <?php if ($entry && $entry['teacher_id'] == $teacher['id']) echo 'selected'; ?>>
                                                                                <?php echo htmlspecialchars($teacher['teacher_name']); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group mb-2">
                                                                    <select name="timetable[<?php echo $day; ?>][<?php echo $p; ?>][subject]" class="form-control form-control-sm subject-select" id="subject-<?php echo $day; ?>-<?php echo $p; ?>" data-selected-subject="<?php echo htmlspecialchars($entry['subject_name'] ?? ''); ?>">
                                                                        <option value="">- Subject -</option>
                                                                        <?php // Subjects are loaded dynamically via JavaScript ?>
                                                                    </select>
                                                                </div>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="time" name="timetable[<?php echo $day; ?>][<?php echo $p; ?>][start_time]" class="form-control" value="<?php echo htmlspecialchars($entry['start_time'] ?? ''); ?>">
                                                                    <input type="time" name="timetable[<?php echo $day; ?>][<?php echo $p; ?>][end_time]" class="form-control" value="<?php echo htmlspecialchars($entry['end_time'] ?? ''); ?>">
                                                                </div>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endfor; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="submit" name="save_timetable" class="btn btn-success mt-3"><i class="fas fa-save mr-2"></i>Save Timetable</button>
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

    <!-- Core Scripts -->
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <?php
    /*
    |--------------------------------------------------------------------------
    | JAVASCRIPT LOGIC
    |--------------------------------------------------------------------------
    |
    | This section contains all the client-side JavaScript for the page.
    | - It is placed at the end of the body for faster page loading.
    | - It handles the dynamic loading of subjects based on the selected teacher.
    |
    */
    ?>
    <script>
    $(document).ready(function() {
        // Function to load subjects for a specific teacher via AJAX
        function loadSubjects(teacherId, subjectDropdown, selectedSubject = '') {
            if (teacherId) {
                $.ajax({
                    url: 'ajax_handler_timetable.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_teacher_subjects',
                        teacher_id: teacherId
                    },
                    success: function(response) {
                        subjectDropdown.empty().append('<option value="">- Subject -</option>');
                        if (response.success && response.subjects.length > 0) {
                            $.each(response.subjects, function(index, subject) {
                                const isSelected = (subject === selectedSubject);
                                subjectDropdown.append($('<option>', {
                                    value: subject,
                                    text: subject,
                                    selected: isSelected
                                }));
                            });
                        }
                    },
                    error: function() {
                        subjectDropdown.empty().append('<option value="">- Error -</option>');
                    }
                });
            } else {
                subjectDropdown.empty().append('<option value="">- Subject -</option>');
            }
        }

        // On page load, populate subjects for any existing timetable entries
        $('table .teacher-select').each(function() {
            const teacherId = $(this).val();
            if (teacherId) {
                const day = $(this).data('day');
                const period = $(this).data('period-id');
                const subjectDropdown = $('#subject-' + day + '-' + period);
                const selectedSubject = subjectDropdown.data('selected-subject');
                
                loadSubjects(teacherId, subjectDropdown, selectedSubject);
            }
        });

        // Event listener for when a teacher is selected in any dropdown
        $(document).on('change', '.teacher-select', function() {
            const teacherId = $(this).val();
            const day = $(this).data('day');
            const period = $(this).data('period-id');
            const subjectDropdown = $('#subject-' + day + '-' + period);

            loadSubjects(teacherId, subjectDropdown);
        });
    });
    </script>
</body>
</html>
<?php
endif; // End ajax check
$conn = null;