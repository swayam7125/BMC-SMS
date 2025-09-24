<?php
// pages/student/view_fees.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/../../encryption.php';

$role = null;
$userId = null;
$student_name = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Authorization check
if ($role !== 'student' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

// Fetch student details
try {
    $stmt = $conn->prepare("SELECT student_name FROM student WHERE id = ?");
    $stmt->execute([$userId]);
    $student_name = $stmt->fetchColumn();
    
    if (!$student_name) {
        die("Student record not found.");
    }
} catch (PDOException $e) {
    error_log("Student data fetch error: " . $e->getMessage());
    die("A system error occurred while fetching student details.");
}

// Fetch outstanding fees
$outstanding_fees = [];
try {
    $stmt_outstanding = $conn->prepare("
        SELECT id, academic_year, std, fee_type, amount, fee_month, fee_year 
        FROM student_fees 
        WHERE student_id = ? AND status = 'Unpaid' 
        ORDER BY fee_year DESC, fee_month DESC, std, fee_type
    ");
    $stmt_outstanding->execute([$userId]);
    $outstanding_fees = $stmt_outstanding->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Outstanding fees fetch error: " . $e->getMessage());
    $outstanding_fees = [];
}

// Fetch payment history
$payment_history = [];
try {
    $stmt_history = $conn->prepare("
        SELECT id, academic_year, std, fee_type, amount, paid_at 
        FROM student_fees 
        WHERE student_id = ? AND status = 'Paid' 
        ORDER BY paid_at DESC
    ");
    $stmt_history->execute([$userId]);
    $payment_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Payment history fetch error: " . $e->getMessage());
    $payment_history = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Fees - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <style>
        .fee-amount {
            font-weight: bold;
            color: #e74a3b;
        }
        .paid-amount {
            font-weight: bold;
            color: #1cc88a;
        }
        .btn-pay {
            transition: all 0.3s ease;
        }
        .btn-pay:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .alert-success {
            border-left: 4px solid #1cc88a;
        }
        .alert-info {
            border-left: 4px solid #36b9cc;
        }
        .card-header {
            border-bottom: 2px solid #e3e6f0;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php require_once __DIR__ . '/../../includes/header.php'; ?>
                <div class="container-fluid" id="main-container">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-money-bill-wave text-primary"></i> My Fee Payments
                        </h1>
                        <small class="text-muted">Welcome, <?php echo htmlspecialchars($student_name); ?>!</small>
                    </div>
                    
                    <div id="alert-placeholder"></div>

                    <!-- Outstanding Fees Card -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-exclamation-triangle text-warning"></i> Outstanding Fees
                                    </h6>
                                    <span class="badge badge-danger"><?php echo count($outstanding_fees); ?> pending</span>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($outstanding_fees)): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-check-circle"></i> You have no outstanding fees at this time. Well done! ✅
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover" id="outstandingFeesTable" width="100%" cellspacing="0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th><i class="fas fa-calendar-alt"></i> Academic Year</th>
                                                        <th><i class="fas fa-graduation-cap"></i> Class</th>
                                                        <th><i class="fas fa-tag"></i> Fee Type</th>
                                                        <th><i class="fas fa-rupee-sign"></i> Amount</th>
                                                        <th><i class="fas fa-credit-card"></i> Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($outstanding_fees as $fee): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($fee['academic_year']); ?></td>
                                                            <td>
                                                                <span class="badge badge-info"><?php echo htmlspecialchars($fee['std']); ?></span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                                            <td class="fee-amount">₹<?php echo number_format($fee['amount'], 2); ?></td>
                                                            <td>
                                                                <form class="pay-fee-form d-inline">
                                                                    <input type="hidden" name="fee_id" value="<?php echo $fee['id']; ?>">
                                                                    <input type="hidden" name="amount" value="<?php echo $fee['amount']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-success btn-pay btn-icon-split">
                                                                        <span class="icon text-white-50">
                                                                            <i class="fas fa-credit-card"></i>
                                                                        </span>
                                                                        <span class="text">Pay Now</span>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-info">
                                                        <td colspan="3"><strong>Total Outstanding:</strong></td>
                                                        <td class="fee-amount">
                                                            <strong>₹<?php echo number_format(array_sum(array_column($outstanding_fees, 'amount')), 2); ?></strong>
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Payment History Card -->
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-history text-success"></i> Payment History
                                    </h6>
                                    <span class="badge badge-success"><?php echo count($payment_history); ?> payments</span>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($payment_history)): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> No payment history found.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover" id="paymentHistoryTable" width="100%" cellspacing="0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th><i class="fas fa-calendar-check"></i> Payment Date</th>
                                                        <th><i class="fas fa-calendar-alt"></i> Academic Year</th>
                                                        <th><i class="fas fa-graduation-cap"></i> Class</th>
                                                        <th><i class="fas fa-tag"></i> Fee Type</th>
                                                        <th><i class="fas fa-rupee-sign"></i> Amount Paid</th>
                                                        <th><i class="fas fa-download"></i> Receipt</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($payment_history as $payment): ?>
                                                        <tr>
                                                            <td>
                                                                <span class="badge badge-light">
                                                                    <?php echo date('d-M-Y H:i', strtotime($payment['paid_at'])); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($payment['academic_year']); ?></td>
                                                            <td>
                                                                <span class="badge badge-info"><?php echo htmlspecialchars($payment['std']); ?></span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                                                            <td class="paid-amount">₹<?php echo number_format($payment['amount'], 2); ?></td>
                                                            <td>
                                                                <a href="generate_invoice.php?fee_id=<?php echo $payment['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-primary" 
                                                                   target="_blank"
                                                                   title="Download Receipt">
                                                                    <i class="fas fa-download"></i> Receipt
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-success">
                                                        <td colspan="4"><strong>Total Paid:</strong></td>
                                                        <td class="paid-amount">
                                                            <strong>₹<?php echo number_format(array_sum(array_column($payment_history, 'amount')), 2); ?></strong>
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
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
        // Initialize DataTables with enhanced configuration
        $('#outstandingFeesTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 10,
            "language": {
                "emptyTable": "No outstanding fees found",
                "info": "Showing _START_ to _END_ of _TOTAL_ outstanding fees",
                "infoEmpty": "No fees to show",
                "search": "Search fees:"
            }
        });

        $('#paymentHistoryTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 10,
            "language": {
                "emptyTable": "No payment history found",
                "info": "Showing _START_ to _END_ of _TOTAL_ payments",
                "infoEmpty": "No payments to show",
                "search": "Search payments:"
            }
        });

        // Enhanced form submission with better error handling
        $('.pay-fee-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const btn = form.find('button[type="submit"]');
            const originalHtml = btn.html();
            const feeAmount = form.find('input[name="amount"]').val();
            
            // Confirm payment
            if (!confirm(`Are you sure you want to pay ₹${parseFloat(feeAmount).toFixed(2)}?`)) {
                return;
            }
            
            // Disable button and show loading
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            $.ajax({
                url: 'process_pay_fees.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                timeout: 30000, // 30 seconds timeout
                success: function(response) {
                    let alertClass = response.success ? 'alert-success' : 'alert-danger';
                    let alertIcon = response.success ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
                    
                    $('#alert-placeholder').html(
                        '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                        '<i class="' + alertIcon + '"></i> ' + response.message +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                        '<span aria-hidden="true">&times;</span></button></div>'
                    );
                    
                    // Scroll to alert
                    $('html, body').animate({
                        scrollTop: $("#alert-placeholder").offset().top - 100
                    }, 500);
                    
                    if (response.success && response.fee_id) {
                        // Trigger download
                        window.location.href = 'generate_invoice.php?fee_id=' + response.fee_id;
                        
                        // Auto-reload after download starts
                        setTimeout(function() {
                            window.location.reload(); 
                        }, 3000);
                    } else {
                        // Re-enable button on failure
                        btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMessage = 'An unexpected error occurred. Please try again.';
                    
                    if (textStatus === 'timeout') {
                        errorMessage = 'The request timed out. Please check your connection and try again.';
                    } else if (jqXHR.status === 500) {
                        errorMessage = 'A server error occurred. Please try again later.';
                    } else if (jqXHR.status === 403) {
                        errorMessage = 'Access denied. Please log in again.';
                    }
                    
                    console.error('Payment Error:', textStatus, errorThrown);
                    if (jqXHR.responseText) {
                        console.error('Server Response:', jqXHR.responseText);
                    }
                    
                    $('#alert-placeholder').html(
                        '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        '<i class="fas fa-exclamation-triangle"></i> ' + errorMessage +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                        '<span aria-hidden="true">&times;</span></button></div>'
                    );
                    
                    // Scroll to alert
                    $('html, body').animate({
                        scrollTop: $("#alert-placeholder").offset().top - 100
                    }, 500);
                    
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Auto-hide success alerts after 5 seconds
        setTimeout(function() {
            $('.alert-success').fadeOut();
        }, 5000);
    });
    </script>
</body>
</html>