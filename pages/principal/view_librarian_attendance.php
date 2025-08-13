<?php
include_once '../../includes/connect.php'; // Assumes this file now provides a PDO connection object, e.g., $conn
include_once '../../encryption.php';

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'principal' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
try {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $principalDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$principalDetails) {
        die("Error: Could not retrieve principal details.");
    }
    $school_id = $principalDetails['school_id'];

} catch (PDOException $e) {
    error_log("Database Error (Principal Fetch): " . $e->getMessage());
    die("A critical database error occurred while fetching user details.");
}

// --- START of date validation logic ---
// Get the current date to use as a maximum for the date input
$current_date = date('Y-m-d');
$filter_date = $_GET['date'] ?? $current_date;

// Server-side check to prevent future dates, even if the URL is manipulated
if ($filter_date > $current_date) {
    $filter_date = $current_date;
}
// --- END of date validation logic ---

$records = [];

try {
    // This query fetches all librarians and their attendance status for the selected date.
    $sql = "SELECT
                l.id as librarian_id,
                l.librarian_name,
                la.status
            FROM
                librarian l
            LEFT JOIN
                librarian_attendance la ON l.id = la.librarian_id AND la.attendance_date = ?
            WHERE
                l.school_id = ?
            ORDER BY
                l.librarian_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$filter_date, $school_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error (Attendance Fetch): " . $e->getMessage());
    // Let the page render with an empty table instead of crashing
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Librarian Attendance - School Management System</title>
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
                    <h1 class="h3 mb-2 text-gray-800">Librarian Attendance History</h1>

                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">View Attendance Records</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                                <form method="GET" action="" class="form-inline">
                                    <div class="form-group">
                                        <label for="date" class="mr-2">Date:</label>
                                        <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>" max="<?php echo $current_date; ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary ml-2"><i class="fas fa-search fa-sm"></i> View</button>
                                </form>
                                <a href="librarian_attendance.php?attendance_date=<?php echo htmlspecialchars($filter_date); ?>" class="btn btn-info">
                                    <i class="fas fa-edit"></i> Update Today's Attendance
                                </a>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Librarian Name</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($records)): ?>
                                            <?php foreach ($records as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['librarian_name']); ?></td>
                                                <td>
                                                    <?php
                                                        $status = $record['status'] ?? 'Not Marked';
                                                        $badge_class = 'badge-secondary';
                                                        if ($status == 'Present') $badge_class = 'badge-success';
                                                        if ($status == 'Absent') $badge_class = 'badge-danger';
                                                        if ($status == 'Leave') $badge_class = 'badge-warning';
                                                        // ADDED: Badge for Half Day
                                                        if ($status == 'Half Day') $badge_class = 'badge-info';
                                                        echo "<span class='badge {$badge_class} p-2'>" . htmlspecialchars($status) . "</span>";
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="librarian_attendance.php?attendance_date=<?php echo htmlspecialchars($filter_date); ?>&edit_librarian_id=<?php echo $record['librarian_id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No librarians found for this school or date.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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
        $('#dataTable').DataTable({
            "order": [] // Disable initial sorting
        });
    });
    </script>
</body>
</html>