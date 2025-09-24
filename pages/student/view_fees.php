<?php
// The PHP part of this file remains the same.
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
} catch (PDOException $e) {
    error_log("Student data fetch error: " . $e->getMessage());
    die("A system error occurred.");
}

// Fetch outstanding fees
$outstanding_fees = [];
try {
    $stmt_outstanding = $conn->prepare("SELECT id, academic_year, std, fee_type, amount FROM student_fees WHERE student_id = ? AND status = 'Unpaid' ORDER BY fee_year, fee_month");
    $stmt_outstanding->execute([$userId]);
    $outstanding_fees = $stmt_outstanding->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Outstanding fees fetch error: " . $e->getMessage());
}

// Fetch payment history
$payment_history = [];
try {
    $stmt_history = $conn->prepare("SELECT academic_year, std, fee_type, amount, paid_at FROM student_fees WHERE student_id = ? AND status = 'Paid' ORDER BY paid_at DESC");
    $stmt_history->execute([$userId]);
    $payment_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Payment history fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Fees - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php require_once __DIR__ . '/../../includes/header.php'; ?>
                <div class="container-fluid" id="main-container">
                    <h1 class="h3 mb-4 text-gray-800">My Fee Payments</h1>
                    <div id="alert-placeholder"></div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Outstanding Fees</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($outstanding_fees)): ?>
                                        <div class="alert alert-info">You have no outstanding fees at this time. ✅</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="outstandingFeesTable" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Academic Year</th>
                                                        <th>Standard</th>
                                                        <th>Fee Type</th>
                                                        <th>Amount</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($outstanding_fees as $fee): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($fee['academic_year']); ?></td>
                                                            <td><?php echo htmlspecialchars($fee['std']); ?></td>
                                                            <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                                            <td>₹<?php echo number_format($fee['amount'], 2); ?></td>
                                                            <td>
                                                                <form class="pay-fee-form">
                                                                    <input type="hidden" name="fee_id" value="<?php echo $fee['id']; ?>">
                                                                    <input type="hidden" name="amount" value="<?php echo $fee['amount']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-success btn-icon-split">
                                                                        <span class="icon text-white-50"><i class="fas fa-credit-card"></i></span>
                                                                        <span class="text">Pay Now</span>
                                                                    </button>
                                                                </form>
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

                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Payment History</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($payment_history)): ?>
                                        <div class="alert alert-info">No payment history found.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="paymentHistoryTable" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Payment Date</th>
                                                        <th>Academic Year</th>
                                                        <th>Fee Type</th>
                                                        <th>Amount Paid</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($payment_history as $payment): ?>
                                                        <tr>
                                                            <td><?php echo date('d-M-Y', strtotime($payment['paid_at'])); ?></td>
                                                            <td><?php echo htmlspecialchars($payment['academic_year']); ?></td>
                                                            <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                                                            <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
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
        $('#outstandingFeesTable').DataTable();
        $('#paymentHistoryTable').DataTable();

        $('.pay-fee-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const btn = form.find('button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            $.ajax({
                url: 'process_pay_fees.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    let alertClass = response.success ? 'alert-success' : 'alert-danger';
                    $('#alert-placeholder').html('<div class="alert ' + alertClass + '">' + response.message + '</div>');
                    
                    if (response.success) {
                        setTimeout(function() {
                            location.reload(); 
                        }, 1500);
                    } else {
                        btn.prop('disabled', false).html('<span class="icon text-white-50"><i class="fas fa-credit-card"></i></span><span class="text">Pay Now</span>');
                    }
                },
                // --- IMPROVEMENT: More detailed error handling for debugging ---
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMessage = 'An unexpected error occurred. Please try again.';
                    if (jqXHR.responseText) {
                        // This can help debug if the server sends back an error message as plain text
                        console.error('Server Response:', jqXHR.responseText);
                    }
                    $('#alert-placeholder').html('<div class="alert alert-danger">' + errorMessage + ' (Status: ' + textStatus + ')</div>');
                    btn.prop('disabled', false).html('<span class="icon text-white-50"><i class="fas fa-credit-card"></i></span><span class="text">Pay Now</span>');
                }
            });
        });
    });
    </script>
</body>
</html>