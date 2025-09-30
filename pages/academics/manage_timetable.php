<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
include_once '../../includes/log_system.php';

// Check if database connection is successful
if (!isset($conn) || !$conn) {
    error_log("Database connection failed in manage_timetable.php");
    die("Database connection failed");
}

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// --- Authorization ---
$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');
$userName = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?: 'Unknown User';

if ($role !== 'principal') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// --- Initialization ---
$school_id = null;
$teachers = [];
$standards = [];
$timetable_data = [];
$errorMessage = '';
$successMessage = '';
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

try {
    // --- Data Fetching ---
    // Get Principal's School ID
    $stmt_school = $conn->prepare('SELECT "school_id" FROM "principal" WHERE "id" = ?');
    $stmt_school->execute([$userId]);
    $school_id = $stmt_school->fetchColumn();

    if ($school_id) {
        // Fetch all teachers for the school
        $stmt_teachers = $conn->prepare('SELECT id, teacher_name FROM teacher WHERE school_id = ? ORDER BY teacher_name');
        $stmt_teachers->execute([$school_id]);
        $teachers = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all standards
        $stmt_standards = $conn->query("SELECT DISTINCT standard FROM standard_subjects ORDER BY standard");
        $standards = $stmt_standards->fetchAll(PDO::FETCH_COLUMN);
    }

    // --- Form Submission Handling ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_timetable'])) {
        $standard = filter_input(INPUT_POST, 'standard', FILTER_SANITIZE_STRING);
        
        // Validate and sanitize timetable data
        $timetable = array_map(function($periods) {
            return array_map(function($details) {
                return array(
                    'teacher' => filter_var($details['teacher'] ?? null, FILTER_SANITIZE_NUMBER_INT),
                    'subject' => filter_var($details['subject'] ?? null, FILTER_SANITIZE_NUMBER_INT)
                );
            }, $periods);
        }, $_POST['timetable'] ?? []);

        if (!empty($standard) && !empty($timetable)) {
            $conn->beginTransaction();

            // Clear existing timetable for this standard
            $stmt_delete = $conn->prepare("DELETE FROM school_timetable WHERE standard = ? AND school_id = ?");
            $stmt_delete->execute([$standard, $school_id]);

            // Insert new timetable entries
            $stmt_insert = $conn->prepare(
                "INSERT INTO school_timetable (school_id, standard, day_of_week, period_number, teacher_id, subject_id) VALUES (?, ?, ?, ?, ?, ?)"
            );

            foreach ($timetable as $day => $periods) {
                foreach ($periods as $period_id => $details) {
                    $teacher_id = !empty($details['teacher']) ? $details['teacher'] : null;
                    $subject_id = !empty($details['subject']) ? $details['subject'] : null;

                    if ($teacher_id && $subject_id) {
                        $stmt_insert->execute([$school_id, $standard, $day, $period_id, $teacher_id, $subject_id]);
                    }
                }
            }

            $conn->commit();
            $successMessage = "Timetable for Standard {$standard} has been saved successfully!";
            log_interaction($role, $userId, "TIMETABLE_UPDATE: Successfully updated timetable for Standard {$standard}", $userName);
        } else {
            $errorMessage = "Please select a standard and fill in the timetable details.";
            log_interaction($role, $userId, "TIMETABLE_UPDATE_FAILED: Invalid or missing data for timetable update", $userName);
        }
    }

    // --- Timetable Data Retrieval (if a standard is selected) ---
    $selected_standard = $_GET['standard'] ?? ($_POST['standard'] ?? null);
    if ($selected_standard && $school_id) {
        $stmt_timetable = $conn->prepare(
            "SELECT day_of_week, period_number, teacher_id, subject_name FROM school_timetable WHERE standard = ? AND school_id = ?"
        );
        $stmt_timetable->execute([$selected_standard, $school_id]);
        $results = $stmt_timetable->fetchAll(PDO::FETCH_ASSOC);

        // Organize data for easy access in the view
        foreach ($results as $row) {
            $timetable_data[$row['day_of_week']][$row['period_number']] = [
                'teacher_id' => $row['teacher_id'],
                'subject_name' => $row['subject_name']
            ];
        }
    }
} catch (PDOException $e) {
    $errorType = $e->getCode();
    error_log("Database Error in manage_timetable.php: " . $e->getMessage() . " [Code: " . $errorType . "]");
    
    switch($errorType) {
        case '23000': // Duplicate entry
            $errorMessage = "This timetable entry already exists for the selected standard.";
            log_interaction($role, $userId, "TIMETABLE_ERROR: Attempted to create duplicate entry", $userName);
            break;
        case '23001': // Foreign key violation
            $errorMessage = "Invalid teacher or subject selected. Please check your selections.";
            log_interaction($role, $userId, "TIMETABLE_ERROR: Foreign key violation - invalid teacher or subject", $userName);
            break;
        case '42S02': // Table not found
            $errorMessage = "Database configuration error. Please contact system administrator.";
            log_interaction($role, $userId, "TIMETABLE_ERROR: Database configuration error - table not found", $userName);
            break;
        default:
            $errorMessage = "A database error occurred. Please try again later. [Error Code: " . $errorType . "]";
            log_interaction($role, $userId, "TIMETABLE_ERROR: Unexpected database error: " . $e->getMessage(), $userName);
    }
    
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Timetable</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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

                <div id="main-content">

                    <div class="container-fluid">
                        <h1 class="h3 mb-4 text-gray-800">Manage Timetable</h1>

                        <?php if ($successMessage): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                        <?php endif; ?>
                        <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                        <?php endif; ?>

                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Select Standard to Manage Timetable</h6>
                            </div>
                            <div class="card-body">
                                <form action="" method="GET" class="form-inline">
                                    <div class="form-group mr-2">
                                        <label for="standardSelect" class="mr-2">Standard:</label>
                                        <select name="standard" id="standardSelect" class="form-control"
                                            onchange="this.form.submit()">
                                            <option value="">-- Select a Standard --</option>
                                            <?php foreach ($standards as $standard_option): ?>
                                            <option value="<?php echo htmlspecialchars($standard_option); ?>"
                                                <?php echo ($selected_standard == $standard_option) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($standard_option); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <?php if ($selected_standard): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Timetable for Standard:
                                    <?php echo htmlspecialchars($selected_standard); ?></h6>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <input type="hidden" name="standard"
                                        value="<?php echo htmlspecialchars($selected_standard); ?>">
                                    <div class="table-responsive">
                                        <table class="table table-bordered text-center">
                                            <thead>
                                                <tr>
                                                    <th>Day</th>
                                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                                    <th>Period <?php echo $i; ?></th>
                                                    <?php endfor; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($days_of_week as $day): ?>
                                                <tr>
                                                    <td class="align-middle"><strong><?php echo $day; ?></strong></td>
                                                    <?php for ($period = 1; $period <= 8; $period++):
                                                                $current_teacher_id = $timetable_data[$day][$period]['teacher_id'] ?? '';
                                                                $current_subject_id = $timetable_data[$day][$period]['subject_id'] ?? '';
                                                            ?>
                                                    <td>
                                                        <div class="form-group mb-1">
                                                            <select
                                                                name="timetable[<?php echo $day; ?>][<?php echo $period; ?>][teacher]"
                                                                class="form-control teacher-select"
                                                                data-day="<?php echo $day; ?>"
                                                                data-period-id="<?php echo $period; ?>">
                                                                <option value="">- Teacher -</option>
                                                                <?php foreach ($teachers as $teacher): ?>
                                                                <option value="<?php echo $teacher['id']; ?>"
                                                                    <?php echo ($current_teacher_id == $teacher['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($teacher['teacher_name']); ?>
                                                                </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group mb-0">
                                                            <select
                                                                name="timetable[<?php echo $day; ?>][<?php echo $period; ?>][subject]"
                                                                id="subject-<?php echo $day; ?>-<?php echo $period; ?>"
                                                                class="form-control subject-select"
                                                                data-selected-subject="<?php echo $current_subject_id; ?>">
                                                                <option value="">- Subject -</option>
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="submit" name="save_timetable" class="btn btn-success mt-3">
                                        <i class="fas fa-save mr-2"></i>Save Timetable
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
    <?php
                    if (!$is_ajax_request):
                        include '../../includes/footer.php';
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>
    <script>
    $(document).ready(function() {
        const standard = "<?php echo htmlspecialchars($selected_standard ?? '', ENT_QUOTES); ?>";

        function loadSubjects(teacherId, subjectDropdown, selectedSubjectId = null) {
            subjectDropdown.empty().append('<option value="">Loading...</option>');

            // Validate inputs
            if (!teacherId || !standard) {
                subjectDropdown.empty().append('<option value="">- Select Teacher First -</option>');
                return;
            }

            if (teacherId && standard) {
                $.ajax({
                    url: '../academics/ajax_handler_timetable.php',
                    type: 'POST',
                    data: {
                        action: 'get_subjects_for_teacher_and_standard',
                        teacher_id: teacherId,
                        standard: standard
                    },
                    dataType: 'json',
                    success: function(response) {
                        subjectDropdown.empty().append('<option value="">- Subject -</option>');
                        if (response.success && response.subjects.length > 0) {
                            $.each(response.subjects, function(index, subject) {
                                const isSelected = (subject.subject_id ==
                                    selectedSubjectId) ? 'selected' : '';
                                subjectDropdown.append(
                                    `<option value="${subject.subject_id}" ${isSelected}>${subject.subject_name}</option>`
                                    );
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