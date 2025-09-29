<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/functions.php';
include_once '../../includes/log_system.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get user info for logging and student details
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'student' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

$student_id = $userId;
$school_id = null;
$selected_period_id = isset($_POST['exam_period_id']) ? (int)$_POST['exam_period_id'] : (isset($_GET['period_id']) ? (int)$_GET['period_id'] : null);
$current_period_details = null;
$student_info = null;
$current_registration = null;
$success_msg = '';
$error_msg = '';
$past_registrations = [];
$available_periods = [];

// NEW VALIDATION FLAGS
$is_attendance_low = false;
$has_unpaid_fees = false;
$min_attendance_req = 75.00; // Default safety value
$student_attendance_percentage = 0; // Initialize student attendance
$unpaid_fee_count = 0; // Initialize unpaid fee count

try {
    // 1. Fetch Student Details and School ID
    $stmt_student = $conn->prepare("SELECT s.school_id, s.student_name, s.std, s.rollno FROM student s WHERE s.id = ?");
    $stmt_student->execute([$student_id]);
    $student_info = $stmt_student->fetch(PDO::FETCH_ASSOC);

    if (!$student_info || !$student_info['school_id']) {
        die("Could not retrieve student information.");
    }
    $school_id = $student_info['school_id'];
    $current_std = $student_info['std'];
    
    // --- START: PRE-REGISTRATION VALIDATION CHECKS (General Status) ---

    // A. Get School Minimum Attendance Requirement
    $stmt_school_settings = $conn->prepare("SELECT minimum_attendance_percentage FROM school WHERE id = ?");
    $stmt_school_settings->execute([$school_id]);
    $min_attendance_req = $stmt_school_settings->fetchColumn() ?? 75.00;

    // B. Calculate Student Attendance Percentage 
    $stmt_overall_attendance = $conn->prepare("
        SELECT 
            CAST(COUNT(CASE WHEN status = 'Present' THEN 1 END) AS NUMERIC) AS present_periods,
            CAST(COUNT(status) AS NUMERIC) AS total_periods
        FROM attendance
        WHERE student_id = ?
    ");
    $stmt_overall_attendance->execute([$student_id]);
    $attendance_data = $stmt_overall_attendance->fetch(PDO::FETCH_ASSOC);

    if ($attendance_data && $attendance_data['total_periods'] > 0) {
        $student_attendance_percentage = ($attendance_data['present_periods'] / $attendance_data['total_periods']) * 100;
    }
    
    if ($student_attendance_percentage < $min_attendance_req) {
        $is_attendance_low = true;
    }

    // C. Check for Unpaid Mandatory Fees
    $stmt_unpaid_fees = $conn->prepare("
        SELECT COUNT(sf.id)
        FROM student_fees sf
        JOIN fees f ON sf.fee_id = f.id
        WHERE sf.student_id = ? AND sf.status = 'Unpaid'
    ");
    $stmt_unpaid_fees->execute([$student_id]);
    $unpaid_fee_count = $stmt_unpaid_fees->fetchColumn();

    if ($unpaid_fee_count > 0) {
        $has_unpaid_fees = true;
    }
    
    // --- END: PRE-REGISTRATION VALIDATION CHECKS ---
    
    // 2. Fetch ALL Available Registration Periods
    $today = date('Y-m-d');
    $stmt_available_periods = $conn->prepare("
        SELECT id, exam_name, start_date, end_date, form_due_date 
        FROM exam_registration_periods 
        WHERE school_id = ? AND form_due_date > ? 
        ORDER BY form_due_date ASC
    ");
    $stmt_available_periods->execute([$school_id, $today]);
    $available_periods = $stmt_available_periods->fetchAll(PDO::FETCH_ASSOC);
    
    // Auto-select the first available period if none selected
    if (!$selected_period_id && !empty($available_periods)) {
        $selected_period_id = $available_periods[0]['id'];
    }

    // 3. Load Details for the Selected Period
    if ($selected_period_id) {
        $stmt_current_period = $conn->prepare("
            SELECT id, exam_name, start_date, end_date, form_due_date 
            FROM exam_registration_periods 
            WHERE id = ? AND school_id = ?
        ");
        $stmt_current_period->execute([$selected_period_id, $school_id]);
        $current_period_details = $stmt_current_period->fetch(PDO::FETCH_ASSOC);

        // 4. Check registration status for the SELECTED period
        if ($current_period_details) {
             $stmt_check_reg = $conn->prepare("
                SELECT id, status, registration_date 
                FROM student_exam_registration 
                WHERE student_id = ? AND exam_period_id = ?
            ");
            $stmt_check_reg->execute([$student_id, $selected_period_id]);
            $current_registration = $stmt_check_reg->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    // 5. Handle Form Submission (Registration)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_exam']) && $current_period_details) {
         if (!validate_csrf_token($_POST['csrf_token'])) {
            $error_msg = "CSRF validation failed. Please try again.";
        } elseif ($current_registration) {
            $error_msg = "You are already registered for the " . h($current_period_details['exam_name']) . ".";
        } elseif ($is_attendance_low) { 
            // Server-side check should still fail if client JS is bypassed
            $error_msg = "Your attendance is too low."; 
        } elseif ($has_unpaid_fees) { 
             // Server-side check should still fail if client JS is bypassed
             $error_msg = "You have unpaid fees."; 
        } else {
            // Success: proceed with registration
            $conn->beginTransaction();

            $stmt_register = $conn->prepare("
                INSERT INTO student_exam_registration 
                (student_id, exam_period_id, school_id, status) 
                VALUES (?, ?, ?, 'Registered')
            ");
            $stmt_register->execute([$student_id, $selected_period_id, $school_id]);
            
            // Notify HR
            $stmt_hr_users = $conn->prepare("SELECT id FROM hr WHERE school_id = ?");
            $stmt_hr_users->execute([$school_id]);
            $hr_ids = $stmt_hr_users->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($hr_ids)) {
                $notification_msg = "New Exam Registration: " . h($student_info['student_name']) . " for " . h($current_period_details['exam_name']);
                $notification_link = "pages/hr/view_student_registrations.php?period_id=" . h($selected_period_id);
                $notification_type = "exam_registration_submission";
                
                $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
                foreach ($hr_ids as $hr_id) {
                    $stmt_notify->execute([$hr_id, $notification_msg, $notification_link, $notification_type]);
                }
            }
            $conn->commit();
            
            $success_msg = "You have successfully registered for the " . h($current_period_details['exam_name']) . "!";
            // Re-fetch registration status
            $current_registration = ['status' => 'Registered', 'registration_date' => date('Y-m-d H:i:s')];
            
            log_interaction($role, $userId, "EXAM REGISTRATION: Submitted registration for '{$current_period_details['exam_name']}'.", $userName);
        }
    }
    
    // 6. Fetch Past Registrations
    $stmt_past_reg = $conn->prepare("
        SELECT erp.exam_name, erp.start_date, erp.end_date, ser.registration_date, ser.status
        FROM student_exam_registration ser
        JOIN exam_registration_periods erp ON ser.exam_period_id = erp.id
        WHERE ser.student_id = ?
        ORDER BY ser.registration_date DESC
    ");
    $stmt_past_reg->execute([$student_id]);
    $past_registrations = $stmt_past_reg->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    $error_msg = "Database Error: " . $e->getMessage();
    log_interaction($role, $userId, "EXAM REGISTRATION ERROR: A database error occurred. " . $e->getMessage(), $userName);
}

// Prepare student subjects
$subjects_list = [];
if ($current_std) {
    $stmt_subjects = $conn->prepare("
        SELECT s.subject_name FROM standard_subjects ss
        JOIN subjects s ON ss.subject_id = s.subject_id
        WHERE ss.standard = ?
    ");
    $stmt_subjects->execute([$current_std]);
    $subjects_list = $stmt_subjects->fetchAll(PDO::FETCH_COLUMN);
}

// Determine if the Submit button should be disabled and the corresponding warning message
$submit_disabled = $current_registration || $is_attendance_low || $has_unpaid_fees;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Exam Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
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
                    <h1 class="h3 mb-4 text-gray-800">Exam Registration</h1>

                    <div id="php-messages">
                        <?php if ($success_msg): ?><div class="alert alert-success"><?php echo h($success_msg); ?></div><?php endif; ?>
                        <?php if ($error_msg): ?><div class="alert alert-danger"><?php echo h($error_msg); ?></div><?php endif; ?>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Exam Period</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="examSelectionForm">
                                <?php echo csrf_field(); ?>
                                <div class="form-group row">
                                    <label for="exam_period_id" class="col-md-3 col-form-label">Available Exams </label>
                                    <div class="col-md-6">
                                        <select class="form-control" id="exam_period_id" name="exam_period_id" required onchange="document.getElementById('examSelectionForm').submit()">
                                            <option value="">-- Select Exam --</option>
                                            <?php foreach ($available_periods as $period): ?>
                                                <?php 
                                                    $display_name = h($period['exam_name']) . " (Due: " . format_date($period['form_due_date'], 'd-M-Y') . ")";
                                                ?>
                                                <option value="<?php echo h($period['id']); ?>" <?php echo ($selected_period_id == $period['id'] ? 'selected' : ''); ?>>
                                                    <?php echo $display_name; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Selecting an exam updates the details below.</small>
                                    </div>
                                </div>
                            </form>

                            <?php if ($current_period_details): ?>
                            <hr>
                            <h5 class="mb-3 text-info">Details for: <?php echo h($current_period_details['exam_name']); ?></h5>
                            
                            <?php if ($current_registration): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-check-circle"></i> Status: <?php echo h($current_registration['status']); ?> | Registered on: <?php echo date('d-M-Y h:i A', strtotime($current_registration['registration_date'])); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="examRegistrationForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="register_exam" value="1">
                                <input type="hidden" name="exam_period_id" value="<?php echo h($current_period_details['id']); ?>">
                                
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>Student Name</label>
                                        <input type="text" class="form-control" value="<?php echo h($student_info['student_name']); ?>" readonly>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Standard</label>
                                        <input type="text" class="form-control" value="<?php echo h($student_info['std']); ?>" readonly>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Roll No.</label>
                                        <input type="text" class="form-control" value="<?php echo h($student_info['rollno']); ?>" readonly>
                                    </div>
                                </div>
                                
                                <h6 class="mt-4 mb-2 text-primary">Exam & Eligibility</h6>
                                <p><strong>Exam Period:</strong> <?php echo format_date($current_period_details['start_date'], 'd-M-Y'); ?> to <?php echo format_date($current_period_details['end_date'], 'd-M-Y'); ?></p>
                                <p class="text-danger">Registration Due Date: <?php echo format_date($current_period_details['form_due_date'], 'd-M-Y'); ?></p>
                                
                                <label class="mt-3">Subjects for Registration (Std: <?php echo h($current_std); ?>)</label>
                                <?php if (!empty($subjects_list)): ?>
                                    <ul class="list-group mb-4">
                                        <?php foreach ($subjects_list as $subject): ?>
                                            <li class="list-group-item py-1"><?php echo h($subject); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                     <div class="alert alert-warning">No subjects found for your standard.</div>
                                <?php endif; ?>
                                
                                <?php if (!$current_registration): ?>
                                    <div class="form-group form-check mt-4">
                                        <input type="checkbox" class="form-check-input" id="confirmDetails">
                                        <label class="form-check-label" for="confirmDetails">
                                            I confirm that all the details provided are correct and I understand the exam dates.
                                        </label>
                                    </div>
                                    
                                    <button type="button" id="submitExamReg" class="btn btn-primary" disabled>
                                        <i class="fas fa-check-to-slot"></i> Submit Registration Form
                                    </button>
                                    <button type="reset" class="btn btn-secondary">Cancel</button>
                                <?php endif; ?>
                            </form>

                            <?php else: ?>
                                <div class="alert alert-info mt-3">Please select an available exam period from the dropdown above.</div>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Past Exam Registrations</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($past_registrations)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Exam Name</th>
                                            <th>Exam Period</th>
                                            <th>Registered On</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($past_registrations as $reg): ?>
                                            <tr>
                                                <td><?php echo h($reg['exam_name']); ?></td>
                                                <td><?php echo format_date($reg['start_date'], 'd-M-Y'); ?> - <?php echo format_date($reg['end_date'], 'd-M-Y'); ?></td>
                                                <td><?php echo date('d-M-Y h:i A', strtotime($reg['registration_date'])); ?></td>
                                                <td><span class="badge badge-<?php echo ($reg['status'] == 'Registered' ? 'success' : 'secondary'); ?>"><?php echo h($reg['status']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <p>No previous exam registration submissions found.</p>
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
    <?php include_once "../../includes/logout_modal.php"; ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/responsive-tables.js"></script>

    
    <script>
    $(document).ready(function() {
        const isAttendanceLow = <?php echo json_encode($is_attendance_low); ?>;
        const hasUnpaidFees = <?php echo json_encode($has_unpaid_fees); ?>;
        const studentAttendancePercentage = <?php echo json_encode(round($student_attendance_percentage, 1)); ?>;
        const minAttendanceReq = <?php echo json_encode(h($min_attendance_req)); ?>;
        const unpaidFeeCount = <?php echo json_encode(h($unpaid_fee_count)); ?>;

        const submitButton = $('#submitExamReg');
        const confirmCheckbox = $('#confirmDetails');
        
        // Hide PHP messages since we'll use SweetAlert for primary messages
        $('#php-messages').hide();
        
        // Function to show SweetAlert for errors
        function showAlert(title, htmlContent, icon) {
             Swal.fire({
                icon: icon,
                title: title,
                html: htmlContent,
                confirmButtonText: 'OK',
                customClass: {
                    container: 'my-swal-container',
                    popup: 'my-swal-popup',
                    confirmButton: 'btn btn-primary'
                }
            });
        }
        
        // Check for server-side success message on load (after successful submission)
        <?php if ($success_msg && $current_period_details): ?>
            showAlert(
                'Registration Successful!', 
                'You have successfully registered for the <?php echo h($current_period_details['exam_name']); ?>! Your details have been submitted for processing.', 
                'success'
            );
            // Clear message after showing alert
            <?php $success_msg = ''; ?>
        <?php endif; ?>


        // Toggle submit button based on checkbox state
        confirmCheckbox.on('change', function() {
            // Button is enabled ONLY if the checkbox is checked AND there's an exam selected.
            submitButton.prop('disabled', !this.checked || !$('#exam_period_id').val());
        });

        // Custom click handler for validation alert and direct submission
        submitButton.on('click', function(e) {
            e.preventDefault();
            
            // 1. Check if an exam is actually selected (should be handled by disabled attribute, but good for safety)
            if (!$('#exam_period_id').val()) {
                showAlert('Selection Required', 'Please select an exam period from the dropdown.', 'warning');
                return;
            }
            
            // 2. Check Confirmation Checkbox (should be handled by disabled attribute, but final check)
            if (!confirmCheckbox.prop('checked')) {
                showAlert('Action Required', 'You must confirm that all details are correct by checking the box before submission.', 'warning');
                return;
            }

            // 3. Check Eligibility
            let errorHtml = '';
            
            if (isAttendanceLow) {
                errorHtml += '<p class="text-left mb-2"><i class="fas fa-fw fa-exclamation-triangle text-danger mr-2"></i><strong>Attendance Low:</strong> Your attendance is ' + studentAttendancePercentage + '%, which is below the required ' + minAttendanceReq + '%.</p>';
            }
            
            if (hasUnpaidFees) {
                errorHtml += '<p class="text-left mb-2"><i class="fas fa-fw fa-money-bill-wave text-danger mr-2"></i><strong>Unpaid Fees:</strong> You have ' + unpaidFeeCount + ' outstanding fee(s). All fees must be paid to submit the form. Please check your "My Fees" section.</p>';
            }
            
            if (errorHtml.length > 0) {
                // Prepend main warning and show attractive error modal
                errorHtml = '<p class="text-danger font-weight-bold">You cannot submit the registration form due to the following reasons. Please resolve the issues and try again.</p><hr>' + errorHtml;
                showAlert('Registration Blocked', errorHtml, 'error');
                
            } else {
                // 4. Eligibility met: Submit the form directly without further confirmation modal.
                // The PHP logic will handle the success message after processing.
                 $('#examRegistrationForm').submit();
            }
        });
    });
    </script>
</body>
</html>