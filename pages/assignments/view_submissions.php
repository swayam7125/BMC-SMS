<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
*/
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/email_functions.php";

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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submission_id = $_POST['submission_id'];
        $action = $_POST['action'];

        if ($action === 'accept') {
            $stmt = $conn->prepare('UPDATE "assignment_submissions" SET "status" = \'Accepted\', "evaluated_at" = CURRENT_TIMESTAMP, "rejection_reason" = NULL WHERE "id" = ?');
            $stmt->execute([$submission_id]);
        } elseif ($action === 'reject') {
            $new_rejection_reason = $_POST['rejection_reason'];

            $stmt_get_reason = $conn->prepare('SELECT "rejection_reason" FROM "assignment_submissions" WHERE "id" = ?');
            $stmt_get_reason->execute([$submission_id]);
            $old_reasons = $stmt_get_reason->fetchColumn() ?? '';

            $timestamp = date("d-m-Y h:i A");
            $formatted_new_reason = "<strong>Feedback on " . $timestamp . ":</strong><br>" . nl2br(htmlspecialchars($new_rejection_reason));
            $updated_reasons = $formatted_new_reason . (!empty($old_reasons) ? "<hr style='margin: 10px 0; border-top: 1px solid #e3e6f0;'>" . $old_reasons : "");

            $stmt = $conn->prepare('UPDATE "assignment_submissions" SET "status" = \'Rejected\', "rejection_reason" = ?, "evaluated_at" = CURRENT_TIMESTAMP, "rejection_count" = "rejection_count" + 1 WHERE "id" = ?');
            $stmt->execute([$updated_reasons, $submission_id]);
        }

        // Email notification logic
        $query = 'SELECT s.student_name, s.email AS student_email, a.title AS assignment_title, t.teacher_name FROM "assignment_submissions" sub JOIN "student" s ON sub.student_id = s.id JOIN "assignments" a ON sub.assignment_id = a.id JOIN "teacher" t ON a.teacher_id = t.id WHERE sub.id = ?';
        $stmt_info = $conn->prepare($query);
        $stmt_info->execute([$submission_id]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if ($info) {
            $status_text = ($action === 'accept') ? 'Accepted' : 'Rejected';
            $email_subject = "Your Assignment Submission has been " . $status_text;
            $email_body = "<p>Dear " . htmlspecialchars($info['student_name']) . ",</p><p>Your submission for the assignment '<strong>" . htmlspecialchars($info['assignment_title']) . "</strong>' has been evaluated by your teacher, " . htmlspecialchars($info['teacher_name']) . ".</p><p><strong>Status: " . $status_text . "</strong></p>";
            if ($action === 'reject' && !empty($_POST['rejection_reason'])) {
                $email_body .= "<p><strong>Teacher's Feedback:</strong><br>" . nl2br(htmlspecialchars($_POST['rejection_reason'])) . "</p><p>Please review the feedback and re-upload your work if necessary.</p>";
            }
            send_email($info['student_email'], $email_subject, $email_body);
        }

        header("Location: view_submissions.php?id=" . $assignment_id);
        exit;
    }

    $stmt_assignment = $conn->prepare('SELECT "title", "standard", "subject" FROM "assignments" WHERE "id" = ? AND "teacher_id" = ?');
    $stmt_assignment->execute([$assignment_id, $userId]);
    $assignment = $stmt_assignment->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        die("Assignment not found or you do not have permission to view it.");
    }

    $stmt_submissions = $conn->prepare('SELECT ss.id, ss.status, ss.submitted_at, ss.rejection_reason, ss.file_path, ss.original_filename, s.student_name, s.rollno, ss.rejection_count FROM "assignment_submissions" ss JOIN "student" s ON ss.student_id = s.id WHERE ss.assignment_id = ? ORDER BY s.rollno ASC');
    $stmt_submissions->execute([$assignment_id]);
    $submissions = $stmt_submissions->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$pageTitle = 'View Submissions';
?>

<?php
/*
|--------------------------------------------------------------------------
| RESPONSIVE & PROFESSIONAL FRONTEND (VIEW)
|--------------------------------------------------------------------------
*/
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">

    <style>
        /* Hide the card view on large screens */
        .mobile-card-view {
            display: none;
        }

        /* Responsive Breakpoint for Tablets and below */
        @media (max-width: 991.98px) {
            .desktop-table {
                display: none;
            }

            .mobile-card-view {
                display: block;
            }

            .card-submission .card-body {
                padding: 1rem;
            }

            .card-submission .info-item {
                display: flex;
                justify-content: space-between;
                padding-bottom: 0.5rem;
                margin-bottom: 0.5rem;
                border-bottom: 1px solid #e3e6f0;
            }

            .card-submission .info-item:last-of-type {
                border-bottom: none;
                padding-bottom: 0;
                margin-bottom: 0;
            }

            .card-submission .actions-row {
                display: flex;
                gap: 0.5rem;
                /* Creates space between buttons */
            }

            .card-submission .actions-row>* {
                flex-grow: 1;
                /* Makes buttons share space equally */
            }
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h1 class="h3 mb-0 text-gray-800">Submissions for "<?php echo htmlspecialchars($assignment['title']); ?>"</h1>
                            <p class="mb-0 text-muted">Standard: <?php echo htmlspecialchars($assignment['standard']); ?> | Subject: <?php echo htmlspecialchars($assignment['subject']); ?></p>
                        </div>
                        <a href="assignment_history.php" class="btn btn-sm btn-secondary shadow-sm mt-2 mt-sm-0">
                            <i class="fas fa-arrow-left fa-sm"></i> Back to History
                        </a>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Student Submissions</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive desktop-table">
                                <table class="table table-striped table-hover" id="submissionsTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Roll No</th>
                                            <th>Student Name</th>
                                            <th>Submitted File</th>
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($submissions as $sub): ?>
                                            <tr class="align-middle">
                                                <td><?php echo htmlspecialchars($sub['rollno']); ?></td>
                                                <td><?php echo htmlspecialchars($sub['student_name']); ?></td>
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($sub['file_path']); ?>" download="<?php echo htmlspecialchars($sub['original_filename']); ?>">
                                                        <i class="fas fa-download"></i>
                                                        <?php echo htmlspecialchars($sub['original_filename']); ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status = $sub['status'];
                                                    $badge_class = 'badge-secondary';
                                                    if ($status == 'Accepted') $badge_class = 'badge-success';
                                                    if ($status == 'Rejected') $badge_class = 'badge-danger';
                                                    if ($status == 'Submitted' || $status == 'Re-submitted') $badge_class = 'badge-info';
                                                    echo "<span class='badge {$badge_class}'>" . htmlspecialchars($status) . "</span>";
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($status === 'Submitted' || $status === 'Re-submitted'): ?>
                                                        <form action="view_submissions.php?id=<?php echo $assignment_id; ?>" method="POST" class="d-inline">
                                                            <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                                            <input type="hidden" name="action" value="accept">
                                                            <button type="submit" class="btn btn-success btn-sm">Accept</button>
                                                        </form>
                                                        <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#rejectModal" data-submission-id="<?php echo $sub['id']; ?>">
                                                            Reject
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">Evaluated</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mobile-card-view">
                                <?php if (!empty($submissions)): ?>
                                    <?php foreach ($submissions as $sub): ?>
                                        <div class="card card-submission shadow-sm mb-3">
                                            <div class="card-body">
                                                <h5 class="card-title text-primary font-weight-bold mb-3"><?php echo htmlspecialchars($sub['student_name']); ?></h5>
                                                <div class="info-item">
                                                    <span class="font-weight-bold">Roll No:</span>
                                                    <span><?php echo htmlspecialchars($sub['rollno']); ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="font-weight-bold">Status:</span>
                                                    <span>
                                                        <?php
                                                        $status = $sub['status'];
                                                        $badge_class = 'badge-secondary';
                                                        if ($status == 'Accepted') $badge_class = 'badge-success';
                                                        if ($status == 'Rejected') $badge_class = 'badge-danger';
                                                        if ($status == 'Submitted' || $status == 'Re-submitted') $badge_class = 'badge-info';
                                                        echo "<span class='badge {$badge_class}'>" . htmlspecialchars($status) . "</span>";
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="font-weight-bold">File:</span>
                                                    <a href="<?php echo htmlspecialchars($sub['file_path']); ?>" download="<?php echo htmlspecialchars($sub['original_filename']); ?>">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                </div>
                                                <?php if ($status === 'Submitted' || $status === 'Re-submitted'): ?>
                                                    <div class="actions-row mt-3">
                                                        <form action="view_submissions.php?id=<?php echo $assignment_id; ?>" method="POST" class="d-inline w-100">
                                                            <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                                            <input type="hidden" name="action" value="accept">
                                                            <button type="submit" class="btn btn-success btn-block">Accept</button>
                                                        </form>
                                                        <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#rejectModal" data-submission-id="<?php echo $sub['id']; ?>">
                                                            Reject
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-gray-400"></i>
                                        <p class="mt-3">No submissions have been made for this assignment yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="view_submissions.php?id=<?php echo $assignment_id; ?>" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Submission</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                    </div>
                    <div class="modal-body">
                        <p>Please provide a reason for rejecting this submission. The student will see this feedback.</p>
                        <input type="hidden" name="submission_id" id="modalSubmissionId">
                        <input type="hidden" name="action" value="reject">
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
    <?php include_once "../../includes/logout_modal.php" ?>


    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#submissionsTable').DataTable();

            $('#rejectModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var submissionId = button.data('submission-id');
                var modal = $(this);
                modal.find('#modalSubmissionId').val(submissionId);
            });
        });
    </script>
</body>

</html>
<?php $conn = null; ?>