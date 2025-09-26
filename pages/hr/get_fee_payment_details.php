<?php
require_once __DIR__ . "/../../includes/connect.php";
require_once __DIR__ . "/../../encryption.php";

if (!isset($_COOKIE['encrypted_user_role']) || decrypt_id($_COOKIE['encrypted_user_role']) !== 'hr') {
    http_response_code(403);
    echo '<div class="alert alert-danger"><i class="fas fa-ban mr-2"></i>Unauthorized access.</div>';
    exit;
}

$fee_id = $_GET['fee_id'];

$stmt = $conn->prepare("
    SELECT s.student_name, s.rollno, s.std, sf.status, sf.payment_date, f.fee_type, f.amount
    FROM student_fees sf
    JOIN student s ON sf.student_id = s.id
    JOIN fees f ON sf.fee_id = f.id
    WHERE sf.fee_id = :fee_id
    ORDER BY s.std, s.rollno
");
$stmt->bindParam(':fee_id', $fee_id);
$stmt->execute();
$details = $stmt->fetchAll();

if(count($details) > 0) {
    $fee_info = $details[0]; // Get fee information from first record
    $paid_count = 0;
    $unpaid_count = 0;
    $total_collected = 0;
    
    foreach ($details as $row) {
        if ($row['status'] === 'Paid') {
            $paid_count++;
            $total_collected += $row['amount'];
        } else {
            $unpaid_count++;
        }
    }
    
    $total_students = count($details);
    $collection_percentage = $total_students > 0 ? ($paid_count / $total_students) * 100 : 0;
    
    // Summary cards
    echo '<div class="row mb-4">';
    echo '<div class="col-md-3">';
    echo '<div class="card border-left-success shadow h-100 py-2">';
    echo '<div class="card-body">';
    echo '<div class="row no-gutters align-items-center">';
    echo '<div class="col mr-2">';
    echo '<div class="text-xs font-weight-bold text-success text-uppercase mb-1">Paid</div>';
    echo '<div class="h5 mb-0 font-weight-bold text-gray-800">' . $paid_count . '</div>';
    echo '</div>';
    echo '<div class="col-auto"><i class="fas fa-check-circle fa text-gray-300"></i></div>';
    echo '</div></div></div></div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card border-left-danger shadow h-100 py-2">';
    echo '<div class="card-body">';
    echo '<div class="row no-gutters align-items-center">';
    echo '<div class="col mr-2">';
    echo '<div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Unpaid</div>';
    echo '<div class="h5 mb-0 font-weight-bold text-gray-800">' . $unpaid_count . '</div>';
    echo '</div>';
    echo '<div class="col-auto"><i class="fas fa-times-circle fa text-gray-300"></i></div>';
    echo '</div></div></div></div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card border-left-primary shadow h-100 py-2">';
    echo '<div class="card-body">';
    echo '<div class="row no-gutters align-items-center">';
    echo '<div class="col mr-2">';
    echo '<div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Collection Rate</div>';
    echo '<div class="h5 mb-0 font-weight-bold text-gray-800">' . round($collection_percentage, 1) . '%</div>';
    echo '</div>';
    echo '<div class="col-auto"><i class="fas fa-chart-pie fa text-gray-300"></i></div>';
    echo '</div></div></div></div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card border-left-info shadow h-100 py-2">';
    echo '<div class="card-body">';
    echo '<div class="row no-gutters align-items-center">';
    echo '<div class="col mr-2">';
    echo '<div class="text-xs font-weight-bold text-info text-uppercase mb-1">Amount Collected</div>';
    echo '<div class="h5 mb-0 font-weight-bold text-gray-800">₹' . number_format($total_collected, 2) . '</div>';
    echo '</div>';
    echo '<div class="col-auto"><i class="fa-solid fa-indian-rupee-sign text-gray-300"></i></div>';
    echo '</div></div></div></div>';
    echo '</div>';
    
    // Progress bar
    echo '<div class="card shadow mb-3">';
    echo '<div class="card-body">';
    echo '<h6 class="font-weight-bold text-primary mb-2">Overall Collection Progress</h6>';
    echo '<div class="progress mb-2" style="height: 20px;">';
    echo '<div class="progress-bar bg-success progress-bar-striped" role="progressbar" style="width: ' . $collection_percentage . '%">';
    echo '<span class="font-weight-bold">' . round($collection_percentage, 1) . '%</span>';
    echo '</div></div>';
    echo '<small class="text-muted">' . $paid_count . ' out of ' . $total_students . ' students have paid their fees</small>';
    echo '</div></div>';
    
    // Detailed table
    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered table-hover table-sm">';
    echo '<thead class="thead-dark">';
    echo '<tr>';
    echo '<th><i class="fas fa-graduation-cap mr-1"></i>Standard</th>';
    echo '<th><i class="fas fa-sort-numeric-up mr-1"></i>Roll No</th>';
    echo '<th><i class="fas fa-user mr-1"></i>Student Name</th>';
    echo '<th><i class="fas fa-info-circle mr-1"></i>Status</th>';
    echo '<th><i class="fas fa-calendar mr-1"></i>Payment Date</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($details as $row) {
        $status_badge = $row['status'] == 'Paid' 
            ? '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Paid</span>' 
            : '<span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Unpaid</span>';
        $payment_date = $row['status'] == 'Paid' 
            ? '<span class="text-success font-weight-bold">' . date('d-M-Y H:i', strtotime($row['payment_date'])) . '</span>'
            : '<span class="text-muted">Not Paid Yet</span>';
        
        $row_class = $row['status'] == 'Paid' ? 'table-light' : 'table-warning';
        
        echo '<tr class="' . $row_class . '">';
        echo '<td><span class="badge badge-info">' . htmlspecialchars($row['std']) . '</span></td>';
        echo '<td><strong>' . htmlspecialchars($row['rollno']) . '</strong></td>';
        echo '<td>';
        echo '<div class="d-flex align-items-center">';
        echo '<div>';
        echo '<div class="font-weight-bold">' . htmlspecialchars($row['student_name']) . '</div>';
        echo '</div>';
        echo '</div>';
        echo '</td>';
        echo '<td>' . $status_badge . '</td>';
        echo '<td>' . $payment_date . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
} else {
    echo '<div class="text-center py-5">';
    echo '<i class="fas fa-users-slash fa-3x text-gray-300 mb-3"></i>';
    echo '<h5 class="text-gray-600">No Students Found</h5>';
    echo '<p class="text-muted">No students have been assigned to this fee yet.</p>';
    echo '</div>';
}
?>