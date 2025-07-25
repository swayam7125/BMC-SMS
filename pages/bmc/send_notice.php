<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Ensure only bmc admin can access
if ($role !== 'bmc' || !$userId) {
    header("Location: ../login.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_notice'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $filePathForDB = null;
    $originalFilename = null;

    if (isset($_FILES['notice_file']) && $_FILES['notice_file']['error'] == 0) {
        $originalFilename = basename($_FILES["notice_file"]["name"]);
        
        // --- CORRECTED PATHS ---
        // Server path for moving the file
        $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/bmc/uploads/';
        // Web path for storing in the database
        $uploadDirWeb = '/BMC-SMS/pages/bmc/uploads/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadDirServer)) {
            mkdir($uploadDirServer, 0777, true);
        }
        
        $storageFilename = uniqid('notice_', true) . '_' . $originalFilename;
        $serverFilePath = $uploadDirServer . $storageFilename;

        if (move_uploaded_file($_FILES["notice_file"]["tmp_name"], $serverFilePath)) {
            $filePathForDB = $uploadDirWeb . $storageFilename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO notice (user_id, title, content, file_path, original_filename) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $title, $content, $filePathForDB, $originalFilename);
    $stmt->execute();
    $stmt->close();

    header("Location: send_notice.php?success=1");
    exit();
}

// Fetch history of the last 5 notices sent by this user
$noticesHistory = [];
$stmt_history = $conn->prepare("SELECT title, created_at FROM notice WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt_history->bind_param("i", $userId);
$stmt_history->execute();
$result_history = $stmt_history->get_result();
while ($row_history = $result_history->fetch_assoc()) {
    $noticesHistory[] = $row_history;
}
$stmt_history->close();
$pageTitle = 'Send Notice';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400i,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send a Notice</h1>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">New Notice</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="send_notice.php" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label for="title">Title</label>
                                            <input type="text" class="form-control" id="title" name="title" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="content">Content</label>
                                            <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="notice_file">Attach File (Optional)</label>
                                            <input type="file" class="form-control-file" id="notice_file" name="notice_file">
                                        </div>
                                        <button type="submit" name="send_notice" class="btn btn-primary">Send Notice</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Sent Notices History (Last 5)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($noticesHistory as $notice): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($notice['title']); ?></td>
                                                        <td><?php echo date('d-m-Y H:i', strtotime($notice['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($noticesHistory)): ?>
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
    
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>