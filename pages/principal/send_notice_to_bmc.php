<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;
$schoolId = null;
$principalName = 'Principal';

// Get user info from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security check
if ($role !== 'schooladmin' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

// Get School ID and principal name
$stmt_principal = $conn->prepare("SELECT school_id, principal_name FROM principal WHERE id = ?");
$stmt_principal->bind_param("i", $userId);
$stmt_principal->execute();
$result_principal = $stmt_principal->get_result();
if ($row = $result_principal->fetch_assoc()) {
    $schoolId = $row['school_id'];
    $principalName = $row['principal_name'];
}
$stmt_principal->close();


// --- FORM PROCESSING ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_notice_to_bmc'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];

    // --- FILE UPLOAD ---
    $filePathForDB = null;
    $originalFilename = null;
    if (isset($_FILES['notice_file']) && $_FILES['notice_file']['error'] == 0) {
        $originalFilename = basename($_FILES["notice_file"]["name"]);
        $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/principal/uploads/bmc_notices/';
        $uploadDirWeb = '/BMC-SMS/pages/principal/uploads/bmc_notices/';
        if (!is_dir($uploadDirServer)) mkdir($uploadDirServer, 0777, true);

        $storageFilename = uniqid('p2b_notice_', true) . '_' . $originalFilename;
        $serverFilePath = $uploadDirServer . $storageFilename;
        if (move_uploaded_file($_FILES["notice_file"]["tmp_name"], $serverFilePath)) {
            $filePathForDB = $uploadDirWeb . $storageFilename;
        }
    }

    // --- DATABASE INSERTION AND NOTIFICATION ---
    $conn->begin_transaction();
    try {
        // Insert notice into the new table
        $stmt_content = $conn->prepare("INSERT INTO principal_to_bmc_notices (principal_id, school_id, title, content, file_path, original_filename) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_content->bind_param("iissss", $userId, $schoolId, $title, $content, $filePathForDB, $originalFilename);
        $stmt_content->execute();
        $stmt_content->close();
        
        // Find all BMC admin user IDs
        $stmt_bmc_users = $conn->query("SELECT id FROM users WHERE role = 'bmc'");
        $bmc_user_ids = $stmt_bmc_users->fetch_all(MYSQLI_ASSOC);
        $stmt_bmc_users->close();

        // Create a notification for each BMC admin
        if (!empty($bmc_user_ids)) {
            $notification_message = "New Notice from " . htmlspecialchars($principalName);
            $notification_link = "/pages/bmc/view_principal_notices.php";
            $notification_type = "principal_notice";
            
            $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
            foreach ($bmc_user_ids as $bmc_user) {
                $bmc_id = $bmc_user['id'];
                $stmt_notify->bind_param("isss", $bmc_id, $notification_message, $notification_link, $notification_type);
                $stmt_notify->execute();
            }
            $stmt_notify->close();
        }
        
        $conn->commit();
        header("Location: send_notice_to_bmc.php?success=1");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Failed to send notice: " . $e->getMessage());
    }
}

$pageTitle = 'Send Notice to BMC';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <link rel="stylesheet" href="../../assets/css/sidebar.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send Notice to BMC</h1>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Notice sent to BMC successfully!</div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Compose Notice</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="send_notice_to_bmc.php" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label for="content">Content</label>
                                    <textarea class="form-control" id="content" name="content" rows="6" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="notice_file">Attach File (Optional)</label>
                                    <input type="file" class="form-control-file" id="notice_file" name="notice_file">
                                </div>
                                <button type="submit" name="send_notice_to_bmc" class="btn btn-primary">Send to BMC</button>
                            </form>
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
</body>
</html>