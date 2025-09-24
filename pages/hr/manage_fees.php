<?php
// pages/hr/manage_fees.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

include_once "../../includes/connect.php";
include_once "../../encryption.php";
require_once "../../includes/log_system.php";

$role = null;
$userId = null;
$hr_school_id = null;
$standards = [];
$fee_history = []; 

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'hr' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

try {
    // Get HR's school ID
    $stmt_school = $conn->prepare('SELECT school_id FROM hr WHERE id = ?');
    $stmt_school->execute([$userId]);
    $hr_data = $stmt_school->fetch(PDO::FETCH_ASSOC);

    if ($hr_data) {
        $hr_school_id = $hr_data['school_id'];

        $stmt_standards = $conn->prepare('
            SELECT DISTINCT scm.standard_name
            FROM "school" s
            JOIN "standard_categories_mapping" scm ON scm.category_name = ANY(s.school_category)
            WHERE s.id = ? ORDER BY scm.standard_name
        ');
        $stmt_standards->execute([$hr_school_id]);
        $standards = $stmt_standards->fetchAll(PDO::FETCH_ASSOC);

        // Get fee history with proper grouping - FIXED QUERY
        $stmt_history = $conn->prepare("
            SELECT
                std, academic_year, fee_type, amount, fee_month, fee_year,
                COUNT(student_id) AS total_students,
                SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) AS paid_students
                -- Removed 'MIN(created_at) as date_added' because the column does not exist.
            FROM student_fees
            WHERE school_id = ?
            GROUP BY std, academic_year, fee_type, amount, fee_month, fee_year
            ORDER BY fee_year DESC, fee_month DESC, 
                CASE 
                    -- Changed REGEXP to ~ and CAST to INTEGER for PostgreSQL compatibility.
                    WHEN std ~ '^[0-9]+$' THEN CAST(std AS INTEGER)
                    ELSE 999
                END,
                std, fee_type
        ");
        $stmt_history->execute([$hr_school_id]);
        $fee_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database error on manage_fees.php: " . $e->getMessage());
    $fee_history = [];
    $standards = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Student Fees - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <style>
        .progress {
            height: 20px;
        }
        .card-header {
            border-bottom: 2px solid #e3e6f0;
        }
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        .table th {
            border-top: none;
            font-weight: 600;
        }
        .view-details-btn {
            transition: all 0.3s ease;
        }
        .view-details-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-money-bill-wave text-primary"></i> Manage Student Fees
                        </h1>
                    </div>
                    
                    <div id="alert-placeholder"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-plus-circle"></i> Add New Fee
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($standards)): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    No standards found for your school. Please contact the administrator to set up standard categories.
                                </div>
                            <?php else: ?>
                                <form id="addFeeForm" method="POST" action="process_add_fees.php">
                                    <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($hr_school_id); ?>">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="standard"><i class="fas fa-graduation-cap"></i> Standard / Class *</label>
                                            <select class="form-control" id="standard" name="standard" required>
                                                <option value="">-- Select Standard --</option>
                                                <?php foreach ($standards as $standard): ?>
                                                    <option value="<?php echo htmlspecialchars($standard['standard_name']); ?>">
                                                        <?php echo htmlspecialchars($standard['standard_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="academic_year"><i class="fas fa-calendar-alt"></i> Academic Year *</label>
                                            <select class="form-control" id="academic_year" name="academic_year" required>
                                                <option value="">-- Select Year --</option>
                                                <?php
                                                $currentYear = date('Y');
                                                for ($i = -1; $i <= 2; $i++):
                                                    $year = $currentYear + $i;
                                                    $academicYear = $year . '-' . ($year + 1);
                                                    $selected = ($i === 0) ? 'selected' : ''; // Current year selected by default
                                                    echo "<option value='" . $academicYear . "' " . $selected . ">" . $academicYear . "</option>";
                                                endfor;
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="fee_type"><i class="fas fa-tag"></i> Fee Type *</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="fee_type" 
                                                   name="fee_type" 
                                                   placeholder="e.g., School Fee, Activity Fee, Transport Fee"
                                                   maxlength="50" 
                                                   required>
                                            <small class="form-text text-muted">Maximum 50 characters</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="fee_amount"><i class="fas fa-rupee-sign"></i> Fee Amount (per student) *</label>
                                            <input type="number" 
                                                   step="0.01" 
                                                   class="form-control" 
                                                   id="fee_amount" 
                                                   name="fee_amount" 
                                                   placeholder="Enter amount in ₹"
                                                   required 
                                                   min="0.01" 
                                                   max="999999.99">
                                        </div>
                                    </div>
                                    <div class="form-group mt-4">
                                        <button type="submit" class="btn btn-primary btn-icon-split">
                                            <span class="icon text-white-50">
                                                <i class="fas fa-plus"></i>
                                            </span>
                                            <span class="text">Add Fees to Students</span>
                                        </button>
                                        <button type="reset" class="btn btn-secondary ml-2">
                                            <i class="fas fa-undo"></i> Reset Form
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-history"></i> Fee Assignment History
                            </h6>
                            <span class="badge badge-info"><?php echo count($fee_history); ?> records</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($fee_history)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No fee history found. Start by adding fees to students using the form above.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="feeHistoryTable" width="100%" cellspacing="0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th><i class="fas fa-calendar"></i> Date Added</th>
                                                <th><i class="fas fa-graduation-cap"></i> Standard</th>
                                                <th><i class="fas fa-calendar-alt"></i> Academic Year</th>
                                                <th><i class="fas fa-tag"></i> Fee Type</th>
                                                <th><i class="fas fa-rupee-sign"></i> Amount</th>
                                                <th><i class="fas fa-chart-pie"></i> Payment Status</th>
                                                <th><i class="fas fa-cogs"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fee_history as $fee): ?>
                                                <?php
                                                    $total = (int)$fee['total_students'];
                                                    $paid = (int)$fee['paid_students'];
                                                    $percentage = ($total > 0) ? ($paid / $total) * 100 : 0;
                                                    $unpaid = $total - $paid;
                                                    
                                                    // Determine progress bar color
                                                    $progressClass = 'bg-danger';
                                                    if ($percentage >= 75) $progressClass = 'bg-success';
                                                    elseif ($percentage >= 50) $progressClass = 'bg-warning';
                                                    elseif ($percentage >= 25) $progressClass = 'bg-info';
                                                ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-light">
                                                            <?php echo date('M Y', mktime(0, 0, 0, $fee['fee_month'], 1, $fee['fee_year'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-primary"><?php echo htmlspecialchars($fee['std']); ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($fee['academic_year']); ?></td>
                                                    <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                                    <td class="font-weight-bold text-success">₹<?php echo number_format($fee['amount'], 2); ?></td>
                                                    <td>
                                                        <div class="mb-2">
                                                            <small class="text-muted">
                                                                <i class="fas fa-check-circle text-success"></i> <?php echo $paid; ?> paid
                                                                <span class="mx-1">•</span>
                                                                <i class="fas fa-clock text-warning"></i> <?php echo $unpaid; ?> pending
                                                            </small>
                                                        </div>
                                                        <div class="progress">
                                                            <div class="progress-bar <?php echo $progressClass; ?>" 
                                                                 role="progressbar" 
                                                                 style="width: <?php echo $percentage; ?>%" 
                                                                 aria-valuenow="<?php echo $percentage; ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100"
                                                                 title="<?php echo number_format($percentage, 1); ?>% paid">
                                                                <?php echo number_format($percentage, 0); ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-info btn-sm view-details-btn"
                                                            data-standard="<?php echo htmlspecialchars($fee['std']); ?>"
                                                            data-academic-year="<?php echo htmlspecialchars($fee['academic_year']); ?>"
                                                            data-fee-type="<?php echo htmlspecialchars($fee['fee_type']); ?>"
                                                            data-fee-month="<?php echo htmlspecialchars($fee['fee_month']); ?>"
                                                            data-fee-year="<?php echo htmlspecialchars($fee['fee_year']); ?>"
                                                            title="View detailed payment status">
                                                            <i class="fas fa-eye"></i> View Details
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
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    
    <div class="modal fade" id="feeDetailsModal" tabindex="-1" role="dialog" aria-labelledby="feeDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feeDetailsModalLabel">
                        <i class="fas fa-list-alt"></i> Payment Status Details
                    </h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body" id="feeDetailsModalBody">
                    </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable with enhanced configuration
            $('#feeHistoryTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 10,
                "columnDefs": [
                    { "orderable": false, "targets": [5, 6] }, // Payment status and actions columns
                    { "width": "10%", "targets": 0 }, // Date column
                    { "width": "8%", "targets": 1 }, // Standard column
                    { "width": "12%", "targets": 2 }, // Academic year column
                    { "width": "20%", "targets": 3 }, // Fee type column
                    { "width": "10%", "targets": 4 }, // Amount column
                    { "width": "25%", "targets": 5 }, // Payment status column
                    { "width": "15%", "targets": 6 }  // Actions column
                ],
                "language": {
                    "emptyTable": "No fee records found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ fee records",
                    "infoEmpty": "No records to display",
                    "search": "Search fees:",
                    "lengthMenu": "Show _MENU_ records per page"
                }
            });

            // View Details Button Click Handler
            $(document).on('click', '.view-details-btn', function() {
                const btn = $(this);
                const modal = $('#feeDetailsModal');
                const modalBody = $('#feeDetailsModalBody');
                const modalTitle = $('#feeDetailsModalLabel');

                const feeData = {
                    school_id: '<?php echo $hr_school_id; ?>',
                    standard: btn.data('standard'),
                    academic_year: btn.data('academic-year'),
                    fee_type: btn.data('fee-type'),
                    fee_month: btn.data('fee-month'),
                    fee_year: btn.data('fee-year')
                };
                
                modalTitle.html('<i class="fas fa-list-alt"></i> Payment Details: ' + feeData.fee_type + ' - Class ' + feeData.standard);
                modalBody.html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading payment details...</p></div>');
                modal.modal('show');

                $.ajax({
                    url: 'get_fee_payment_details.php',
                    type: 'POST',
                    data: feeData,
                    dataType: 'json',
                    timeout: 15000,
                    success: function(response) {
                        if (response.success) {
                            let html = '<div class="row">';
                            
                            // Paid Students Column
                            html += '<div class="col-md-6">';
                            html += '<div class="card border-success">';
                            html += '<div class="card-header bg-success text-white">';
                            html += '<h6 class="m-0"><i class="fas fa-check-circle"></i> Paid Students (' + response.paid.length + ')</h6>';
                            html += '</div>';
                            html += '<div class="card-body" style="max-height: 400px; overflow-y: auto;">';
                            
                            if (response.paid.length > 0) {
                                html += '<div class="list-group list-group-flush">';
                                response.paid.forEach(student => {
                                    html += '<div class="list-group-item d-flex justify-content-between align-items-center">';
                                    html += '<div>';
                                    html += '<strong>' + student.student_name + '</strong><br>';
                                    html += '<small class="text-muted">Roll No: ' + student.rollno + '</small>';
                                    if (student.paid_at) {
                                        html += '<br><small class="text-success">Paid: ' + new Date(student.paid_at).toLocaleDateString() + '</small>';
                                    }
                                    html += '</div>';
                                    html += '<span class="badge badge-success"><i class="fas fa-check"></i></span>';
                                    html += '</div>';
                                });
                                html += '</div>';
                            } else {
                                html += '<div class="text-center text-muted py-3">';
                                html += '<i class="fas fa-info-circle fa-2x mb-2"></i>';
                                html += '<p>No students have paid this fee yet.</p>';
                                html += '</div>';
                            }
                            html += '</div></div></div>';
                            
                            // Unpaid Students Column
                            html += '<div class="col-md-6">';
                            html += '<div class="card border-danger">';
                            html += '<div class="card-header bg-danger text-white">';
                            html += '<h6 class="m-0"><i class="fas fa-times-circle"></i> Unpaid Students (' + response.unpaid.length + ')</h6>';
                            html += '</div>';
                            html += '<div class="card-body" style="max-height: 400px; overflow-y: auto;">';
                            
                            if (response.unpaid.length > 0) {
                                html += '<div class="list-group list-group-flush">';
                                response.unpaid.forEach(student => {
                                    html += '<div class="list-group-item d-flex justify-content-between align-items-center">';
                                    html += '<div>';
                                    html += '<strong>' + student.student_name + '</strong><br>';
                                    html += '<small class="text-muted">Roll No: ' + student.rollno + '</small>';
                                    html += '</div>';
                                    html += '<span class="badge badge-danger"><i class="fas fa-clock"></i></span>';
                                    html += '</div>';
                                });
                                html += '</div>';
                            } else {
                                html += '<div class="text-center text-success py-3">';
                                html += '<i class="fas fa-check-circle fa-2x mb-2"></i>';
                                html += '<p>All students have paid this fee!</p>';
                                html += '</div>';
                            }
                            html += '</div></div></div>';
                            html += '</div>';
                            
                            modalBody.html(html);
                        } else {
                            modalBody.html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' + response.message + '</div>');
                        }
                    },
                    error: function(jqXHR, textStatus) {
                        let errorMsg = 'An error occurred while fetching payment details.';
                        if (textStatus === 'timeout') {
                            errorMsg = 'Request timed out. Please try again.';
                        }
                        modalBody.html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' + errorMsg + '</div>');
                    }
                });
            });
            
            // Add Fee Form Submission
            $('#addFeeForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const btn = form.find('button[type="submit"]');
                const originalHtml = btn.html();
                
                // Client-side validation
                const amount = parseFloat($('#fee_amount').val());
                if (amount <= 0 || amount > 999999.99) {
                    showAlert('danger', 'Please enter a valid amount between ₹0.01 and ₹999,999.99');
                    return;
                }
                
                const feeType = $('#fee_type').val().trim();
                if (feeType.length > 50) {
                    showAlert('danger', 'Fee type cannot exceed 50 characters');
                    return;
                }
                
                // Disable button and show loading
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding fees...');

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    timeout: 30000,
                    success: function(response) {
                        showAlert(response.success ? 'success' : 'danger', response.message);
                        
                        if (response.success) {
                            form[0].reset();
                            // Reload page after 3 seconds to show updated fee history
                            setTimeout(function() { 
                                window.location.reload(); 
                            }, 3000);
                        }
                    },
                    error: function(jqXHR, textStatus) {
                        let errorMsg = 'An unexpected error occurred. Please try again.';
                        if (textStatus === 'timeout') {
                            errorMsg = 'Request timed out. Please check your connection and try again.';
                        }
                        showAlert('danger', errorMsg);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
            
            // Helper function to show alerts
            function showAlert(type, message) {
                const alertClass = 'alert-' + type;
                const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
                
                const alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                    '<i class="' + iconClass + '"></i> ' + message +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span></button></div>';
                
                $('#alert-placeholder').html(alertHtml);
                
                // Scroll to alert
                $('html, body').animate({
                    scrollTop: $("#alert-placeholder").offset().top - 100
                }, 500);
                
                // Auto-hide success alerts
                if (type === 'success') {
                    setTimeout(function() {
                        $('.alert-success').fadeOut();
                    }, 5000);
                }
            }
            
            // Form validation on input
            $('#fee_amount').on('input', function() {
                const amount = parseFloat($(this).val());
                if (amount > 999999.99) {
                    $(this).val(999999.99);
                }
            });
            
            $('#fee_type').on('input', function() {
                const maxLength = 50;
                const currentLength = $(this).val().length;
                if (currentLength > maxLength) {
                    $(this).val($(this).val().substring(0, maxLength));
                }
                // Show character count
                const remaining = maxLength - $(this).val().length;
                $(this).next('small').html('Characters remaining: ' + remaining + ' / ' + maxLength);
            });
        });
    </script>
</body>
</html>