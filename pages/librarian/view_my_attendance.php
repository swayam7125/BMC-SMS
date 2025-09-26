<?php
// --- Includes & Setup ---
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/ajax_helpers.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

// --- Authorization ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'librarian' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// --- Data Fetching ---
try {
    $query = 'SELECT "attendance_date", "status" FROM "librarian_attendance" WHERE "librarian_id" = ? ORDER BY "attendance_date" DESC';
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance Report</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/table-to-card.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php
        if (!$is_ajax_request) {
            include '../../includes/sidebar.php';
        }
        ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
                if (!$is_ajax_request) {
                    include '../../includes/header.php';
                }
                ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">My Attendance Report</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">My Records</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="attendanceTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($records as $record): ?>
                                            <tr>
                                                <td><?php echo date('d M, Y', strtotime($record['attendance_date'])); ?></td>
                                                <td><?php
                                                    $status = htmlspecialchars($record['status']);
                                                    $badge_class = 'badge-secondary';
                                                    if ($status == 'Present') $badge_class = 'badge-success';
                                                    if ($status == 'Absent') $badge_class = 'badge-danger';
                                                    if ($status == 'Leave') $badge_class = 'badge-warning';
                                                    echo "<span class='badge {$badge_class}'>{$status}</span>";
                                                    ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            if (!$is_ajax_request) {
                include '../../includes/footer.php';
            }
            ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#attendanceTable').DataTable({
                "responsive": true,
                "autoWidth": false,
                "order": [
                    [0, "desc"]
                ] // Sort by date descending
            });
        });
    </script>
</body>

</html>
<?php
$conn = null;
?>