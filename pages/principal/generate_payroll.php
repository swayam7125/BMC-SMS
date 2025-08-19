<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

date_default_timezone_set('Asia/Kolkata');

/**
 * MODIFIED: Calculates working days (Mon-Sat) in a month, excluding public holidays.
 *
 * @param int $year The year.
 * @param int $month The month (1-12).
 * @param PDO $conn The database connection object.
 * @param int $school_id The ID of the school.
 * @return int The total number of working days.
 */
function getWorkingDays($year, $month, $conn, $school_id)
{
    // 1. Get total number of weekdays (Mon-Sat)
    $weekdays = 0;
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $dayOfWeek = date('N', strtotime("$year-$month-$i"));
        if ($dayOfWeek < 7) { // Mon-Sat
            $weekdays++;
        }
    }

    // 2. Count how many holidays fall on these weekdays
    $holiday_on_weekday_stmt = $conn->prepare(
        "SELECT COUNT(*) FROM holidays 
         WHERE school_id = ? 
         AND EXTRACT(YEAR FROM holiday_date) = ? 
         AND EXTRACT(MONTH FROM holiday_date) = ?
         AND EXTRACT(ISODOW FROM holiday_date) < 7" // ISODOW: 1=Mon, 6=Sat, 7=Sun
    );
    $holiday_on_weekday_stmt->execute([$school_id, $year, $month]);
    $holiday_count = $holiday_on_weekday_stmt->fetchColumn();

    // 3. Subtract the holidays from the total weekdays
    return $weekdays - $holiday_count;
}

function formatIndianCurrency($number)
{
    $number = (string) round($number, 2);
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

if ($role !== 'principal' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
$payroll_data = [];
$errorMessage = '';

try {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $principalDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $school_id = $principalDetails['school_id'];

    $current_month = date('n');
    $current_year = date('Y');

    $default_month = $current_month;
    $default_year = $current_year;


    $filter_month = $_GET['month'] ?? $default_month;
    $filter_year = $_GET['year'] ?? $default_year;

        $teacher_stmt = $conn->prepare("SELECT id, teacher_name, salary FROM teacher WHERE school_id = ? ORDER BY teacher_name");
        $teacher_stmt->execute([$school_id]);
        $teachers = $teacher_stmt->fetchAll(PDO::FETCH_ASSOC);

        $paid_stmt = $conn->prepare("SELECT teacher_id, payment_date FROM payroll_records WHERE school_id = ? AND salary_month = ? AND salary_year = ?");
        $paid_stmt->execute([$school_id, $filter_month, $filter_year]);
        $paid_teachers = $paid_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($teachers as $teacher) {
            $base_salary = $teacher['salary'];
            // MODIFIED: Pass DB connection and school_id to the function
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

            $payroll_data[] = [
                'teacher_id' => $teacher['id'],
                'teacher_name' => $teacher['teacher_name'],
                'base_salary' => $base_salary,
                'total_working_days' => $total_working_days,
                'present_days' => $present_days,
                'absent_days' => $absent_days,
                'deduction_amount' => $deduction_amount,
                'net_salary_paid' => $net_salary,
                'status' => isset($paid_teachers[$teacher['id']]) ? 'Paid' : 'Pending',
                'payment_date' => $paid_teachers[$teacher['id']] ?? null
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
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
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
                                            <option value="<?php echo $m; ?>" <?php if ($m == $filter_month)
                                                   echo 'selected'; ?>><?php echo date('F', mktime(0, 0, 0, $m, 10)); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <label for="year" class="mr-2">Year:</label>
                                    <select name="year" id="year" class="form-control">
                                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                            <option value="<?php echo $y; ?>" <?php if ($y == $filter_year)
                                                   echo 'selected'; ?>><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-cogs"></i> Generate
                                    Preview</button>
                            </form>
                        </div>
                    </div>

                    <?php if (!empty($payroll_data)): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Payroll Preview for
                                    <?php echo date('F', mktime(0, 0, 0, $filter_month, 10)) . ' ' . $filter_year; ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Teacher Name</th>
                                                <th>Net Salary</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payroll_data as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                                                    <td><?php echo formatIndianCurrency($row['net_salary_paid']); ?></td>
                                                    <td>
                                                        <?php if ($row['status'] == 'Paid'): ?>
                                                            <span class="badge badge-success">Paid</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['status'] == 'Paid'): ?>
                                                            <small>Paid on
                                                                <?php echo date('d M, Y', strtotime($row['payment_date'])); ?></small>
                                                        <?php else: ?>
                                                            <form action="process_payment.php" method="POST">
                                                                <input type="hidden" name="teacher_id"
                                                                    value="<?php echo $row['teacher_id']; ?>">
                                                                <input type="hidden" name="principal_id"
                                                                    value="<?php echo $userId; ?>">
                                                                <input type="hidden" name="school_id"
                                                                    value="<?php echo $school_id; ?>">
                                                                <input type="hidden" name="salary_month"
                                                                    value="<?php echo $filter_month; ?>">
                                                                <input type="hidden" name="salary_year"
                                                                    value="<?php echo $filter_year; ?>">
                                                                <input type="hidden" name="base_salary"
                                                                    value="<?php echo $row['base_salary']; ?>">
                                                                <input type="hidden" name="total_working_days"
                                                                    value="<?php echo $row['total_working_days']; ?>">
                                                                <input type="hidden" name="present_days"
                                                                    value="<?php echo $row['present_days']; ?>">
                                                                <input type="hidden" name="absent_days"
                                                                    value="<?php echo $row['absent_days']; ?>">
                                                                <input type="hidden" name="deduction_amount"
                                                                    value="<?php echo $row['deduction_amount']; ?>">
                                                                <input type="hidden" name="net_salary_paid"
                                                                    value="<?php echo $row['net_salary_paid']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-success"><i
                                                                        class="fas fa-check"></i> Pay Now</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>