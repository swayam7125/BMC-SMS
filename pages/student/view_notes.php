<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;
$notes = [];
$error_message = null; // Variable to hold potential error messages

if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$role || !$userId) {
    header("Location: ./login.php");
    exit;
}

try {
    // --- START: NOTIFICATION LOGIC ---
    if ($role === 'student' && $userId) {
        // FIX: Changed integer 0 to boolean FALSE for PostgreSQL compatibility.
        $stmt_mark_all_read = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND type = 'new_notes' AND is_read = FALSE");
        $stmt_mark_all_read->execute([$userId]);
    }
    if (isset($_GET['notif_id']) && is_numeric($_GET['notif_id'])) {
        $notification_id = $_GET['notif_id'];
        // FIX: Changed integer 1 to boolean TRUE.
        $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt_mark_read->execute([$notification_id, $userId]);
    }
    // --- END: NOTIFICATION LOGIC ---

    $schoolId = null;
    $studentStd = null;
    $teacherStds = [];

    // --- START: FETCH USER-SPECIFIC DATA ---
    switch ($role) {
        case 'student':
            $stmt = $conn->prepare("SELECT school_id, std FROM student WHERE id = ?");
            $stmt->execute([$userId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $schoolId = $row['school_id'];
                $studentStd = $row['std'];
                if (!$schoolId || !$studentStd) {
                    $error_message = "Your profile is incomplete (missing School or Standard assignment). Please contact an administrator.";
                }
            } else {
                $error_message = "Could not find your student profile.";
            }
            break;
        case 'teacher':
            $stmt = $conn->prepare("SELECT school_id, std FROM teacher WHERE id = ?");
            $stmt->execute([$userId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $schoolId = $row['school_id'];
                if (!empty($row['std'])) {
                    $teacherStds = explode(',', $row['std']);
                }
                if (!$schoolId) {
                    $error_message = "Your profile is incomplete (missing School assignment). Please contact an administrator.";
                }
            } else {
                $error_message = "Could not find your teacher profile.";
            }
            break;
        case 'principal':
            $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
            $stmt->execute([$userId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $schoolId = $row['school_id'];
                if (!$schoolId) {
                    $error_message = "Your profile is incomplete (missing School assignment). Please contact an administrator.";
                }
            } else {
                $error_message = "Could not find your principal profile.";
            }
            break;
    }
    // --- END: FETCH USER-SPECIFIC DATA ---

    // --- START: FETCH NOTES IF NO ERRORS ---
    if (!$error_message) {
        $base_sql = "SELECT 
                        n.title, n.content, n.file_path, n.original_filename, n.created_at, n.target_standard, 
                        COALESCE(t.teacher_name, p.principal_name, u.email) as sender
                     FROM notes n 
                     JOIN users u ON n.user_id = u.id
                     LEFT JOIN teacher t ON u.id = t.id AND u.role = 'teacher'
                     LEFT JOIN principal p ON u.id = p.id AND u.role = 'principal'";
        $params = [];

        switch ($role) {
            case 'student':
                $base_sql .= " WHERE n.school_id = ? AND n.target_standard = ?";
                $params = [$schoolId, $studentStd];
                break;
            case 'teacher':
                if (!empty($teacherStds)) {
                    $placeholders = implode(',', array_fill(0, count($teacherStds), '?'));
                    $base_sql .= " WHERE (n.school_id = ? AND n.target_standard IN ($placeholders)) OR n.user_id = ?";
                    $params = array_merge([$schoolId], $teacherStds, [$userId]);
                } else {
                    $base_sql .= " WHERE n.user_id = ?";
                    $params = [$userId];
                }
                break;
            case 'principal':
                $base_sql .= " WHERE n.school_id = ?";
                $params = [$schoolId];
                break;
        }

        $base_sql .= " ORDER BY n.created_at DESC";

        if (!empty($params)) {
            $stmt = $conn->prepare($base_sql);
            $stmt->execute($params);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    // --- END: FETCH NOTES ---

} catch (PDOException $e) {
    error_log("DB Error in view_notes.php: " . $e->getMessage());
    $error_message = "A database error occurred. Please try again later.";
}

$pageTitle = 'View Notes';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
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
                                <table class="table table-bordered" id="notesTable" width="100%" cellspacing="0">
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
                                        <?php if ($error_message): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-danger font-weight-bold">
                                                    <?php echo htmlspecialchars($error_message); ?>
                                                </td>
                                            </tr>
                                        <?php elseif (empty($notes)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No notes have been received yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($notes as $note): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($note['sender']); ?></td>
                                                    <td><?php echo htmlspecialchars($note['title']); ?></td>
                                                    <td><?php echo nl2br(htmlspecialchars($note['content'])); ?></td>
                                                    <td><?php echo htmlspecialchars($note['target_standard']); ?></td>
                                                    <td><?php echo date('d-m-Y H:i', strtotime($note['created_at'])); ?></td>
                                                    <td>
                                                        <?php if ($note['file_path']): ?>
                                                            <a href="<?php echo htmlspecialchars(BASE_URL . ltrim($note['file_path'], '/')); ?>" class="btn btn-success btn-sm" download="<?php echo htmlspecialchars($note['original_filename']); ?>">
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
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#notesTable').DataTable({
                "order": [
                    [4, "desc"]
                ] // Sort by the 'Date' column descending
            });
        });
    </script>
</body>

</html>