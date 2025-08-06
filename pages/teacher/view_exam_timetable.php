<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";

// FIX #2: Define the base path constant, consistent with your other files.
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

$role = null;
$userId = null;
$schoolId = null;
$timetables = [];

if (isset($_COOKIE['encrypted_user_role'])) $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id'])) $userId = decrypt_id($_COOKIE['encrypted_user_id']);

if (!in_array($role, ['student', 'teacher']) || !$userId) {
    header("Location: ../../login.php");
    exit;
}

try {
    if ($role == 'student') {
        $stmt_school = $conn->prepare("SELECT school_id FROM student WHERE id = ?");
    } else { // teacher
        $stmt_school = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
    }
    $stmt_school->execute([$userId]);
    $school_data = $stmt_school->fetch(PDO::FETCH_ASSOC);
    $schoolId = $school_data['school_id'] ?? null;

    if ($schoolId) {
        // FIX #1: Changed 1 and 0 to TRUE and FALSE for PostgreSQL compatibility.
        $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND type = 'exam_timetable' AND is_read = FALSE");
        $stmt_mark_read->execute([$userId]);

        $stmt_fetch = $conn->prepare("SELECT title, description, file_path, original_filename, created_at FROM exam_timetables WHERE school_id = ? ORDER BY created_at DESC");
        $stmt_fetch->execute([$schoolId]);
        $timetables = $stmt_fetch->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("DB Error in view_exam_timetable.php: " . $e->getMessage());
    die("A database error occurred. Please check the server logs for details.");
}

$pageTitle = 'Exam Timetables';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Exam Timetables</h1>

                    <?php if (empty($timetables)): ?>
                        <div class="card shadow mb-4">
                            <div class="card-body text-center">No exam timetables have been published yet.</div>
                        </div>
                    <?php else: foreach ($timetables as $tt): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($tt['title']); ?></h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($tt['description'])): ?><p><?php echo nl2br(htmlspecialchars($tt['description'])); ?></p><?php endif; ?>
                                    <p class="small text-muted">Published on: <?php echo date('d F, Y', strtotime($tt['created_at'])); ?></p>
                                    
                                    <a href="<?php echo htmlspecialchars(BASE_WEB_PATH . ltrim($tt['file_path'], '/')); ?>" class="btn btn-primary" download="<?php echo htmlspecialchars($tt['original_filename']); ?>">
                                        <i class="fas fa-download fa-sm"></i> Download Timetable
                                    </a>
                                </div>
                            </div>
                    <?php endforeach;
                    endif; ?>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>