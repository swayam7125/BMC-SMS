<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/email_functions.php";
include_once "../../includes/ajax_helpers.php";

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// --- Authorization & Initialization ---
$role = null;
$userId = null;
$schoolId = null;
$studentStd = null;
$successMessage = '';
$errorMessage = '';

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
    // Mark 'new_assignment' notifications as read for the student
    if ($userId) {
        $stmt_mark_read = $conn->prepare('UPDATE "notifications" SET "is_read" = true WHERE "user_id" = ? AND "type" = \'new_assignment\' AND "is_read" = false');
        $stmt_mark_read->execute([$userId]);
    }

    // Handle Assignment Submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['assignment_file']) && isset($_POST['assignment_id'])) {
        $assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
        $file_path = null;

        if ($_FILES['assignment_file']['error'] == 0) {
            $upload_dir = '../../uploads/submissions/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_name = $userId . '_' . time() . '_' . basename($_FILES['assignment_file']['name']);
            $file_path = 'uploads/submissions/' . $file_name;

            if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], '../../' . $file_path)) {
                $stmt_check = $conn->prepare('SELECT "id" FROM "assignment_submissions" WHERE "assignment_id" = ? AND "student_id" = ?');
                $stmt_check->execute([$assignment_id, $userId]);

                if ($stmt_check->fetch()) {
                    // Update existing submission
                    $stmt_update = $conn->prepare('UPDATE "assignment_submissions" SET "file_path" = ?, "status" = \'Submitted\', "submitted_at" = CURRENT_TIMESTAMP, "rejection_reason" = NULL WHERE "assignment_id" = ? AND "student_id" = ?');
                    $stmt_update->execute([$file_path, $assignment_id, $userId]);
                } else {
                    // Insert new submission
                    $stmt_insert = $conn->prepare('INSERT INTO "assignment_submissions" ("assignment_id", "student_id", "file_path", "status") VALUES (?, ?, ?, \'Submitted\')');
                    $stmt_insert->execute([$assignment_id, $userId, $file_path]);
                }
                $successMessage = "Assignment submitted successfully!";
            } else {
                $errorMessage = "Sorry, there was an error uploading your file.";
            }
        } else {
            $errorMessage = "File upload error. Please try again.";
        }
    }

    // Fetch student's standard and school ID
    $stmt_student_info = $conn->prepare('SELECT "std", "school_id" FROM "student" WHERE "id" = ?');
    $stmt_student_info->execute([$userId]);
    $student_info = $stmt_student_info->fetch(PDO::FETCH_ASSOC);
    if ($student_info) {
        $studentStd = $student_info['std'];
        $schoolId = $student_info['school_id'];
    }

    // --- Data Fetching for Display ---
    $filter = $_GET['filter'] ?? 'all';
    $sortBy = $_GET['sort_by'] ?? 'due_date_asc';

    $sql = "SELECT a.id, a.title, a.description, a.subject, a.due_date, a.file_path,
            t.teacher_name, s.status as submission_status, s.file_path as submission_file,
            s.rejection_reason
            FROM assignments a
            JOIN teacher t ON a.teacher_id = t.id
            LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = :userId
            WHERE a.standard = :std AND a.school_id = :schoolId";

    // Filtering logic
    if ($filter === 'submitted') $sql .= " AND s.status IS NOT NULL AND s.status != 'Rejected'";
    if ($filter === 'pending') $sql .= " AND s.status IS NULL AND a.due_date >= CURRENT_DATE";
    if ($filter === 'missed') $sql .= " AND s.status IS NULL AND a.due_date < CURRENT_DATE";
    if ($filter === 'rejected') $sql .= " AND s.status = 'Rejected'";

    // Sorting logic
    switch ($sortBy) {
        case 'due_date_desc':
            $sql .= " ORDER BY a.due_date DESC";
            break;
        case 'subject_asc':
            $sql .= " ORDER BY a.subject ASC, a.due_date ASC";
            break;
        default:
            $sql .= " ORDER BY a.due_date ASC";
            break;
    }

    $stmt_assignments = $conn->prepare($sql);
    $stmt_assignments->execute([':userId' => $userId, ':std' => $studentStd, ':schoolId' => $schoolId]);
    $assignments = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in view_assignments.php: " . $e->getMessage());
    $errorMessage = "A database error occurred.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assignments</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css" />
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
                        <h1 class="h3 mb-4 text-gray-800">My Assignments</h1>

                        <?php if ($successMessage): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                        <?php endif; ?>
                        <?php if ($errorMessage): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                        <?php endif; ?>

                        <div class="card shadow mb-4">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <form id="filterForm" class="form-inline">
                                    <div class="form-group mr-3">
                                        <label for="filterBy" class="mr-2">Filter:</label>
                                        <select id="filterBy" name="filter" class="form-control">
                                            <option value="all" <?php if ($filter == 'all') echo 'selected'; ?>>All</option>
                                            <option value="pending" <?php if ($filter == 'pending') echo 'selected'; ?>>Pending</option>
                                            <option value="submitted" <?php if ($filter == 'submitted') echo 'selected'; ?>>Submitted</option>
                                            <option value="missed" <?php if ($filter == 'missed') echo 'selected'; ?>>Missed</option>
                                            <option value="rejected" <?php if ($filter == 'rejected') echo 'selected'; ?>>Rejected</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="sortBy" class="mr-2">Sort by:</label>
                                        <select id="sortBy" name="sort_by" class="form-control">
                                            <option value="due_date_asc" <?php if ($sortBy == 'due_date_asc') echo 'selected'; ?>>Due Date (Asc)</option>
                                            <option value="due_date_desc" <?php if ($sortBy == 'due_date_desc') echo 'selected'; ?>>Due Date (Desc)</option>
                                            <option value="subject_asc" <?php if ($sortBy == 'subject_asc') echo 'selected'; ?>>Subject</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="row">
                            <?php if (empty($assignments)): ?>
                                <div class="col-12 text-center mt-5">
                                    <i class="fas fa-check-circle fa-4x text-gray-400"></i>
                                    <p class="mt-3">No assignments found based on the current filter.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($assignments as $assignment): ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="card assignment-card shadow">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                                <?php
                                                $status = 'Pending';
                                                $badge_class = 'warning';
                                                if ($assignment['submission_status'] == 'Submitted') {
                                                    $status = 'Submitted';
                                                    $badge_class = 'info';
                                                }
                                                if ($assignment['submission_status'] == 'Accepted') {
                                                    $status = 'Accepted';
                                                    $badge_class = 'success';
                                                }
                                                if ($assignment['submission_status'] == 'Rejected') {
                                                    $status = 'Rejected';
                                                    $badge_class = 'danger';
                                                }
                                                if (!$assignment['submission_status'] && strtotime($assignment['due_date']) < time()) {
                                                    $status = 'Missed';
                                                    $badge_class = 'secondary';
                                                }
                                                ?>
                                                <span class="badge badge-<?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                            </div>
                                            <div class="card-body">
                                                <p><strong>Subject:</strong> <?php echo htmlspecialchars($assignment['subject']); ?></p>
                                                <p><strong>Teacher:</strong> <?php echo htmlspecialchars($assignment['teacher_name']); ?></p>
                                                <p><strong>Due Date:</strong> <?php echo date('F j, Y', strtotime($assignment['due_date'])); ?></p>
                                                <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>

                                                <?php if ($assignment['rejection_reason']): ?>
                                                    <div class="alert alert-danger">
                                                        <strong>Reason for Rejection:</strong> <?php echo htmlspecialchars($assignment['rejection_reason']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-footer bg-light d-flex justify-content-between">
                                                <?php if ($assignment['file_path']): ?>
                                                    <a href="../../<?php echo htmlspecialchars($assignment['file_path']); ?>" class="btn btn-sm btn-info" download><i class="fas fa-download mr-1"></i> Download</a>
                                                <?php endif; ?>

                                                <?php if ($status !== 'Missed' && $status !== 'Accepted'): ?>
                                                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#uploadModal" data-assignment-id="<?php echo $assignment['id']; ?>" data-assignment-title="<?php echo htmlspecialchars($assignment['title']); ?>">
                                                        <i class="fas fa-upload mr-1"></i> <?php echo ($assignment['submission_status']) ? 'Re-submit' : 'Submit'; ?>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form action="" method="post" enctype="multipart/form-data">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Submit: <span id="modalAssignmentTitle"></span></h5>
                                        <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Please select your completed assignment file to upload.</p>
                                        <input type="hidden" name="assignment_id" id="modalAssignmentId">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" name="assignment_file" id="assignment_file_modal" required>
                                            <label class="custom-file-label" for="assignment_file_modal">Choose file...</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                                        <button class="btn btn-primary" type="submit">Upload</button>
                                    </div>
                                </form>
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
        <script src="../../assets/js/sb-admin-2.min.js"></script>
        <script src="../../assets/js/responsive-tables.js"></script>

        <script src="../../assets/js/view_assignment.js"></script>
</body>

</html>