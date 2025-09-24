<?php
// --- 1. Includes and Setup ---
require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/../../encryption.php';
require_once __DIR__ . '/../../includes/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// --- 2. Authentication and Authorization ---
$role = null;
$userId = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'student' || !$userId) {
    die("Unauthorized access. Please log in.");
}

// --- 3. Get and Validate Fee ID ---
$fee_id = filter_input(INPUT_GET, 'fee_id', FILTER_VALIDATE_INT);
if (!$fee_id) {
    die("Invalid Fee ID provided.");
}

// --- 4. Fetch Invoice Data from Database ---
try {
    $stmt = $conn->prepare("
        SELECT 
            sf.id AS fee_id, sf.amount, sf.paid_at, sf.fee_type, sf.academic_year,
            st.student_name, st.rollno, st.std,
            sc.school_name, sc.school_logo, sc.address AS school_address
        FROM student_fees sf
        JOIN student st ON sf.student_id = st.id
        JOIN school sc ON st.school_id = sc.id
        WHERE sf.id = ? AND sf.student_id = ? AND sf.status = 'Paid'
    ");
    $stmt->execute([$fee_id, $userId]);
    $invoice_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice_data) {
        die("Invoice not found or you do not have permission to view it.");
    }

} catch (PDOException $e) {
    error_log("Invoice generation error: " . $e->getMessage());
    die("A system error occurred while generating the invoice.");
}

// --- 5. Build HTML for the PDF ---
$school_logo_src = '';
if (!empty($invoice_data['school_logo'])) {
    $school_logo_path = $_SERVER['DOCUMENT_ROOT'] . $invoice_data['school_logo'];
    
    if (file_exists($school_logo_path)) {
        $school_logo_data = base64_encode(file_get_contents($school_logo_path));
        $image_info = getimagesize($school_logo_path);
        if ($image_info !== false) {
            $school_logo_src = 'data:' . $image_info['mime'] . ';base64,' . $school_logo_data;
        }
    }
}

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #' . htmlspecialchars($invoice_data['fee_id']) . '</title>
    <style>
        body { 
            font-family: DejaVu Sans, Arial, sans-serif; 
            margin: 20px; 
            color: #333; 
            line-height: 1.4;
        }
        .invoice-box { 
            max-width: 800px; 
            margin: auto; 
            padding: 30px; 
            border: 1px solid #eee; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); 
            font-size: 16px; 
        }
        .invoice-box table { 
            width: 100%; 
            text-align: left; 
            border-collapse: collapse; 
        }
        .invoice-box table td { 
            padding: 8px; 
            vertical-align: top; 
        }
        .invoice-box table tr.top table td { 
            padding-bottom: 20px; 
        }
        .invoice-box table tr.top table td.title { 
            font-size: 28px; 
            color: #333; 
            font-weight: bold;
        }
        .invoice-box table tr.information table td { 
            padding-bottom: 30px; 
        }
        .invoice-box table tr.heading td { 
            background: #f8f9fa; 
            border-bottom: 2px solid #ddd; 
            font-weight: bold; 
            padding: 12px 8px;
        }
        .invoice-box table tr.details td { 
            padding-bottom: 20px; 
        }
        .invoice-box table tr.item td { 
            border-bottom: 1px solid #eee; 
            padding: 12px 8px;
        }
        .invoice-box table tr.total td { 
            padding-top: 15px;
        }
        .invoice-box table tr.total td:nth-child(2) { 
            border-top: 2px solid #333; 
            font-weight: bold; 
            font-size: 18px;
        }
        .text-right { 
            text-align: right; 
        }
        .school-logo { 
            max-width: 120px; 
            max-height: 80px; 
            height: auto;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .invoice-subtitle {
            color: #7f8c8d;
            font-size: 14px;
        }
        .amount-paid {
            background-color: #e8f5e8;
            padding: 10px;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="invoice-header">
            <div class="invoice-title">PAYMENT RECEIPT</div>
            <div class="invoice-subtitle">School Fee Management System</div>
        </div>
        
        <table>
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                ' . ($school_logo_src ? '<img src="' . $school_logo_src . '" class="school-logo" alt="School Logo">' : '<div style="font-size: 20px; font-weight: bold;">' . htmlspecialchars($invoice_data['school_name']) . '</div>') . '
                            </td>
                            <td class="text-right">
                                <strong>Receipt No:</strong> INV-' . str_pad($invoice_data['fee_id'], 6, '0', STR_PAD_LEFT) . '<br>
                                <strong>Payment Date:</strong> ' . date('d M, Y', strtotime($invoice_data['paid_at'])) . '<br>
                                <strong>Academic Year:</strong> ' . htmlspecialchars($invoice_data['academic_year']) . '
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                <strong>' . htmlspecialchars($invoice_data['school_name']) . '</strong><br>
                                ' . nl2br(htmlspecialchars($invoice_data['school_address'])) . '
                            </td>
                            <td class="text-right">
                                <strong>Student Details:</strong><br>
                                <strong>Name:</strong> ' . htmlspecialchars($invoice_data['student_name']) . '<br>
                                <strong>Roll No:</strong> ' . htmlspecialchars($invoice_data['rollno']) . '<br>
                                <strong>Class:</strong> ' . htmlspecialchars($invoice_data['std']) . '
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="heading">
                <td><strong>Fee Description</strong></td>
                <td class="text-right"><strong>Amount</strong></td>
            </tr>
            
            <tr class="item">
                <td>
                    <strong>' . htmlspecialchars($invoice_data['fee_type']) . '</strong><br>
                    <small>Academic Year: ' . htmlspecialchars($invoice_data['academic_year']) . '</small>
                </td>
                <td class="text-right">&#8377;' . number_format($invoice_data['amount'], 2) . '</td>
            </tr>
            
            <tr class="total">
                <td class="text-right"><strong>Total Amount Paid:</strong></td>
                <td class="text-right amount-paid">
                    <strong>₹' . number_format($invoice_data['amount'], 2) . '</strong>
                </td>
            </tr>
        </table>
        
        <div style="margin-top: 40px; text-align: center; color: #666; font-size: 12px;">
            <p>This is a computer-generated receipt and does not require a signature.</p>
            <p>Generated on: ' . date('d M, Y H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';

// --- 6. Generate and Stream PDF ---
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans'); // <-- FIX
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Clear any output buffers
if (ob_get_level()) {
    ob_end_clean();
}

$filename = "receipt-" . str_pad($invoice_data['fee_id'], 6, '0', STR_PAD_LEFT) . "-" . date('Y-m-d') . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
?>