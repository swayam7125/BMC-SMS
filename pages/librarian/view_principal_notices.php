<?php
// Use absolute paths for includes
include_once __DIR__ . "/../../encryption.php";
include_once __DIR__ . "/../../includes/connect.php";
include_once __DIR__ . "/../../includes/ajax_helpers.php";

$role = null;
$userId = null;
$schoolId = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security check
if ($role !== 'librarian' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

try {
    // Get Librarian's School ID using PDO
    $stmt_school = $conn->prepare("SELECT school_id FROM librarian WHERE id = ?");
    $stmt_school->execute([$userId]);
    $schoolId = $stmt_school->fetchColumn();

    // --- Mark all unread principal notices as read for this librarian (PDO VERSION) ---
    if ($userId) {
        // PostgreSQL uses 'true'/'false' for booleans
        $stmt_mark_all_read = $conn->prepare("UPDATE notifications SET is_read = true WHERE user_id = ? AND type = 'principal_to_librarian_notice' AND is_read = false");
        $stmt_mark_all_read->execute([$userId]);
    }

    // Fetch notices for the librarian's school using PDO
    $notices = [];
    if ($schoolId) {
        $sql = "SELECT 
                    p_n.title, 
                    p_n.content, 
                    p_n.file_path, 
                    p_n.original_filename, 
                    p_n.created_at,
                    p.principal_name as sender
                 FROM principal_to_librarian_notices p_n
                 JOIN principal p ON p_n.principal_id = p.id
                 WHERE p_n.school_id = ?
                 ORDER BY p_n.created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$schoolId]);
        $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Error viewing principal notices: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
} 

$pageTitle = 'View Principal Notices';

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
<?php
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Notices from Principal</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Notice Feed</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="librarian-viewNoticeTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>From</th>
                                            <th>Title</th>
                                            <th>Content</th>
                                            <th>Date</th>
                                            <th>Attachment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($notices)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No notices received from the principal yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($notices as $notice): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($notice['sender']); ?></td>
                                                    <td><?php echo htmlspecialchars($notice['title']); ?></td>
                                                    <td><?php echo nl2br(htmlspecialchars($notice['content'])); ?></td>
                                                    <td><?php echo date('d-m-Y H:i', strtotime($notice['created_at'])); ?></td>
                                                    <td>
                                                        <?php if (!empty($notice['file_path'])): ?>
                                                            <a href="<?php echo htmlspecialchars($notice['file_path']); ?>" class="btn btn-success btn-sm" download="<?php echo htmlspecialchars($notice['original_filename']); ?>">
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                        <?php else: ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
<?php
if (!is_ajax_request()) {
?>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>

    <?php include_once "../../includes/logout_modal.php"?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#librarian-viewNoticeTable').DataTable({
                "order": [[ 3, "desc" ]] // Sort by date descending
            });
        });
    </script>

</body>
</html>
<?php
}
$conn = null; // Close the connection
?>