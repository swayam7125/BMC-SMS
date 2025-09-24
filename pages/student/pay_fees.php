<?php
require_once "../../includes/connect.php";
require_once "../../encryption.php";
require_once "../../includes/ajax_helpers.php";
require_once '../../includes/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// Authorization check
$role = null;
$userId = null;
$student_name = null;
$student_std = null;
$school_id = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
if ($role !== 'student' || !$userId) {
    if (is_ajax_request()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    } else {
        header("Location: ../../login.php");
    }
    exit;
}

// Fetch student details for context
try {
    $stmt = $conn->prepare("SELECT student_name, std, school_id FROM student WHERE id = ?");
    $stmt->execute([$userId]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student_data) {
        $student_name = $student_data['student_name'];
        $student_std = $student_data['std'];
        $school_id = $student_data['school_id'];
    }
} catch (PDOException $e) {
    error_log("Student data fetch error: " . $e->getMessage());
    die("A system error occurred.");
}

// Handle POST request for payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax_request()) {
    header('Content-Type: application/json');

    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $fee_id = filter_input(INPUT_POST, 'fee_id', FILTER_VALIDATE_INT);
    $paid_at = date('Y-m-d H:i:s');
    $invoice_path = null;

    if ($amount === false || $amount <= 0 || !$fee_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount or fee ID.']);
        exit;
    }

    try {
        $conn->beginTransaction();
        
        // Update the fee status in the database
        $stmt_update = $conn->prepare("UPDATE student_fees SET status = 'Paid', paid_at = ?, invoice_path = ? WHERE id = ? AND student_id = ? AND amount = ? AND status = 'Unpaid'");
        $stmt_update->execute([$paid_at, $invoice_path, $fee_id, $userId, $amount]);

        if ($stmt_update->rowCount() > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Payment successful!', 'fee_id' => $fee_id]);
        } else {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to process payment. Fee already paid or record mismatch.']);
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Payment processing error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again.']);
    }
    exit;
}

// Fetch outstanding fees for the student
$outstanding_fees = [];
try {
    $stmt = $conn->prepare("SELECT id, academic_year, std, fee_month, fee_year, amount, fee_type FROM student_fees WHERE student_id = ? AND status = 'Unpaid'");
    $stmt->execute([$userId]);
    $outstanding_fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Outstanding fees fetch error: " . $e->getMessage());
}

// Fetch payment history for the student
$payment_history = [];
try {
    $stmt = $conn->prepare("SELECT id, academic_year, std, fee_month, fee_year, amount, fee_type, paid_at FROM student_fees WHERE student_id = ? AND status = 'Paid' ORDER BY paid_at DESC");
    $stmt->execute([$userId]);
    $payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Payment history fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pay Fees - Student Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .container-fluid {
            padding: 2rem;
        }
    </style>
</head>
<body id="page-top">
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Fee Payment</h1>
        <div id="alert-placeholder"></div>

        <div class="row">
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Outstanding Fees</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($outstanding_fees)): ?>
                            <div class="alert alert-info">No outstanding fees at this time.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
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
                                                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-credit-card"></i> Pay Now</button>
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

            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Payment History</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($payment_history)): ?>
                            <div class="alert alert-info">No payment history found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Academic Year</th>
                                            <th>Standard</th>
                                            <th>Fee Type</th>
                                            <th>Amount Paid</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payment_history as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['academic_year']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['std']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                                                <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo date('d-M-Y', strtotime($payment['paid_at'])); ?></td>
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

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
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
                        if (response.success) {
                            $('#alert-placeholder').html('<div class="alert alert-success">Payment successful! Reloading page...</div>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $('#alert-placeholder').html('<div class="alert alert-danger">' + response.message + '</div>');
                            btn.prop('disabled', false).html('<i class="fas fa-credit-card"></i> Pay Now');
                        }
                    },
                    error: function() {
                        $('#alert-placeholder').html('<div class="alert alert-danger">An unexpected error occurred. Please try again.</div>');
                        btn.prop('disabled', false).html('<i class="fas fa-credit-card"></i> Pay Now');
                    }
                });
            });
        });
    </script>
</body>
</html>