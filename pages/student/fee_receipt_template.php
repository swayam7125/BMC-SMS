<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Receipt</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .receipt-container { max-width: 800px; margin: auto; border: 1px solid #ddd; padding: 30px; }
        .receipt-header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #3498db; padding-bottom: 20px; }
        .receipt-header img { max-height: 80px; margin-bottom: 15px; }
        .receipt-header h2 { margin: 0; font-weight: 700; color: #3498db; }
        .details-grid { width: 100%; margin-bottom: 30px; }
        .details-grid td { padding: 5px 0; }
        .details-grid strong { display: inline-block; width: 140px; }
        .receipt-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .receipt-table th, .receipt-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .receipt-table th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 1.1em; }
        .receipt-footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 0.9em; color: #777; }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <?php if (!empty($receipt_data[0]['school_logo'])): ?>
                <img src="<?php echo htmlspecialchars($receipt_data[0]['school_logo']); ?>" alt="School Logo">
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($receipt_data[0]['school_name']); ?></h2>
            <p><?php echo htmlspecialchars($receipt_data[0]['address']); ?></p>
        </div>
        <h4 style="text-align: center; color: #555; margin-bottom: 30px;">Official Fee Receipt</h4>
        
        <table class="details-grid">
            <tr>
                <td><strong>Student Name:</strong></td>
                <td><?php echo htmlspecialchars($receipt_data[0]['student_name']); ?></td>
                <td><strong>Receipt No:</strong></td>
                <td class="text-right"><?php echo "PAY-" . str_pad($receipt_data[0]['transaction_id'], 6, '0', STR_PAD_LEFT); ?></td>
            </tr>
            <tr>
                <td><strong>Standard:</strong></td>
                <td><?php echo htmlspecialchars($receipt_data[0]['std']); ?></td>
                <td><strong>Payment Date:</strong></td>
                <td class="text-right"><?php echo date('d M, Y', strtotime($receipt_data[0]['payment_date'])); ?></td>
            </tr>
            <tr>
                <td><strong>Roll No:</strong></td>
                <td><?php echo htmlspecialchars($receipt_data[0]['rollno']); ?></td>
                <td></td><td></td>
            </tr>
        </table>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php $total_paid = 0; $i = 1; ?>
                <?php foreach ($receipt_data as $item): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($item['fee_type']); ?></td>
                        <td class="text-right">₹ <?php echo number_format($item['amount_paid'], 2); ?></td>
                    </tr>
                    <?php $total_paid += $item['amount_paid']; ?>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="text-right"><strong>Total Paid:</strong></td>
                    <td class="text-right"><strong>₹ <?php echo number_format($total_paid, 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="receipt-footer">
            <p>This is a computer-generated receipt and does not require a signature.</p>
            <p>Thank you for your payment!</p>
        </div>
    </div>
</body>
</html>