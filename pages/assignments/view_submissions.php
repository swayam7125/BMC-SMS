<?php
// Standard setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;
$assignment = null;
$submissions = [];

if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security Check: Ensure user is a logged-in teacher
if (!$role || $role !== 'teacher' || !isset($_GET['id'])) {
    header("Location: ../../login.php");
    exit;
}

$assignmentId = intval($_GET['id']);

// Security check: Fetch assignment ONLY if it belongs to the logged-in teacher
$stmt = $conn->prepare("SELECT title, standard, subject, due_date FROM assignments WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $assignmentId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();
$stmt->close();

// If assignment not found or doesn't belong to the teacher, deny access
if (!$assignment) {
    die("Access Denied: You do not have permission to view this assignment or it does not exist.");
}

// --- CORRECTED QUERY based on your bmc.sql file ---
// Fetches submissions, using correct column names: `rollno`, `submitted_at`, and `file_path`.
$stmt_submissions = $conn->prepare("
    SELECT
        st.student_name,
        st.rollno,
        s.submitted_at,
        s.file_path,
        s.original_filename
    FROM assignment_submissions s
    JOIN student st ON s.student_id = st.id
    WHERE s.assignment_id = ?
    ORDER BY CAST(st.rollno AS UNSIGNED) ASC
");
$stmt_submissions->bind_param("i", $assignmentId);
$stmt_submissions->execute();
$result_submissions = $stmt_submissions->get_result();
$submissions = $result_submissions->fetch_all(MYSQLI_ASSOC);
$stmt_submissions->close();

$pageTitle = 'View Submissions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Submissions for: "<?php echo htmlspecialchars($assignment['title']); ?>"</h1>
                        <a href="assignment_history.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to History
                        </a>
                    </div>
                    <p>Standard: <strong><?php echo htmlspecialchars($assignment['standard']); ?></strong> | Subject: <strong><?php echo htmlspecialchars($assignment['subject']); ?></strong> | Due Date: <strong><?php echo date("d M, Y", strtotime($assignment['due_date'])); ?></strong></p>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Student Submissions</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Roll No.</th>
                                            <th>Student Name</th>
                                            <th>Submission Date</th>
                                            <th>Submitted File</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($submissions)): ?>
                                            <?php foreach ($submissions as $sub): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($sub['rollno']); ?></td>
                                                    <td><?php echo htmlspecialchars($sub['student_name']); ?></td>
                                                    <td><?php echo date("d-m-Y h:i A", strtotime($sub['submitted_at'])); ?></td>
                                                    <td>
                                                        <?php if ($sub['file_path']): ?>
                                                            <a href="<?php echo htmlspecialchars($sub['file_path']); ?>" download="<?php echo htmlspecialchars($sub['original_filename']); ?>" class="btn btn-success btn-sm">
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                        <?php else: ?>
                                                            No file
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No submissions have been made for this assignment yet.</td>
                                            </tr>
                                        <?php endif; ?>
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
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>$(document).ready(function() { $('#dataTable').DataTable({"order": [[0, "asc"]]}); });</script>
</body>
</html>
<?php
$conn->close();
?>