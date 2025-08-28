<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
*/
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/ajax_helpers.php";

$role = null;
$userId = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'superadmin' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_notice'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $filePathForDB = null;
        $originalFilename = null;

        if (isset($_FILES['notice_file']) && $_FILES['notice_file']['error'] == 0) {
            $originalFilename = basename($_FILES["notice_file"]["name"]);
            $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/bmc/uploads/';
            $uploadDirWeb = '/BMC-SMS/pages/bmc/uploads/';
            if (!is_dir($uploadDirServer)) mkdir($uploadDirServer, 0777, true);

            $storageFilename = uniqid('notice_', true) . '_' . $originalFilename;
            $serverFilePath = $uploadDirServer . $storageFilename;

            if (move_uploaded_file($_FILES["notice_file"]["tmp_name"], $serverFilePath)) {
                $filePathForDB = $uploadDirWeb . $storageFilename;
            }
        }

        // --- FIX: Removed the non-existent "sent_by" column ---
        $stmt = $conn->prepare('INSERT INTO "notice" (title, content, file_path, original_filename) VALUES (?, ?, ?, ?)');
        $stmt->execute([$title, $content, $filePathForDB, $originalFilename]);

        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
    }

    // --- FIX: Removed "sent_by" from the query ---
    $stmt_history = $conn->prepare('SELECT "title", "created_at" FROM "notice" ORDER BY "created_at" DESC');
    $stmt_history->execute();
    $noticesHistory = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = "Send Notice to Principals";
?>

<?php
/*
|--------------------------------------------------------------------------
| RESPONSIVE & PROFESSIONAL FRONTEND (VIEW)
|--------------------------------------------------------------------------
*/
if (!is_ajax_request()):
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title><?php echo htmlspecialchars($pageTitle); ?></title>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
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
                        <h1 class="h3 mb-4 text-gray-800">Send Notice to All Principals</h1>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">Notice sent successfully!</div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-lg-7 mb-4">
                                <div class="card shadow h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Compose Notice</h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" enctype="multipart/form-data" action="send_notice.php">
                                            <div class="form-group">
                                                <label for="title">Title</label>
                                                <input type="text" class="form-control" id="title" name="title" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="content">Content / Description</label>
                                                <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="notice_file">Attach File (Optional)</label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="notice_file" name="notice_file">
                                                    <label class="custom-file-label" for="notice_file">Choose file...</label>
                                                </div>
                                            </div>
                                            <button type="submit" name="send_notice" class="btn btn-primary"><i class="fas fa-paper-plane mr-2"></i>Send Notice</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5 mb-4">
                                <div class="card shadow h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Sent History</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="historyTable" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Date Sent</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($noticesHistory)): ?>
                                                        <?php foreach ($noticesHistory as $notice): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($notice['title']); ?></td>
                                                                <td><?php echo date('d-m-Y H:i', strtotime($notice['created_at'])); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="2" class="text-center">No notices sent yet.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include '../../includes/footer.php'; ?>
            </div>
        </div>
        <?php include_once "../../includes/logout_modal.php" ?>

        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>

        <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>

        <script>
            $(document).ready(function() {
                $('#historyTable').DataTable({
                    "order": [
                        [1, "desc"]
                    ]
                });
                $('.custom-file-input').on('change', function() {
                    var fileName = $(this).val().split('\\').pop();
                    $(this).siblings('.custom-file-label').addClass("selected").html(fileName);
                });
            });
        </script>
    </body>

    </html>
<?php
endif; // End ajax check
$conn = null;