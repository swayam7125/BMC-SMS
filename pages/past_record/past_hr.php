<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

// Get the user's role from cookies to ensure they are authorized
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect if the user is not logged in or not a principal/superadmin
if (!in_array($role, ['principal', 'superadmin'])) {
    header("Location: ../../login.php");
    exit;
}

// --- DATA FETCHING LOGIC ---
$deleted_hr_users = [];
$school_filter_id = null;
$where_clauses = [];
$params = [];

// If the user is a principal, filter by their school_id
if ($role === 'principal') {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
    $stmt = $conn->prepare('SELECT school_id FROM principal WHERE id = ?');
    $stmt->execute([$userId]);
    $school_filter_id = $stmt->fetchColumn();
    if ($school_filter_id) {
        $where_clauses[] = 'dh.school_id = ?';
        $params[] = $school_filter_id;
    }
}

try {
    // Build the query to fetch deleted HR users and join with the school table to get the school name
    $query = 'SELECT dh.*, s.school_name 
              FROM "deleted_hr" dh
              LEFT JOIN "school" s ON dh.school_id = s.id';

    if (!empty($where_clauses)) {
        $query .= ' WHERE ' . implode(' AND ', $where_clauses);
    }

    $query .= ' ORDER BY dh.deleted_at DESC';

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $deleted_hr_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle potential database errors gracefully
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Deleted HR Records - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Deleted HR Records</h1>
                    </div>
                    <p class="mb-4">This page contains a log of all HR staff that have been deleted from the system.</p>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Deleted HR Staff Log</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="pastHrTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>School Name</th>
                                            <th>Batch</th>
                                            <th>Deleted By</th>
                                            <th>Deleted At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($deleted_hr_users)): ?>
                                            <?php foreach ($deleted_hr_users as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['hr_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['school_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['batch'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['deleted_by_role'] ?? 'N/A'); ?></td>
                                                    <td><?php echo date("d-m-Y h:i A", strtotime($row['deleted_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No deleted HR records found.</td>
                                            </tr>
                                        <?php endif; ?>
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
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#pastHrTable').DataTable({
                "order": [
                    [7, "desc"]
                ] // Order by the 'Deleted At' column, descending
            });
        });
    </script>
</body>

</html>