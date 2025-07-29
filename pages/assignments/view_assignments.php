<?php
// Standard setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/email_functions.php"; // Include email functions

$role = null;
$userId = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security Check: Ensure user is logged in and is a student
if (!$role || $role !== 'student') {
    header("Location: ../../login.php");
    exit;
}

// --- HANDLE ASSIGNMENT SUBMISSION (POST REQUEST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['assignment_file']) && isset($_POST['assignment_id'])) {
    $assignment_id = $_POST['assignment_id'];
    $student_id = $userId;

    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == 0) {
        $originalFilename = basename($_FILES["assignment_file"]["name"]);
        $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/assignments/submit/';
        $uploadDirWeb = '/BMC-SMS/pages/assignments/submit/';
        if (!is_dir($uploadDirServer)) {
            mkdir($uploadDirServer, 0777, true);
        }

        $storageFilename = uniqid('sub_', true) . '_' . $originalFilename;
        $serverFilePath = $uploadDirServer . $storageFilename;

        if (move_uploaded_file($_FILES["assignment_file"]["tmp_name"], $serverFilePath)) {
            $filePathForDB = $uploadDirWeb . $storageFilename;

            // Check if a submission already exists to decide whether to INSERT or UPDATE
            $check_stmt = $conn->prepare("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
            $check_stmt->bind_param("ii", $assignment_id, $student_id);
            $check_stmt->execute();
            $existing_submission = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ($existing_submission) {
                // Update existing submission for re-uploads
                $update_stmt = $conn->prepare("UPDATE assignment_submissions SET file_path = ?, original_filename = ?, status = 'Re-submitted', submitted_at = NOW(), rejection_reason = NULL, evaluated_at = NULL WHERE id = ?");
                $update_stmt->bind_param("ssi", $filePathForDB, $originalFilename, $existing_submission['id']);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                // Insert new submission
                $insert_stmt = $conn->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, file_path, original_filename, status) VALUES (?, ?, ?, ?, 'Submitted')");
                $insert_stmt->bind_param("iiss", $assignment_id, $student_id, $filePathForDB, $originalFilename);
                $insert_stmt->execute();
                $insert_stmt->close();
            }

            // --- START: Email Notification to Teacher ---
            $query = "SELECT s.student_name, a.title, t.email AS teacher_email, t.teacher_name
                      FROM assignments a
                      JOIN teacher t ON a.teacher_id = t.id
                      JOIN student s ON s.id = ?
                      WHERE a.id = ?";
            $stmt_info = $conn->prepare($query);
            $stmt_info->bind_param("ii", $student_id, $assignment_id);
            $stmt_info->execute();
            $info = $stmt_info->get_result()->fetch_assoc();

            if ($info) {
                $student_name = $info['student_name'];
                $assignment_title = $info['title'];
                $teacher_email = $info['teacher_email'];
                $teacher_name = $info['teacher_name'];

                $email_subject = "Assignment Submitted: " . htmlspecialchars($assignment_title);
                $email_body = "
                    <p>Dear " . htmlspecialchars($teacher_name) . ",</p>
                    <p>A student, <strong>" . htmlspecialchars($student_name) . "</strong>, has submitted their work for the assignment titled '<strong>" . htmlspecialchars($assignment_title) . "</strong>'.</p>
                    <p>Please log in to the portal to review the submission.</p>
                ";
                send_email($teacher_email, $email_subject, $email_body);
            }
            $stmt_info->close();
            // --- END: Email Notification to Teacher ---

            header("Location: view_assignments.php?submission=success");
            exit();
        }
    }
    header("Location: view_assignments.php?submission=error");
    exit();
}

// --- FETCH DATA FOR DISPLAY ---
$student_info_stmt = $conn->prepare("SELECT school_id, std FROM student WHERE id = ?");
$student_info_stmt->bind_param("i", $userId);
$student_info_stmt->execute();
$student_info = $student_info_stmt->get_result()->fetch_assoc();
$schoolId = $student_info['school_id'] ?? 0;
$studentStd = $student_info['std'] ?? '';
$student_info_stmt->close();

$sql = "
    SELECT 
        a.id, a.title, a.subject, a.description, a.due_date,
        a.file_path, a.original_filename, t.teacher_name,
        ss.status as submission_status, ss.rejection_reason
    FROM assignments a
    JOIN teacher t ON a.teacher_id = t.id
    LEFT JOIN assignment_submissions ss ON a.id = ss.assignment_id AND ss.student_id = ?
    WHERE a.school_id = ? AND a.standard = ? ORDER BY a.due_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $userId, $schoolId, $studentStd);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>My Assignments</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
    .rejection-reason {
        background-color: #fff3cd;
        border-left: 4px solid #f6c23e;
        padding: 10px;
        margin-top: 10px;
        border-radius: 0 4px 4px 0;
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
                    <h1 class="h3 mb-4 text-gray-800">My Assignments</h1>
                    <?php if (isset($_GET['submission']) && $_GET['submission'] == 'success'): ?>
                    <div class="alert alert-success">Assignment submitted successfully!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['submission']) && $_GET['submission'] == 'error'): ?>
                    <div class="alert alert-danger">There was an error submitting your assignment. Please try again.
                    </div>
                    <?php endif; ?>

                    <div id="assignment-list">
                        <?php if (!empty($assignments)): ?>
                        <?php foreach ($assignments as $assignment):
                                $status = $assignment['submission_status'] ?? 'Pending';
                                ?>
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1 text-primary"><?php echo htmlspecialchars($assignment['title']); ?>
                                    </h5>
                                    <?php
                                            $badge_class = 'badge-warning'; // Pending
                                            if ($status == 'Accepted')
                                                $badge_class = 'badge-success';
                                            if ($status == 'Rejected')
                                                $badge_class = 'badge-danger';
                                            if ($status == 'Submitted' || $status == 'Re-submitted')
                                                $badge_class = 'badge-info';
                                            ?>
                                    <span
                                        class="badge <?php echo $badge_class; ?> p-2 align-self-start"><?php echo str_replace('-', ' ', $status); ?></span>
                                </div>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                                <small class="text-muted">Due:
                                    <?php echo date("F j, Y", strtotime($assignment['due_date'])); ?></small>

                                <?php if ($status === 'Rejected' && !empty($assignment['rejection_reason'])): ?>
                                <div class="rejection-reason mt-2">
                                    <strong>Teacher's Feedback:</strong>
                                    <?php echo htmlspecialchars($assignment['rejection_reason']); ?>
                                </div>
                                <?php endif; ?>

                                <div class="float-right mt-2">
                                    <?php if ($assignment['file_path']): ?>
                                    <a href="<?php echo htmlspecialchars($assignment['file_path']); ?>"
                                        class="btn btn-sm btn-outline-secondary" download>
                                        <i class="fas fa-download"></i> Download Attachment
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($status === 'Pending' || $status === 'Rejected'): ?>
                                    <button class="btn btn-sm btn-primary" data-toggle="modal"
                                        data-target="#uploadModal" data-assignment-id="<?php echo $assignment['id']; ?>"
                                        data-assignment-title="<?php echo htmlspecialchars($assignment['title']); ?>">
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
                        <button class="close" type="button" data-dismiss="modal"><span
                                aria-hidden="true">×</span></button>
                    </div>
                    <div class="modal-body">
                        <p>You are submitting for: <strong id="modalAssignmentTitle"></strong></p>
                        <input type="hidden" name="assignment_id" id="modalAssignmentId">
                        <div class="form-group">
                            <label for="submissionFile">Upload your file</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="submissionFile" name="assignment_file"
                                    required>
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

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <script>
    $(document).ready(function() {
        // This script runs when the upload modal is about to be shown
        $('#uploadModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var assignmentId = button.data('assignment-id'); // Extract info from data-* attributes
            var assignmentTitle = button.data('assignment-title');

            // Update the modal's content.
            var modal = $(this);
            modal.find('#modalAssignmentTitle').text(assignmentTitle);
            modal.find('#modalAssignmentId').val(assignmentId);
        });

        // This script updates the file input label to show the selected file name
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).siblings('.custom-file-label').addClass("selected").html(fileName);
        });
    });
    </script>
</body>

</html>