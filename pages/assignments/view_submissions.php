<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
*/
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/email_functions.php";
include_once "../../includes/ajax_helpers.php";

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

// --- Authorization & Initialization ---
$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');

if (!$role || $role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$assignment_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($assignment_id === 0) {
    header("Location: assignment_history.php");
    exit;
}

try {
    // Mark notifications for this assignment as read
    $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = true WHERE user_id = ? AND type = 'assignment_submission' AND link LIKE ?");
    $stmt_mark_read->execute([$userId, '%view_submissions.php?id=' . $assignment_id]);

    // Handle submission status updates (Accept/Reject)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'], $_POST['action'])) {
        $submission_id = $_POST['submission_id'];
        $action = $_POST['action'];

        if ($action === 'accept') {
            $stmt = $conn->prepare('UPDATE "assignment_submissions" SET "status" = \'Accepted\', "evaluated_at" = CURRENT_TIMESTAMP, "rejection_reason" = NULL WHERE "id" = ?');
            $stmt->execute([$submission_id]);
        } elseif ($action === 'reject') {
            $rejection_reason = filter_input(INPUT_POST, 'rejection_reason', FILTER_SANITIZE_STRING);
            $stmt = $conn->prepare('UPDATE "assignment_submissions" SET "status" = \'Rejected\', "evaluated_at" = CURRENT_TIMESTAMP, "rejection_reason" = ? WHERE "id" = ?');
            $stmt->execute([$rejection_reason, $submission_id]);
        }
    }

    // Fetch assignment details
    $stmt_assignment = $conn->prepare("SELECT title, std FROM assignments WHERE id = ? AND teacher_id = ?");
    $stmt_assignment->execute([$assignment_id, $userId]);
    $assignment = $stmt_assignment->fetch(PDO::FETCH_ASSOC);
    if (!$assignment) {
        // Teacher is trying to access an assignment that isn't theirs
        header("Location: assignment_history.php");
        exit;
    }

    // Fetch submissions for this assignment
    $stmt_submissions = $conn->prepare("
        SELECT s.id, st.student_name, st.gr_no, s.file_path, s.submitted_at, s.status, s.evaluated_at
        FROM assignment_submissions s
        JOIN student st ON s.student_id = st.id
        WHERE s.assignment_id = ?
        ORDER BY st.student_name ASC
    ");
    $stmt_submissions->execute([$assignment_id]);
    $submissions = $stmt_submissions->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in view_submissions.php: " . $e->getMessage());
    // Optionally set an error message to display
}

/*
|--------------------------------------------------------------------------
| FRONTEND VIEW (HTML)
|--------------------------------------------------------------------------
*/
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>View Submissions</title>
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php if (!$is_ajax_request) {
            include '../../includes/sidebar.php';
        } ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php if (!$is_ajax_request) {
                    include '../../includes/header.php';
                } ?>
                <div id="main-content">
                    <div class="container-fluid">
                        <h1 class="h3 mb-2 text-gray-800">Submissions for "<?php echo htmlspecialchars($assignment['title']); ?>"</h1>
                        <p class="mb-4">Standard: <?php echo htmlspecialchars($assignment['std']); ?></p>

                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="submissionsTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Student Name</th>
                                                <th>GR No.</th>
                                                <th>Submitted At</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($submissions as $sub): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($sub['student_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($sub['gr_no']); ?></td>
                                                    <td><?php echo date('M d, Y h:i A', strtotime($sub['submitted_at'])); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php
                                                                                    switch ($sub['status']) {
                                                                                        case 'Accepted':
                                                                                            echo 'success';
                                                                                            break;
                                                                                        case 'Rejected':
                                                                                            echo 'danger';
                                                                                            break;
                                                                                        default:
                                                                                            echo 'warning';
                                                                                    }
                                                                                    ?>"><?php echo htmlspecialchars($sub['status']); ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="../../<?php echo htmlspecialchars($sub['file_path']); ?>" class="btn btn-info btn-sm" download><i class="fas fa-download"></i></a>
                                                        <?php if ($sub['status'] !== 'Accepted'): ?>
                                                            <form action="" method="POST" class="d-inline">
                                                                <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                                                <button type="submit" name="action" value="accept" class="btn btn-success btn-sm"><i class="fas fa-check"></i></button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($sub['status'] !== 'Rejected'): ?>
                                                            <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#rejectModal" data-submission-id="<?php echo $sub['id']; ?>"><i class="fas fa-times"></i></button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form action="" method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Reject Submission</h5>
                                        <button class="close" type="button" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Please provide a reason for rejection. This will be visible to the student.</p>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="submission_id" id="modalSubmissionId">
                                        <div class="form-group">
                                            <label for="rejection_reason">Rejection Reason</label>
                                            <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="3" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                                        <button class="btn btn-danger" type="submit">Confirm Rejection</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!$is_ajax_request) {
                            include '../../includes/footer.php';
                        } ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/js/view_submission.js"></script>
</body>

</html>