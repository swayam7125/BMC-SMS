<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if this is an AJAX request
if (is_ajax_request()) {
    // Start output buffering to capture the HTML
    ob_start();
}

date_default_timezone_set('Asia/Kolkata');

// This function is also needed
function formatIndianCurrency($number) {
    $number = (string)round($number, 2); $parts = explode('.', $number); $integer_part = $parts[0]; $decimal_part = isset($parts[1]) ? '.' . str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : ''; $len = strlen($integer_part); if ($len <= 3) { return '₹' . $integer_part . $decimal_part; } $last_three = substr($integer_part, -3); $rest_units = substr($integer_part, 0, -3); $rest_formatted = strrev(implode(',', str_split(strrev($rest_units), 2))); return '₹' . $rest_formatted . ',' . $last_three . $decimal_part;
}

// Authorization check for teacher
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'teacher' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$salary_records = [];
$errorMessage = '';

try {
    // UPDATED: Fetch from the new 'teacher_payroll' table
    $stmt = $conn->prepare(
        "SELECT * FROM teacher_payroll 
         WHERE teacher_id = ? 
         ORDER BY salary_year DESC, salary_month DESC"
    );
    $stmt->execute([$userId]);
    $salary_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Salary History</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>
<body id="page-top">
    <div id="wrapper">
<?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?>        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
<?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">My Salary History</h1>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Download Salary Slips</h6>
                        </div>
                        <div class="card-body">
                            <form action="../hr/download_slips.php" method="POST" target="_blank">
                                <div class="form-row align-items-end">
                                    <div class="form-group col-md-4">
                                        <label for="download_period">Select Period:</label>
                                        <select name="period" id="download_period" class="form-control">
                                            <option value="current_month">Current Month</option>
                                            <option value="last_3_months">Last 3 Months</option>
                                            <option value="last_6_months">Last 6 Months</option>
                                            <option value="current_fy">Current Financial Year</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <button type="submit" class="btn btn-success"><i class="fas fa-download"></i> Download Slips</button>
                                    </div>
                                </div>
                                <small class="text-muted">Note: Bulk download requires a server-side PDF generation library (e.g., FPDF) to be installed and configured in `download_slips.php`.</small>
                            </form>
                        </div>
                    </div>


                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Processed Salary Records</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Salary Period</th>
                                            <th>Net Amount Paid</th>
                                            <th>Payment Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($salary_records)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No salary records found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach($salary_records as $record): ?>
                                            <tr>
                                                <td><?php echo date('F Y', mktime(0, 0, 0, $record['salary_month'], 1, $record['salary_year'])); ?></td>
                                                <td><?php echo formatIndianCurrency($record['net_salary_paid']); ?></td>
                                                <td><?php echo date('d M, Y', strtotime($record['payment_date'])); ?></td>
                                                <td>
                                                    <a href="../hr/generate_slip.php?id=<?php echo encrypt_id($record['id']); ?>&type=teacher" target="_blank" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> View Slip
                                                    </a>
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
<?php
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?>        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
<?php
// Add this block at the very end of the file
if (is_ajax_request()) {
    // Get the captured HTML
    $content = ob_get_clean();
    
    // Extract just the main content area for the AJAX response
    if (preg_match('/<div class="container-fluid".*?>(.*?)<\/div>/s', $content, $matches)) {
        echo '<div class="container-fluid">' . $matches[1] . '</div>';
    } else {
        // Fallback if the main container isn't found
        echo $content;
    }
    // Stop the script for AJAX requests
    exit;
}
?>
</html>