<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security Check
if ($role !== 'bmc' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

// --- Mark all 'principal_notice' notifications as read for the current BMC admin ---
$stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type = 'principal_notice' AND is_read = 0");
if ($stmt_mark_read) {
    $stmt_mark_read->bind_param("i", $userId);
    $stmt_mark_read->execute();
    $stmt_mark_read->close();
}

// --- Fetch all notices from principals ---
$notices = [];
$sql = "SELECT 
            pbn.title, 
            pbn.content, 
            pbn.file_path, 
            pbn.original_filename, 
            pbn.created_at,
            p.principal_name,
            s.school_name
        FROM principal_to_bmc_notices pbn
        JOIN principal p ON pbn.principal_id = p.id
        JOIN school s ON pbn.school_id = s.id
        ORDER BY pbn.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notices[] = $row;
    }
}

$pageTitle = 'View Principal Notices';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <!-- <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css"> -->
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Notices from Principals</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Received Notices</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="noticesTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>From</th>
                                            <th>School</th>
                                            <th>Title</th>
                                            <th>Content</th>
                                            <th>Date</th>
                                            <th>Attachment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($notices as $notice): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($notice['principal_name']); ?></td>
                                                <td><?php echo htmlspecialchars($notice['school_name']); ?></td>
                                                <td><?php echo htmlspecialchars($notice['title']); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($notice['content'])); ?></td>
                                                <td><?php echo date('d-m-Y H:i', strtotime($notice['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($notice['file_path']): ?>
                                                        <a href="<?php echo htmlspecialchars($notice['file_path']); ?>" class="btn btn-success btn-sm" download="<?php echo htmlspecialchars($notice['original_filename']); ?>">
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($notices)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No notices received from principals yet.</td>
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
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5><button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a></div>
            </div>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#noticesTable').DataTable({
                "order": [[ 4, "desc" ]] // Sort by the Date column descending
            });
        });
    </script>
</body>
</html>