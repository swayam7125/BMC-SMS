<?php
// --- Includes & Setup ---
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
date_default_timezone_set('Asia/Kolkata');

// Helper function for currency formatting
function formatIndianCurrency($number)
{
    $num = round($number, 2);
    $num = number_format($num, 2, '.', ',');
    return '₹' . preg_replace('/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i', "$1,", $num);
}

// --- Authorization ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
if ($role !== 'librarian' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// --- Data Fetching ---
try {
    $stmt = $conn->prepare("SELECT * FROM librarian_payroll WHERE librarian_id = ? ORDER BY salary_year DESC, salary_month DESC");
    $stmt->execute([$userId]);
    $salary_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
}

if (!is_ajax_request()) {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Salary History</title>
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
        <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
        <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/sidebar.css">
        <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
        <link rel="stylesheet" href="../../assets/css/table-to-card.css">
    </head>

    <body id="page-top">
        <div id="wrapper">
            <?php include '../../includes/sidebar.php'; ?>
            <div id="content-wrapper" class="d-flex flex-column">
                <div id="content">
                    <?php include_once '../../includes/header.php'; ?>
                    <div class="container-fluid">
                        <h1 class="h3 mb-4 text-gray-800">My Salary History</h1>

                        <?php if (!empty($errorMessage)): ?>
                            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                        <?php endif; ?>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Processed Salary Records</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="salaryTable" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Salary Period</th>
                                                <th>Net Amount Paid</th>
                                                <th>Payment Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($salary_records as $record): ?>
                                                <tr>
                                                    <td><?php echo date('F Y', mktime(0, 0, 0, $record['salary_month'], 1, $record['salary_year'])); ?></td>
                                                    <td><?php echo formatIndianCurrency($record['net_salary_paid']); ?></td>
                                                    <td><?php echo date('d M, Y', strtotime($record['payment_date'])); ?></td>
                                                    <td><a href="../payroll/generate_slip.php?id=<?php echo encrypt_id($record['id']); ?>&type=librarian" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View Slip</a></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include_once '../../includes/footer.php'; ?>
            </div>
        </div>
        <?php include_once "../../includes/logout_modal.php" ?>
        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>
        <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#salaryTable').DataTable({
                    "responsive": true,
                    "autoWidth": false,
                    "order": []
                });
            });
        </script>
    </body>

    </html>
<?php
}
?>