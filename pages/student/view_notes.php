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

// --- START: MARK ALL NOTE NOTIFICATIONS AS READ ON PAGE VIEW ---
if ($role === 'student' && $userId) {
    $stmt_mark_all_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type = 'new_notes' AND is_read = 0");
    if ($stmt_mark_all_read) {
        $stmt_mark_all_read->bind_param("i", $userId);
        $stmt_mark_all_read->execute();
        $stmt_mark_all_read->close();
    }
}
// --- END: MARK ALL NOTE NOTIFICATIONS AS READ ---


// --- START: MARK INDIVIDUAL AS READ LOGIC ---
if (isset($_GET['notif_id']) && is_numeric($_GET['notif_id'])) {
    $notification_id = $_GET['notif_id'];
    $current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
    if ($current_user_id) {
        $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt_mark_read->bind_param("ii", $notification_id, $current_user_id);
        $stmt_mark_read->execute();
        $stmt_mark_read->close();
    }
}
// --- END: MARK INDIVIDUAL AS READ LOGIC ---

if (!$role || !$userId) {
    header("Location: ./login.php");
    exit;
}

$schoolId = null;
$studentStd = null;
$teacherStds = [];

switch ($role) {
    case 'student':
        $stmt = $conn->prepare("SELECT school_id, std FROM student WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $schoolId = $row['school_id'];
            $studentStd = $row['std'];
        }
        $stmt->close();
        break;
    case 'teacher':
        $stmt = $conn->prepare("SELECT school_id, std FROM teacher WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $schoolId = $row['school_id'];
            if (!empty($row['std'])) {
                $teacherStds = explode(',', $row['std']);
            }
        }
        $stmt->close();
        break;
    case 'schooladmin':
        $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $schoolId = $row['school_id'];
        }
        $stmt->close();
        break;
}

$notes = [];
$base_sql = "SELECT 
                n.title, 
                n.content, 
                n.file_path, 
                n.original_filename, 
                n.created_at, 
                n.target_standard, 
                COALESCE(t.teacher_name, p.principal_name, u.email) as sender
             FROM notes n 
             JOIN users u ON n.user_id = u.id
             LEFT JOIN teacher t ON u.id = t.id AND u.role = 'teacher'
             LEFT JOIN principal p ON u.id = p.id AND u.role = 'schooladmin'";
$params = [];
$types = '';

switch ($role) {
    case 'student':
        $base_sql .= " WHERE n.school_id = ? AND n.target_standard = ?";
        $params = [$schoolId, $studentStd];
        $types = "is";
        break;
    case 'teacher':
        if (!empty($teacherStds)) {
            $placeholders = implode(',', array_fill(0, count($teacherStds), '?'));
            $base_sql .= " WHERE (n.school_id = ? AND n.target_standard IN ($placeholders)) OR n.user_id = ?";
            $params = array_merge([$schoolId], $teacherStds, [$userId]);
            $types = "i" . str_repeat('s', count($teacherStds)) . "i";
        } else {
            $base_sql .= " WHERE n.user_id = ?";
            $params = [$userId];
            $types = "i";
        }
        break;
    case 'schooladmin':
        $base_sql .= " WHERE n.school_id = ?";
        $params = [$schoolId];
        $types = "i";
        break;
}

$base_sql .= " ORDER BY n.created_at DESC";

$stmt = $conn->prepare($base_sql);
if ($stmt && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
    $stmt->close();
}
// **FIX: The line "$conn->close();" was REMOVED from here.**
$pageTitle = 'View Notes';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400i,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    
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
                    <h1 class="h3 mb-4 text-gray-800">Received Notes</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Notes Feed</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="notesTable " width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>From</th>
                                            <th>Title</th>
                                            <th>Content</th>
                                            <th>For Standard</th>
                                            <th>Date</th>
                                            <th>Attachment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($notes as $note): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($note['sender']); ?></td>
                                                <td><?php echo htmlspecialchars($note['title']); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($note['content'])); ?></td>
                                                <td><?php echo htmlspecialchars($note['target_standard']); ?></td>
                                                <td><?php echo date('d-m-Y H:i', strtotime($note['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($note['file_path']): ?>
                                                        <a href="<?php echo htmlspecialchars(BASE_WEB_PATH . ltrim($note['file_path'], '/')); ?>" class="btn btn-success btn-sm" download="<?php echo htmlspecialchars($note['original_filename']); ?>">
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($notes)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No notes received yet.</td>
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
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <script src="../../assets/js/custom_student_scripts.js"></script>
</body>
</html>
<?php 
if (isset($conn)) {
    $conn->close();
}
?>