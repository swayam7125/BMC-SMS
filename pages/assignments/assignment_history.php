<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/ajax_helpers.php";

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// --- Authorization & Initialization ---
$role = null;
$userId = null;
$availableStandards = [];
$assignments = [];

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
    // Fetch standards taught by the teacher
    $stmt_standards = $conn->prepare('SELECT "std" FROM "teacher" WHERE "id" = ?');
    $stmt_standards->execute([$userId]);
    $teacher_standards_str = $stmt_standards->fetchColumn();
    if (!empty($teacher_standards_str)) {
        $availableStandards = explode(',', trim($teacher_standards_str, '{}'));
    }

    // Determine the standard to filter by
    $selectedStd = $_GET['std'] ?? 'all';

    // Build the query to fetch assignments
    $sql = 'SELECT a.id, a.title, a.std, a.subject, a.due_date, a.created_at,
            (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as submission_count
            FROM assignments a WHERE a.teacher_id = ?';
    $params = [$userId];

    if ($selectedStd !== 'all') {
        $sql .= ' AND a.std = ?';
        $params[] = $selectedStd;
    }
    $sql .= ' ORDER BY a.created_at DESC';

    $stmt_assignments = $conn->prepare($sql);
    $stmt_assignments->execute($params);
    $assignments = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error in assignment_history.php: " . $e->getMessage());
    // You can set an error message here to display to the user if needed
}

?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Assignment History</title>
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
            <?php 
            if (!$is_ajax_request) { 
                include '../../includes/sidebar.php';
            } 
            ?>
        <div id="content-wrapper" class="d-flex flex-column">
                <div id="content">
                    <?php 
                    if (!$is_ajax_request) {
                        include '../../includes/header.php';
                    }
                    ?>
                    <div id="main-content">
                    <div class="container-fluid">
                        <div class="d-sm-flex align-items-center justify-content-between mb-4">
                            <h1 class="h3 mb-0 text-gray-800">Assignment History</h1>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <form action="" method="GET" class="form-inline">
                                    <label for="stdFilter" class="mr-2">Filter by Standard:</label>
                                    <select name="std" id="stdFilter" class="form-control" onchange="this.form.submit()">
                                        <option value="all" <?php echo ($selectedStd == 'all') ? 'selected' : ''; ?>>All Standards</option>
                                        <?php foreach ($availableStandards as $standard): ?>
                                            <option value="<?php echo htmlspecialchars($standard); ?>" <?php echo ($selectedStd == $standard) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($standard); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($assignments)): ?>
                                    <div class="list-group">
                                        <?php foreach ($assignments as $assignment): ?>
                                            <a href="view_submissions.php?id=<?php echo $assignment['id']; ?>" class="list-group-item list-group-item-action flex-column align-items-start mb-2 border rounded">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h5 class="mb-1 text-primary"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                                    <small>Posted on: <?php echo date('M d, Y', strtotime($assignment['created_at'])); ?></small>
                                                </div>
                                                <p class="mb-1">
                                                    <strong>Standard:</strong> <?php echo htmlspecialchars($assignment['std']); ?> |
                                                    <strong>Subject:</strong> <?php echo htmlspecialchars($assignment['subject']); ?>
                                                </p>
                                                <small>Due Date: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></small>
                                                <span class="badge badge-info badge-pill float-right mt-2">
                                                    <?php echo $assignment['submission_count']; ?> Submissions
                                                </span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-folder-open fa-3x text-gray-400"></i>
                                        <p class="mt-3">No assignments found for the selected standard.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
                <?php if (!$is_ajax_request) { include '../../includes/footer.php'; } ?>
            </div>
        </div>
        <?php include_once "../../includes/logout_modal.php" ?>
        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>
        <script src="../../assets/js/responsive-tables.js"></script>
    </body>

    </html>
<?php 
$conn = null;
?>