<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fee Receipt</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #333; }
        .container { border: 1px solid #ddd; padding: 25px; margin: 0 auto; width: 95%; }
        .header { display: table; width: 100%; border-bottom: 2px solid #004aad; padding-bottom: 10px; }
        .logo { display: table-cell; width: 100px; vertical-align: middle; }
        .school-info { display: table-cell; vertical-align: middle; }
        .school-info h1 { margin: 0; font-size: 24px; color: #004aad; }
        .school-info p { margin: 0; font-size: 12px; }
        .title { text-align: right; display: table-cell; vertical-align: middle; }
        .title h2 { margin: 0; font-size: 20px; color: #555; }
        .details { margin-top: 25px; display: table; width: 100%; }
        .student-details, .receipt-details { display: table-cell; width: 50%; font-size: 13px; line-height: 1.6; }
        .receipt-details { text-align: right; }
        .fee-table { margin-top: 30px; width: 100%; border-collapse: collapse; }
        .fee-table th, .fee-table td { border: 1px solid #ddd; padding: 12px; font-size: 14px; }
        .fee-table th { background-color: #f2f7ff; color: #004aad; text-align: left; }
        .fee-table .total-row td { font-weight: bold; font-size: 16px; background-color: #f2f7ff; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #777; }
    </style>
</head>
<body>
    <?php
    $common_data = $receipt_data[0];
    $total_amount = 0;
    $receipt_no = 'TXN-' . implode('-', array_column($receipt_data, 'transaction_id'));
    ?>
    <div class="container">
        <div class="header">
            <?php
            $logoPath = $common_data['school_logo'];
            if ($logoPath && file_exists($_SERVER['DOCUMENT_ROOT'] . $logoPath)) {
                $absoluteLogoPath = $_SERVER['DOCUMENT_ROOT'] . $logoPath;
                $type = pathinfo($absoluteLogoPath, PATHINFO_EXTENSION);
                $data = file_get_contents($absoluteLogoPath);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                echo '<div class="logo"><img src="' . $base64 . '" width="80" alt="School Logo"></div>';
            } else {
                echo '<div class="logo"></div>';
            }
            ?>
            <div class="school-info">
                <h1><?php echo htmlspecialchars($common_data['school_name']); ?></h1>
                <p><?php echo htmlspecialchars($common_data['address']); ?></p>
            </div>
            <div class="title">
                <h2>FEE RECEIPT</h2>
            </div>
        </div>

        <div class="details">
            <div class="student-details">
                <strong>Paid By:</strong><br>
                <?php echo htmlspecialchars($common_data['student_name']); ?><br>
                Standard: <?php echo htmlspecialchars($common_data['std']); ?><br>
                Roll No: <?php echo htmlspecialchars($common_data['rollno']); ?>
            </div>
            <div class="receipt-details">
                <strong>Receipt No:</strong> <?php echo $receipt_no; ?><br>
                <strong>Receipt Date:</strong> <?php echo date('d-M-Y'); ?>
            </div>
        </div>

        <table class="fee-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receipt_data as $fee_item): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($fee_item['fee_type']); ?>
                            <?php if ($fee_item['payment_date']): ?>
                                <br><small style="color: #555;">(Paid on: <?php echo date('d-M-Y', strtotime($fee_item['payment_date'])); ?>)</small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">&#8377; <?php echo number_format($fee_item['amount'], 2); ?></td>
                    </tr>
                    <?php $total_amount += $fee_item['amount']; ?>
                <?php endforeach; ?>
                
                <tr class="total-row">
                    <td style="text-align: right;"><strong>Total Paid</strong></td>
                    <td style="text-align: right;"><strong>&#8377; <?php echo number_format($total_amount, 2); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>This is a computer-generated receipt and does not require a signature.</p>
        </div>
    </div>
</body>
</html>