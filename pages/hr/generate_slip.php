<?php
// --- SETUP AND INCLUDES ---
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // ADDED: Log system dependency

date_default_timezone_set('Asia/Kolkata');

// --- USER AUTHENTICATION AND DETAILS ---
$hr_user_id = null;
$hr_role = null;
$hr_user_name = 'Unknown';

if (isset($_COOKIE['encrypted_user_id'])) {
    $hr_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (isset($_COOKIE['encrypted_user_role'])) {
    $hr_role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_name'])) {
    $hr_user_name = decrypt_id($_COOKIE['encrypted_user_name']);
}

// Although this is a public-facing link, we can still log if an HR user is generating it.
// No redirection is needed if the user is not HR, as the link might be shared.

// --- HELPER FUNCTION TO CONVERT NUMBER TO WORDS ---
function getIndianCurrencyInWords(float $number)
{
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
        7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 11 => 'eleven', 12 => 'twelve',
        13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
        18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty', 40 => 'forty',
        50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety'
    );
    $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise;
}

// --- DATA FETCHING ---
$slip_id = isset($_GET['id']) ? decrypt_id($_GET['id']) : null;
$type = $_GET['type'] ?? '';
$record = null;
$staff_details = null;
$school_details = null;

if ($slip_id && $type) {
    try {
        switch ($type) {
            case 'teacher':
                $stmt = $conn->prepare("SELECT * FROM teacher_payroll WHERE id = ?");
                $stmt->execute([$slip_id]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($record) {
                    $staff_stmt = $conn->prepare("SELECT teacher_name as name, school_id FROM teacher WHERE id = ?");
                    $staff_stmt->execute([$record['teacher_id']]);
                    $staff_details = $staff_stmt->fetch(PDO::FETCH_ASSOC);
                }
                break;
            case 'librarian':
                $stmt = $conn->prepare("SELECT * FROM librarian_payroll WHERE id = ?");
                $stmt->execute([$slip_id]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($record) {
                    $staff_stmt = $conn->prepare("SELECT librarian_name as name, school_id FROM librarian WHERE id = ?");
                    $staff_stmt->execute([$record['librarian_id']]);
                    $staff_details = $staff_stmt->fetch(PDO::FETCH_ASSOC);
                }
                break;
            case 'principal':
                $stmt = $conn->prepare("SELECT * FROM principal_payroll WHERE id = ?");
                $stmt->execute([$slip_id]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($record) {
                    $staff_stmt = $conn->prepare("SELECT principal_name as name, school_id FROM principal WHERE id = ?");
                    $staff_stmt->execute([$record['principal_id']]);
                    $staff_details = $staff_stmt->fetch(PDO::FETCH_ASSOC);
                }
                break;
            // *** ADDED: New case to handle HR staff slips.
            case 'hr':
                $stmt = $conn->prepare("SELECT * FROM hr_payroll WHERE id = ?");
                $stmt->execute([$slip_id]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($record) {
                    $staff_stmt = $conn->prepare("SELECT hr_name as name, school_id FROM hr WHERE id = ?");
                    $staff_stmt->execute([$record['hr_id']]);
                    $staff_details = $staff_stmt->fetch(PDO::FETCH_ASSOC);
                }
                break;
        }

        if ($staff_details) {
            $school_stmt = $conn->prepare("SELECT school_name, school_logo, address FROM school WHERE id = ?");
            $school_stmt->execute([$staff_details['school_id']]);
            $school_details = $school_stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // --- LOG DATA FETCHING ERROR ---
        if ($hr_role === 'hr') {
            log_interaction($hr_role, $hr_user_id, "Error generating payslip: " . $e->getMessage(), $hr_user_name);
        }
        // -----------------------------
        die("Error: " . $e->getMessage());
    }
}

if (!$record || !$staff_details || !$school_details) {
    // --- LOG FAILED ATTEMPT ---
    if ($hr_role === 'hr') {
        $log_message = "Failed to generate payslip. Slip ID: {$slip_id}, Type: {$type}. Reason: Record, staff, or school details not found.";
        log_interaction($hr_role, $hr_user_id, $log_message, $hr_user_name);
    }
    // -------------------------
    die("<h1>Payslip not found or invalid access.</h1>");
}

// --- LOG SUCCESSFUL PAYSLIP GENERATION ---
if ($hr_role === 'hr') {
    $salary_period_log = date('F Y', mktime(0, 0, 0, $record['salary_month'], 1, $record['salary_year']));
    $log_message = "Generated payslip for {$staff_details['name']} ({$type}) for the period of {$salary_period_log}.";
    log_interaction($hr_role, $hr_user_id, $log_message, $hr_user_name);
}
// ------------------------------------------

$salary_period = date('F Y', mktime(0, 0, 0, $record['salary_month'], 1, $record['salary_year']));
$amount_in_words = getIndianCurrencyInWords($record['net_salary_paid']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip for <?php echo htmlspecialchars($staff_details['name']); ?> - <?php echo $salary_period; ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        #payslip {
            width: 800px;
            background: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            padding: 40px;
            margin-bottom: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #343a40;
            padding-bottom: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .header img {
            max-height: 80px;
            margin-right: 20px;
        }
        .header-text {
            text-align: left;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .header p {
            margin: 0;
            font-size: 14px;
        }
        .slip-title {
            text-align: center;
            margin-bottom: 25px;
        }
        .slip-title h2 {
            display: inline-block;
            border-bottom: 2px solid #343a40;
            padding-bottom: 5px;
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }
        .employee-details {
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .details-table {
            width: 100%;
        }
        .details-table td {
            padding: 5px 0;
            font-size: 14px;
        }
        .details-table td:first-child {
            font-weight: 600;
            width: 25%;
        }
        .salary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .salary-table th, .salary-table td {
            border: 1px solid #dee2e6;
            padding: 10px;
            font-size: 14px;
        }
        .salary-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .salary-table tfoot td {
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .amount {
            text-align: right;
        }
        .earnings, .deductions {
            width: 50%;
            vertical-align: top;
        }
        .net-salary-row td {
            border-top: 2px solid #343a40;
        }
        .amount-in-words {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-size: 14px;
        }
        .amount-in-words span {
            font-weight: 600;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #888;
        }
        .print-download-buttons {
            text-align: center;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .print-download-buttons {
                display: none;
            }
            #payslip {
                box-shadow: none;
                border: none;
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div id="payslip">
        <div class="header">
            <img src="<?php echo htmlspecialchars($school_details['school_logo']); ?>" alt="School Logo">
            <div class="header-text">
                <h1><?php echo htmlspecialchars($school_details['school_name']); ?></h1>
                <p><?php echo htmlspecialchars($school_details['address']); ?></p>
            </div>
        </div>
        <div class="slip-title">
            <h2>Payslip for <?php echo $salary_period; ?></h2>
        </div>
        <div class="employee-details">
            <table class="details-table">
                <tr>
                    <td>Employee Name:</td>
                    <td><?php echo htmlspecialchars($staff_details['name']); ?></td>
                    <td>Payment Date:</td>
                    <td><?php echo date('d-m-Y', strtotime($record['payment_date'])); ?></td>
                </tr>
                <tr>
                    <td>Designation:</td>
                    <td><?php echo ucfirst($type); ?></td>
                    <td>Total Working Days:</td>
                    <td><?php echo $record['total_working_days']; ?></td>
                </tr>
                 <tr>
                    <td>Present Days:</td>
                    <td><?php echo $record['present_days']; ?></td>
                    <td>Absent Days:</td>
                    <td><?php echo $record['absent_days']; ?></td>
                </tr>
            </table>
        </div>
        <table class="salary-table">
            <thead>
                <tr>
                    <th>Earnings</th>
                    <th class="text-right">Amount</th>
                    <th>Deductions</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td class="text-right"><?php echo number_format($record['base_salary'], 2); ?></td>
                    <td>Absence Deduction</td>
                    <td class="text-right"><?php echo number_format($record['deduction_amount'], 2); ?></td>
                </tr>
                <tr>
                    <td>Incentives</td>
                    <td class="text-right"><?php echo number_format($record['total_incentives'], 2); ?></td>
                    <td></td>
                    <td class="text-right"></td>
                </tr>
            </tbody>
            <tfoot>
                <?php
                    $total_earnings = $record['base_salary'] + $record['total_incentives'];
                    $total_deductions = $record['deduction_amount'];
                    $earnings_formatted = number_format($total_earnings, 2);
                    $deduction_formatted = number_format($total_deductions, 2);
                    $net_paid_formatted = number_format($record['net_salary_paid'], 2);
                ?>
                <tr>
                    <td><strong>Total Earnings</strong></td>
                    <td class="amount"><strong><?php echo $earnings_formatted; ?></strong></td>
                    <td><strong>Total Deductions</strong></td>
                    <td class="amount"><strong><?php echo $deduction_formatted; ?></strong></td>
                </tr>
                <tr class="net-salary-row">
                    <td colspan="3"><strong>Net Salary Paid</strong></td>
                    <td class="amount"><strong><?php echo $net_paid_formatted; ?></strong></td>
                </tr>
            </tfoot>
        </table>
        <div class="amount-in-words">
            <span>Amount in Words:</span> <?php echo ucwords($amount_in_words); ?>
        </div>
        <div class="footer">
            This is a computer-generated salary slip and does not require a signature.
        </div>
    </div>
    <div class="print-download-buttons">
        <button class="btn btn-secondary" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
        <button class="btn btn-primary" id="download-pdf"><i class="fas fa-file-pdf"></i> Download as PDF</button>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        document.getElementById('download-pdf').addEventListener('click', function() {
            const slipElement = document.getElementById('payslip');
            const { jsPDF } = window.jspdf;

            html2canvas(slipElement, { scale: 2 }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save("Payslip-<?php echo $salary_period; ?>-<?php echo htmlspecialchars($staff_details['name']); ?>.pdf");
            });
        });
    </script>
</body>
</html>