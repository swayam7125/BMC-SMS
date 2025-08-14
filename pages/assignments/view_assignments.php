<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/email_functions.php";

$role = null;
$userId = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$role || $role !== 'student') {
    header("Location: ../../login.php");
    exit;
}

try {
    if ($userId) {
        $stmt_mark_read = $conn->prepare('UPDATE "notifications" SET "is_read" = true WHERE "user_id" = ? AND "type" = \'new_assignment\' AND "is_read" = false');
        $stmt_mark_read->execute([$userId]);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['assignment_file']) && isset($_POST['assignment_id'])) {
        $assignment_id = $_POST['assignment_id'];
        $student_id = $userId;

        if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == 0) {
            $originalFilename = basename($_FILES["assignment_file"]["name"]);
            $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/assignments/submit/';
            $uploadDirWeb = '/BMC-SMS/pages/assignments/submit/';
            if (!is_dir($uploadDirServer)) mkdir($uploadDirServer, 0777, true);

            $storageFilename = uniqid('sub_', true) . '_' . $originalFilename;
            $serverFilePath = $uploadDirServer . $storageFilename;

            if (move_uploaded_file($_FILES["assignment_file"]["tmp_name"], $serverFilePath)) {
                $filePathForDB = $uploadDirWeb . $storageFilename;

                $check_stmt = $conn->prepare('SELECT "id" FROM "assignment_submissions" WHERE "assignment_id" = ? AND "student_id" = ?');
                $check_stmt->execute([$assignment_id, $student_id]);
                $existing_submission = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_submission) {
                    $update_stmt = $conn->prepare('UPDATE "assignment_submissions" SET "file_path" = ?, "original_filename" = ?, "status" = \'Re-submitted\', "submitted_at" = CURRENT_TIMESTAMP, "evaluated_at" = NULL WHERE "id" = ?');
                    $update_stmt->execute([$filePathForDB, $originalFilename, $existing_submission['id']]);
                } else {
                    $insert_stmt = $conn->prepare('INSERT INTO "assignment_submissions" (assignment_id, student_id, file_path, original_filename, status) VALUES (?, ?, ?, ?, \'Submitted\')');
                    $insert_stmt->execute([$assignment_id, $student_id, $filePathForDB, $originalFilename]);
                }

                $stmt_teacher_id = $conn->prepare('SELECT "teacher_id" FROM "assignments" WHERE "id" = ?');
                $stmt_teacher_id->execute([$assignment_id]);
                $teacher_info = $stmt_teacher_id->fetch(PDO::FETCH_ASSOC);

                if ($teacher_info) {
                    $teacher_id = $teacher_info['teacher_id'];
                    $stmt_student_name = $conn->prepare('SELECT "student_name" FROM "student" WHERE "id" = ?');
                    $stmt_student_name->execute([$student_id]);
                    $student_info = $stmt_student_name->fetch(PDO::FETCH_ASSOC);
                    $student_name = $student_info['student_name'] ?? 'A student';

                    $notification_message = htmlspecialchars($student_name) . " has submitted an assignment.";
                    $notification_link = "pages/assignments/view_submissions.php?id=" . $assignment_id;
                    $notification_type = "assignment_submission";

                    $insert_notif_stmt = $conn->prepare('INSERT INTO "notifications" (user_id, message, link, type) VALUES (?, ?, ?, ?)');
                    $insert_notif_stmt->execute([$teacher_id, $notification_message, $notification_link, $notification_type]);
                }
                header("Location: view_assignments.php?submission=success");
                exit();
            }
        }
        header("Location: view_assignments.php?submission=error");
        exit();
    }

    $student_info_stmt = $conn->prepare('SELECT "school_id", "std" FROM "student" WHERE "id" = ?');
    $student_info_stmt->execute([$userId]);
    $student_info = $student_info_stmt->fetch(PDO::FETCH_ASSOC);
    $schoolId = $student_info['school_id'] ?? 0;
    $studentStd = $student_info['std'] ?? '';

    // --- NEW FILTER LOGIC ---
    $filter = $_GET['filter'] ?? 'all';
    $filter_clause = '';

    switch ($filter) {
        case 'submitted':
            $filter_clause = ' AND ss.status IN (\'Submitted\', \'Re-submitted\')';
            break;
        case 'accepted':
            $filter_clause = ' AND ss.status = \'Accepted\'';
            break;
        case 'rejected':
            $filter_clause = ' AND ss.status = \'Rejected\'';
            break;
        case 'pending':
            $filter_clause = ' AND ss.status IS NULL';
            break;
        default:
            $filter = 'all';
            break;
    }
    
    $sql = 'SELECT a.id, a.title, a.subject, a.description, a.due_date, a.file_path, a.original_filename, t.teacher_name, ss.status as submission_status, ss.rejection_reason FROM "assignments" a JOIN "teacher" t ON a.teacher_id = t.id LEFT JOIN "assignment_submissions" ss ON a.id = ss.assignment_id AND ss.student_id = ? WHERE a.school_id = ? AND a.standard = ?' . $filter_clause . ' ORDER BY a.due_date DESC';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId, $schoolId, $studentStd]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>My Assignments</title>

    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/view_assignments.css">
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
                    <h1 class="h3 mb-4 text-gray-800">My Assignments</h1>
                    <div class="d-flex justify-content-start mb-4">
                        <a href="view_assignments.php?filter=all" class="btn btn-outline-primary mr-2 <?php echo ($filter == 'all') ? 'active' : ''; ?>">All</a>
                        <a href="view_assignments.php?filter=pending" class="btn btn-outline-warning mr-2 <?php echo ($filter == 'pending') ? 'active' : ''; ?>">Pending</a>
                        <a href="view_assignments.php?filter=submitted" class="btn btn-outline-info mr-2 <?php echo ($filter == 'submitted') ? 'active' : ''; ?>">Submitted</a>
                        <a href="view_assignments.php?filter=accepted" class="btn btn-outline-success mr-2 <?php echo ($filter == 'accepted') ? 'active' : ''; ?>">Accepted</a>
                        <a href="view_assignments.php?filter=rejected" class="btn btn-outline-danger <?php echo ($filter == 'rejected') ? 'active' : ''; ?>">Rejected</a>
                    </div>
                    <?php if (isset($_GET['submission']) && $_GET['submission'] == 'success'): ?>
                        <div class="alert alert-success">Assignment submitted successfully!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['submission']) && $_GET['submission'] == 'error'): ?>
                        <div class="alert alert-danger">Error submitting assignment. Please try again.</div>
                    <?php endif; ?>

                    <div id="assignment-list">
                        <?php if (!empty($assignments)): ?>
                            <?php foreach ($assignments as $assignment):
                                $status = $assignment['submission_status'] ?? 'Pending';
                            ?>
                                <div class="card shadow mb-4">
                                    <div class="card-body">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1 text-primary"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                            <?php
                                            $status_text = str_replace('-', ' ', $status);
                                            $badge_class = 'badge-warning'; // Pending
                                            if ($status == 'Accepted') $badge_class = 'badge-success';
                                            if ($status == 'Rejected') $badge_class = 'badge-danger';
                                            if ($status == 'Submitted' || $status == 'Re-submitted') $badge_class = 'badge-info';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?> p-2 align-self-start"><?php echo $status_text; ?></span>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                                        <small class="text-muted">Due: <?php echo date("F j, Y", strtotime($assignment['due_date'])); ?></small>

                                        <?php if ($status === 'Rejected' && !empty($assignment['rejection_reason'])): ?>
                                            <div class="rejection-reason mt-3">
                                                <h6 class="font-weight-bold text-warning">Feedback History:</h6>
                                                <?php echo $assignment['rejection_reason']; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="float-right mt-2">
                                            <?php if ($assignment['file_path']): ?>
                                                <a href="<?php echo htmlspecialchars($assignment['file_path']); ?>" class="btn btn-sm btn-outline-secondary" download>
                                                    <i class="fas fa-download"></i> Download Attachment
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($status === 'Pending' || $status === 'Rejected'): ?>
                                                <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#uploadModal" data-assignment-id="<?php echo $assignment['id']; ?>" data-assignment-title="<?php echo htmlspecialchars($assignment['title']); ?>">
                                                    <?php echo ($status === 'Rejected') ? '<i class="fas fa-upload"></i> Re-upload' : 'Submit'; ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center">
                                <p>No assignments found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="view_assignments.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Submit Assignment</h5>
                        <button class="close" type="button" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                    </div>
                    <div class="modal-body">
                        <p>You are submitting for: <strong id="modalAssignmentTitle"></strong></p>
                        <input type="hidden" name="assignment_id" id="modalAssignmentId">
                        <div class="form-group">
                            <label for="submissionFile">Upload your file</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="submissionFile" name="assignment_file" required>
                                <label class="custom-file-label" for="submissionFile">Choose file...</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">Submit Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#uploadModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var assignmentId = button.data('assignment-id');
                var assignmentTitle = button.data('assignment-title');
                var modal = $(this);
                modal.find('#modalAssignmentTitle').text(assignmentTitle);
                modal.find('#modalAssignmentId').val(assignmentId);
            });
            $('.custom-file-input').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).siblings('.custom-file-label').addClass("selected").html(fileName);
            });
        });
    </script>
</body>

</html>
<?php $conn = null; ?>