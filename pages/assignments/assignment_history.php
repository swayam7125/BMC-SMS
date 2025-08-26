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

if (!$role || $role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

try {
    $stmt_standards = $conn->prepare('SELECT "std" FROM "teacher" WHERE "id" = ?');
    $stmt_standards->execute([$userId]);
    $teacher_standards_str = $stmt_standards->fetchColumn();
    $availableStandards = [];
    if (!empty($teacher_standards_str)) {
        $availableStandards = explode(',', trim($teacher_standards_str, '{}'));
    }

    $selectedStd = $_GET['std'] ?? 'all';

    $sql = ' 
        SELECT
            a.id, a.title, a.standard, a.subject, a.created_at, a.due_date,
            (SELECT COUNT(*) FROM "assignment_submissions" s WHERE s.assignment_id = a.id) as submission_count,
            (SELECT COUNT(*) FROM "student" st WHERE st.school_id = a.school_id AND st.std = a.standard) as total_students,
            (SELECT COUNT(*) FROM "notifications" n WHERE n.user_id = a.teacher_id AND n.is_read = false AND n.type = \'assignment_submission\' AND n.link LIKE \'%view_submissions.php?id=\' || a.id) as new_submission_count
        FROM "assignments" a
        WHERE a.teacher_id = ?';

    $params = [$userId];
    if ($selectedStd !== 'all') {
        $sql .= ' AND a.standard = ?';
        $params[] = $selectedStd;
    }

    $sql .= ' ORDER BY a.created_at DESC';

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($userId) {
        $stmt_mark_all_read = $conn->prepare('UPDATE "notifications" SET "is_read" = true WHERE "user_id" = ? AND "type" = \'assignment_submission\' AND "is_read" = false');
        $stmt_mark_all_read->execute([$userId]);
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = 'Teacher - Assignment History';
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
        <title><?php echo htmlspecialchars($pageTitle); ?></title>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
        <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                    /* Hide table on small screens */
                }

                .mobile-card-view {
                    display: block;
                    /* Show cards on small screens */
                }

                .page-header-button {
                    width: 100% !important;
                    margin-top: 1rem;
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
                            <h1 class="h3 mb-0 text-gray-800">Sent Assignment History</h1>
                            <a href="send_assignment.php" class="btn btn-primary shadow-sm page-header-button">
                                <i class="fas fa-plus fa-sm text-white-50"></i> Send New Assignment
                            </a>
                        </div>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">Assignment sent successfully!</div>
                        <?php endif; ?>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Assignment Log</h6>
                                <div class="d-inline-flex align-items-center">
                                    <label for="standardFilter" class="m-0 mr-2 d-none d-sm-block">Filter:</label>
                                    <select class="form-control form-control-sm" id="standardFilter" onchange="window.location.href = 'assignment_history.php?std=' + this.value;">
                                        <option value="all">All Standards</option>
                                        <?php foreach ($availableStandards as $std): ?>
                                            <option value="<?php echo htmlspecialchars(trim($std)); ?>" <?php echo ($selectedStd == trim($std)) ? 'selected' : ''; ?>>
                                                Standard <?php echo htmlspecialchars(trim($std)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">

                                <div class="desktop-table table-responsive">
                                    <table class="table table-striped table-hover" width="100%" cellspacing="0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Title</th>
                                                <th>Assigned To</th>
                                                <th>Subject</th>
                                                <th>Sent</th>
                                                <th>Due</th>
                                                <th class="text-center">Submissions</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($assignments)): ?>
                                                <?php foreach ($assignments as $assignment): ?>
                                                    <tr class="align-middle">
                                                        <td>
                                                            <span class="font-weight-bold"><?php echo htmlspecialchars($assignment['title']); ?></span>
                                                        </td>
                                                        <td><span class="badge badge-pill badge-secondary">Std <?php echo htmlspecialchars($assignment['standard']); ?></span></td>
                                                        <td><?php echo htmlspecialchars($assignment['subject']); ?></td>
                                                        <td><?php echo date("d M, Y", strtotime($assignment['created_at'])); ?></td>
                                                        <td><?php echo date("d M, Y", strtotime($assignment['due_date'])); ?></td>
                                                        <td class="text-center">
                                                            <span class="font-weight-bold"><?php echo htmlspecialchars($assignment['submission_count']); ?></span> / <?php echo htmlspecialchars($assignment['total_students']); ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <a href="view_submissions.php?id=<?php echo $assignment['id']; ?>" class="btn btn-primary btn-icon-split btn-sm">
                                                                <span class="icon text-white-50"><i class="fas fa-eye"></i></span>
                                                                <span class="text">View</span>
                                                                <?php if ($assignment['new_submission_count'] > 0): ?>
                                                                    <span class="badge badge-danger badge-counter ml-2"><?php echo $assignment['new_submission_count']; ?></span>
                                                                <?php endif; ?>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mobile-card-view">
                                    <?php if (!empty($assignments)): ?>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <div class="card shadow-sm mb-3">
                                                <div class="card-header bg-light py-3">
                                                    <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                                </div>
                                                <div class="card-body">
                                                    <ul class="list-group list-group-flush">
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">Assigned To: <span class="badge badge-secondary badge-pill">Standard <?php echo htmlspecialchars($assignment['standard']); ?></span></li>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">Subject: <strong><?php echo htmlspecialchars($assignment['subject']); ?></strong></li>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">Submissions: <strong><?php echo htmlspecialchars($assignment['submission_count']); ?> / <?php echo htmlspecialchars($assignment['total_students']); ?></strong></li>
                                                    </ul>
                                                </div>
                                                <div class="card-footer d-flex justify-content-between align-items-center">
                                                    <small class="text-danger font-weight-bold"><i class="fas fa-calendar-times mr-1"></i> Due: <?php echo date("d M, Y", strtotime($assignment['due_date'])); ?></small>
                                                    <a href="view_submissions.php?id=<?php echo $assignment['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye"></i> View
                                                        <?php if ($assignment['new_submission_count'] > 0): ?>
                                                            <span class="badge badge-light ml-2"><?php echo $assignment['new_submission_count']; ?></span>
                                                        <?php endif; ?>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if (empty($assignments)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-folder-open fa-3x text-gray-400"></i>
                                        <p class="mt-3">No assignments found for the selected standard.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include '../../includes/footer.php'; ?>
            </div>
        </div>
        <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
        <?php include_once "../../includes/logout_modal.php" ?>

        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>
    </body>

    </html>
<?php
endif; // End ajax check
$conn = null;
?>