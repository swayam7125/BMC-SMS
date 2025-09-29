<?php
require_once __DIR__ . "/../../includes/connect.php";
require_once __DIR__ . "/../../encryption.php";

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!isset($_COOKIE['encrypted_user_id'])) {
    header("Location: ../../login.php");
    exit;
}
$student_id = decrypt_id($_COOKIE['encrypted_user_id']);

// Fetch all fees for the student
$stmt = $conn->prepare("
    SELECT sf.id, f.fee_type, f.amount, f.due_date, sf.status, sf.payment_date
    FROM student_fees sf
    JOIN fees f ON sf.fee_id = f.id
    WHERE sf.student_id = :student_id
    ORDER BY f.due_date DESC
");
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$student_fees = $stmt->fetchAll();

// Calculate totals
$total_paid = 0;
$total_unpaid = 0;
$paid_count = 0;
$unpaid_count = 0;

foreach ($student_fees as $fee) {
    if ($fee['status'] === 'Paid') {
        $total_paid += $fee['amount'];
        $paid_count++;
    } else {
        $total_unpaid += $fee['amount'];
        $unpaid_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Fees - Student Portal</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">My Fees</h1>
                    </div>
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Paid</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($total_paid, 2); ?></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-check-circle fa text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Pending</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($total_unpaid, 2); ?></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-exclamation-circle fa text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Paid Count</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $paid_count; ?></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-clipboard-list fa text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Count</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $unpaid_count; ?></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-clock fa text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Fee Payment History</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($student_fees)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-receipt fa-3x text-gray-300 mb-3"></i>
                                    <h5 class="text-gray-600">No Fees Assigned Yet</h5>
                                    <p class="text-muted">You don't have any fees assigned to your account.</p>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="/BMC-SMS/pages/student/pay_fee.php" id="bulkPayForm" target="_blank">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="text-center"><input type="checkbox" id="selectAllCheckbox"></th>
                                                    <th>Fee Type</th>
                                                    <th>₹ Amount</th>
                                                    <th>Due Date</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($student_fees as $fee) : ?>
                                                    <tr>
                                                        <td class="text-center">
                                                            <?php if ($fee['status'] === 'Unpaid') : ?>
                                                                <input type="checkbox" class="fee-checkbox" name="fee_ids[]" value="<?php echo $fee['id']; ?>" data-amount="<?php echo $fee['amount']; ?>">
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><div class="font-weight-bold text-primary"><?php echo htmlspecialchars($fee['fee_type']); ?></div></td>
                                                        <td><span class="font-weight-bold">₹<?php echo number_format($fee['amount'], 2); ?></span></td>
                                                        <td>
                                                            <?php 
                                                            $due_date = new DateTime($fee['due_date']);
                                                            $now = new DateTime();
                                                            $is_overdue = ($fee['status'] === 'Unpaid' && $due_date < $now);
                                                            ?>
                                                            <span class="<?php echo $is_overdue ? 'text-danger font-weight-bold' : ''; ?>">
                                                                <?php echo htmlspecialchars(date('d-M-Y', strtotime($fee['due_date']))); ?>
                                                                <?php if ($is_overdue): ?><small class="d-block"><i class="fas fa-exclamation-triangle"></i> Overdue</small><?php endif; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($fee['status'] === 'Paid') : ?>
                                                                <span class="badge badge-success badge-pill"><i class="fas fa-check-circle mr-1"></i>Paid</span>
                                                                <small class="d-block text-muted mt-1"><?php echo date('d-M-Y', strtotime($fee['payment_date'])); ?></small>
                                                            <?php else : ?>
                                                                <span class="badge badge-danger badge-pill"><i class="fas fa-times-circle mr-1"></i>Unpaid</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($fee['status'] === 'Unpaid') : ?>
                                                                <a href="/BMC-SMS/pages/student/pay_fee.php?fee_id=<?php echo $fee['id']; ?>" class="btn btn-success btn-sm shadow-sm" target="_blank"><i class="fas fa-credit-card mr-1"></i> Pay Now</a>
                                                            <?php else : ?>
                                                                <a href="/BMC-SMS/pages/student/pay_fee.php?fee_id=<?php echo $fee['id']; ?>" class="btn btn-primary btn-sm shadow-sm" target="_blank"><i class="fas fa-download mr-1"></i> Receipt</a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="payment-summary" class="mt-4" style="display: none;">
                                        <div class="card border-left-primary shadow-sm">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8">
                                                        <h5 class="font-weight-bold text-primary mb-1">
                                                            <span id="selected-count">0</span> fee(s) selected for payment
                                                        </h5>
                                                        <p class="text-muted mb-0">Select one or more unpaid fees to pay them all at once.</p>
                                                    </div>
                                                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                                                        <h4 class="font-weight-bold mb-2">Total: ₹<span id="total-amount">0.00</span></h4>
                                                        <button type="submit" id="pay-selected-btn" class="btn btn-success btn-lg shadow-sm" disabled>
                                                            <i class="fas fa-shield-alt mr-2"></i>Proceed to Pay
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
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
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="../../assets/js/responsive-tables.js"></script>


    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({ "order": [[ 3, "desc" ]], "pageLength": 25 });

            const checkboxes = $('.fee-checkbox');
            const selectAll = $('#selectAllCheckbox');
            const payBtn = $('#pay-selected-btn');
            const paymentSummary = $('#payment-summary');
            const totalAmountSpan = $('#total-amount');
            const selectedCountSpan = $('#selected-count');

            function updateSummary() {
                let total = 0;
                let count = 0;
                checkboxes.filter(':checked').each(function() {
                    total += parseFloat($(this).data('amount'));
                    count++;
                });

                totalAmountSpan.text(total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                selectedCountSpan.text(count);

                if (count > 0) {
                    paymentSummary.slideDown();
                    payBtn.prop('disabled', false);
                } else {
                    paymentSummary.slideUp();
                    payBtn.prop('disabled', true);
                }
                selectAll.prop('checked', count > 0 && count === checkboxes.length);
            }

            selectAll.on('change', function() {
                checkboxes.prop('checked', $(this).is(':checked')).trigger('change');
            });

            checkboxes.on('change', function() {
                updateSummary();
            });
            updateSummary();
        });
    </script>
</body>
</html>