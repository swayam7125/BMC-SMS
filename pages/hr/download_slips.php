<?php
// 1. REQUIRE THE DOMPDF LIBRARY
require_once '../../includes/dompdf/autoload.inc.php';

// Reference the Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

include_once '../../includes/connect.php';
include_once '../../encryption.php';

date_default_timezone_set('Asia/Kolkata');

// --- HELPER FUNCTION TO CONVERT NUMBER TO WORDS ---
function getIndianCurrencyInWords(float $number)
{
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(0 => '', 1 => 'one', 2 => 'two',
        3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
        7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve',
        13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen',
        16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
        19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
        40 => 'forty', 50 => 'fifty', 60 => 'sixty',
        70 => 'seventy', 80 => 'eighty', 90 => 'ninety');
    $digits = array('', 'hundred','thousand','lakh', 'crore');
    while( $i < $digits_length ) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
        } else $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ($Rupees ? 'Rupees ' . ucwords($Rupees) : '') . ucwords($paise) . " Only";
}

// --- Authorization and Data Fetching ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$period = $_POST['period'] ?? 'current_month';

if ((!$role || !$userId) || !in_array($role, ['teacher', 'librarian', 'principal', 'hr'])) {
    die("Unauthorized access.");
}

$where_clause = '';
$params = [$userId];
switch ($period) {
    case 'last_3_months':
        $from_date = date('Y-m-d', strtotime('-3 months'));
        $to_date = date('Y-m-d');
        $where_clause = "AND DATE(pr.payment_date) BETWEEN ? AND ?";
        array_push($params, $from_date, $to_date);
        break;
    case 'last_6_months':
        $from_date = date('Y-m-d', strtotime('-6 months'));
        $to_date = date('Y-m-d');
        $where_clause = "AND DATE(pr.payment_date) BETWEEN ? AND ?";
        array_push($params, $from_date, $to_date);
        break;
    case 'current_fy':
        $current_month = date('n');
        $current_year = date('Y');
        $from_year = ($current_month < 4) ? $current_year - 1 : $current_year;
        $from_date = $from_year . '-04-01';
        $to_date = date('Y-m-d');
        $where_clause = "AND DATE(pr.payment_date) BETWEEN ? AND ?";
        array_push($params, $from_date, $to_date);
        break;
    default:
        $current_month_num = date('n');
        $current_year_num = date('Y');
        $where_clause = "AND pr.salary_month = ? AND pr.salary_year = ?";
        array_push($params, $current_month_num, $current_year_num);
        break;
}

try {
    if ($role === 'teacher') {
        $sql = "SELECT pr.*, t.teacher_name as employee_name, s.school_name, s.school_logo, s.address as school_address 
                FROM teacher_payroll pr JOIN teacher t ON pr.teacher_id = t.id JOIN school s ON pr.school_id = s.id
                WHERE pr.teacher_id = ? {$where_clause}
                ORDER BY pr.salary_year DESC, pr.salary_month DESC";
    } elseif ($role === 'librarian') {
        $sql = "SELECT pr.*, l.librarian_name as employee_name, s.school_name, s.school_logo, s.address as school_address
                FROM librarian_payroll pr JOIN librarian l ON pr.librarian_id = l.id JOIN school s ON pr.school_id = s.id
                WHERE pr.librarian_id = ? {$where_clause}
                ORDER BY pr.salary_year DESC, pr.salary_month DESC";
    } elseif ($role === 'principal') {
        $sql = "SELECT pr.*, p.principal_name as employee_name, s.school_name, s.school_logo, s.address as school_address
                FROM principal_payroll pr JOIN principal p ON pr.principal_id = p.id JOIN school s ON pr.school_id = s.id
                WHERE pr.principal_id = ? {$where_clause}
                ORDER BY pr.salary_year DESC, pr.salary_month DESC";
    } else { // HR role
        $sql = "SELECT pr.*, p.hr_name as employee_name, s.school_name, s.school_logo, s.address as school_address
                FROM hr_payroll pr JOIN hr p ON pr.hr_id = p.id JOIN school s ON pr.school_id = s.id
                WHERE pr.hr_id = ? {$where_clause}
                ORDER BY pr.salary_year DESC, pr.salary_month DESC";
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { die("Error fetching records: " . $e->getMessage()); }

if (empty($records)) { die("No salary records found for the selected period."); }


// --- HTML & CSS DESIGN ---
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 25px; }
        body { font-family: "DejaVu Sans", sans-serif; color: #333; font-size: 12px; }
        .payslip { border: 1px solid #ddd; padding: 20px; margin-bottom: 25px; page-break-after: always; }
        .payslip:last-child { page-break-after: auto; }
        
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-height: 60px; position: absolute; top: 25px; left: 25px; }
        .school-name { font-size: 22px; font-weight: bold; }
        .school-address { font-size: 11px; }
        .slip-title { font-size: 16px; font-weight: bold; text-align: center; margin-bottom: 15px; text-decoration: underline; }

        .employee-details { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 1px solid #ccc; }
        .employee-details td { padding: 6px 10px; }
        .employee-details .label { font-weight: bold; width: 150px; }

        .salary-table { width: 100%; border-collapse: collapse; }
        .salary-table th, .salary-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .salary-table th { background-color: #f2f2f2; font-weight: bold; }
        .salary-table .amount { text-align: right; }
        .salary-table tfoot td { font-weight: bold; }
        .salary-table tfoot .net-salary-row { background-color: #e9e9e9; font-size: 13px; }
        
        .amount-in-words { margin-top: 15px; font-size: 11px; }
        .amount-in-words span { font-weight: bold; }

        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #777; }
    </style>
</head>
<body>';

foreach ($records as $details) {
    $base_salary_formatted = '₹ ' . number_format($details['base_salary'], 2);
    $deduction_formatted = '₹ ' . number_format($details['deduction_amount'], 2);
    $incentives_formatted = '₹ ' . number_format($details['total_incentives'], 2);
    $net_paid_formatted = '₹ ' . number_format($details['net_salary_paid'], 2);
    $amount_in_words = getIndianCurrencyInWords($details['net_salary_paid']);
    $logo_path = '../../' . $details['school_logo'];
    $logo_data = '';
    if ($details['school_logo'] && file_exists($logo_path)) {
        $logo_type = pathinfo($logo_path, PATHINFO_EXTENSION);
        $data = file_get_contents($logo_path);
        $logo_data = 'data:image/' . $logo_type . ';base64,' . base64_encode($data);
    }
    
    $html .= '
    <div class="payslip">
        <div class="header">
            ' . ($logo_data ? '<img src="' . $logo_data . '">' : '') . '
            <div class="school-name">' . htmlspecialchars($details['school_name']) . '</div>
            <div class="school-address">' . htmlspecialchars($details['school_address']) . '</div>
        </div>
        <div class="slip-title">Salary Slip for ' . date('F Y', mktime(0,0,0, $details['salary_month'], 1, $details['salary_year'])) . '</div>
        
        <table class="employee-details">
            <tr>
                <td class="label">Employee Name</td><td>: ' . htmlspecialchars($details['employee_name']) . '</td>
                <td class="label">Payment Date</td><td>: ' . date('d M, Y', strtotime($details['payment_date'])) . '</td>
            </tr>
            <tr>
                <td class="label">Designation</td><td>: ' . ucfirst($role) . '</td>
                <td class="label">Working Days</td><td>: ' . $details['total_working_days'] . '</td>
            </tr>
             <tr>
                <td class="label">Days Present</td><td>: ' . $details['present_days'] . '</td>
                <td class="label">Days Absent</td><td>: ' . $details['absent_days'] . '</td>
            </tr>
        </table>

        <table class="salary-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Base Salary</td>
                    <td class="amount">' . $base_salary_formatted . '</td>
                </tr>
                 <tr>
                    <td>Incentives / Adjustments</td>
                    <td class="amount">' . $incentives_formatted . '</td>
                </tr>
                <tr>
                    <td>Absent Day Deduction</td>
                    <td class="amount">- ' . $deduction_formatted . '</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Gross Earnings</strong></td>
                    <td class="amount"><strong>' . '₹ ' . number_format($details['base_salary'] + $details['total_incentives'], 2) . '</strong></td>
                </tr>
                <tr>
                    <td><strong>Total Deductions</strong></td>
                    <td class="amount"><strong>- ' . $deduction_formatted . '</strong></td>
                </tr>
                <tr class="net-salary-row">
                    <td><strong>Net Salary Paid</strong></td>
                    <td class="amount"><strong>' . $net_paid_formatted . '</strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="amount-in-words">
            <span>Amount in Words:</span> ' . $amount_in_words . '
        </div>
        
        <div class="footer">
            This is a computer-generated salary slip and does not require a signature.
        </div>
    </div>';
}
$html .= '</body></html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Salary-Slips.pdf";
if (!empty($records)) {
    switch ($period) {
        case 'current_month':
            $month_name = date('F-Y', mktime(0,0,0, $records[0]['salary_month'], 1, $records[0]['salary_year']));
            $filename = "Salary-Slip-{$month_name}.pdf";
            break;
        case 'last_3_months': $filename = "Salary-Slips-Last-3-Months.pdf"; break;
        case 'last_6_months': $filename = "Salary-Slips-Last-6-Months.pdf"; break;
        case 'current_fy': $filename = "Salary-Slips-Current-Financial-Year.pdf"; break;
    }
}

$dompdf->stream($filename, ["Attachment" => 1]);
exit;
?>