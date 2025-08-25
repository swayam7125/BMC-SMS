<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Authorization check for payroll user
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'payroll' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// Get the payroll user's assigned school ID
$school_id = null;
try {
    $stmt = $conn->prepare("SELECT school_id FROM payroll WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
} catch (Exception $e) {
    die("Error fetching user data: " . $e->getMessage());
}

if (!$school_id) {
    die("Error: Payroll user is not associated with any school.");
}

// Helper function to format currency
function formatIndianCurrency($number) {
    $number = (string)round($number, 2); $parts = explode('.', $number); $integer_part = $parts[0]; $decimal_part = isset($parts[1]) ? '.' . str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : ''; $len = strlen($integer_part); if ($len <= 3) { return '₹' . $integer_part . $decimal_part; } $last_three = substr($integer_part, -3); $rest_units = substr($integer_part, 0, -3); $rest_formatted = strrev(implode(',', str_split(strrev($rest_units), 2))); return '₹' . $rest_formatted . ',' . $last_three . $decimal_part;
}

// --- FILTERING LOGIC ---
$filter_month = $_GET['month'] ?? date('n'); // Default to current month
$filter_year = $_GET['year'] ?? date('Y');   // Default to current year
$filter_role = $_GET['role'] ?? 'all';       // Default to all roles

// Fetch combined salary history based on filters
$salary_history = [];
$params = [':school_id' => $school_id];
$where_clauses = "pr.school_id = :school_id";
$where_clauses_lpr = "lpr.school_id = :school_id";

if ($filter_month !== 'all') {
    $where_clauses .= " AND pr.salary_month = :month";
    $where_clauses_lpr .= " AND lpr.salary_month = :month";
    $params[':month'] = $filter_month;
}
if ($filter_year !== 'all') {
    $where_clauses .= " AND pr.salary_year = :year";
    $where_clauses_lpr .= " AND lpr.salary_year = :year";
    $params[':year'] = $filter_year;
}

$teacher_query = "
    SELECT 'Teacher' as staff_role, t.teacher_name as staff_name, pr.salary_month, pr.salary_year, pr.net_salary_paid, pr.payment_date
    FROM payroll_records pr
    JOIN teacher t ON pr.teacher_id = t.id
    WHERE $where_clauses
";

$librarian_query = "
    SELECT 'Librarian' as staff_role, l.librarian_name as staff_name, lpr.salary_month, lpr.salary_year, lpr.net_salary_paid, lpr.payment_date
    FROM librarian_payroll_records lpr
    JOIN librarian l ON lpr.librarian_id = l.id
    WHERE $where_clauses_lpr
";

// Combine queries based on role filter
if ($filter_role === 'teacher') {
    $query = $teacher_query;
} elseif ($filter_role === 'librarian') {
    $query = $librarian_query;
} else {
    $query = "$teacher_query UNION ALL $librarian_query";
}

$query .= " ORDER BY payment_date DESC, salary_year DESC, salary_month DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $salary_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Salary Payment History</title>
<link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
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
                    <h1 class="h3 mb-4 text-gray-800">Salary Payment History</h1>
                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>

                    <!-- Filter Form -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter History</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group mr-2">
                                    <label for="month" class="mr-2">Month:</label>
                                    <select name="month" id="month" class="form-control">
                                        <option value="all" <?php if ($filter_month == 'all') echo 'selected'; ?>>All Months</option>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo $m; ?>" <?php if ($m == $filter_month) echo 'selected'; ?>><?php echo date('F', mktime(0, 0, 0, $m, 10)); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <label for="year" class="mr-2">Year:</label>
                                    <select name="year" id="year" class="form-control">
                                        <option value="all" <?php if ($filter_year == 'all') echo 'selected'; ?>>All Years</option>
                                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                            <option value="<?php echo $y; ?>" <?php if ($y == $filter_year) echo 'selected'; ?>><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <label for="role" class="mr-2">Role:</label>
                                    <select name="role" id="role" class="form-control">
                                        <option value="all" <?php if ($filter_role == 'all') echo 'selected'; ?>>All Roles</option>
                                        <option value="teacher" <?php if ($filter_role == 'teacher') echo 'selected'; ?>>Teacher</option>
                                        <option value="librarian" <?php if ($filter_role == 'librarian') echo 'selected'; ?>>Librarian</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Processed Payments</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Staff Name</th>
                                            <th>Role</th>
                                            <th>Salary Period</th>
                                            <th>Amount Paid</th>
                                            <th>Payment Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($salary_history as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['staff_name']); ?></td>
                                                <td><span class="badge badge-info"><?php echo htmlspecialchars($record['staff_role']); ?></span></td>
                                                <td><?php echo date('F', mktime(0, 0, 0, $record['salary_month'], 10)) . ' ' . $record['salary_year']; ?></td>
                                                <td class="font-weight-bold text-success"><?php echo formatIndianCurrency($record['net_salary_paid']); ?></td>
                                                <td><?php echo date('d M, Y h:i A', strtotime($record['payment_date'])); ?></td>
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
    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [[ 4, "desc" ]] // Sort by payment date descending by default
            });
        });
    </script>
</body>
</html>
