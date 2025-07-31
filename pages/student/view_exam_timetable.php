<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;
$schoolId = null;

if (isset($_COOKIE['encrypted_user_role'])) $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id'])) $userId = decrypt_id($_COOKIE['encrypted_user_id']);

if (!in_array($role, ['student', 'teacher']) || !$userId) {
    header("Location: ../../login.php");
    exit;
}

// Get School ID from either student or teacher table
if ($role == 'student') {
    $stmt_school = $conn->prepare("SELECT school_id FROM student WHERE id = ?");
} else { // teacher
    $stmt_school = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
}
$stmt_school->bind_param("i", $userId);
$stmt_school->execute();
$schoolId = $stmt_school->get_result()->fetch_assoc()['school_id'];
$stmt_school->close();

// Mark all 'exam_timetable' notifications as read
$stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type = 'exam_timetable' AND is_read = 0");
if ($stmt_mark_read) {
    $stmt_mark_read->bind_param("i", $userId);
    $stmt_mark_read->execute();
    $stmt_mark_read->close();
}

// Fetch all exam timetables for the school
$timetables = [];
$stmt_fetch = $conn->prepare("SELECT title, description, file_path, original_filename, created_at FROM exam_timetables WHERE school_id = ? ORDER BY created_at DESC");
$stmt_fetch->bind_param("i", $schoolId);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
$timetables = $result->fetch_all(MYSQLI_ASSOC);
$stmt_fetch->close();

$pageTitle = 'Exam Timetables';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
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
                            <div class="card-body text-center">
                                No exam timetables have been published yet.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($timetables as $tt): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($tt['title']); ?></h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($tt['description'])): ?>
                                        <p><?php echo nl2br(htmlspecialchars($tt['description'])); ?></p>
                                    <?php endif; ?>
                                    <p class="small text-muted">Published on: <?php echo date('d F, Y', strtotime($tt['created_at'])); ?></p>
                                    <a href="<?php echo htmlspecialchars($tt['file_path']); ?>" class="btn btn-primary" download="<?php echo htmlspecialchars($tt['original_filename']); ?>">
                                        <i class="fas fa-download fa-sm"></i> Download Timetable
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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