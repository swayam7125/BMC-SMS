<?php
// Define a constant for the project root directory for reliable file includes.
define('ROOT_PATH', dirname(__DIR__, 3));

include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
include_once '../../includes/log_system.php'; // Includes your log_interaction function

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : 'guest';
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : 0;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'Guest';

if ($role !== 'hr' || !$userId) {
    if (function_exists('log_interaction')) {
        log_interaction($role, $userId, "Unauthorized attempt to access manage_fees.php", $userName);
    }
    header("Location: ../../login.php");
    exit;
}

$stmt = $conn->prepare("SELECT school_id FROM hr WHERE id = :hr_id");
$stmt->bindParam(':hr_id', $userId);
$stmt->execute();
$hr = $stmt->fetch();
$school_id = $hr['school_id'];

// ACTION: User is submitting the form to add a new fee. This is the primary action to log.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fee'])) {
    $standard = $_POST['standard'];
    $fee_type = trim($_POST['fee_type']);
    $amount = $_POST['amount'];
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $student_ids = $_POST['student_ids'] ?? [];

    // Log the initiation of the action
    log_interaction($role, $userId, "Initiated adding new fee '$fee_type' for Standard $standard.", $userName);

    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("INSERT INTO fees (school_id, standard, fee_type, amount, due_date) VALUES (:school_id, :standard, :fee_type, :amount, :due_date)");
        $stmt->execute([':school_id' => $school_id, ':standard' => $standard, ':fee_type' => $fee_type, ':amount' => $amount, ':due_date' => $due_date]);
        $last_fee_id = $conn->lastInsertId();
        
        $students_to_assign = [];
        if (!empty($student_ids)) {
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
            $validate_stmt = $conn->prepare("SELECT id FROM student WHERE id IN ($placeholders) AND school_id = ? AND std = ?");
            $params = array_merge($student_ids, [$school_id, $standard]);
            $validate_stmt->execute($params);
            $students_to_assign = $validate_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $all_students_stmt = $conn->prepare("SELECT id FROM student WHERE school_id = :school_id AND std = :standard");
            $all_students_stmt->execute([':school_id' => $school_id, ':standard' => $standard]);
            $students_to_assign = $all_students_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (!empty($students_to_assign)) {
            $assign_stmt = $conn->prepare("INSERT INTO student_fees (student_id, fee_id) VALUES (:student_id, :fee_id)");
            $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");

            foreach ($students_to_assign as $student) {
                $assign_stmt->execute([':student_id' => $student['id'], ':fee_id' => $last_fee_id]);
                $message = "A new fee of ₹" . number_format($amount, 2) . " for '" . htmlspecialchars($fee_type) . "' has been added. The due date is " . date('d-M-Y', strtotime($due_date)) . ".";
                $link = "/BMC-SMS/pages/student/view_fees.php"; 
                $notify_stmt->execute([$student['id'], $message, $link, 'new_fee']);
            }
        }
        
        $conn->commit();
        $success_message = "Fee added and assigned to " . count($students_to_assign) . " students successfully! Notifications have been sent.";
        
        // Log the successful completion of the action
        log_interaction($role, $userId, "Successfully added fee '$fee_type' (₹$amount) and assigned it to " . count($students_to_assign) . " students in Standard $standard.", $userName);

    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Failed to add fee: " . $e->getMessage();
        
        // Log the failure of the action
        log_interaction($role, $userId, "Failed to add fee '$fee_type' for Standard $standard. Error: " . $e->getMessage(), $userName);
    }
}

// Fetch all fees for the school with payment counts
$stmt = $conn->prepare("
    SELECT f.id, f.standard, f.fee_type, f.amount, f.due_date, 
           COUNT(sf.id) as total_students,
           COUNT(CASE WHEN sf.status = 'Paid' THEN 1 END) as paid_students
    FROM fees f
    LEFT JOIN student_fees sf ON f.id = sf.fee_id
    WHERE f.school_id = :school_id
    GROUP BY f.id, f.standard, f.fee_type, f.amount, f.due_date
    ORDER BY f.due_date DESC, f.standard
");
$stmt->bindParam(':school_id', $school_id);
$stmt->execute();
$fees = $stmt->fetchAll();

// Calculate summary statistics
$total_fees = count($fees);
$total_amount_expected = 0;
$total_amount_collected = 0;
$total_students_affected = 0;

foreach ($fees as $fee) {
    $total_amount_expected += ($fee['amount'] * $fee['total_students']);
    $total_amount_collected += ($fee['amount'] * $fee['paid_students']);
    $total_students_affected += $fee['total_students'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Fees - HR Portal</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <style>
        .searchable-dropdown .dropdown-menu {
            display: none; position: absolute; width: 100%; z-index: 1000;
            max-height: 250px; overflow-y: auto; border: 1px solid #d1d3e2;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); background-color: #fff;
        }
        .searchable-dropdown .form-check:hover { background-color: #f8f9fa; }
        .searchable-dropdown .form-check-label { width: 100%; cursor: pointer; }
        .input-group-icon { position: relative; }
        .input-group-icon .form-control { padding-left: 2.375rem; }
        .input-group-icon .input-icon {
            position: absolute; top: 0; left: 0; z-index: 3;
            width: 2.375rem; height: calc(1.5em + 0.75rem + 2px);
            display: flex; align-items: center; justify-content: center; color: #858796;
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage School Fees</h1>
                        <div class="d-none d-sm-inline-block">
                            <span class="badge badge-primary badge-pill mr-2">
                                <i class="fas fa-calculator fa-sm"></i> Total Fees: <?php echo $total_fees; ?>
                            </span>
                        </div>
                    </div>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Expected</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($total_amount_expected, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa-solid fa-indian-rupee-sign text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Amount Collected</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($total_amount_collected, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Collection Rate</div>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                        <?php echo $total_amount_expected > 0 ? round(($total_amount_collected / $total_amount_expected) * 100, 1) : 0; ?>%
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="progress progress-sm mr-2">
                                                        <div class="progress-bar bg-info" role="progressbar"
                                                            style="width: <?php echo $total_amount_expected > 0 ? ($total_amount_collected / $total_amount_expected) * 100 : 0; ?>%"
                                                            aria-valuenow="<?php echo $total_amount_expected > 0 ? ($total_amount_collected / $total_amount_expected) * 100 : 0; ?>" 
                                                            aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Amount</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($total_amount_expected - $total_amount_collected, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-plus-circle mr-2"></i>Add New Fee and Assign to Standard
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="standard" class="font-weight-bold"><i class="fas fa-graduation-cap mr-1"></i>Standard *</label>
                                        <select id="standard" name="standard" class="form-control" required>
                                            <option value="">Choose a Standard...</option>
                                            <?php for ($i=1; $i<=12; $i++): ?><option value="<?php echo $i; ?>">Standard <?php echo $i; ?></option><?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-8 searchable-dropdown" id="studentSelectorWrapper" style="display: none;">
                                        <label for="studentDisplay" class="font-weight-bold"><i class="fas fa-user-check mr-1"></i>Select Students (Optional)</label>
                                        <div class="input-group-icon">
                                            <input type="text" id="studentDisplay" class="form-control" placeholder="Leave blank to assign to all students...">
                                            <span class="input-icon"><i class="fas fa-search"></i></span>
                                        </div>
                                        <div id="studentOptions" class="dropdown-menu p-2 w-100">
                                            </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="fee_type" class="font-weight-bold"><i class="fas fa-tag mr-1"></i>Fee Type *</label>
                                        <input type="text" class="form-control" id="fee_type" name="fee_type" placeholder="e.g., Tuition Fee" required>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="amount" class="font-weight-bold"><i class="fa-solid fa-indian-rupee-sign mr-1"></i>Amount *</label>
                                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="1" placeholder="0.00" required>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="due_date" class="font-weight-bold"><i class="fas fa-calendar mr-1"></i>Due Date</label>
                                        <input type="date" class="form-control" id="due_date" name="due_date">
                                    </div>
                                    <div class="form-group col-md-2 d-flex align-items-end">
                                        <button type="submit" name="add_fee" class="btn btn-primary btn-block shadow-sm"><i class="fas fa-plus mr-1"></i>Add Fee</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-table mr-2"></i>Fee Structure Overview
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($fees)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-receipt fa-3x text-gray-300 mb-3"></i>
                                    <h5 class="text-gray-600">No Fees Created Yet</h5>
                                    <p class="text-muted">Start by creating your first fee using the form above.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th><i class="fas fa-graduation-cap mr-2"></i>Standard</th>
                                                <th><i class="fas fa-tag mr-2"></i>Fee Type</th>
                                                <th><i class="fa-solid fa-indian-rupee-sign"></i>Amount</th>
                                                <th><i class="fas fa-calendar mr-2"></i>Due Date</th>
                                                <th><i class="fas fa-chart-pie mr-2"></i>Payment Status</th>
                                                <th><i class="fas fa-cog mr-2"></i>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fees as $fee) : ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-info badge-pill">
                                                            Standard <?php echo htmlspecialchars($fee['standard']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="font-weight-bold text-primary">
                                                            <?php echo htmlspecialchars($fee['fee_type']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="font-weight-bold">₹<?php echo number_format($fee['amount'], 2); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $due_date = new DateTime($fee['due_date']);
                                                        $now = new DateTime();
                                                        $is_overdue = ($due_date < $now);
                                                        ?>
                                                        <span class="<?php echo $is_overdue ? 'text-danger font-weight-bold' : ''; ?>">
                                                            <?php echo htmlspecialchars(date('d-M-Y', strtotime($fee['due_date']))); ?>
                                                            <?php if ($is_overdue): ?>
                                                                <small class="d-block">
                                                                    <i class="fas fa-exclamation-triangle"></i> Overdue
                                                                </small>
                                                            <?php endif; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($fee['total_students'] > 0): ?>
                                                             <div class="d-flex align-items-center">
                                                                <span class="mr-3">
                                                                    <strong><?php echo $fee['paid_students']; ?>/<?php echo $fee['total_students']; ?></strong> Paid
                                                                </span>
                                                                <div class="progress flex-grow-1" style="height: 10px;">
                                                                    <div class="progress-bar bg-success" role="progressbar"
                                                                        style="width: <?php echo ($fee['paid_students'] / $fee['total_students']) * 100; ?>%"
                                                                        aria-valuenow="<?php echo ($fee['paid_students'] / $fee['total_students']) * 100; ?>"
                                                                        aria-valuemin="0" aria-valuemax="100">
                                                                    </div>
                                                                </div>
                                                                <small class="ml-2 text-muted">
                                                                    <?php echo round(($fee['paid_students'] / $fee['total_students']) * 100, 1); ?>%
                                                                </small>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">No students assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-info btn-sm shadow-sm view-details-btn" 
                                                                data-fee-id="<?php echo $fee['id']; ?>" 
                                                                data-fee-type="<?php echo htmlspecialchars($fee['fee_type']); ?>"
                                                                data-standard="<?php echo htmlspecialchars($fee['standard']); ?>">
                                                            <i class="fas fa-eye mr-1"></i> View Details
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
    
    <div class="modal fade" id="feeDetailsModal" tabindex="-1" role="dialog" aria-labelledby="feeDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="feeDetailsModalLabel">
                        <i class="fas fa-chart-bar mr-2"></i>Payment Status Details
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="feeDetailsContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2">Loading student details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "order": [[ 3, "desc" ]], 
            "pageLength": 25,
            "responsive": true,
        });

        function setupCustomDropdown(wrapper) {
            const displayInput = wrapper.find('input[type="text"]');
            const optionsContainer = wrapper.find('.dropdown-menu');
            
            displayInput.on('focus', () => optionsContainer.show());
            displayInput.on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                optionsContainer.find('.form-check').each(function() {
                    const label = $(this).find('label').text().toLowerCase();
                    $(this).toggle(label.includes(searchTerm));
                });
            });

            optionsContainer.on('change', '.form-check-input', function() {
                const selectedLabels = [];
                optionsContainer.find('.form-check-input:checked').each(function() {
                    selectedLabels.push($(this).siblings('label').text().trim().split(' (')[0]);
                });
                displayInput.val(selectedLabels.join(', '));
            });
        }
        
        setupCustomDropdown($('#studentSelectorWrapper'));

        $(document).on('click', e => {
            if (!$(e.target).closest('.searchable-dropdown').length) $('.dropdown-menu').hide();
        });
        
        $('#standard').on('change', function() {
            const standard = $(this).val();
            const studentWrapper = $('#studentSelectorWrapper');
            const studentOptions = $('#studentOptions');
            const studentDisplay = $('#studentDisplay');

            studentOptions.html('');
            studentDisplay.val('');
            
            if (standard) {
                studentWrapper.slideDown();
                studentOptions.html('<div class="p-2 text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</div>');
                
                $.ajax({
                    url: 'get_students_by_standard.php',
                    type: 'GET',
                    data: { standard: standard },
                    dataType: 'json',
                    success: function(students) {
                        studentOptions.html('');
                        if (students.length > 0) {
                            students.forEach(student => {
                                const optionHtml = `
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="student_ids[]" value="${student.id}" id="student_${student.id}">
                                        <label class="form-check-label" for="student_${student.id}">
                                            ${student.student_name} (Roll: ${student.rollno})
                                        </label>
                                    </div>`;
                                studentOptions.append(optionHtml);
                            });
                        } else {
                            studentOptions.html('<div class="p-2 text-muted text-center">No students found in this standard.</div>');
                        }
                    },
                    error: function() {
                        studentOptions.html('<div class="p-2 text-danger text-center">Error loading students.</div>');
                    }
                });
            } else {
                studentWrapper.slideUp();
            }
        });

        $('.view-details-btn').on('click', function() {
            var feeId = $(this).data('fee-id');
            var feeType = $(this).data('fee-type');
            var standard = $(this).data('standard');
            
            $('#feeDetailsModalLabel').html('<i class="fas fa-chart-bar mr-2"></i>Payment Status for Standard ' + standard + ' - ' + feeType);
            $('#feeDetailsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Loading...</p></div>');
            $('#feeDetailsModal').modal('show');
            
            $.ajax({
                url: 'get_fee_payment_details.php',
                type: 'GET',
                data: { fee_id: feeId },
                success: function(response) {
                    $('#feeDetailsContent').html(response);
                },
                error: function() {
                    $('#feeDetailsContent').html('<div class="text-center py-4"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><h5 class="text-danger">Error Loading Data</h5></div>');
                }
            });
        });

        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
    </script>
</body>
</html>