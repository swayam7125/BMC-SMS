<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
*/
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/ajax_helpers.php";

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

// --- Authorization & Initialization ---
$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');

if (!$role || !$userId || $role !== 'superadmin') {
    header("Location: ../../login.php");
    exit;
}

$notices = [];

try {
    // Mark relevant notifications as read
    $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = true WHERE user_id = ? AND type = 'principal_notice' AND is_read = false");
    $stmt_mark_read->execute([$userId]);

    // Fetch all notices sent from principals to BMC
    $sql = "SELECT pbn.title, pbn.content, pbn.file_path, pbn.original_filename, pbn.created_at, p.principal_name as sender
            FROM principal_to_bmc_notices pbn
            JOIN principal p ON pbn.principal_id = p.id
            ORDER BY pbn.created_at DESC";
    $stmt_notices = $conn->query($sql);
    $notices = $stmt_notices->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in view_principal_notices.php: " . $e->getMessage());
    // Optionally set an error message
}

/*
|--------------------------------------------------------------------------
| FRONTEND VIEW (HTML)
|--------------------------------------------------------------------------
*/
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>View Principal Notices</title>
        <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    </head>

    <body id="page-top">
        <div id="wrapper">
            <?php if (!$is_ajax_request) {
                include '../../includes/sidebar.php';
            } ?>
            <div id="content-wrapper" class="d-flex flex-column">
                <div id="content">
                    <?php if (!$is_ajax_request) {
                        include '../../includes/header.php';
                    } ?>
                    <div id="main-content">

                    <div class="container-fluid">
                        <h1 class="h3 mb-4 text-gray-800">Notices from Principals</h1>

                        <div id="noticeAccordion">
                            <?php if (empty($notices)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-bell-slash fa-3x text-gray-400"></i>
                                    <p class="mt-3">No notices have been received from principals yet.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notices as $index => $notice): ?>
                                    <div class="card shadow mb-2">
                                        <div class="card-header" id="heading<?php echo $index; ?>">
                                            <h2 class="mb-0">
                                                <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse<?php echo $index; ?>">
                                                    <div class="d-flex justify-content-between">
                                                        <span><?php echo htmlspecialchars($notice['title']); ?></span>
                                                        <small>From: <?php echo htmlspecialchars($notice['sender']); ?> on <?php echo date('d-m-Y', strtotime($notice['created_at'])); ?></small>
                                                    </div>
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="collapse<?php echo $index; ?>" class="collapse" data-parent="#noticeAccordion">
                                            <div class="card-body">
                                                <p><?php echo nl2br(htmlspecialchars($notice['content'])); ?></p>
                                                <?php if ($notice['file_path']): ?>
                                                    <hr>
                                                    <a href="<?php echo htmlspecialchars($notice['file_path']); ?>" download="<?php echo htmlspecialchars($notice['original_filename']); ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-download"></i> Download Attachment
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if (!$is_ajax_request) {
                            include '../../includes/footer.php';
                        } ?>
            </div>
        </div>
        <?php include_once "../../includes/logout_modal.php" ?>
        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>
    </body>

    </html>
<?php
                    $conn = null;
?>