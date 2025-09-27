<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // Log system included

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacher_id = $userId;
$school_id = null;
$teacher_standards = [];
$teacher_subjects = [];
$students = [];
$message = '';
$message_type = '';

try {
    // Get school_id and assigned standards for the teacher
    $stmt = $conn->prepare("SELECT school_id, std FROM teacher WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($teacher_info) {
        $school_id = $teacher_info['school_id'];
        $teacher_standards = $teacher_info['std'] ? explode(',', trim($teacher_info['std'], '{}')) : [];
    } else {
        die("Could not find teacher information.");
    }
} catch (PDOException $e) {
    die("Database error fetching teacher info: " . $e->getMessage());
}

// Handle form submission for adding attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $standard = $_POST['standard'];
    $subject = $_POST['subject'];
    $period = $_POST['period'];
    $attendance_date = $_POST['attendance_date'];
    $attendance_data = $_POST['attendance'] ?? [];
    $student_count = count($attendance_data);

    if (empty($attendance_data)) {
        $message = "No students selected to mark attendance.";
        $message_type = 'danger';
    } else {
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO attendance (student_id, teacher_id, school_id, standard, subject, period_number, attendance_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?::attendance_status) ON CONFLICT (student_id, attendance_date, period_number) DO UPDATE SET status = EXCLUDED.status, teacher_id = EXCLUDED.teacher_id");

            foreach ($attendance_data as $student_id => $status) {
                $stmt->execute([$student_id, $teacher_id, $school_id, $standard, $subject, $period, $attendance_date, $status]);
            }
            $conn->commit();
            $message = "Attendance for {$student_count} student(s) has been successfully recorded for Period {$period} on {$attendance_date}.";
            $message_type = 'success';
            // Log the successful action
            log_interaction($role, $userId, "ATTENDANCE: Marked lecture attendance for {$student_count} students in Standard {$standard}, Subject {$subject}, Period {$period}.", $userName);

        } catch (PDOException $e) {
            $conn->rollBack();
            $message = "Error: " . $e->getMessage();
            $message_type = 'danger';
            // Log the error
            log_interaction($role, $userId, "ATTENDANCE ERROR: Failed to mark lecture attendance. DB Error: " . $e->getMessage(), $userName);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Lecture Attendance</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>
<body id="page-top">
    <div id="wrapper">
<?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?>        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
<?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?>                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Add Lecture Attendance</h1>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Lecture Details</h6>
                        </div>
                        <div class="card-body">
                            <form id="filter-form" method="GET">
                                <div class="form-row">
                                    <div class="form-group col-md-3">
                                        <label for="standard">Standard</label>
                                        <select id="standard" name="standard" class="form-control" required>
                                            <option value="">Select Standard</option>
                                            <?php foreach ($teacher_standards as $std): ?>
                                                <option value="<?php echo htmlspecialchars($std); ?>" <?php echo (isset($_GET['standard']) && $_GET['standard'] == $std) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($std); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="subject">Subject</label>
                                        <select id="subject" name="subject" class="form-control" required>
                                            <option value="">Select Subject</option>
                                            </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="period">Period</label>
                                        <select id="period" name="period" class="form-control" required>
                                            <option value="">Select Period</option>
                                            </select>
                                    </div>
                                    <div class="form-group col-md-3 align-self-end">
                                        <button type="submit" class="btn btn-primary">Load Students</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php
                    // If filters are set, fetch students and display the attendance form
                    if (isset($_GET['standard']) && isset($_GET['subject']) && isset($_GET['period'])) {
                        $selected_std = $_GET['standard'];
                        $selected_sub = $_GET['subject'];
                        $selected_per = $_GET['period'];
                        try {
                            $stmt_students = $conn->prepare("SELECT id, student_name, rollno FROM student WHERE school_id = ? AND std = ? ORDER BY rollno");
                            $stmt_students->execute([$school_id, $selected_std]);
                            $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            echo "<div class='alert alert-danger'>Error fetching students: " . $e->getMessage() . "</div>";
                        }

                        if (!empty($students)) {
                    ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                             <h6 class="m-0 font-weight-bold text-primary">Mark Attendance for <?php echo date('d-M-Y'); ?></h6>
                             <div>
                                <button type="button" class="btn btn-success btn-sm" onclick="markAll('Present')">Mark All Present</button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="markAll('Absent')">Mark All Absent</button>
                             </div>
                        </div>
                        <div class="card-body">
                            <form action="add_lecture_attendance.php" method="POST">
                                <input type="hidden" name="standard" value="<?php echo htmlspecialchars($selected_std); ?>">
                                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($selected_sub); ?>">
                                <input type="hidden" name="period" value="<?php echo htmlspecialchars($selected_per); ?>">
                                <input type="hidden" name="attendance_date" value="<?php echo date('Y-m-d'); ?>">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Roll No</th>
                                                <th>Student Name</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['rollno']); ?></td>
                                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                                        <label class="btn btn-outline-success btn-sm active">
                                                            <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Present" checked> Present
                                                        </label>
                                                        <label class="btn btn-outline-danger btn-sm">
                                                            <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Absent"> Absent
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Attendance</button>
                            </form>
                        </div>
                    </div>
                    <?php
                        } else {
                            echo "<div class='alert alert-info'>No students found for the selected standard.</div>";
                        }
                    }
                    ?>
                </div>
            </div>
<?php
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?>        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        function markAll(status) {
            document.querySelectorAll('input[type="radio"][value="' + status + '"]').forEach(function(radio) {
                radio.click();
            });
        }

        $(document).ready(function() {
            var selectedStandard = "<?php echo isset($_GET['standard']) ? $_GET['standard'] : ''; ?>";
            var selectedSubject = "<?php echo isset($_GET['subject']) ? $_GET['subject'] : ''; ?>";
            var selectedPeriod = "<?php echo isset($_GET['period']) ? $_GET['period'] : ''; ?>";

            function fetchSubjects(standard, subjectToSelect) {
                if (standard) {
                    $.ajax({
                        url: '../academics/ajax_handler_timetable.php',
                        type: 'POST',
                        data: { action: 'get_subjects_for_teacher_standard', standard: standard, teacher_id: <?php echo $teacher_id; ?> },
                        success: function(response) {
                            $('#subject').html('<option value="">Select Subject</option>').append(response);
                            if (subjectToSelect) {
                                $('#subject').val(subjectToSelect).trigger('change');
                            }
                        }
                    });
                } else {
                    $('#subject').html('<option value="">Select Subject</option>');
                    $('#period').html('<option value="">Select Period</option>');
                }
            }
            
            function fetchPeriods(standard, subject, periodToSelect) {
                if(standard && subject) {
                    $.ajax({
                         url: '../academics/ajax_handler_timetable.php',
                         type: 'POST',
                         data: { action: 'get_periods_for_teacher_subject', standard: standard, subject: subject, teacher_id: <?php echo $teacher_id; ?> },
                         success: function(response){
                            $('#period').html('<option value="">Select Period</option>').append(response);
                             if(periodToSelect) {
                                $('#period').val(periodToSelect);
                            }
                         }
                    });
                } else {
                     $('#period').html('<option value="">Select Period</option>');
                }
            }

            $('#standard').on('change', function() {
                var standard = $(this).val();
                fetchSubjects(standard, null);
                $('#period').html('<option value="">Select Period</option>');
            });

            $('#subject').on('change', function() {
                 var standard = $('#standard').val();
                 var subject = $(this).val();
                 fetchPeriods(standard, subject, null);
            });

            // On page load, if standard is already selected (from GET params), fetch its subjects
            if (selectedStandard) {
                fetchSubjects(selectedStandard, selectedSubject);
            }
             if (selectedStandard && selectedSubject) {
                fetchPeriods(selectedStandard, selectedSubject, selectedPeriod);
            }
        });
    </script>
</body>
</html>