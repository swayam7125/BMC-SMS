<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Authorization check for principal
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'principal') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
try {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
} catch (Exception $e) {
    die("Error fetching principal's school ID: " . $e->getMessage());
}

if (!$school_id) {
    die("Error: Could not determine the school for this principal.");
}

$attendance_date = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : date('Y-m-d');
$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $attendance_data = $_POST['attendance'] ?? [];
    $attendance_date_post = $_POST['attendance_date'];

    try {
        $conn->beginTransaction();
        $upsert_stmt = $conn->prepare(
            "INSERT INTO payroll_attendance (payroll_id, school_id, attendance_date, status, marked_by_user_id)
             VALUES (?, ?, ?, ?, ?)
             ON CONFLICT (payroll_id, attendance_date) 
             DO UPDATE SET status = EXCLUDED.status, marked_by_user_id = EXCLUDED.marked_by_user_id, updated_at = NOW()"
        );

        foreach ($attendance_data as $payroll_id => $details) {
            $status = $details['status'];
            $upsert_stmt->execute([$payroll_id, $school_id, $attendance_date_post, $status, $userId]);
        }
        $conn->commit();
        $successMessage = "Attendance for " . date('d M, Y', strtotime($attendance_date_post)) . " has been saved successfully!";
    } catch (Exception $e) {
        $conn->rollBack();
        $errorMessage = "Error saving attendance: " . $e->getMessage();
    }
}

// Fetch payroll staff and their attendance for the selected date
$payroll_staff = [];
try {
    $query = "SELECT p.id, p.payroll_name, pa.status 
              FROM payroll p
              LEFT JOIN payroll_attendance pa ON p.id = pa.payroll_id AND pa.attendance_date = ?
              WHERE p.school_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$attendance_date, $school_id]);
    $payroll_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errorMessage = "Error fetching payroll staff: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Payroll Attendance</title>
<link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-4 text-gray-800">Manage Payroll Attendance</h1>

                    <?php if ($successMessage): ?><div class="alert alert-success"><?php echo $successMessage; ?></div><?php endif; ?>
                    <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo $errorMessage; ?></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Date</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group">
                                    <label for="attendance_date" class="mr-2">Attendance Date:</label>
                                    <input type="date" id="attendance_date" name="attendance_date" value="<?php echo htmlspecialchars($attendance_date); ?>" class="form-control" onchange="this.form.submit()">
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Mark Attendance for <?php echo date('d F, Y', strtotime($attendance_date)); ?></h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($attendance_date); ?>">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Payroll Staff Name</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payroll_staff as $staff): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($staff['payroll_name']); ?></td>
                                                <td>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="attendance[<?php echo $staff['id']; ?>][status]" value="Present" <?php if ($staff['status'] == 'Present') echo 'checked'; ?> required>
                                                        <label class="form-check-label">Present</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="attendance[<?php echo $staff['id']; ?>][status]" value="Absent" <?php if ($staff['status'] == 'Absent') echo 'checked'; ?>>
                                                        <label class="form-check-label">Absent</label>
                                                    </div>
                                                     <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="attendance[<?php echo $staff['id']; ?>][status]" value="Leave" <?php if ($staff['status'] == 'Leave') echo 'checked'; ?>>
                                                        <label class="form-check-label">Leave</label>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" name="submit_attendance" class="btn btn-primary mt-3">Save Attendance</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>