<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // Log system included

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = 'Super Admin'; // As this page is for superadmin

if ($role !== 'superadmin') {
    header("Location: ../../login.php");
    exit;
}

$success_msg = '';
$error_msg = '';
$principals = [];
$past_notices = [];

try {
    // Fetch all principals for the dropdown
    $stmt_principals = $conn->prepare(
        "SELECT p.id, p.principal_name, s.school_name 
         FROM principal p 
         JOIN school s ON p.school_id = s.id 
         ORDER BY s.school_name, p.principal_name"
    );
    $stmt_principals->execute();
    $principals = $stmt_principals->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $target_principals = $_POST['target_principals'] ?? [];

        if (empty($title) || empty($content) || empty($target_principals)) {
            $error_msg = "Title, content, and at least one principal must be selected.";
        } else {
            $conn->beginTransaction();
            
            // 1. Insert the notice into the 'superadmin_notices' table
            $stmt_insert_notice = $conn->prepare(
                "INSERT INTO notice (user_id, title, content) VALUES (?, ?, ?)"
            );
            $stmt_insert_notice->execute([$userId, $title, $content]);
            $notice_id = $conn->lastInsertId();

            // 2. Create notifications for the selected principals
            $notification_msg = "New notice from BMC: " . htmlspecialchars($title);
            $notification_link = "pages/principal/view_notice.php?notice_id=" . $notice_id;
            $notification_type = "new_notice"; // A specific type for BMC notices

            $stmt_notify = $conn->prepare(
                "INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)"
            );
            
            foreach ($target_principals as $principal_id) {
                // Ensure we are inserting a valid principal ID
                if (filter_var($principal_id, FILTER_VALIDATE_INT)) {
                    $stmt_notify->execute([$principal_id, $notification_msg, $notification_link, $notification_type]);
                }
            }
            
            $conn->commit();
            $success_msg = "Notice has been successfully sent to the selected principals!";
            // Log the successful actions
            log_interaction($role, $userId, "NOTICE: Sent notice titled '{$title}' to " . count($target_principals) . " principal(s).", $userName);

        }
    }

    // Fetch past notices sent by the superadmin
    $stmt_past_notices = $conn->prepare(
        "SELECT title, content, created_at FROM notice WHERE user_id = ? ORDER BY created_at DESC"
    );
    $stmt_past_notices->execute([$userId]);
    $past_notices = $stmt_past_notices->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $error_msg = "Database Error: " . $e->getMessage();
    // Log the error
    log_interaction($role, $userId, "NOTICE ERROR: DB Error on Send Notice page. Error: " . $e->getMessage(), $userName);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Notice to Principals</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send Notice to Principals</h1>
                    
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                    <?php endif; ?>
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Compose Notice</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="title">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label for="content">Content *</label>
                                    <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="target_principals">Send To *</label>
                                    <select class="form-control" id="target_principals" name="target_principals[]" multiple="multiple" required>
                                        <?php foreach ($principals as $principal): ?>
                                            <option value="<?php echo $principal['id']; ?>">
                                                <?php echo htmlspecialchars($principal['principal_name']) . ' (' . htmlspecialchars($principal['school_name']) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Notice</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Sent Notices History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date Sent</th>
                                            <th>Title</th>
                                            <th>Content</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($past_notices as $notice): ?>
                                            <tr>
                                                <td><?php echo date('d-M-Y h:i A', strtotime($notice['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($notice['title']); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars(substr($notice['content'], 0, 150))) . (strlen($notice['content']) > 150 ? '...' : ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
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
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#target_principals').select2({
                placeholder: "Select one or more principals",
                allowClear: true
            });
            $('#dataTable').DataTable({
                "order": [[ 0, "desc" ]] 
            });
        });
    </script>
</body>
</html>