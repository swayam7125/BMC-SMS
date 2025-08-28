<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
*/
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

if (!$role || !$userId) {
    header("Location: ../../login.php");
    exit;
}

try {
    if ($role === 'superadmin') {
        $stmt_mark_read = $conn->prepare('UPDATE "notifications" SET "is_read" = true WHERE "user_id" = ? AND "type" = \'principal_notice\' AND "is_read" = false');
        $stmt_mark_read->execute([$userId]);
    }

    $sql = 'SELECT 
                pbn.title, pbn.content, pbn.file_path, 
                pbn.original_filename, pbn.created_at,
                p.principal_name as sender
             FROM principal_to_bmc_notices pbn
             JOIN principal p ON pbn.principal_id = p.id
             ORDER BY pbn.created_at DESC';

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = "View Principal Notices";
?>

<?php
/*
|--------------------------------------------------------------------------
| RESPONSIVE & PROFESSIONAL FRONTEND (VIEW)
|--------------------------------------------------------------------------
*/
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">

    <style>
        .mobile-card-view {
            display: none;
        }

        @media (max-width: 991.98px) {
            .desktop-table {
                display: none;
            }

            .mobile-card-view {
                display: block;
            }
        }
    </style>
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
                            <h6 class="m-0 font-weight-bold text-primary">Notice Board</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive desktop-table">
                                <table class="table table-hover" id="noticesTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Content</th>
                                            <th>Sender</th>
                                            <th>Date</th>
                                            <th>Attachment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($notices)): ?>
                                            <?php foreach ($notices as $notice): ?>
                                                <tr>
                                                    <td class="font-weight-bold"><?php echo htmlspecialchars($notice['title']); ?></td>
                                                    <td><?php echo nl2br(htmlspecialchars($notice['content'])); ?></td>
                                                    <td><?php echo htmlspecialchars($notice['sender']); ?></td>
                                                    <td><?php echo date('d M, Y', strtotime($notice['created_at'])); ?></td>
                                                    <td>
                                                        <?php if ($notice['file_path']): ?>
                                                            <a href="<?php echo htmlspecialchars($notice['file_path']); ?>" class="btn btn-sm btn-success" download="<?php echo htmlspecialchars($notice['original_filename']); ?>">
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">None</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mobile-card-view">
                                <?php if (!empty($notices)): ?>
                                    <?php foreach ($notices as $notice): ?>
                                        <div class="card shadow-sm mb-3">
                                            <div class="card-header bg-light py-3">
                                                <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($notice['title']); ?></h6>
                                                <small class="text-muted">From <?php echo htmlspecialchars($notice['sender']); ?> on <?php echo date('d M, Y', strtotime($notice['created_at'])); ?></small>
                                            </div>
                                            <div class="card-body">
                                                <p><?php echo nl2br(htmlspecialchars($notice['content'])); ?></p>
                                                <?php if ($notice['file_path']): ?>
                                                    <hr>
                                                    <a href="<?php echo htmlspecialchars($notice['file_path']); ?>" class="btn btn-success btn-block" download="<?php echo htmlspecialchars($notice['original_filename']); ?>">
                                                        <i class="fas fa-download"></i> Download Attachment
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <?php if (empty($notices)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-bell-slash fa-3x text-gray-400"></i>
                                    <p class="mt-3">No notices have been received from principals yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>
<?php $conn = null; ?>