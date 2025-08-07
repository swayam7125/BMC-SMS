<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use absolute paths for includes
include_once __DIR__ . "/../../encryption.php";
include_once __DIR__ . "/../../includes/connect.php";

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
if ($role !== 'principal' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

// Get School ID and principal name using PDO
try {
    $stmt_principal = $conn->prepare("SELECT school_id, principal_name FROM principal WHERE id = ?");
    $stmt_principal->execute([$userId]);
    $principal_data = $stmt_principal->fetch(PDO::FETCH_ASSOC);
    if ($principal_data) {
        $schoolId = $principal_data['school_id'];
        $principalName = $principal_data['principal_name'];
    }
} catch (PDOException $e) {
    error_log("Error fetching principal data: " . $e->getMessage());
    die("A database error occurred.");
}


// --- FORM PROCESSING ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_notice_to_librarian'])) {
    if (!$schoolId) {
        die("Could not determine your school. Action aborted.");
    }
    
    $title = $_POST['title'];
    $content = $_POST['content'];

    // --- FILE UPLOAD ---
    $filePathForDB = null;
    $originalFilename = null;
    if (isset($_FILES['notice_file']) && $_FILES['notice_file']['error'] == 0) {
        $originalFilename = basename($_FILES["notice_file"]["name"]);
        $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/principal/uploads/librarian_notices/';
        if (!is_dir($uploadDirServer)) {
            mkdir($uploadDirServer, 0777, true);
        }

        $storageFilename = 'p2l_notice_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $originalFilename);
        $serverFilePath = $uploadDirServer . $storageFilename;
        if (move_uploaded_file($_FILES["notice_file"]["tmp_name"], $serverFilePath)) {
            // Store the web-accessible path
            $filePathForDB = '/BMC-SMS/pages/principal/uploads/librarian_notices/' . $storageFilename;
        }
    }

    // --- DATABASE INSERTION AND NOTIFICATION (PDO Transaction) ---
    try {
        $conn->beginTransaction();

        // 1. Insert notice into the dedicated table
        $stmt_content = $conn->prepare("INSERT INTO principal_to_librarian_notices (principal_id, school_id, title, content, file_path, original_filename) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_content->execute([$userId, $schoolId, $title, $content, $filePathForDB, $originalFilename]);
        
        // 2. Find all Librarian user IDs for the specific school
        $stmt_librarian_users = $conn->prepare("SELECT id FROM librarian WHERE school_id = ?");
        $stmt_librarian_users->execute([$schoolId]);
        $librarian_user_ids = $stmt_librarian_users->fetchAll(PDO::FETCH_COLUMN, 0);

        // 3. Create a notification for each Librarian in the school
        if (!empty($librarian_user_ids)) {
            $notification_message = "New Notice from Principal " . htmlspecialchars($principalName);
            $notification_link = "/pages/librarian/view_principal_notices.php";
            $notification_type = "principal_to_librarian_notice";
            
            $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
            foreach ($librarian_user_ids as $librarian_id) {
                $stmt_notify->execute([$librarian_id, $notification_message, $notification_link, $notification_type]);
            }
        }
        
        $conn->commit();
        header("Location: send_notice_to_librarian.php?success=1");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Failed to send librarian notice: " . $e->getMessage());
        die("Failed to send notice. Please check the logs.");
    }
}

$pageTitle = 'Send Notice to Librarian';
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
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send Notice to Librarian</h1>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Notice sent to Librarian(s) successfully!</div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Compose Notice</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="send_notice_to_librarian.php" enctype="multipart/form-data">
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
                                <button type="submit" name="send_notice_to_librarian" class="btn btn-primary">
                                    <i class="fas fa-paper-plane mr-2"></i>Send to Librarian
                                </button>
                            </form>
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
</body>
</html>
