<?php
require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/../../encryption.php';
require_once __DIR__ . '/../../includes/ajax_helpers.php';

$role = null;
$userId = null;
$student_name = null;
$school_id = null;
$student_std = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Authorization check
if ($role !== 'student' || !$userId) {
    if (is_ajax_request()) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
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

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Fees</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <style>
        .container-fluid {
            padding: 2rem;
        }
        .btn-pay {
            width: 100%;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php require_once __DIR__ . '/../../includes/header.php'; ?>
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
                                                                    <button type="submit" class="btn btn-sm btn-success btn-pay">Pay Now</button>
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
                                            <table class="table table-bordered" id="paymentHistoryTable" width="100%" cellspacing="0">
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
            </div>
            <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/js/ajax-forms.js"></script>
    <script>
        $(document).ready(function() {
            $('.pay-fee-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const btn = form.find('button[type="submit"]');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

                $.ajax({
                    url: 'pay_fees.php', 
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#alert-placeholder').html('<div class="alert alert-success">Payment successful! Reloading page...</div>');
                            setTimeout(function() {
                                // Reload the content of the main section
                                $('#main-content').load('view_fees.php');
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
<?php
} else {
    // This is an AJAX request, so we only serve the content.
    // The main layout has already been rendered.
    // The script should be modified to work within the AJAX framework if it is to be a part of it.
    // The previous code block is the full-page version. This is the AJAX version.
    
    // We can assume the page content has been loaded, so just render the content section.
    // For a fully dynamic page, you would need to re-render the sections here.
    // However, given the nature of the request, we'll assume the full page reload is handled on the client side.
    
    // Re-run the data fetching logic to ensure fresh data.
    $outstanding_fees = [];
    try {
        $stmt = $conn->prepare("SELECT id, academic_year, std, fee_month, fee_year, amount, fee_type FROM student_fees WHERE student_id = ? AND status = 'Unpaid'");
        $stmt->execute([$userId]);
        $outstanding_fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Outstanding fees fetch error for AJAX: " . $e->getMessage());
    }

    $payment_history = [];
    try {
        $stmt = $conn->prepare("SELECT id, academic_year, std, fee_month, fee_year, amount, fee_type, paid_at FROM student_fees WHERE student_id = ? AND status = 'Paid' ORDER BY paid_at DESC");
        $stmt->execute([$userId]);
        $payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Payment history fetch error for AJAX: " . $e->getMessage());
    }

    // Now, render the content that would be inside the container-fluid div.
    // The client-side script will inject this HTML into the #main-content div.
    echo '<div class="container-fluid">';
    echo '<h1 class="h3 mb-4 text-gray-800">Fee Payment</h1>';
    echo '<div id="alert-placeholder"></div>';

    echo '<div class="row">';
    
    // Outstanding Fees Card
    echo '<div class="col-xl-6 col-lg-6">';
    echo '<div class="card shadow mb-4">';
    echo '<div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Outstanding Fees</h6></div>';
    echo '<div class="card-body">';
    if (empty($outstanding_fees)) {
        echo '<div class="alert alert-info">No outstanding fees at this time.</div>';
    } else {
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered" id="outstandingFeesTable" width="100%" cellspacing="0">';
        echo '<thead><tr><th>Academic Year</th><th>Standard</th><th>Fee Type</th><th>Amount</th><th>Action</th></tr></thead>';
        echo '<tbody>';
        foreach ($outstanding_fees as $fee) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($fee['academic_year']) . '</td>';
            echo '<td>' . htmlspecialchars($fee['std']) . '</td>';
            echo '<td>' . htmlspecialchars($fee['fee_type']) . '</td>';
            echo '<td>₹' . number_format($fee['amount'], 2) . '</td>';
            echo '<td>';
            echo '<form class="pay-fee-form">';
            echo '<input type="hidden" name="fee_id" value="' . $fee['id'] . '">';
            echo '<input type="hidden" name="amount" value="' . $fee['amount'] . '">';
            echo '<button type="submit" class="btn btn-sm btn-success btn-pay">Pay Now</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    echo '</div></div></div>';

    // Payment History Card
    echo '<div class="col-xl-6 col-lg-6">';
    echo '<div class="card shadow mb-4">';
    echo '<div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Payment History</h6></div>';
    echo '<div class="card-body">';
    if (empty($payment_history)) {
        echo '<div class="alert alert-info">No payment history found.</div>';
    } else {
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered" id="paymentHistoryTable" width="100%" cellspacing="0">';
        echo '<thead><tr><th>Academic Year</th><th>Standard</th><th>Fee Type</th><th>Amount Paid</th><th>Date</th></tr></thead>';
        echo '<tbody>';
        foreach ($payment_history as $payment) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($payment['academic_year']) . '</td>';
            echo '<td>' . htmlspecialchars($payment['std']) . '</td>';
            echo '<td>' . htmlspecialchars($payment['fee_type']) . '</td>';
            echo '<td>₹' . number_format($payment['amount'], 2) . '</td>';
            echo '<td>' . date('d-M-Y', strtotime($payment['paid_at'])) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    echo '</div></div></div>';
    
    echo '</div>'; // End of row
    echo '</div>'; // End of container-fluid

    // Include the scripts necessary for this page to function correctly within the AJAX framework
    echo '<script src="../../assets/vendor/jquery/jquery.min.js"></script>';
    echo '<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>';
    echo '<script src="../../assets/js/sb-admin-2.min.js"></script>';
    echo '<script src="../../assets/js/ajax-forms.js"></script>';
    echo '<script>
        $(document).ready(function() {
            $(\'.pay-fee-form\').on(\'submit\', function(e) {
                e.preventDefault();
                const form = $(this);
                const btn = form.find(\'button[type="submit"]\');
                btn.prop(\'disabled\', true).html(\'<i class="fas fa-spinner fa-spin"></i> Processing...\');

                $.ajax({
                    url: \'pay_fees.php\',
                    type: \'POST\',
                    data: form.serialize(),
                    dataType: \'json\',
                    success: function(response) {
                        if (response.success) {
                            $(\'#alert-placeholder\').html(\'<div class="alert alert-success">Payment successful! Reloading page...</div>\');
                            setTimeout(function() {
                                $(\'#main-content\').load(\'view_fees.php\');
                            }, 2000);
                        } else {
                            $(\'#alert-placeholder\').html(\'<div class="alert alert-danger">\' + response.message + \'</div>\');
                            btn.prop(\'disabled\', false).html(\'<i class="fas fa-credit-card"></i> Pay Now\');
                        }
                    },
                    error: function() {
                        $(\'#alert-placeholder\').html(\'<div class="alert alert-danger">An unexpected error occurred. Please try again.</div>\');
                        btn.prop(\'disabled\', false).html(\'<i class="fas fa-credit-card"></i> Pay Now\');
                    }
                });
            });
        });
    </script>';
    exit;
}
?>