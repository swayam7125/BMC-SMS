<?php
// Note: Debugging lines have been removed as the issue is resolved.
include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;
$notices = [];
$error_message = null; // Variable to hold potential error messages

if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$role || !$userId) {
    header("Location: ../login.php");
    exit;
}

try {
    // --- START: NOTIFICATION LOGIC ---
    if (($role === 'student' || $role === 'teacher') && $userId) {
        // FIX: Changed integer 0 to boolean FALSE to match PostgreSQL boolean type.
        $stmt_mark_all_read = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND type = 'school_notice' AND is_read = FALSE");
        $stmt_mark_all_read->execute([$userId]);
    }
    if (isset($_GET['notif_id']) && is_numeric($_GET['notif_id'])) {
        $notification_id = $_GET['notif_id'];
        // FIX: Changed integer 1 to boolean TRUE.
        $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt_mark_read->execute([$notification_id, $userId]);
    }
    // --- END: NOTIFICATION LOGIC ---

    // --- START: FETCH USER-SPECIFIC DATA ---
    $schoolId = null;
    $studentStd = null;
    if ($role == 'student') {
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
    } elseif ($role == 'teacher') {
        $stmt = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
        $stmt->execute([$userId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $schoolId = $row['school_id'];
            if (!$schoolId) {
                $error_message = "Your profile is incomplete (missing School assignment). Please contact an administrator.";
            }
        } else {
            $error_message = "Could not find your teacher profile.";
        }
    }
    // --- END: FETCH USER-SPECIFIC DATA ---

    // --- START: FETCH NOTICES IF NO ERRORS AND SCHOOLID IS VALID ---
    if (!$error_message && $schoolId) {
        $sql = "SELECT DISTINCT c.title, c.content, c.file_path, c.original_filename, c.created_at
                FROM school_notices_content c
                JOIN school_notice_recipients r ON c.id = r.notice_id
                WHERE c.school_id = ?";

        $params = [$schoolId];
        $can_fetch = false;

        if ($role == 'teacher') {
            $sql .= " AND r.recipient_type = 'teacher'";
            $can_fetch = true;
        } elseif ($role == 'student' && $studentStd) {
            $sql .= " AND r.recipient_type = 'standard' AND r.recipient_identifier = ?";
            $params[] = $studentStd;
            $can_fetch = true;
        }

        if ($can_fetch) {
            $sql .= " ORDER BY c.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif (!$error_message) {
        $error_message = "No notice viewing rules configured for your role.";
    }
    // --- END: FETCH NOTICES ---

} catch (PDOException $e) {
    // Revert to a user-friendly error message for production.
    error_log("DB Error in view_notice.php: " . $e->getMessage());
    $error_message = "A database error occurred. Please try again later.";
}

$pageTitle = 'View School Notices';
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
                    <h1 class="h3 mb-4 text-gray-800">School Notices</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Notice Feed</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($error_message): ?>
                                <div class="text-center text-danger font-weight-bold">
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php elseif (empty($notices)): ?>
                                <div class="text-center">No school notices have been sent to you yet.</div>
                            <?php else: ?>
                                <?php foreach ($notices as $notice): ?>
                                    <div class="card mb-3">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($notice['title']); ?></h5>
                                            <small class="text-muted">Posted on: <?php echo date('d-m-Y H:i', strtotime($notice['created_at'])); ?></small>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars($notice['content'])); ?></p>
                                            <?php if ($notice['file_path']): ?>
                                                <hr>
                                                <a href="<?php echo htmlspecialchars(BASE_URL . ltrim($notice['file_path'], '/')); ?>" class="btn btn-success btn-sm" download="<?php echo htmlspecialchars($notice['original_filename']); ?>">
                                                    <i class="fas fa-download"></i> Download Attachment
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>