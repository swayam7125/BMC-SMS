<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
include_once '../../includes/log_system.php';
include_once '../../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'hr') {
    header("Location: ../../login.php");
    exit;
}

$hr_id = $userId;
$school_id = null;
$message = '';
$message_type = '';
$exam_periods = [];
$students_registered = [];
$selected_period_id = null;
$selected_exam_name = "No Exam Selected"; // Changed default view text

try {
    // 1. Fetch HR's school_id
    $stmt_school = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
    $stmt_school->execute([$hr_id]);
    $school_id = $stmt_school->fetchColumn();
    if (!$school_id) {
        die("Could not retrieve HR's school information.");
    }

    // 2. Fetch all exam registration periods for the school
    $stmt_periods = $conn->prepare("
        SELECT id, exam_name, start_date, end_date, form_due_date 
        FROM exam_registration_periods 
        WHERE school_id = ?
        ORDER BY form_due_date DESC
    ");
    $stmt_periods->execute([$school_id]);
    $exam_periods = $stmt_periods->fetchAll(PDO::FETCH_ASSOC);

    // 3. Determine which period to display (from GET request or default)
    if (isset($_GET['period_id']) && is_numeric($_GET['period_id'])) {
        $selected_period_id = (int)$_GET['period_id'];
        
        // Find the selected exam name for display
        $found_period = false;
        foreach ($exam_periods as $period) {
            if ($period['id'] == $selected_period_id) {
                $selected_exam_name = h($period['exam_name']);
                $found_period = true;
                break;
            }
        }
        if (!$found_period) {
             $selected_period_id = null;
             $message = "Selected exam period not found or does not belong to your school.";
             $message_type = 'danger';
        }
    }

    // 4. Fetch Registered Students
    if ($selected_period_id) {
        // Fetch students registered for the specific period
        $stmt_students = $conn->prepare("
            SELECT 
                ser.id as registration_id,
                ser.student_id,
                ser.registration_date,
                s.student_name,
                s.rollno,
                s.std,
                ser.status
            FROM student_exam_registration ser
            JOIN student s ON ser.student_id = s.id
            WHERE ser.school_id = ? AND ser.exam_period_id = ?
            ORDER BY s.std, s.rollno
        ");
        $stmt_students->execute([$school_id, $selected_period_id]);
        $students_registered = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $selected_exam_name = "N/A";
    }
    
} catch (PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
    $message_type = 'danger';
    log_interaction($role, $userId, "EXAM REGISTRATION VIEW ERROR: " . $e->getMessage(), $userName);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Student Exam Registrations</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css" />
    <style>
        .student-row:hover {
            background-color: #f2f2f2;
            cursor: pointer;
        }
    </style>
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
                    <h1 class="h3 mb-4 text-gray-800">Student Exam Registrations</h1>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo h($message); ?></div>
                    <?php endif; ?>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Registrations</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="form-inline">
                                <label for="period_id" class="mr-2">Select Exam Period:</label>
                                <select name="period_id" id="period_id" class="form-control mr-3">
                                    <option value="">Select an Exam...</option>
                                    <?php foreach ($exam_periods as $period): 
                                        $display_name = h($period['exam_name']) . " (" . format_date($period['start_date'], 'M j') . " - " . format_date($period['end_date'], 'M j') . ")";
                                    ?>
                                        <option value="<?php echo h($period['id']); ?>" <?php echo ($selected_period_id == $period['id'] ? 'selected' : ''); ?>>
                                            <?php echo $display_name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-info">View</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Registered Students: <?php echo h($selected_exam_name); ?></h6>
                        </div>
                        <div class="card-body">
                            <?php if ($selected_period_id && !empty($students_registered)): ?>
                                <div class="alert alert-info">
                                    Total Students Registered: <strong><?php echo count($students_registered); ?></strong>
                                    <small class="float-right text-muted">Click a row for detailed student information (Attendance/Fees).</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Standard</th>
                                                <th>Roll No</th>
                                                <th>Student Name</th>
                                                <th>Registration Date</th>
                                                <th>Status</th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students_registered as $student): ?>
                                                <tr class="student-row" 
                                                    data-student-id="<?php echo encrypt_id($student['student_id']); ?>" 
                                                    data-registration-id="<?php echo h($student['registration_id']); ?>"
                                                    data-toggle="modal" 
                                                    data-target="#studentDetailsModal">
                                                    <td><span class="badge badge-primary"><?php echo h($student['std']); ?></span></td>
                                                    <td><?php echo h($student['rollno']); ?></td>
                                                    <td><?php echo h($student['student_name']); ?></td>
                                                    <td><?php echo date('d-M-Y h:i A', strtotime($student['registration_date'])); ?></td>
                                                    <td>
                                                        <span class="badge badge-success"><?php echo h($student['status']); ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php elseif ($selected_period_id): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-info-circle"></i> No students have submitted their registration for the **<?php echo h($selected_exam_name); ?>** yet.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary">
                                    Please select an exam period from the dropdown above to view student registrations.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
<?php
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?>      
        </div>
    </div>
    
    <div class="modal fade" id="studentDetailsModal" tabindex="-1" role="dialog" aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentDetailsModalLabel">Student Registration Details</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="modal-content-area">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i> Loading details...
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once "../../includes/logout_modal.php"; ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable only if there are registered students to display
            <?php if (!empty($students_registered)): ?>
            $('#dataTable').DataTable({
                "order": [[ 0, "asc" ]] // Order by Standard column by default
            });
            <?php endif; ?>

            // JavaScript to handle modal click and AJAX load
            $('#studentDetailsModal').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget); // Button that triggered the modal
                const studentIdEnc = button.data('student-id');
                const registrationId = button.data('registration-id');
                const modalBody = $('#modal-content-area');

                // Show loading spinner
                modalBody.html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i><p>Loading student details...</p></div>');

                // AJAX call to fetch detailed information
                $.ajax({
                    url: 'get_student_exam_details.php',
                    type: 'GET',
                    data: {
                        student_id: studentIdEnc,
                        registration_id: registrationId
                    },
                    success: function(response) {
                        modalBody.html(response);
                    },
                    error: function(xhr, status, error) {
                        modalBody.html('<div class="alert alert-danger">Error loading details (' + xhr.status + ': ' + error + '). Please check the network connection or logs.</div>');
                        console.error("AJAX Error:", status, error);
                    }
                });
            });
        });
    </script>
</body>
</html>