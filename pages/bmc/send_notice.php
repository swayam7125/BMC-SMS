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

if ($role !== 'superadmin' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

$successMessage = '';
$errorMessage = '';
$notice_history = [];

try {
    // --- Form Submission Handling ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_notice'])) {
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);
        $filePathForDB = null;
        $originalFilename = null;

        // Handle file upload
        if (isset($_FILES['notice_file']) && $_FILES['notice_file']['error'] == 0) {
            $originalFilename = basename($_FILES["notice_file"]["name"]);
            $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/bmc/uploads/';
            if (!is_dir($uploadDirServer)) mkdir($uploadDirServer, 0777, true);

            $safeFilename = time() . "_" . preg_replace("/[^a-zA-Z0-9.\-_]/", "_", $originalFilename);
            $filePathForDB = '/BMC-SMS/pages/bmc/uploads/' . $safeFilename;

            if (!move_uploaded_file($_FILES["notice_file"]["tmp_name"], $uploadDirServer . $safeFilename)) {
                $errorMessage = "Sorry, there was an error uploading your file.";
                $filePathForDB = null; // Reset on failure
            }
        }

        if (empty($errorMessage)) {
            $conn->beginTransaction();

            // Insert the notice
            $stmt_notice = $conn->prepare("INSERT INTO bmc_notices (title, content, file_path, original_filename, sent_by_id) VALUES (?, ?, ?, ?, ?)");
            $stmt_notice->execute([$title, $content, $filePathForDB, $originalFilename, $userId]);
            $notice_id = $conn->lastInsertId();

            // Create notifications for all principals
            $stmt_principals = $conn->query("SELECT id FROM principal");
            $principals = $stmt_principals->fetchAll(PDO::FETCH_ASSOC);

            $notification_message = "New notice from BMC: " . $title;
            $notification_link = "pages/principal/view_notice.php?notice_id=" . $notice_id;
            $notification_type = "new_notice";

            $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, user_role, message, link, type) VALUES (?, 'principal', ?, ?, ?)");
            foreach ($principals as $principal) {
                $stmt_notify->execute([$principal['id'], $notification_message, $notification_link, $notification_type]);
            }

            $conn->commit();
            $successMessage = "Notice has been successfully sent to all principals.";
        }
    }

    // --- Data Fetching for History Table ---
    $stmt_history = $conn->prepare("SELECT title, created_at FROM bmc_notices WHERE sent_by_id = ? ORDER BY created_at DESC");
    $stmt_history->execute([$userId]);
    $notice_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log("Database error in send_notice.php: " . $e->getMessage());
    $errorMessage = "A database error occurred. Please try again.";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Send Notice to Principals</title>
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                        <h1 class="h3 mb-4 text-gray-800">Send Notice to Principals</h1>

                        <?php if ($successMessage): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                        <?php endif; ?>
                        <?php if ($errorMessage): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-lg-7">
                                <div class="card shadow mb-4">
                                    <div class="card-header">
                                        <h6 class="m-0 font-weight-bold text-primary">New Notice</h6>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="POST" enctype="multipart/form-data">
                                            <div class="form-group">
                                                <label for="title">Title</label>
                                                <input type="text" name="title" id="title" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="content">Content</label>
                                                <textarea name="content" id="content" class="form-control" rows="5" required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>Attach File (Optional)</label>
                                                <div class="custom-file">
                                                    <input type="file" name="notice_file" id="notice_file" class="custom-file-input">
                                                    <label class="custom-file-label" for="notice_file">Choose file...</label>
                                                </div>
                                            </div>
                                            <button type="submit" name="send_notice" class="btn btn-primary">Send Notice</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="card shadow mb-4">
                                    <div class="card-header">
                                        <h6 class="m-0 font-weight-bold text-primary">Sent History</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="historyTable" width="100%">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Date Sent</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($notice_history as $notice): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($notice['title']); ?></td>
                                                            <td><?php echo date("d-m-Y h:i A", strtotime($notice['created_at'])); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
$conn = null;
?>