<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
if ($role !== 'principal') {
    header("Location: /BMC-SMS/dashboard.php");
    exit;
}

$leave_history = [];
try {
    $query = "SELECT t.teacher_name, l.from_date, l.to_date, l.leave_type, l.reason, l.status, l.rejection_reason
              FROM leave_applications l
              JOIN teacher t ON l.teacher_id = t.id
              WHERE l.status IN ('Approved', 'Rejected')
              ORDER BY l.applied_on DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $leave_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Principal Leave History Error: " . $e->getMessage());
    die("A database error occurred.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leave Application History</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Leave Application History</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Approved & Rejected Applications</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Teacher Name</th>
                                            <th>From Date</th>
                                            <th>To Date</th>
                                            <th>Leave Type</th>
                                            <th>Reason</th>
                                            <th>Status & Rejection Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($leave_history)): foreach ($leave_history as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['from_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['to_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo ($row['status'] == 'Approved') ? 'success' : 'danger'; ?> p-2"><?php echo htmlspecialchars($row['status']); ?></span>
                                                        <?php if ($row['status'] == 'Rejected' && !empty($row['rejection_reason'])): ?>
                                                            <br><small class="text-muted mt-1"><strong>Reason:</strong> <?php echo htmlspecialchars($row['rejection_reason']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                        else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No processed leave applications found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>