<?php
// pages/hr/manage_fees.php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";
require_once "../../includes/log_system.php";

$role = null;
$userId = null;
$hr_school_id = null;
$standards = [];
$fee_history = []; // NEW: Variable to hold fee history

// --- 1. User Authentication and Authorization ---
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

// --- 2. Fetch HR's School, Available Standards, and Fee History ---
try {
    $stmt_school = $conn->prepare('SELECT s.id FROM hr h JOIN school s ON h.school_id = s.id WHERE h.id = ?');
    $stmt_school->execute([$userId]);
    $hr_data = $stmt_school->fetch(PDO::FETCH_ASSOC);

    if ($hr_data) {
        $hr_school_id = $hr_data['id'];

        // Fetch standards for the dropdown
        $stmt_standards = $conn->prepare('
            SELECT DISTINCT scm.standard_name
            FROM "school" s
            JOIN "standard_categories_mapping" scm ON scm.category_name = ANY(s.school_category)
            WHERE s.id = ? ORDER BY scm.standard_name
        ');
        $stmt_standards->execute([$hr_school_id]);
        $standards = $stmt_standards->fetchAll(PDO::FETCH_ASSOC);

        // NEW: Fetch fee assignment history
        $stmt_history = $conn->prepare("
            SELECT
                std, academic_year, fee_type, amount, fee_month, fee_year,
                COUNT(student_id) AS total_students,
                COUNT(student_id) FILTER (WHERE status = 'Paid') AS paid_students
            FROM student_fees
            WHERE school_id = ?
            GROUP BY std, academic_year, fee_type, amount, fee_month, fee_year
            ORDER BY fee_year DESC, fee_month DESC, std
        ");
        $stmt_history->execute([$hr_school_id]);
        $fee_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database error on manage_fees.php: " . $e->getMessage());
    $errors[] = "A database error occurred. Please try again later.";
}

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Student Fees - School Management System</title>
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
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Add Student Fees</h1>
                    </div>
                    <div id="alert-placeholder"></div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Fee Information</h6>
                        </div>
                        <div class="card-body">
                            <form id="addFeeForm" method="POST" action="process_add_fees.php">
                                <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($hr_school_id); ?>">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="standard">Standard / Class *</label>
                                        <select class="form-control" id="standard" name="standard" required>
                                            <option value="">-- Select Standard --</option>
                                            <?php foreach ($standards as $standard): ?>
                                                <option value="<?php echo htmlspecialchars($standard['standard_name']); ?>"><?php echo htmlspecialchars($standard['standard_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="academic_year">Academic Year *</label>
                                        <select class="form-control" id="academic_year" name="academic_year" required>
                                            <option value="">-- Select Year --</option>
                                            <?php
                                            $currentYear = date('Y');
                                            for ($i = -1; $i <= 2; $i++):
                                                $year = $currentYear + $i;
                                                $academicYear = $year . '-' . ($year + 1);
                                                echo "<option value='" . $academicYear . "'>" . $academicYear . "</option>";
                                            endfor;
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="fee_type">Fee Type (e.g., School Fee, Activity Fee) *</label>
                                        <input type="text" class="form-control" id="fee_type" name="fee_type" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="fee_amount">Fee Amount (per student) *</label>
                                        <input type="number" step="0.01" class="form-control" id="fee_amount" name="fee_amount" required min="0">
                                    </div>
                                </div>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Fees</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Fee Assignment History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="feeHistoryTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date Added</th>
                                            <th>Standard</th>
                                            <th>Academic Year</th>
                                            <th>Fee Type</th>
                                            <th>Amount</th>
                                            <th>Payment Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($fee_history)): ?>
                                            <tr><td colspan="6" class="text-center">No fee history found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($fee_history as $fee): ?>
                                                <tr>
                                                    <td><?php echo date('F Y', mktime(0, 0, 0, $fee['fee_month'], 1, $fee['fee_year'])); ?></td>
                                                    <td><?php echo htmlspecialchars($fee['std']); ?></td>
                                                    <td><?php echo htmlspecialchars($fee['academic_year']); ?></td>
                                                    <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                                    <td>₹<?php echo number_format($fee['amount'], 2); ?></td>
                                                    <td>
                                                        <?php
                                                            $total = (int)$fee['total_students'];
                                                            $paid = (int)$fee['paid_students'];
                                                            $percentage = ($total > 0) ? ($paid / $total) * 100 : 0;
                                                        ?>
                                                        <div class="mb-1"><?php echo $paid; ?> / <?php echo $total; ?> Paid</div>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
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
            // NEW: Initialize DataTable for the history table
            $('#feeHistoryTable').DataTable({
                "order": [[ 0, "desc" ]] // Sort by the first column (Date Added) descending
            });

            $('#addFeeForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const btn = form.find('button[type="submit"]');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        let alertClass = response.success ? 'alert-success' : 'alert-danger';
                        $('#alert-placeholder').html('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' + response.message + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        
                        if (response.success) {
                            form[0].reset();
                            // Reload the page to update the history table
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            btn.prop('disabled', false).html('<i class="fas fa-plus"></i> Add Fees');
                        }
                    },
                    error: function() {
                        $('#alert-placeholder').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">An unexpected error occurred. Please try again.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        btn.prop('disabled', false).html('<i class="fas fa-plus"></i> Add Fees');
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php
}
?>