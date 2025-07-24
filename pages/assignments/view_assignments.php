<?php
// Standard setup from your dashboard.php
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

// Security Check: Ensure user is logged in and is a student
if (!$role || $role !== 'student') {
    header("Location: ../../login.php");
    exit;
}

// --- PHP to fetch and filter assignments from the database ---

// Get filter values from URL, with defaults
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$filterSubject = isset($_GET['subject']) ? $_GET['subject'] : 'all';

// First, get the student's class and school to fetch relevant assignments
$student_info_stmt = $conn->prepare("SELECT school_id, std FROM student WHERE id = ?");
$student_info_stmt->bind_param("i", $userId);
$student_info_stmt->execute();
$student_info_result = $student_info_stmt->get_result();
$student_info = $student_info_result->fetch_assoc();
$schoolId = $student_info['school_id'] ?? 0;
$studentStd = $student_info['std'] ?? '';
$student_info_stmt->close();

// Base SQL to get assignments for the student's class, joining with submissions to check status
// Assumes table `assignments` and `student_submissions`
$sql = "
    SELECT 
        a.id, 
        a.title, 
        a.subject, 
        a.description, 
        a.due_date, 
        CASE WHEN ss.id IS NOT NULL THEN 1 ELSE 0 END as submitted
    FROM assignments a
    LEFT JOIN student_submissions ss ON a.id = ss.assignment_id AND ss.student_id = ?
    WHERE a.school_id = ? AND a.class_std = ?
";
$params = ['iii', $userId, $schoolId, $studentStd];
$whereClauses = [];

// Dynamically add WHERE clauses based on filters
if (!empty($searchTerm)) {
    $whereClauses[] = "(a.title LIKE ? OR a.description LIKE ?)";
    $likeSearchTerm = "%" . $searchTerm . "%";
    $params[0] .= 'ss';
    $params[] = $likeSearchTerm;
    $params[] = $likeSearchTerm;
}

if ($filterStatus !== 'all') {
    $whereClauses[] = ($filterStatus === 'submitted') ? "ss.id IS NOT NULL" : "ss.id IS NULL";
}

if ($filterSubject !== 'all') {
    $whereClauses[] = "a.subject = ?";
    $params[0] .= 's';
    $params[] = $filterSubject;
}

if (!empty($whereClauses)) {
    $sql .= " AND " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY a.due_date DESC";

// Prepare and execute the main query
$stmt = $conn->prepare($sql);
if ($stmt) {
    // Use call_user_func_array for dynamic parameter binding
    call_user_func_array([$stmt, 'bind_param'], $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $assignments = []; // Default to empty array on error
}

// Get a unique list of subjects for the filter dropdown from the database
$subject_stmt = $conn->prepare("SELECT DISTINCT subject FROM assignments WHERE school_id = ? AND class_std = ? ORDER BY subject ASC");
$subject_stmt->bind_param("is", $schoolId, $studentStd);
$subject_stmt->execute();
$subject_result = $subject_stmt->get_result();
$subjects = array_column($subject_result->fetch_all(MYSQLI_ASSOC), 'subject');
$subject_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Student - My Assignments</title>

    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        /* Custom styles for assignment cards */
        .assignment-card {
            transition: all 0.2s ease-in-out;
        }

        .assignment-card:not(.submitted) {
            cursor: pointer;
        }

        .assignment-card:not(.submitted):hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
        }

        .assignment-card.submitted {
            cursor: not-allowed;
            background-color: #f8f9fc;
        }

        .assignment-card.submitted .text-primary {
            color: #858796 !important;
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

                    <!-- Filter and Search Bar -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="view_assignments.php" method="GET" class="form-row align-items-center">
                                <div class="col-md-5 mb-2 mb-md-0">
                                    <input type="text" name="search" class="form-control" placeholder="Search by title or keyword..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                                </div>
                                <div class="col-md-2 mb-2 mb-md-0">
                                    <select name="status" class="form-control">
                                        <option value="all" <?php if ($filterStatus == 'all') echo 'selected'; ?>>All Statuses</option>
                                        <option value="pending" <?php if ($filterStatus == 'pending') echo 'selected'; ?>>Pending</option>
                                        <option value="submitted" <?php if ($filterStatus == 'submitted') echo 'selected'; ?>>Submitted</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2 mb-md-0">
                                    <select name="subject" class="form-control">
                                        <option value="all" <?php if ($filterSubject == 'all') echo 'selected'; ?>>All Subjects</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo htmlspecialchars($subject); ?>" <?php if ($filterSubject == $subject) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($subject); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search fa-sm"></i> Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Assignment Cards Section -->
                    <div id="assignment-list">
                        <?php if (!empty($assignments)): ?>
                            <?php foreach ($assignments as $assignment): ?>
                                <div class="card shadow mb-4 assignment-card <?php echo $assignment['submitted'] ? 'submitted' : ''; ?>"
                                    <?php if (!$assignment['submitted']): ?>
                                    data-toggle="modal"
                                    data-target="#uploadModal"
                                    data-assignment-id="<?php echo htmlspecialchars($assignment['id']); ?>"
                                    data-assignment-title="<?php echo htmlspecialchars($assignment['title']); ?>"
                                    <?php endif; ?>>
                                    <div class="card-body">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1 text-primary"><?php echo htmlspecialchars($assignment['title']); ?> <small class="text-muted">(<?php echo htmlspecialchars($assignment['subject']); ?>)</small></h5>
                                            <?php if ($assignment['submitted']): ?>
                                                <span class="badge badge-success p-2">Submitted</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning p-2">Pending</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($assignment['description']); ?></p>
                                        <small class="text-muted">Due: <?php echo date("F j, Y", strtotime($assignment['due_date'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Message when no assignments match filters -->
                            <div class="card shadow mb-4">
                                <div class="card-body text-center">
                                    <i class="fas fa-box-open fa-3x text-gray-400 mb-3"></i>
                                    <p class="lead text-gray-800">No assignments found.</p>
                                    <p class="text-gray-500">Try adjusting your search or filter criteria.</p>
                                    <a href="view_assignments.php" class="btn btn-secondary">Clear Filters</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>

    <!-- Upload Assignment Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="upload_handler.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Submit Assignment</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                    </div>
                    <div class="modal-body">
                        <p>You are submitting for: <strong id="modalAssignmentTitle"></strong></p>
                        <input type="hidden" name="assignment_id" id="modalAssignmentId">
                        <div class="form-group">
                            <label for="pdfUpload">Upload your assignment (.pdf only)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="pdfUpload" name="assignment_file" accept=".pdf" required>
                                <label class="custom-file-label" for="pdfUpload">Choose PDF file...</label>
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

    <!-- Standard Modals and Scripts -->
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="../../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <!-- Custom Page JS -->
    <script src="../../assets/js/student-assignment.js"></script>
</body>

</html>