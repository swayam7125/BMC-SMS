<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/ajax_helpers.php";

// Check if this is an AJAX request
if (is_ajax_request()) {
    // Start output buffering to capture the HTML
    ob_start();
}

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if (!$role || !$userId || $role !== 'teacher') {
    header("Location: ../../login.php?error=Access Denied");
    exit;
}

$isClassTeacher = false;
$classTeacherStd = '';
$schoolId = null;
$error = '';

try {
    $stmt = $conn->prepare("SELECT class_teacher, class_teacher_std, school_id FROM teacher WHERE id = ? AND class_teacher = B'1'");
    $stmt->execute([$userId]);
    if ($teacher_info = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($teacher_info['class_teacher_std'])) {
            $isClassTeacher = true;
            $classTeacherStd = $teacher_info['class_teacher_std'];
            $schoolId = $teacher_info['school_id'];
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && $isClassTeacher) {
        if (isset($_FILES['timetable_file']) && $_FILES['timetable_file']['error'] == 0) {
            $originalFilename = basename($_FILES["timetable_file"]["name"]);
            $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/teacher/uploads/timetables/';
            $uploadDirWeb = '/pages/teacher/uploads/timetables/';

            if (!is_dir($uploadDirServer)) {
                mkdir($uploadDirServer, 0777, true);
            }
            $storageFilename = uniqid('tt_', true) . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $originalFilename);
            $serverFilePath = $uploadDirServer . $storageFilename;

            if (move_uploaded_file($_FILES["timetable_file"]["tmp_name"], $serverFilePath)) {
                $filePathForDB = $uploadDirWeb . $storageFilename;

                $conn->beginTransaction();

                $insert_stmt = $conn->prepare("INSERT INTO exam_timetables (school_id, title, file_path, original_filename, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                $title = "Class Timetable for Standard " . $classTeacherStd;
                $insert_stmt->execute([$schoolId, $title, $filePathForDB, $originalFilename, $userId]);

                $stmt_students = $conn->prepare("SELECT id FROM student WHERE school_id = ? AND std = ?");
                $stmt_students->execute([$schoolId, $classTeacherStd]);
                $student_ids_to_notify = $stmt_students->fetchAll(PDO::FETCH_COLUMN, 0);

                if (!empty($student_ids_to_notify)) {
                    $notification_message = "A new timetable has been uploaded for your class.";
                    $notification_link = "pages/student/view_timetable.php";
                    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, 'exam_timetable')");
                    foreach ($student_ids_to_notify as $student_id) {
                        $stmt_notify->execute([$student_id, $notification_message, $notification_link]);
                    }
                }

                $conn->commit();
                header("Location: send_timetable.php?success=1");
                exit();
            } else {
                $error = "Failed to move the uploaded file.";
            }
        } else {
            $error = "File upload failed. Please select a valid file.";
        }
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    $error = "A database error occurred: " . $e->getMessage();
    error_log("Send Timetable Error: " . $e->getMessage());
}
$pageTitle = 'Send Timetable';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send Timetable</h1>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Timetable uploaded successfully!</div>
                    <?php endif; ?>

                    <?php if (!$isClassTeacher): ?>
                        <div class="alert alert-danger">You are not assigned as a class teacher. You do not have permission to upload a timetable.</div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Upload Timetable for Your Class</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="send_timetable.php" enctype="multipart/form-data">
                                    <div class="form-group"><label for="target_standard">Standard</label><input type="text" class="form-control" id="target_standard" name="target_standard" value="Standard <?php echo htmlspecialchars($classTeacherStd); ?>" readonly></div>
                                    <div class="form-group"><label for="timetable_file">Upload Timetable File (PDF, PNG, JPG)</label><input type="file" class="form-control-file" id="timetable_file" name="timetable_file" accept=".pdf,.png,.jpg,.jpeg" required></div>
                                    <button type="submit" name="send_timetable" class="btn btn-primary">Upload Timetable</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
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