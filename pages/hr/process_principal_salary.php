<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

date_default_timezone_set('Asia/Kolkata');

function formatIndianCurrency($number)
{
    $number = (string)round($number, 2);
    $parts = explode('.', $number);
    $integer_part = $parts[0];
    $decimal_part = isset($parts[1]) ? '.' . str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : '';
    $len = strlen($integer_part);
    if ($len <= 3) {
        return '₹' . $integer_part . $decimal_part;
    }
    $last_three = substr($integer_part, -3);
    $rest_units = substr($integer_part, 0, -3);
    $rest_formatted = strrev(implode(',', str_split(strrev($rest_units), 2)));
    return '₹' . $rest_formatted . ',' . $last_three . $decimal_part;
}

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'hr' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
try {
    $stmt = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
} catch (Exception $e) {
    die("Error fetching user data: " . $e->getMessage());
}

if (!$school_id) {
    die("Error: HR user is not associated with any school.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_pay_submit'])) {
    $selected_principals = $_POST['selected_principals'] ?? [];
    $payroll_data_submitted = $_POST['payroll_data'] ?? [];
    $salary_month = filter_input(INPUT_POST, 'salary_month', FILTER_VALIDATE_INT);
    $salary_year = filter_input(INPUT_POST, 'salary_year', FILTER_VALIDATE_INT);

    if (empty($selected_principals)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?month=$salary_month&year=$salary_year&error=" . urlencode("No principals were selected for payment."));
        exit();
    }

    try {
        $conn->beginTransaction();
        // Use the new table name 'principal_payroll'
        $payment_stmt = $conn->prepare(
            "INSERT INTO principal_payroll (principal_id, hr_user_id, school_id, salary_month, salary_year, base_salary, total_working_days, present_days, absent_days, deduction_amount, total_incentives, net_salary_paid) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $notify_stmt = $conn->prepare(
            "INSERT INTO notifications (user_id, message, type, link) VALUES (?, ?, ?, ?)"
        );
        foreach ($selected_principals as $principal_id) {
            $principal_id = (int)$principal_id;
            $data = $payroll_data_submitted[$principal_id] ?? null;
            if ($data) {
                $payment_stmt->execute([
                    $principal_id,
                    $userId,
                    $school_id,
                    $salary_month,
                    $salary_year,
                    (float)$data['base_salary'],
                    (int)$data['total_working_days'],
                    (float)$data['present_days'],
                    (int)$data['absent_days'],
                    (float)$data['deduction_amount'],
                    (float)$data['total_incentives'],
                    (float)$data['net_salary_paid']
                ]);
                $monthName = date('F', mktime(0, 0, 0, $salary_month, 10));
                $message = "Your salary for $monthName $salary_year amounting to " . formatIndianCurrency($data['net_salary_paid']) . " has been processed.";
                $notify_stmt->execute([$principal_id, $message, 'principal_salary', 'pages/principal/view_my_salary.php']);
            }
        }
        $conn->commit();
        $count = count($selected_principals);
        header("Location: " . $_SERVER['PHP_SELF'] . "?month=$salary_month&year=$salary_year&success=" . urlencode("Successfully processed payments for $count principal(s)!"));
        exit();
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?month=$salary_month&year=$salary_year&error=" . urlencode("Error: " . $e->getMessage()));
        exit();
    }
}

function getWorkingDays($year, $month, $conn, $school_id)
{
    $weekdays = 0;
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($i = 1; $i <= $daysInMonth; $i++) {
        if (date('N', strtotime("$year-$month-$i")) < 7) {
            $weekdays++;
        }
    }
    $holiday_stmt = $conn->prepare("SELECT COUNT(*) FROM holidays WHERE school_id = ? AND EXTRACT(YEAR FROM holiday_date) = ? AND EXTRACT(MONTH FROM holiday_date) = ? AND EXTRACT(ISODOW FROM holiday_date) < 7");
    $holiday_stmt->execute([$school_id, $year, $month]);
    return $weekdays - $holiday_stmt->fetchColumn();
}

$payroll_data = [];
$errorMessage = '';
$pending_principals_exist = false;
$filter_month = $_GET['month'] ?? date('n');
$filter_year = $_GET['year'] ?? date('Y');

try {
    $principal_stmt = $conn->prepare("SELECT id, principal_name, salary FROM principal WHERE school_id = ?");
    $principal_stmt->execute([$school_id]);
    $principals = $principal_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($principals)) {
        // Use the new table name 'principal_payroll'
        $paid_stmt = $conn->prepare("SELECT principal_id FROM principal_payroll WHERE school_id = ? AND salary_month = ? AND salary_year = ?");
        $paid_stmt->execute([$school_id, $filter_month, $filter_year]);
        $paid_principals = $paid_stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($principals as $principal) {
            $base_salary = $principal['salary'];
            $total_working_days = getWorkingDays($filter_year, $filter_month, $conn, $school_id);
            $att_stmt = $conn->prepare("SELECT SUM(CASE WHEN status = 'Present' THEN 1.0 WHEN status = 'Half Day' THEN 0.5 ELSE 0 END) as present, COUNT(CASE WHEN status = 'Absent' THEN 1 ELSE NULL END) as absent FROM principal_attendance WHERE principal_id = ? AND EXTRACT(YEAR FROM attendance_date) = ? AND EXTRACT(MONTH FROM attendance_date) = ?");
            $att_stmt->execute([$principal['id'], $filter_year, $filter_month]);
            $att_counts = $att_stmt->fetch(PDO::FETCH_ASSOC);
            $present_days = (float)($att_counts['present'] ?? 0);
            $absent_days = (int)($att_counts['absent'] ?? 0);

            $incentiveStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM staff_incentives WHERE staff_id = ? AND staff_role = 'principal' AND salary_month = ? AND salary_year = ?");
            $incentiveStmt->execute([$principal['id'], $filter_month, $filter_year]);
            $total_incentives = (float)$incentiveStmt->fetchColumn();

            $per_day_salary = ($total_working_days > 0 && $base_salary > 0) ? ($base_salary / $total_working_days) : 0;
            $earned_salary = $per_day_salary * $present_days;
            $deduction_amount = $per_day_salary * $absent_days;
            $net_salary = $earned_salary + $total_incentives;

            $is_paid = in_array($principal['id'], $paid_principals);
            if (!$is_paid) {
                $pending_principals_exist = true;
            }

            $payroll_data[$principal['id']] = [
                'principal_name' => $principal['principal_name'],
                'base_salary' => $base_salary,
                'deduction_amount' => $deduction_amount,
                'total_incentives' => $total_incentives,
                'net_salary_paid' => $net_salary,
                'status' => $is_paid ? 'Paid' : 'Pending',
                'total_working_days' => $total_working_days,
                'present_days' => $present_days,
                'absent_days' => $absent_days,
            ];
        }
    }
} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Process Principal Payroll</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
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
                    <h1 class="h3 mb-4 text-gray-800">Process Principal Payroll</h1>
                    <?php if (isset($_GET['success'])): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_GET['success']); ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
                    <?php if (isset($_GET['error'])): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_GET['error']); ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
                    <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo $errorMessage; ?></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Payroll Period</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="month-select" class="mr-2">Month:</label>
                                    <select name="month" id="month-select" class="form-control">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo $m; ?>" <?php if ($m == $filter_month) echo 'selected'; ?>>
                                                <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-3">
                                    <label for="year-select" class="mr-2">Year:</label>
                                    <select name="year" id="year-select" class="form-control">
                                        <?php
                                        $current_year = date('Y');
                                        for ($y = $current_year; $y >= $current_year - 5; $y--):
                                        ?>
                                            <option value="<?php echo $y; ?>" <?php if ($y == $filter_year) echo 'selected'; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                        </div>
                    </div>

                    <?php if (!empty($payroll_data)): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Payroll Preview for <?php echo date('F Y', mktime(0, 0, 0, $filter_month, 1)); ?></h6>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <input type="hidden" name="salary_month" value="<?php echo $filter_month; ?>">
                                    <input type="hidden" name="salary_year" value="<?php echo $filter_year; ?>">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th><input type="checkbox" id="selectAllPrincipal"></th>
                                                    <th>Principal Name</th>
                                                    <th>Base Salary</th>
                                                    <th>Absence Deductions</th>
                                                    <th>Adjustments (Incentives/Penalties)</th>
                                                    <th>Net Salary</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payroll_data as $principal_id => $row): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if ($row['status'] == 'Pending'): ?>
                                                                <input type="checkbox" name="selected_principals[]" value="<?php echo $principal_id; ?>" class="principal-checkbox">
                                                                <input type="hidden" name="payroll_data[<?php echo $principal_id; ?>][base_salary]" value="<?php echo $row['base_salary']; ?>">
                                                                <input type="hidden" name="payroll_data[<?php echo $principal_id; ?>][total_working_days]" value="<?php echo $row['total_working_days']; ?>">
                                                                <input type="hidden" name="payroll_data[<?php echo $principal_id; ?>][present_days]" value="<?php echo $row['present_days']; ?>">
                                                                <input type="hidden" name="payroll_data[<?php echo $principal_id; ?>][absent_days]" value="<?php echo $row['absent_days']; ?>">
                                                                <input type="hidden" name="payroll_data[<?php echo $principal_id; ?>][deduction_amount]" value="<?php echo $row['deduction_amount']; ?>">
                                                                <input type="hidden" name="payroll_data[<?php echo $principal_id; ?>][total_incentives]" value="<?php echo $row['total_incentives']; ?>">
                                                                <input type="hidden" name="payroll_data[<?php echo $principal_id; ?>][net_salary_paid]" value="<?php echo $row['net_salary_paid']; ?>">
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['principal_name']); ?></td>
                                                        <td><?php echo formatIndianCurrency($row['base_salary']); ?></td>
                                                        <td class="text-danger"><?php echo formatIndianCurrency($row['deduction_amount']); ?></td>
                                                        <td class="font-weight-bold text-<?php echo $row['total_incentives'] >= 0 ? 'success' : 'danger'; ?>"><?php echo formatIndianCurrency($row['total_incentives']); ?></td>
                                                        <td class="font-weight-bold text-primary"><?php echo formatIndianCurrency($row['net_salary_paid']); ?></td>
                                                        <td><span class="badge badge-<?php echo ($row['status'] == 'Paid') ? 'success' : 'warning'; ?>"><?php echo $row['status']; ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if ($pending_principals_exist): ?>
                                        <button type="submit" name="bulk_pay_submit" class="btn btn-success mt-3"><i class="fas fa-check-double"></i> Pay Selected Principals</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php elseif (!$errorMessage): ?>
                        <div class="alert alert-info">No principal found for this school.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            if (!$is_ajax_request) {
                include '../../includes/footer.php';
            }
            ?>
        </div>
    </div>

    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>