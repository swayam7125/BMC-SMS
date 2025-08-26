<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
*/
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/email_functions.php";
include_once "../../includes/ajax_helpers.php";

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
        $filter = $_POST['current_filter'] ?? 'all';
        $sort_by = $_POST['current_sort'] ?? 'recent';

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
                header("Location: view_assignments.php?submission=success&filter=$filter&sort_by=$sort_by");
                exit();
            }
        }
        header("Location: view_assignments.php?submission=error&filter=$filter&sort_by=$sort_by");
        exit();
    }

    $student_info_stmt = $conn->prepare('SELECT "school_id", "std" FROM "student" WHERE "id" = ?');
    $student_info_stmt->execute([$userId]);
    $student_info = $student_info_stmt->fetch(PDO::FETCH_ASSOC);
    $schoolId = $student_info['school_id'] ?? 0;
    $studentStd = $student_info['std'] ?? '';

    // --- FILTER & SORT LOGIC ---
    $filter = $_GET['filter'] ?? 'all';
    $sort_by = $_GET['sort_by'] ?? 'recent';
    $filter_clause = '';
    $order_by_clause = 'ORDER BY a.created_at DESC'; // Default sort

    if ($sort_by === 'due_date') {
        $order_by_clause = 'ORDER BY a.due_date ASC, a.created_at DESC';
    }

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

    $sql = 'SELECT a.id, a.title, a.subject, a.description, a.due_date, a.file_path, a.original_filename, t.teacher_name, ss.status as submission_status, ss.rejection_reason FROM "assignments" a JOIN "teacher" t ON a.teacher_id = t.id LEFT JOIN "assignment_submissions" ss ON a.id = ss.assignment_id AND ss.student_id = ? WHERE a.school_id = ? AND a.standard = ?' . $filter_clause . ' ' . $order_by_clause;
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId, $schoolId, $studentStd]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<?php
/*
|--------------------------------------------------------------------------
| RESPONSIVE & PROFESSIONAL FRONTEND (VIEW)
|--------------------------------------------------------------------------
*/
if (!is_ajax_request()):
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>My Assignments</title>

        <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
        <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/sidebar.css">
        <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">

        <style>
            .control-bar {
                background-color: #f8f9fc;
                padding: 1rem;
                border-radius: .35rem;
                border: 1px solid #e3e6f0;
            }

            .nav-pills .nav-link {
                color: #5a5c69;
                font-weight: 500;
            }

            .nav-pills .nav-link.active {
                color: #fff;
                background-color: #4e73df;
                box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
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

                        <div class="mb-4">
                            <div class="d-none d-md-flex justify-content-between align-items-center">
                                <ul class="nav nav-pills mb-3 mb-md-0">
                                    <li class="nav-item"><a class="nav-link btn btn-outline-primary mr-2 <?php echo ($filter == 'all') ? 'active text-light' : 'text-dark'; ?>" href="view_assignments.php?filter=all&sort_by=<?php echo $sort_by; ?>">All</a></li>
                                    <li class="nav-item"><a class="nav-link btn btn-outline-warning mr-2 <?php echo ($filter == 'pending') ? 'active text-light' : 'text-dark'; ?>" href="view_assignments.php?filter=pending&sort_by=<?php echo $sort_by; ?>">Pending</a></li>
                                    <li class="nav-item"><a class="nav-link btn btn-outline-info mr-2 <?php echo ($filter == 'submitted') ? 'active text-light' : 'text-dark'; ?>" href="view_assignments.php?filter=submitted&sort_by=<?php echo $sort_by; ?>">Submitted</a></li>
                                    <li class="nav-item"><a class="nav-link btn btn-outline-success mr-2 <?php echo ($filter == 'accepted') ? 'active text-light' : 'text-dark'; ?>" href="view_assignments.php?filter=accepted&sort_by=<?php echo $sort_by; ?>">Accepted</a></li>
                                    <li class="nav-item"><a class="nav-link btn btn-outline-danger <?php echo ($filter == 'rejected') ? 'active text-light' : 'text-dark'; ?>" href="view_assignments.php?filter=rejected&sort_by=<?php echo $sort_by; ?>">Rejected</a></li>
                                </ul>
                                <div class="d-inline-flex align-items-center">
                                    <label for="sortByDesktop" class="m-0 mr-2 text-gray-600 font-weight-bold">Sort:</label>
                                    <select class="form-control form-control-sm" id="sortByDesktop">
                                        <option value="recent" <?php echo ($sort_by == 'recent') ? 'selected' : ''; ?>>Recently Added</option>
                                        <option value="due_date" <?php echo ($sort_by == 'due_date') ? 'selected' : ''; ?>>Due Date</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-md-none">
                                <div class="row">
                                    <div class="col-6">
                                        <label for="filterByMobile" class="text-gray-600 font-weight-bold">Filter by:</label>
                                        <select class="form-control form-control-sm" id="filterByMobile">
                                            <option value="all" <?php echo ($filter == 'all') ? 'selected' : ''; ?>>All</option>
                                            <option value="pending" <?php echo ($filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="submitted" <?php echo ($filter == 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                                            <option value="accepted" <?php echo ($filter == 'accepted') ? 'selected' : ''; ?>>Accepted</option>
                                            <option value="rejected" <?php echo ($filter == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label for="sortByMobile" class="text-gray-600 font-weight-bold">Sort by:</label>
                                        <select class="form-control form-control-sm" id="sortByMobile">
                                            <option value="recent" <?php echo ($sort_by == 'recent') ? 'selected' : ''; ?>>Recently Added</option>
                                            <option value="due_date" <?php echo ($sort_by == 'due_date') ? 'selected' : ''; ?>>Due Date</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
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
                                    // --- STATUS & COLOR LOGIC ---
                                    $status = $assignment['submission_status'] ?? 'Pending';
                                    $status_text = str_replace('-', ' ', $status);
                                    $badge_class = 'warning';
                                    $due_date_class = 'text-danger font-weight-bold';

                                    if ($status == 'Accepted') {
                                        $badge_class = 'success';
                                        $due_date_class = 'text-success font-weight-bold';
                                    } elseif ($status == 'Rejected') {
                                        $badge_class = 'danger';
                                    } elseif ($status == 'Submitted' || $status == 'Re-submitted') {
                                        $badge_class = 'info';
                                        $due_date_class = 'text-muted';
                                    }
                                ?>
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                            <span class="badge badge-<?php echo $badge_class; ?> p-2"><?php echo $status_text; ?></span>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <small class="text-muted">By <?php echo htmlspecialchars($assignment['teacher_name']); ?> | Subject: <strong><?php echo htmlspecialchars($assignment['subject']); ?></strong></small>
                                            </div>
                                            <p class="mb-3 text-gray-700"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>

                                            <?php if ($status === 'Rejected' && !empty($assignment['rejection_reason'])): ?>
                                                <div class="alert alert-warning">
                                                    <h6 class="font-weight-bold">Teacher's Feedback:</h6>
                                                    <?php echo $assignment['rejection_reason']; ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center">
                                                <div class="<?php echo $due_date_class; ?> mb-2 mb-sm-0">
                                                    <i class="fas fa-calendar-times mr-1"></i> Due: <?php echo date("F j, Y", strtotime($assignment['due_date'])); ?>
                                                </div>
                                                <div class="float-right">
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
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center mt-5">
                                    <i class="fas fa-folder-open fa-3x text-gray-400"></i>
                                    <p class="mt-3">No assignments found for this filter.</p>
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
                            <input type="hidden" name="current_filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <input type="hidden" name="current_sort" value="<?php echo htmlspecialchars($sort_by); ?>">
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

        <?php
        /*
    |--------------------------------------------------------------------------
    | JAVASCRIPT LOGIC
    |--------------------------------------------------------------------------
    */
        ?>
        <script>
            $(document).ready(function() {
                function updateUrlAndReload() {
                    const sortByValue = $('#sortByDesktop, #sortByMobile').val();
                    const filterByValue = $('#filterByMobile').val();

                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('sort_by', sortByValue);
                    currentUrl.searchParams.set('filter', filterByValue);
                    window.location.href = currentUrl.toString();
                }

                // Handlers for desktop controls
                $('#sortByDesktop').on('change', function() {
                    const sortByValue = $(this).val();
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('sort_by', sortByValue);
                    window.location.href = currentUrl.toString();
                });

                // Handlers for mobile controls
                $('#filterByMobile, #sortByMobile').on('change', function() {
                    const filterValue = $('#filterByMobile').val();
                    const sortValue = $('#sortByMobile').val();
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('filter', filterValue);
                    currentUrl.searchParams.set('sort_by', sortValue);
                    window.location.href = currentUrl.toString();
                });

                // Modal population
                $('#uploadModal').on('show.bs.modal', function(event) {
                    var button = $(event.relatedTarget);
                    var assignmentId = button.data('assignment-id');
                    var assignmentTitle = button.data('assignment-title');
                    var modal = $(this);
                    modal.find('#modalAssignmentTitle').text(assignmentTitle);
                    modal.find('#modalAssignmentId').val(assignmentId);
                });

                // Custom file input label
                $('.custom-file-input').on('change', function() {
                    var fileName = $(this).val().split('\\').pop();
                    $(this).siblings('.custom-file-label').addClass("selected").html(fileName);
                });
            });
        </script>
    </body>

    </html>
<?php
endif; // End ajax check
?>