<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

date_default_timezone_set('Asia/Kolkata');

function formatIndianCurrency($number) {
    $number = (string)round($number, 2); $parts = explode('.', $number); $integer_part = $parts[0]; $decimal_part = isset($parts[1]) ? '.' . str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : ''; $len = strlen($integer_part); if ($len <= 3) { return '₹' . $integer_part . $decimal_part; } $last_three = substr($integer_part, -3); $rest_units = substr($integer_part, 0, -3); $rest_formatted = strrev(implode(',', str_split(strrev($rest_units), 2))); return '₹' . $rest_formatted . ',' . $last_three . $decimal_part;
}

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

// Bulk Payment Processing Logic
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

        $payment_stmt = $conn->prepare(
            "INSERT INTO principal_payroll_records (principal_id, payroll_user_id, school_id, salary_month, salary_year, base_salary, total_working_days, present_days, absent_days, deduction_amount, net_salary_paid) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $notify_stmt = $conn->prepare(
            "INSERT INTO notifications (user_id, message, type, link) VALUES (?, ?, ?, ?)"
        );

        foreach ($selected_principals as $principal_id) {
            $principal_id = (int)$principal_id;
            $data = $payroll_data_submitted[$principal_id] ?? null;

            if ($data) {
                $payment_stmt->execute([
                    $principal_id, $userId, $school_id, $salary_month, $salary_year,
                    (float)$data['base_salary'], (int)$data['total_working_days'], 
                    (float)$data['present_days'], (int)$data['absent_days'],
                    (float)$data['deduction_amount'], (float)$data['net_salary_paid']
                ]);
                
                $monthName = date('F', mktime(0, 0, 0, $salary_month, 10));
                $message = "Your salary for $monthName $salary_year amounting to " . formatIndianCurrency($data['net_salary_paid']) . " has been processed.";
                $notify_stmt->execute([$principal_id, $message, 'principal_salary', 'pages/principal/view_my_salary.php']);
            }
        }

        $conn->commit();
        
        $count = count($selected_principals);
        $success_message = "Successfully processed payments for $count principal(s)!";
        header("Location: " . $_SERVER['PHP_SELF'] . "?month=$salary_month&year=$salary_year&success=" . urlencode($success_message));
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error_message = "Error: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF'] . "?month=$salary_month&year=$salary_year&error=" . urlencode($error_message));
        exit();
    }
}

// Function to get working days
function getWorkingDays($year, $month, $conn, $school_id) {
    $weekdays = 0;
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $dayOfWeek = date('N', strtotime("$year-$month-$i"));
        if ($dayOfWeek < 7) { $weekdays++; }
    }
    $holiday_on_weekday_stmt = $conn->prepare("SELECT COUNT(*) FROM holidays WHERE school_id = ? AND EXTRACT(YEAR FROM holiday_date) = ? AND EXTRACT(MONTH FROM holiday_date) = ? AND EXTRACT(ISODOW FROM holiday_date) < 7");
    $holiday_on_weekday_stmt->execute([$school_id, $year, $month]);
    $holiday_count = $holiday_on_weekday_stmt->fetchColumn();
    return $weekdays - $holiday_count;
}

// Display Logic
$payroll_data = [];
$errorMessage = '';
$pending_principals_exist = false;
$filter_month = $_GET['month'] ?? date('n');
$filter_year = $_GET['year'] ?? date('Y');

try {
    // **FIXED QUERY:** Changed fetch() to fetchAll() to get all principals for the school
    $principal_stmt = $conn->prepare("SELECT id, principal_name, salary FROM principal WHERE school_id = ?");
    $principal_stmt->execute([$school_id]);
    $principals = $principal_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($principals)) {
        $paid_stmt = $conn->prepare("SELECT principal_id, payment_date FROM principal_payroll_records WHERE school_id = ? AND salary_month = ? AND salary_year = ?");
        $paid_stmt->execute([$school_id, $filter_month, $filter_year]);
        $paid_principals = $paid_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // **FIXED LOGIC:** Added a foreach loop to process every principal found
        foreach ($principals as $principal) {
            $base_salary = $principal['salary'];
            $total_working_days = getWorkingDays($filter_year, $filter_month, $conn, $school_id);
            
            $payableDaysStmt = $conn->prepare(
                "SELECT SUM(CASE WHEN status = 'Present' THEN 1.0 WHEN status = 'Half Day' THEN 0.5 ELSE 0 END) as payable_days 
                FROM principal_attendance 
                WHERE principal_id = ? AND EXTRACT(YEAR FROM attendance_date) = ? AND EXTRACT(MONTH FROM attendance_date) = ?"
            );
            $payableDaysStmt->execute([$principal['id'], $filter_year, $filter_month]);
            $present_days = (float)$payableDaysStmt->fetchColumn();

            $absentStmt = $conn->prepare('SELECT COUNT(*) FROM principal_attendance WHERE principal_id = ? AND status = \'Absent\' AND EXTRACT(YEAR FROM attendance_date) = ? AND EXTRACT(MONTH FROM attendance_date) = ?');
            $absentStmt->execute([$principal['id'], $filter_year, $filter_month]);
            $absent_days = $absentStmt->fetchColumn();
            
            $per_day_salary = ($total_working_days > 0 && $base_salary > 0) ? ($base_salary / $total_working_days) : 0;
            $net_salary = $per_day_salary * $present_days;
            $deduction_amount = $per_day_salary * $absent_days;
            
            $is_paid = isset($paid_principals[$principal['id']]);
            if (!$is_paid) { $pending_principals_exist = true; }

            $payroll_data[$principal['id']] = [
                'principal_name' => $principal['principal_name'], 'base_salary' => $base_salary, 'total_working_days' => $total_working_days, 'present_days' => $present_days, 'absent_days' => $absent_days, 'deduction_amount' => $deduction_amount, 'net_salary_paid' => $net_salary, 'status' => $is_paid ? 'Paid' : 'Pending', 'payment_date' => $paid_principals[$principal['id']] ?? null
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
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Process Principal Payroll</h1>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Payroll Period</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group mr-2">
                                    <label for="month" class="mr-2">Month:</label>
                                    <select name="month" id="month" class="form-control">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo $m; ?>" <?php if ($m == $filter_month) echo 'selected'; ?>><?php echo date('F', mktime(0, 0, 0, $m, 10)); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <label for="year" class="mr-2">Year:</label>
                                    <select name="year" id="year" class="form-control">
                                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                            <option value="<?php echo $y; ?>" <?php if ($y == $filter_year) echo 'selected'; ?>><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-cogs"></i> Generate Preview</button>
                            </form>
                        </div>
                    </div>

                    <?php if (!empty($payroll_data)): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Payroll Preview for <?php echo date('F', mktime(0, 0, 0, $filter_month, 10)) . ' ' . $filter_year; ?></h6>
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
                                                    <th>Working Days</th>
                                                    <th>Present Days</th>
                                                    <th>Absent Days</th>
                                                    <th>Deductions</th>
                                                    <th>Net Salary</th>
                                                    <th>Status</th>
                                                    <th>Payment Date</th>
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
                                                                <input type="hidden" name="payroll_data[<?php echo $principal_id; ?>][net_salary_paid]" value="<?php echo $row['net_salary_paid']; ?>">
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['principal_name']); ?></td>
                                                        <td><?php echo formatIndianCurrency($row['base_salary']); ?></td>
                                                        <td><?php echo $row['total_working_days']; ?></td>
                                                        <td><?php echo $row['present_days']; ?></td>
                                                        <td><?php echo $row['absent_days']; ?></td>
                                                        <td class="font-weight-bold text-danger"><?php echo formatIndianCurrency($row['deduction_amount']); ?></td>
                                                        <td class="font-weight-bold text-success"><?php echo formatIndianCurrency($row['net_salary_paid']); ?></td>
                                                        <td>
                                                            <span class="badge badge-<?php echo ($row['status'] == 'Paid') ? 'success' : 'warning'; ?>"><?php echo $row['status']; ?></span>
                                                        </td>
                                                        <td>
                                                            <?php if ($row['status'] == 'Paid'): ?>
                                                                <small><?php echo date('d M, Y', strtotime($row['payment_date'])); ?></small>
                                                            <?php endif; ?>
                                                        </td>
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
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
     <script>
    $(document).ready(function() {
        $('#selectAllPrincipal').on('click', function() {
            $('.principal-checkbox').prop('checked', this.checked);
        });
        $('.principal-checkbox').on('click', function() {
            if ($('.principal-checkbox:checked').length == $('.principal-checkbox').length) {
                $('#selectAllPrincipal').prop('checked', true);
            } else {
                $('#selectAllPrincipal').prop('checked', false);
            }
        });
    });
    </script>
</body>
</html>