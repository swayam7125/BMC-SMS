<?php
// Add this line at the very beginning
require_once __DIR__ . '/../../includes/ajax_helpers.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/ajax_helpers.php";

$role = null;
$userId = null;
$schoolId = null;

if (isset($_COOKIE['encrypted_user_role'])) $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id'])) $userId = decrypt_id($_COOKIE['encrypted_user_id']);

if ($role !== 'principal' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

try {
    $stmt_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt_school->execute([$userId]);
    $schoolId = $stmt_school->fetchColumn();

    if (!$schoolId) {
        throw new Exception("Principal not associated with any school.");
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_timetable'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];

        if (empty($title)) {
            header("Location: send_exam_timetable.php?error=Please select a timetable title.");
            exit;
        }

        if (!isset($_FILES['timetable_file']) || $_FILES['timetable_file']['error'] != 0) {
            header("Location: send_exam_timetable.php?error=File upload is required.");
            exit;
        }

        $originalFilename = basename($_FILES["timetable_file"]["name"]);
        $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/uploads/timetables/';
        $uploadDirWeb = 'uploads/timetables/';
        if (!is_dir($uploadDirServer)) mkdir($uploadDirServer, 0777, true);

        $storageFilename = uniqid('examtt_', true) . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $originalFilename);
        $serverFilePath = $uploadDirServer . $storageFilename;

        if (!move_uploaded_file($_FILES["timetable_file"]["tmp_name"], $serverFilePath)) {
            header("Location: send_exam_timetable.php?error=Failed to move uploaded file.");
            exit;
        }

        $filePathForDB = $uploadDirWeb . $storageFilename;

        $conn->beginTransaction();

        $stmt_insert = $conn->prepare("INSERT INTO exam_timetables (principal_id, school_id, title, description, file_path, original_filename) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_insert->execute([$userId, $schoolId, $title, $description, $filePathForDB, $originalFilename]);

        $notification_message = "New Exam Timetable: " . htmlspecialchars($title);
        $notification_type = "exam_timetable";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");

        $stmt_teachers = $conn->prepare("SELECT id FROM teacher WHERE school_id = ?");
        $stmt_teachers->execute([$schoolId]);
        $teachers = $stmt_teachers->fetchAll(PDO::FETCH_COLUMN, 0);

        $stmt_students = $conn->prepare("SELECT id FROM student WHERE school_id = ?");
        $stmt_students->execute([$schoolId]);
        $students = $stmt_students->fetchAll(PDO::FETCH_COLUMN, 0);

        $teacher_link = "pages/teacher/view_exam_timetable.php";
        foreach ($teachers as $teacher_id) {
            $stmt_notify->execute([$teacher_id, $notification_message, $teacher_link, $notification_type]);
        }

        $student_link = "pages/student/view_exam_timetable.php";
        foreach ($students as $student_id) {
            $stmt_notify->execute([$student_id, $notification_message, $student_link, $notification_type]);
        }

        $conn->commit();
        header("Location: send_exam_timetable.php?success=Timetable sent successfully!");
        exit();
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log("Send Exam Timetable Error: " . $e->getMessage());
    die("Failed to send timetable: " . $e->getMessage());
}

$pageTitle = 'Send Exam Timetable';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send Exam Timetable</h1>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Upload Timetable</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="send_exam_timetable.php" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="title">Timetable Title</label>
                                    <select class="form-control" id="title" name="title" required>
                                        <option value="">-- Select Exam Type --</option>
                                        <option value="Term 1 Exam Timetable">Term 1 Exam Timetable</option>
                                        <option value="Term 2 Exam Timetable">Term 2 Exam Timetable</option>
                                        <option value="Final Exam Timetable">Final Exam Timetable</option>
                                    </select>
                                </div>
                                <div class="form-group"><label for="description">Description (Optional)</label><textarea class="form-control" id="description" name="description" rows="3"></textarea></div>
                                <div class="form-group"><label for="timetable_file">Timetable File (PDF, JPG, PNG)</label><input type="file" class="form-control-file" id="timetable_file" name="timetable_file" required></div>
                                <button type="submit" name="send_timetable" class="btn btn-primary">Send to All</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            if (!$is_ajax_request) {
                include '../../includes/footer.php';
            }
            ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
<?php
// Add this block at the very end of the file
if (is_ajax_request()) {
    // Get the captured HTML
    $content = ob_get_clean();

    // Extract just the main content area for the AJAX response
    if (preg_match('/<div class="container-fluid".*?>(.*?)<\/div>/s', $content, $matches)) {
        echo '<div class="container-fluid">' . $matches[1] . '</div>';
    } else {
        // Fallback if the main container isn't found
        echo $content;
    }
    // Stop the script for AJAX requests
    exit;
}
?>

</html>