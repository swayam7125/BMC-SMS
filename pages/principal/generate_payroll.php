<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

date_default_timezone_set('Asia/Kolkata');

// This function is used by both the display and payment logic
function formatIndianCurrency($number) {
    $number = (string)round($number, 2); $parts = explode('.', $number); $integer_part = $parts[0]; $decimal_part = isset($parts[1]) ? '.' . str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : ''; $len = strlen($integer_part); if ($len <= 3) { return '₹' . $integer_part . $decimal_part; } $last_three = substr($integer_part, -3); $rest_units = substr($integer_part, 0, -3); $rest_formatted = strrev(implode(',', str_split(strrev($rest_units), 2))); return '₹' . $rest_formatted . ',' . $last_three . $decimal_part;
}

// Authorization check
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'principal' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// BULK PAYMENT PROCESSING LOGIC IS NOW INSIDE THIS FILE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_pay_submit'])) {
    $selected_teachers = $_POST['selected_teachers'] ?? [];
    $payroll_data_submitted = $_POST['payroll_data'] ?? [];
    $salary_month = filter_input(INPUT_POST, 'salary_month', FILTER_VALIDATE_INT);
    $salary_year = filter_input(INPUT_POST, 'salary_year', FILTER_VALIDATE_INT);
    $school_id = null;

    if (empty($selected_teachers)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?month=$salary_month&year=$salary_year&error=" . urlencode("No teachers were selected for payment."));
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
        $stmt->execute([$userId]);
        $school_id = $stmt->fetchColumn();

        $conn->beginTransaction();

        $payment_stmt = $conn->prepare(
            "INSERT INTO payroll_records (teacher_id, principal_id, school_id, salary_month, salary_year, base_salary, total_working_days, present_days, absent_days, deduction_amount, net_salary_paid) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $notify_stmt = $conn->prepare(
            "INSERT INTO notifications (user_id, message, type, link) VALUES (?, ?, ?, ?)"
        );

        foreach ($selected_teachers as $teacher_id) {
            $teacher_id = (int)$teacher_id;
            $data = $payroll_data_submitted[$teacher_id] ?? null;

            if ($data) {
                $payment_stmt->execute([
                    $teacher_id, $userId, $school_id, $salary_month, $salary_year,
                    (float)$data['base_salary'], (int)$data['total_working_days'], 
                    (int)$data['present_days'], (int)$data['absent_days'],
                    (float)$data['deduction_amount'], (float)$data['net_salary_paid']
                ]);
                
                $monthName = date('F', mktime(0, 0, 0, $salary_month, 10));
                $message = "Your salary for $monthName $salary_year amounting to " . formatIndianCurrency($data['net_salary_paid']) . " has been processed.";
                $notify_stmt->execute([$teacher_id, $message, 'salary', 'pages/teacher/view_salary_history.php']);
            }
        }

        $conn->commit();
        
        $count = count($selected_teachers);
        $success_message = "Successfully processed payments for $count teacher(s)!";
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

// --- Display logic starts here ---

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

$school_id = null;
$payroll_data = [];
$errorMessage = '';
$pending_teachers_exist = false;

try {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $principalDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $school_id = $principalDetails['school_id'];
    $filter_month = $_GET['month'] ?? date('n');
    $filter_year = $_GET['year'] ?? date('Y');

    $teacher_stmt = $conn->prepare("SELECT id, teacher_name, salary FROM teacher WHERE school_id = ? ORDER BY teacher_name");
    $teacher_stmt->execute([$school_id]);
    $teachers = $teacher_stmt->fetchAll(PDO::FETCH_ASSOC);

    $paid_stmt = $conn->prepare("SELECT teacher_id, payment_date FROM payroll_records WHERE school_id = ? AND salary_month = ? AND salary_year = ?");
    $paid_stmt->execute([$school_id, $filter_month, $filter_year]);
    $paid_teachers = $paid_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($teachers as $teacher) {
        $base_salary = $teacher['salary'];
        $total_working_days = getWorkingDays($filter_year, $filter_month, $conn, $school_id);
        $presentStmt = $conn->prepare('SELECT COUNT(*) FROM teacher_attendance WHERE teacher_id = ? AND status = \'Present\' AND EXTRACT(YEAR FROM attendance_date) = ? AND EXTRACT(MONTH FROM attendance_date) = ?');
        $presentStmt->execute([$teacher['id'], $filter_year, $filter_month]);
        $present_days = $presentStmt->fetchColumn();
        $absentStmt = $conn->prepare('SELECT COUNT(*) FROM teacher_attendance WHERE teacher_id = ? AND status = \'Absent\' AND EXTRACT(YEAR FROM attendance_date) = ? AND EXTRACT(MONTH FROM attendance_date) = ?');
        $absentStmt->execute([$teacher['id'], $filter_year, $filter_month]);
        $absent_days = $absentStmt->fetchColumn();
        $per_day_salary = ($total_working_days > 0 && $base_salary > 0) ? ($base_salary / $total_working_days) : 0;
        $net_salary = $per_day_salary * $present_days;
        $deduction_amount = $per_day_salary * $absent_days;
        $is_paid = isset($paid_teachers[$teacher['id']]);
        if (!$is_paid) { $pending_teachers_exist = true; }

        $payroll_data[$teacher['id']] = [
            'teacher_name' => $teacher['teacher_name'], 'base_salary' => $base_salary, 'total_working_days' => $total_working_days, 'present_days' => $present_days, 'absent_days' => $absent_days, 'deduction_amount' => $deduction_amount, 'net_salary_paid' => $net_salary, 'status' => $is_paid ? 'Paid' : 'Pending', 'payment_date' => $paid_teachers[$teacher['id']] ?? null
        ];
    }
} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Process Teacher Payroll</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                    <h1 class="h3 mb-4 text-gray-800">Process Teacher Payroll</h1>

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
                                                    <th><input type="checkbox" id="selectAll"></th>
                                                    <th>Teacher Name</th>
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
                                                <?php foreach ($payroll_data as $teacher_id => $row): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if ($row['status'] == 'Pending'): ?>
                                                                <input type="checkbox" name="selected_teachers[]" value="<?php echo $teacher_id; ?>" class="teacher-checkbox">
                                                                <input type="hidden" name="payroll_data[<?php echo $teacher_id; ?>][base_salary]" value="<?php echo $row['base_salary']; ?>">
                                                                <input type="hidden" name="payroll_data[<?php echo $teacher_id; ?>][total_working_days]" value="<?php echo $row['total_working_days']; ?>">
                                                                <input type="hidden" name="payroll_data[<?php echo $teacher_id; ?>][present_days]" value="<?php echo $row['present_days']; ?>">
                                                                <input type="hidden" name="payroll_data[<?php echo $teacher_id; ?>][absent_days]" value="<?php echo $row['absent_days']; ?>">
                                                                <input type="hidden" name="payroll_data[<?php echo $teacher_id; ?>][deduction_amount]" value="<?php echo $row['deduction_amount']; ?>">
                                                                <input type="hidden" name="payroll_data[<?php echo $teacher_id; ?>][net_salary_paid]" value="<?php echo $row['net_salary_paid']; ?>">
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                                                        <td><?php echo formatIndianCurrency($row['base_salary']); ?></td>
                                                        <td><?php echo $row['total_working_days']; ?></td>
                                                        <td><?php echo $row['present_days']; ?></td>
                                                        <td><?php echo $row['absent_days']; ?></td>
                                                        <td class="font-weight-bold text-danger"><?php echo formatIndianCurrency($row['deduction_amount']); ?></td>
                                                        <td class="font-weight-bold text-success"><?php echo formatIndianCurrency($row['net_salary_paid']); ?></td>
                                                        <td>
                                                            <?php if ($row['status'] == 'Paid'): ?>
                                                                <span class="badge badge-success">Paid</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-warning">Pending</span>
                                                            <?php endif; ?>
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
                                    <?php if ($pending_teachers_exist): ?>
                                        <button type="submit" name="bulk_pay_submit" class="btn btn-success mt-3"><i class="fas fa-check-double"></i> Pay Selected Teachers</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
        <?php
            if (!is_ajax_request()) {
        ?>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#selectAll').on('click', function() {
            $('.teacher-checkbox').prop('checked', this.checked);
        });
        
        $('.teacher-checkbox').on('click', function() {
            if ($('.teacher-checkbox:checked').length == $('.teacher-checkbox').length) {
                $('#selectAll').prop('checked', true);
            } else {
                $('#selectAll').prop('checked', false);
            }
        });
    });
    </script>
</body>
</html>
<?php 
} 
?>