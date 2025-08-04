<?php
// MODIFICATION: Removed session_start()
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/email_functions.php"; // Include email functions

$role = decrypt_id($_COOKIE['encrypted_user_role'] ?? '');
$userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? ''); // This is the teacher's ID

if (!$role || $role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$assignment_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($assignment_id === 0) {
    header("Location: assignment_history.php");
    exit;
}

// Handle POST requests to accept or reject submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = $_POST['submission_id'];
    $action = $_POST['action'];

    if ($action === 'accept') {
        $stmt = $conn->prepare("UPDATE assignment_submissions SET status = 'Accepted', evaluated_at = NOW(), rejection_reason = NULL WHERE id = ?");
        $stmt->bind_param("i", $submission_id);
    } elseif ($action === 'reject') {
        $new_rejection_reason = $_POST['rejection_reason'];

        // --- MODIFICATION: Get old reasons to create a history ---
        // 1. Get the current reason history
        $stmt_get_reason = $conn->prepare("SELECT rejection_reason FROM assignment_submissions WHERE id = ?");
        $stmt_get_reason->bind_param("i", $submission_id);
        $stmt_get_reason->execute();
        $old_reasons = $stmt_get_reason->get_result()->fetch_assoc()['rejection_reason'] ?? '';
        $stmt_get_reason->close();

        // 2. Build the new history string with a timestamp
        $timestamp = date("d-m-Y h:i A");
        // Sanitize new input and preserve line breaks
        $formatted_new_reason = "<strong>Feedback on " . $timestamp . ":</strong><br>" . nl2br(htmlspecialchars($new_rejection_reason));

        $updated_reasons = $formatted_new_reason;
        if (!empty($old_reasons)) {
            // Prepend new reason to the top of the history
            $updated_reasons .= "<hr style='margin: 10px 0; border-top: 1px solid #e3e6f0;'>" . $old_reasons;
        }

        // 3. Update the submission with the new reason history
        $stmt = $conn->prepare("UPDATE assignment_submissions SET status = 'Rejected', rejection_reason = ?, evaluated_at = NOW(), rejection_count = rejection_count + 1 WHERE id = ?");
        $stmt->bind_param("si", $updated_reasons, $submission_id);
    }

    if (isset($stmt) && $stmt->execute()) {
        // Email notification logic remains the same...
        // ... (rest of the email code)
    }
    // The rest of the POST handling logic remains the same
    if (isset($_POST['rejection_reason'])) {
        $rejection_reason = $_POST['rejection_reason'];
         // --- START: Email Notification to Student ---
        // 1. Get all necessary info for the email
        $query = "SELECT 
                    s.student_name, s.email AS student_email,
                    a.title AS assignment_title,
                    t.teacher_name
                  FROM assignment_submissions sub
                  JOIN student s ON sub.student_id = s.id
                  JOIN assignments a ON sub.assignment_id = a.id
                  JOIN teacher t ON a.teacher_id = t.id
                  WHERE sub.id = ?";
        $stmt_info = $conn->prepare($query);
        $stmt_info->bind_param("i", $submission_id);
        $stmt_info->execute();
        $info = $stmt_info->get_result()->fetch_assoc();

        if ($info) {
            $status_text = ($action === 'accept') ? 'Accepted' : 'Rejected';
            $email_subject = "Your Assignment Submission has been " . $status_text;

            $email_body = "<p>Dear " . htmlspecialchars($info['student_name']) . ",</p>
                         <p>Your submission for the assignment '<strong>" . htmlspecialchars($info['assignment_title']) . "</strong>' has been evaluated by your teacher, " . htmlspecialchars($info['teacher_name']) . ".</p>
                         <p><strong>Status: " . $status_text . "</strong></p>";

            if ($action === 'reject' && !empty($rejection_reason)) {
                $email_body .= "<p><strong>Teacher's Feedback:</strong><br>" . nl2br(htmlspecialchars($rejection_reason)) . "</p>
                              <p>Please review the feedback and re-upload your work if necessary.</p>";
            } else if ($action === 'accept') {
                $email_body .= "<p>Great work!</p>";
            }

            send_email($info['student_email'], $email_subject, $email_body);
        }
        $stmt_info->close();
        // --- END: Email Notification to Student ---
    }
    if (isset($stmt)) {
        $stmt->close();
    }
    header("Location: view_submissions.php?id=" . $assignment_id); // Refresh page
    exit;
}

// --- FETCH DATA FOR DISPLAY ---
// Fetch assignment details
$stmt_assignment = $conn->prepare("SELECT title, standard, subject FROM assignments WHERE id = ? AND teacher_id = ?");
$stmt_assignment->bind_param("ii", $assignment_id, $userId);
$stmt_assignment->execute();
$assignment = $stmt_assignment->get_result()->fetch_assoc();
$stmt_assignment->close();

if (!$assignment) {
    die("Assignment not found or you do not have permission to view it.");
}

// Fetch submissions for this assignment
$stmt_submissions = $conn->prepare("
    SELECT ss.id, ss.status, ss.submitted_at, ss.rejection_reason, ss.file_path, ss.original_filename, s.student_name, s.rollno, ss.rejection_count
    FROM assignment_submissions ss
    JOIN student s ON ss.student_id = s.id
    WHERE ss.assignment_id = ?
    ORDER BY s.rollno ASC
");
$stmt_submissions->bind_param("i", $assignment_id);
$stmt_submissions->execute();
$submissions = $stmt_submissions->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_submissions->close();

$pageTitle = 'View Submissions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <!-- <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css"> -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Submissions for
                        "<?php echo htmlspecialchars($assignment['title']); ?>"</h1>
                    <p class="mb-4">Standard: <?php echo htmlspecialchars($assignment['standard']); ?> | Subject:
                        <?php echo htmlspecialchars($assignment['subject']); ?>
                    </p>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Student Submissions</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="submissionsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Roll No</th>
                                            <th>Student Name</th>
                                            <th>Submitted File</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($submissions as $sub): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sub['rollno']); ?></td>
                                            <td><?php echo htmlspecialchars($sub['student_name']); ?></td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($sub['file_path']); ?>"
                                                    download="<?php echo htmlspecialchars($sub['original_filename']); ?>">
                                                    <i class="fas fa-download"></i>
                                                    <?php echo htmlspecialchars($sub['original_filename']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php
                                                    $status = $sub['status'];
                                                    $status_text = htmlspecialchars($status);
                                                    $badge_class = 'badge-secondary';
                                                    if ($status == 'Accepted') $badge_class = 'badge-success';
                                                    if ($status == 'Rejected') $badge_class = 'badge-danger';
                                                    if ($status == 'Submitted' || $status == 'Re-submitted') $badge_class = 'badge-info';
                                                    
                                                    // MODIFICATION: Display the rejection count
                                                    if ($status == 'Rejected' && $sub['rejection_count'] > 0) {
                                                        $times = $sub['rejection_count'] == 1 ? 'time' : 'times';
                                                        $status_text .= " (" . $sub['rejection_count'] . " $times)";
                                                    }
                                                    
                                                    echo "<span class='badge {$badge_class}'>{$status_text}</span>";
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($status === 'Submitted' || $status === 'Re-submitted'): ?>
                                                <form action="view_submissions.php?id=<?php echo $assignment_id; ?>"
                                                    method="POST" class="d-inline">
                                                    <input type="hidden" name="submission_id"
                                                        value="<?php echo $sub['id']; ?>">
                                                    <input type="hidden" name="action" value="accept">
                                                    <button type="submit" class="btn btn-success btn-sm">Accept</button>
                                                </form>
                                                <button type="button" class="btn btn-danger btn-sm" data-toggle="modal"
                                                    data-target="#rejectModal"
                                                    data-submission-id="<?php echo $sub['id']; ?>">
                                                    Reject
                                                </button>
                                                <?php else: ?>
                                                <span>Evaluated</span>
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
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">×</span></button>
                    </div>
                    <div class="modal-body">
                        <p>Please provide a reason for rejecting this submission. The student will see this reason.</p>
                        <input type="hidden" name="submission_id" id="modalSubmissionId">
                        <input type="hidden" name="action" value="reject">
                        <div class="form-group">
                            <label for="rejection_reason">Rejection Reason</label>
                            <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="3"
                                required></textarea>
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
        <?php include_once "../../includes/logout_modal.php"?>


    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
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