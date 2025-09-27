<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // Log system included

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

// Only students should access this page directly
if ($role !== 'student') {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['payment_id'])) {
    die("Payment ID is required.");
}

$payment_id = decrypt_id($_GET['payment_id']);
$student_id = $userId;
$receipt_data = null;

try {
    // Fetch payment and student details
    $stmt = $conn->prepare("
        SELECT
            sf.id as payment_id,
            sf.amount_paid,
            sf.payment_date,
            f.fee_type,
            f.amount as total_amount,
            s.student_name,
            s.rollno,
            s.std,
            sch.school_name,
            sch.school_logo,
            sch.address as school_address
        FROM student_fees sf
        JOIN fees f ON sf.fee_id = f.id
        JOIN student s ON sf.student_id = s.id
        JOIN school sch ON s.school_id = sch.id
        WHERE sf.id = ? AND sf.student_id = ? AND sf.status = 'Paid'
    ");
    $stmt->execute([$payment_id, $student_id]);
    $receipt_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receipt_data) {
        die("Receipt not found or you do not have permission to view it.");
    }

    // Log the successful generation of the receipt
    log_interaction($role, $userId, "FEE RECEIPT: Generated receipt for Payment ID: {$payment_id}.", $userName);

} catch (PDOException $e) {
    // Log the database error
    log_interaction($role, $userId, "FEE RECEIPT ERROR: Failed to generate receipt for Payment ID: {$payment_id}. DB Error: " . $e->getMessage(), $userName);
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Receipt</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fc;
        }
        .receipt-container {
            max-width: 800px;
            margin: 50px auto;
            border: 1px solid #ddd;
            padding: 30px;
            background-color: #fff;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #4e73df;
            padding-bottom: 20px;
        }
        .receipt-header img {
            max-height: 80px;
            margin-bottom: 15px;
        }
        .receipt-header h2 {
            margin: 0;
            font-weight: 700;
            color: #4e73df;
        }
        .receipt-details, .student-details {
            margin-bottom: 30px;
        }
        .receipt-details p, .student-details p {
            margin-bottom: 8px;
        }
        .receipt-details strong, .student-details strong {
            display: inline-block;
            width: 150px;
            color: #5a5c69;
        }
        .receipt-table th, .receipt-table td {
            vertical-align: middle;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e3e6f0;
            font-size: 0.9rem;
            color: #858796;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .receipt-container {
                margin: 0;
                border: none;
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <?php if ($receipt_data['school_logo']): ?>
                <img src="<?php echo htmlspecialchars($receipt_data['school_logo']); ?>" alt="School Logo">
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($receipt_data['school_name']); ?></h2>
            <p><?php echo htmlspecialchars($receipt_data['school_address']); ?></p>
        </div>

        <h4 class="text-center text-gray-800 mb-4">Official Fee Receipt</h4>

        <div class="row">
            <div class="col-6 student-details">
                <h5>Billed To:</h5>
                <p><strong>Student Name:</strong> <?php echo htmlspecialchars($receipt_data['student_name']); ?></p>
                <p><strong>Standard:</strong> <?php echo htmlspecialchars($receipt_data['std']); ?></p>
                <p><strong>Roll No:</strong> <?php echo htmlspecialchars($receipt_data['rollno']); ?></p>
            </div>
            <div class="col-6 receipt-details text-right">
                <h5>Payment Details:</h5>
                <p><strong>Receipt No:</strong> <?php echo "PAY-" . str_pad($receipt_data['payment_id'], 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Payment Date:</strong> <?php echo date('d M, Y', strtotime($receipt_data['payment_date'])); ?></p>
            </div>
        </div>

        <table class="table table-bordered receipt-table">
            <thead class="thead-light">
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($receipt_data['fee_type']); ?></td>
                    <td class="text-right">₹ <?php echo number_format($receipt_data['amount_paid'], 2); ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="font-weight-bold">
                    <td class="text-right">Total Paid:</td>
                    <td class="text-right">₹ <?php echo number_format($receipt_data['amount_paid'], 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="receipt-footer">
            <p>This is a computer-generated receipt and does not require a signature.</p>
            <p>Thank you for your payment!</p>
        </div>

        <div class="text-center mt-4 no-print">
            <button class="btn btn-primary" onclick="window.print();"><i class="fas fa-print"></i> Print Receipt</button>
            <a href="view_fees.php" class="btn btn-secondary">Back to Fees</a>
        </div>
    </div>
</body>
</html>