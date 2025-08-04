<?php
// Start the session to handle flash messages
session_start();

// Include necessary files
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Initialize variables
$role = null;
$userId = null;
$errorMessage = '';
$principalDetails = null;
$attendance_records = [];

// Retrieve and decrypt user role and ID from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Authorization Check: Ensure user is a logged-in principal
if (!$role || $role !== 'principal') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Fetch principal details to get their school_id
$stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$principalDetails = $result->fetch_assoc();
$stmt->close();

if (!$principalDetails || empty($principalDetails['school_id'])) {
    $errorMessage = "Access Denied: You are not assigned to a school.";
}

// Determine the date to display
$attendance_date_display = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if (empty($errorMessage)) {
    // Corrected query to fetch ALL teachers and their attendance status for the selected date
    $stmt = $conn->prepare("
        SELECT 
            t.id AS teacher_id, 
            t.teacher_name, 
            t.batch, 
            t.class_teacher, 
            t.class_teacher_std, 
            ta.status 
        FROM 
            teacher t
        LEFT JOIN 
            teacher_attendance ta ON t.id = ta.teacher_id AND ta.attendance_date = ?
        WHERE 
            t.school_id = ?
        ORDER BY 
            t.teacher_name ASC
    ");
    // Note: The order of parameters in bind_param is changed to match the '?' in the new query
    $stmt->bind_param("si", $attendance_date_display, $principalDetails['school_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance_records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Teacher Attendance - School Management System</title>
    
    <!-- <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css"> -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-2 text-gray-800">Teacher Attendance History</h1>

                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $successMessage; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">View Attendance Records</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                                    <div class="d-flex align-items-center">
                                        <form method="GET" action="" class="form-inline">
                                            <div class="form-group">
                                                <label for="date" class="mr-2">Date:</label>
                                                <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($attendance_date_display); ?>">
                                            </div>
                                            <button type="submit" class="btn btn-primary ml-2">View</button>
                                        </form>

                                        <div class="form-group ml-3">
                                            <label for="batchFilter" class="mr-2">Batch:</label>
                                            <select id="batchFilter" class="form-control">
                                                <option value="">All</option>
                                                <option value="Morning">Morning</option>
                                                <option value="Evening">Evening</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <a href="teacher_attendence.php?attendance_date=<?php echo htmlspecialchars($attendance_date_display); ?>" class="btn btn-info">
                                        <i class="fas fa-edit"></i> Update Attendance
                                    </a>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Teacher Name</th>
                                                <th>Batch</th>
                                                <th>Class Teacher</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($attendance_records)): ?>
                                                <?php foreach ($attendance_records as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['teacher_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['batch']); ?></td>
                                                    <td>
                                                        <?php if ($record['class_teacher']): ?>
                                                            Yes (Std: <?php echo htmlspecialchars($record['class_teacher_std']); ?>)
                                                        <?php else: ?>
                                                            No
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            // If status is NULL, it means not marked yet
                                                            $status = $record['status'] ?? 'Not Marked'; 
                                                            $badge_class = 'badge-secondary'; // Default for 'Not Marked'
                                                            if ($status == 'Present') $badge_class = 'badge-success';
                                                            if ($status == 'Absent') $badge_class = 'badge-danger';
                                                            if ($status == 'Leave') $badge_class = 'badge-warning';
                                                            echo "<span class='badge {$badge_class}'>" . htmlspecialchars($status) . "</span>";
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a href="teacher_attendence.php?attendance_date=<?php echo htmlspecialchars($attendance_date_display); ?>&edit_teacher_id=<?php echo $record['teacher_id']; ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No teachers found for this school.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
        <?php include_once "../../includes/logout_modal.php"?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
    $(document).ready(function() {
        var table = $('#dataTable').DataTable();

        $('#batchFilter').on('change', function(){
            var selectedBatch = $(this).val();
            table.column(1).search(selectedBatch).draw();
        });
    });
    </script>
</body>
</html>