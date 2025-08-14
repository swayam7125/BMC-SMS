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

// All users with a valid role can view, so we just check if a role exists.
// The sidebar logic will handle menu visibility.
if (!$role || !$userId) {
    header("Location: ../../login.php");
    exit;
}

try {
    // This query marks notifications as 'read' for the logged-in user.
    // This part is specific to superadmins who get formal notifications.
    if ($role === 'superadmin') {
        $stmt_mark_read = $conn->prepare('UPDATE "notifications" SET "is_read" = true WHERE "user_id" = ? AND "type" = \'principal_notice\' AND "is_read" = false');
        $stmt_mark_read->execute([$userId]);
    }

    // This SQL query fetches all notices and correctly sorts them by date descending.
    $sql = 'SELECT 
                pbn.title, pbn.content, pbn.file_path, 
                pbn.original_filename, pbn.created_at,
                p.principal_name, s.school_name
            FROM "principal_to_bmc_notices" pbn
            JOIN "principal" p ON pbn.principal_id = p.id
            JOIN "school" s ON pbn.school_id = s.id
            ORDER BY pbn.created_at DESC'; // This ensures newest notices are first.
    $stmt_notices = $conn->query($sql);
    $notices = $stmt_notices->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$pageTitle = 'View Principal Notices';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
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
                                                        <a href="<?php echo htmlspecialchars(BASE_URL . ltrim($notice['file_path'], '/')); ?>" class="btn btn-success btn-sm" download="<?php echo htmlspecialchars($notice['original_filename']); ?>">
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
        <?php include_once "../../includes/logout_modal.php"?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            // --- FIX: The "order" option has been removed. ---
            // This tells DataTables to respect the initial order of the rows
            // provided by the server, which is already sorted with the newest notices first.
            $('#noticesTable').DataTable();
        });
    </script>
</body>
</html>
<?php $conn = null; ?>