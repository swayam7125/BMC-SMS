<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

if (!$role || $role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

try {
    // --- CORRECTED: Using PDO to fetch assignment history ---
    // Note: PostgreSQL uses || for string concatenation
    $stmt = $conn->prepare('
        SELECT
            a.id, a.title, a.standard, a.subject, a.created_at, a.due_date,
            (SELECT COUNT(*) FROM "assignment_submissions" s WHERE s.assignment_id = a.id) as submission_count,
            (SELECT COUNT(*) FROM "student" st WHERE st.school_id = a.school_id AND st.std = a.standard) as total_students,
            (SELECT COUNT(*) FROM "notifications" n WHERE n.user_id = a.teacher_id AND n.is_read = false AND n.type = \'assignment_submission\' AND n.link LIKE \'%view_submissions.php?id=\' || a.id) as new_submission_count
        FROM "assignments" a
        WHERE a.teacher_id = ?
        ORDER BY a.created_at DESC
    ');
    $stmt->execute([$userId]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark all 'assignment_submission' notifications as read
    if ($userId) {
        $stmt_mark_all_read = $conn->prepare('UPDATE "notifications" SET "is_read" = true WHERE "user_id" = ? AND "type" = \'assignment_submission\' AND "is_read" = false');
        $stmt_mark_all_read->execute([$userId]);
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = 'Teacher - Assignment History';
?>
<!DOCTYPE html>
<html lang="en">
<!-- The rest of the HTML remains the same -->
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-2 text-gray-800">Sent Assignment History</h1>
                    <p class="mb-4">A record of all assignments you have sent. You can view submission status and details for each.</p>
                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Assignment sent successfully!</div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Assignment Log</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="assignmentHistoryTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Assigned To</th>
                                            <th>Subject</th>
                                            <th>Sent Date</th>
                                            <th>Due Date</th>
                                            <th>Submissions</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($assignments)): ?>
                                        <?php foreach ($assignments as $assignment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                            <td>Standard <?php echo htmlspecialchars($assignment['standard']); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['subject']); ?></td>
                                            <td><?php echo date("d-m-Y", strtotime($assignment['created_at'])); ?></td>
                                            <td><?php echo date("d-m-Y", strtotime($assignment['due_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['submission_count']); ?> / <?php echo htmlspecialchars($assignment['total_students']); ?></td>
                                            <td>
                                                <a href="view_submissions.php?id=<?php echo $assignment['id']; ?>" class="btn btn-primary btn-sm" title="View Submissions">
                                                    <i class="fas fa-eye"></i> View
                                                    <?php if ($assignment['new_submission_count'] > 0): ?>
                                                        <span class="badge badge-danger ml-2"><?php echo $assignment['new_submission_count']; ?></span>
                                                    <?php endif; ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr><td colspan="7" class="text-center">You have not sent any assignments yet.</td></tr>
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
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php"?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>$(document).ready(function() { $('#assignmentHistoryTable').DataTable(); });</script>
</body>
</html>
<?php $conn = null; ?>
