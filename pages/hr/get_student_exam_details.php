<?php
require_once __DIR__ . "/../../includes/connect.php";
require_once __DIR__ . "/../../encryption.php";
require_once __DIR__ . "/../../includes/functions.php"; // For h()

// 1. Authorization Check (HR Role)
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$hr_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'hr' || !$hr_user_id) {
    http_response_code(403);
    echo h('Unauthorized access.');
    exit;
}

$student_id = isset($_GET['student_id']) ? (int)decrypt_id($_GET['student_id']) : 0;
$registration_id = isset($_GET['registration_id']) ? (int)$_GET['registration_id'] : 0;
$school_id = null;

// 2. Determine HR's School ID (Reliable fetch from DB)
try {
    $stmt_school_fetch = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
    $stmt_school_fetch->execute([$hr_user_id]);
    $school_id = $stmt_school_fetch->fetchColumn();
} catch (PDOException $e) {
    http_response_code(500);
    echo h('Database error during HR verification.');
    exit;
}

// 3. Basic Request Validation
if (!$student_id || !$registration_id || !$school_id) {
    // This is the source of the 400 Bad Request error seen in the image
    http_response_code(400);
    echo h('Invalid request parameters (Student ID, Registration ID, or School ID missing/invalid).');
    exit;
}

$details = [];
$subjects = [];
$unpaid_fees = [];
$attendance_percentage = 0;
$min_attendance_req = 0;

try {
    // A. Fetch Student Details, Registration Status, and School Settings
    // CRITICAL: We ensure the student belongs to the HR's school by checking s.school_id = ?
    $stmt = $conn->prepare("
        SELECT 
            s.student_name, s.rollno, s.std, s.id as student_id_raw,
            sch.minimum_attendance_percentage,
            erp.exam_name, erp.start_date, erp.end_date,
            ser.registration_date, ser.status
        FROM student_exam_registration ser
        JOIN student s ON ser.student_id = s.id
        JOIN exam_registration_periods erp ON ser.exam_period_id = erp.id
        JOIN school sch ON s.school_id = sch.id
        WHERE ser.id = ? AND s.id = ? AND s.school_id = ?
    ");
    // Bind parameters: registration_id, student_id, school_id (to ensure they belong together)
    $stmt->execute([$registration_id, $student_id, $school_id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$details) {
        http_response_code(404);
        echo h('Student registration record not found or does not belong to your school.');
        exit;
    }
    
    $min_attendance_req = $details['minimum_attendance_percentage'];
    $current_std = $details['std'];

    // B. Fetch Subjects
    $stmt_subjects = $conn->prepare("
        SELECT s.subject_name FROM standard_subjects ss
        JOIN subjects s ON ss.subject_id = s.subject_id
        WHERE ss.standard = ?
        ORDER BY s.subject_name
    ");
    $stmt_subjects->execute([$current_std]);
    $subjects = $stmt_subjects->fetchAll(PDO::FETCH_COLUMN);

    // C. Fetch Unpaid Fees
    $stmt_fees = $conn->prepare("
        SELECT f.fee_type, f.amount, f.due_date 
        FROM student_fees sf
        JOIN fees f ON sf.fee_id = f.id
        WHERE sf.student_id = ? AND sf.status = 'Unpaid'
    ");
    $stmt_fees->execute([$student_id]);
    $unpaid_fees = $stmt_fees->fetchAll(PDO::FETCH_ASSOC);

    // D. Calculate Attendance Percentage 
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
        $attendance_percentage = ($attendance_data['present_periods'] / $attendance_data['total_periods']) * 100;
    } else {
        $attendance_percentage = 0;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo h('Database Error: ' . $e->getMessage());
    exit;
}

// --- HTML OUTPUT ---
?>
<div class="row">
    <div class="col-md-6 border-right">
        <h5 class="text-primary mb-3"><i class="fas fa-user-graduate mr-2"></i>Student & Exam Info</h5>
        <table class="table table-borderless table-sm detail-table">
            <tr><th>Name:</th><td><?php echo h($details['student_name']); ?></td></tr>
            <tr><th>Standard:</th><td><span class="badge badge-primary"><?php echo h($details['std']); ?></span></td></tr>
            <tr><th>Roll No:</th><td><?php echo h($details['rollno']); ?></td></tr>
            <tr><th>Registered On:</th><td><?php echo date('d-M-Y h:i A', strtotime($details['registration_date'])); ?></td></tr>
            <tr><th>Exam Name:</th><td><strong><?php echo h($details['exam_name']); ?></strong></td></tr>
            <tr><th>Exam Period:</th><td><?php echo format_date($details['start_date'], 'd-M-Y'); ?> to <?php echo format_date($details['end_date'], 'd-M-Y'); ?></td></tr>
        </table>
    </div>

    <div class="col-md-6">
        <h5 class="text-primary mb-3"><i class="fas fa-clipboard-check mr-2"></i>Eligibility Status</h5>

        <?php $attendance_status_class = ($attendance_percentage < $min_attendance_req) ? 'border-left-danger' : 'border-left-success'; ?>
        <div class="card mb-3 <?php echo $attendance_status_class; ?> shadow-sm">
            <div class="card-body py-2">
                <div class="font-weight-bold text-uppercase mb-1">Attendance</div>
                <div class="h5 mb-0 text-gray-800">
                    <?php echo round($attendance_percentage, 1); ?>% 
                    <?php if ($attendance_percentage < $min_attendance_req): ?>
                        <i class="fas fa-exclamation-triangle text-danger ml-2"></i>
                    <?php else: ?>
                        <i class="fas fa-check-circle text-success ml-2"></i>
                    <?php endif; ?>
                </div>
                <small class="text-muted">Min required: <?php echo h($min_attendance_req); ?>%</small>
            </div>
        </div>
        
        <?php if (!empty($unpaid_fees)): ?>
            <div class="card border-left-danger shadow-sm">
                <div class="card-body py-2">
                    <div class="font-weight-bold text-uppercase mb-1">Unpaid Fees (<span class="text-danger"><?php echo count($unpaid_fees); ?></span>)</div>
                    <ul class="list-unstyled mt-2 mb-0 small text-danger">
                        <?php foreach ($unpaid_fees as $fee): ?>
                            <li>
                                <i class="fas fa-times-circle fa-xs mr-1"></i>
                                <?php echo h($fee['fee_type']); ?> (₹<?php echo number_format($fee['amount'], 2); ?>) - Due: <?php echo format_date($fee['due_date'], 'd-M-Y'); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-left-success shadow-sm">
                <div class="card-body py-2">
                    <div class="font-weight-bold text-uppercase mb-1">Fees Status</div>
                    <div class="h5 mb-0 text-success"><i class="fas fa-check-circle mr-1"></i> All Paid</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<h5 class="text-primary mt-4 mb-3"><i class="fas fa-book-open mr-2"></i>Registered Subjects (Standard: <?php echo h($current_std); ?>)</h5>
<?php if (!empty($subjects)): ?>
    <div class="row">
    <?php 
    $subjects_per_column = ceil(count($subjects) / 3);
    $subjects_chunks = array_chunk($subjects, $subjects_per_column);
    
    foreach ($subjects_chunks as $subject_group): 
    ?>
        <div class="col-md-4">
            <ul class="list-group list-group-flush small">
                <?php foreach ($subject_group as $subject): ?>
                    <li class="list-group-item px-0 py-1 border-0 d-flex align-items-center">
                        <i class="fas fa-dot-circle fa-xs mr-2 text-info"></i> <?php echo h($subject); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-warning">No subjects found for this student's current standard (<?php echo h($current_std); ?>).</div>
<?php endif; ?>