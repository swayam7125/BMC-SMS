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
    header("Location: ../../dashboard.php");
    exit;
}

// --- HANDLE ASSIGNMENT SUBMISSION (POST REQUEST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['assignment_file'])) {
    $assignment_id = $_POST['assignment_id'];
    $student_id = $userId;
    
    $filePathForDB = null;
    $originalFilename = null;

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

            $insert_stmt = $conn->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, file_path, original_filename) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("iiss", $assignment_id, $student_id, $filePathForDB, $originalFilename);
            $insert_stmt->execute();
            $insert_stmt->close();

            header("Location: view_assignments.php?submission=success");
            exit();
        } else {
            header("Location: view_assignments.php?submission=error");
            exit();
        }
    }
}


// --- PHP to fetch and filter assignments from the database ---
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$filterSubject = isset($_GET['subject']) ? $_GET['subject'] : 'all';

$student_info_stmt = $conn->prepare("SELECT school_id, std FROM student WHERE id = ?");
$student_info_stmt->bind_param("i", $userId);
$student_info_stmt->execute();
$student_info_result = $student_info_stmt->get_result();
$student_info = $student_info_result->fetch_assoc();
$schoolId = $student_info['school_id'] ?? 0;
$studentStd = $student_info['std'] ?? '';
$student_info_stmt->close();

$sql = "
    SELECT 
        a.id, a.title, a.subject, a.description, a.due_date,
        a.file_path, a.original_filename, t.teacher_name,
        CASE WHEN ss.id IS NOT NULL THEN 1 ELSE 0 END as submitted
    FROM assignments a
    JOIN teacher t ON a.teacher_id = t.id
    LEFT JOIN assignment_submissions ss ON a.id = ss.assignment_id AND ss.student_id = ?
    WHERE a.school_id = ? AND a.standard = ?
";

$types = "iis";
$params = [$userId, $schoolId, $studentStd];
if (!empty($searchTerm)) {
    $sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
    $likeSearchTerm = "%" . $searchTerm . "%";
    $types .= 'ss';
    $params[] = $likeSearchTerm;
    $params[] = $likeSearchTerm;
}
if ($filterStatus !== 'all') {
    $sql .= ($filterStatus === 'submitted') ? " AND ss.id IS NOT NULL" : " AND ss.id IS NULL";
}
if ($filterSubject !== 'all') {
    $sql .= " AND a.subject = ?";
    $types .= 's';
    $params[] = $filterSubject;
}
$sql .= " ORDER BY a.due_date DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $assignments = [];
}
$subject_stmt = $conn->prepare("SELECT DISTINCT subject FROM assignments WHERE school_id = ? AND standard = ? ORDER BY subject ASC");
$subject_stmt->bind_param("is", $schoolId, $studentStd);
$subject_stmt->execute();
$subject_result = $subject_stmt->get_result();
$subjects = array_column($subject_result->fetch_all(MYSQLI_ASSOC), 'subject');
$subject_stmt->close();
$conn->close();

$pageTitle = 'Student - My Assignments';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400i,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .assignment-card { transition: all 0.2s ease-in-out; }
        .assignment-card:not(.submitted) { cursor: pointer; }
        .assignment-card:not(.submitted):hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important; }
        .assignment-card.submitted { cursor: not-allowed; background-color: #f8f9fc; }
        .assignment-card.submitted .text-primary { color: #858796 !important; }
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
                        <div class="alert alert-danger">There was an error submitting your assignment. Please try again.</div>
                    <?php endif; ?>
                    
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="view_assignments.php" method="GET" class="form-row align-items-center">
                                <div class="col-md-5 mb-2 mb-md-0">
                                    <input type="text" name="search" class="form-control" placeholder="Search by title..." value="<?php echo htmlspecialchars($searchTerm); ?>">
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
                                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search fa-sm"></i> Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>

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
                                            <div>
                                                <h5 class="mb-1 text-primary"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                                <h6 class="text-muted small"><?php echo htmlspecialchars($assignment['subject']); ?> | Assigned by: <?php echo htmlspecialchars($assignment['teacher_name']); ?></h6>
                                            </div>
                                            <?php if ($assignment['submitted']): ?>
                                                <span class="badge badge-success p-2 align-self-start">Submitted</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning p-2 align-self-start">Pending</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                                        <small class="text-muted">Due: <?php echo date("F j, Y", strtotime($assignment['due_date'])); ?></small>
                                        <?php if ($assignment['file_path']): ?>
                                            <a href="<?php echo htmlspecialchars($assignment['file_path']); ?>" class="btn btn-sm btn-outline-secondary float-right" download="<?php echo htmlspecialchars($assignment['original_filename']); ?>">
                                                <i class="fas fa-download"></i> Download Attachment
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="card shadow mb-4">
                                <div class="card-body text-center">
                                    <p class="lead text-gray-800">No assignments found.</p>
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

    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="view_assignments.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Submit Assignment</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
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
    
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    
    <script>
    // JavaScript to pass assignment info to the upload modal
    $('#uploadModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var assignmentId = button.data('assignment-id');
        var assignmentTitle = button.data('assignment-title');
        var modal = $(this);
        modal.find('#modalAssignmentTitle').text(assignmentTitle);
        modal.find('#modalAssignmentId').val(assignmentId);
    });

    // To show the selected filename in the file input
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass("selected").html(fileName);
    });
    </script>
</body>
</html>