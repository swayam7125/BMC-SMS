<?php
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

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

$record_id = isset($_GET['id']) ? decrypt_id($_GET['id']) : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;

if (!$record_id || !$type || !$userId || !$role) {
    die("Invalid request or unauthorized access.");
}

$details = null;
try {
    if ($type === 'teacher' && $role === 'teacher') {
        $sql = "SELECT pr.*, t.teacher_name as employee_name, s.school_name, s.school_logo, s.address as school_address 
                FROM payroll_records pr JOIN teacher t ON pr.teacher_id = t.id JOIN school s ON pr.school_id = s.id
                WHERE pr.id = ? AND pr.teacher_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$record_id, $userId]);
        $details = $stmt->fetch(PDO::FETCH_ASSOC);

    } elseif ($type === 'librarian' && $role === 'librarian') {
        $sql = "SELECT pr.*, l.librarian_name as employee_name, s.school_name, s.school_logo, s.address as school_address 
                FROM librarian_payroll_records pr JOIN librarian l ON pr.librarian_id = l.id JOIN school s ON pr.school_id = s.id
                WHERE pr.id = ? AND pr.librarian_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$record_id, $userId]);
        $details = $stmt->fetch(PDO::FETCH_ASSOC);
    } else { die("Unauthorized access."); }

    if (!$details) { die("Salary slip not found or you do not have permission to view it."); }
} catch (Exception $e) { die("Error fetching salary slip details: " . $e->getMessage()); }

$base_salary_formatted = '₹ ' . number_format($details['base_salary'], 2);
$deduction_formatted = '₹ ' . number_format($details['deduction_amount'], 2);
$net_paid_formatted = '₹ ' . number_format($details['net_salary_paid'], 2);
$amount_in_words = getIndianCurrencyInWords($details['net_salary_paid']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Slip - <?php echo date('F Y', mktime(0, 0, 0, $details['salary_month'], 1, $details['salary_year'])); ?></title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    
    <style>
        body { background-color: #e0e0e0; font-family: 'Nunito', sans-serif; }
        .payslip { 
            max-width: 800px; 
            margin: 30px auto; 
            background: #fff; 
            padding: 30px; 
            border: 1px solid #ddd; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            color: #333; 
            font-size: 14px;
        }
        
        .header { text-align: center; margin-bottom: 20px; position: relative; padding: 10px 0; border-bottom: 2px solid #333; }
        .header img { max-height: 70px; position: absolute; top: 0; left: 0; }
        .school-name { font-size: 26px; font-weight: bold; }
        .school-address { font-size: 13px; }
        .slip-title { font-size: 18px; font-weight: bold; text-align: center; margin: 20px 0; text-decoration: underline; }

        .employee-details { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #ccc; }
        .employee-details td { padding: 8px 12px; }
        .employee-details .label { font-weight: bold; width: 150px; }

        .salary-table { width: 100%; border-collapse: collapse; }
        .salary-table th, .salary-table td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        .salary-table th { background-color: #f2f2f2; font-weight: bold; }
        .salary-table .amount { text-align: right; }
        .salary-table tfoot td { font-weight: bold; }
        .salary-table tfoot .net-salary-row { background-color: #e9e9e9; font-size: 16px; }
        
        .amount-in-words { margin-top: 20px; font-size: 13px; }
        .amount-in-words span { font-weight: bold; }

        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #777; }
        .print-download-buttons { text-align: center; margin: 30px auto; max-width: 800px;}
        
        @media print {
            body { background-color: #fff; }
            .payslip { margin: 0; border: none; box-shadow: none; }
            .print-download-buttons { display: none; }
        }
    </style>
</head>
<body>
    <div class="payslip" id="payslip">
        <div class="header">
            <?php if (!empty($details['school_logo'])): ?>
                <img src="/BMC-SMS/<?php echo htmlspecialchars($details['school_logo']); ?>" alt="School Logo">
            <?php endif; ?>
            <div class="school-name"><?php echo htmlspecialchars($details['school_name']); ?></div>
            <div class="school-address"><?php echo htmlspecialchars($details['school_address']); ?></div>
        </div>
        <div class="slip-title">Salary Slip for <?php echo date('F Y', mktime(0, 0, 0, $details['salary_month'], 1, $details['salary_year'])); ?></div>
        
        <table class="employee-details">
            <tr>
                <td class="label">Employee Name</td><td>: <?php echo htmlspecialchars($details['employee_name']); ?></td>
                <td class="label">Payment Date</td><td>: <?php echo date('d M, Y', strtotime($details['payment_date'])); ?></td>
            </tr>
            <tr>
                <td class="label">Designation</td><td>: <?php echo ucfirst(htmlspecialchars($type)); ?></td>
                <td class="label">Working Days</td><td>: <?php echo $details['total_working_days']; ?></td>
            </tr>
             <tr>
                <td class="label">Days Present</td><td>: <?php echo $details['present_days']; ?></td>
                <td class="label">Days Absent</td><td>: <?php echo $details['absent_days']; ?></td>
            </tr>
        </table>

        <table class="salary-table table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Base Salary</td>
                    <td class="amount"><?php echo $base_salary_formatted; ?></td>
                </tr>
                <tr>
                    <td>Absent Day Deduction</td>
                    <td class="amount">- <?php echo $deduction_formatted; ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Gross Earnings</strong></td>
                    <td class="amount"><strong><?php echo $base_salary_formatted; ?></strong></td>
                </tr>
                <tr>
                    <td><strong>Total Deductions</strong></td>
                    <td class="amount"><strong>- <?php echo $deduction_formatted; ?></strong></td>
                </tr>
                <tr class="net-salary-row">
                    <td><strong>Net Salary Paid</strong></td>
                    <td class="amount"><strong><?php echo $net_paid_formatted; ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="amount-in-words">
            <span>Amount in Words:</span> <?php echo $amount_in_words; ?>
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
        document.getElementById('download-pdf').addEventListener('click', function () {
            const slipElement = document.getElementById('payslip');
            const { jsPDF } = window.jspdf;

            html2canvas(slipElement, { scale: 2 }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save("Salary-Slip-<?php echo date('F-Y', mktime(0, 0, 0, $details['salary_month'], 1, $details['salary_year'])); ?>.pdf");
            });
        });
    </script>
</body>
</html>