<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;
$schoolId = null;

if (isset($_COOKIE['encrypted_user_role'])) $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id'])) $userId = decrypt_id($_COOKIE['encrypted_user_id']);

if ($role !== 'schooladmin' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

// Get School ID
$stmt_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
$stmt_school->bind_param("i", $userId);
$stmt_school->execute();
$schoolId = $stmt_school->get_result()->fetch_assoc()['school_id'];
$stmt_school->close();


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

    // --- FILE UPLOAD ---
    $originalFilename = basename($_FILES["timetable_file"]["name"]);
    $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/uploads/timetables/';
    $uploadDirWeb = '/BMC-SMS/uploads/timetables/';
    if (!is_dir($uploadDirServer)) mkdir($uploadDirServer, 0777, true);

    $storageFilename = uniqid('examtt_', true) . '_' . $originalFilename;
    $serverFilePath = $uploadDirServer . $storageFilename;
    
    if (!move_uploaded_file($_FILES["timetable_file"]["tmp_name"], $serverFilePath)) {
        header("Location: send_exam_timetable.php?error=Failed to move uploaded file.");
        exit;
    }
    
    $filePathForDB = $uploadDirWeb . $storageFilename;

    // --- DATABASE & NOTIFICATIONS ---
    $conn->begin_transaction();
    try {
        // 1. Insert timetable into the new table
        $stmt_insert = $conn->prepare("INSERT INTO exam_timetables (principal_id, school_id, title, description, file_path, original_filename) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("iissss", $userId, $schoolId, $title, $description, $filePathForDB, $originalFilename);
        $stmt_insert->execute();
        $stmt_insert->close();

        // 2. Prepare for notifications
        $notification_message = "New Exam Timetable: " . htmlspecialchars($title);
        $notification_type = "exam_timetable";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");

        // 3. Get all teachers of the school
        $stmt_teachers = $conn->prepare("SELECT id FROM teacher WHERE school_id = ?");
        $stmt_teachers->bind_param("i", $schoolId);
        $stmt_teachers->execute();
        $teachers = $stmt_teachers->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_teachers->close();

        // 4. Get all students of the school
        $stmt_students = $conn->prepare("SELECT id FROM student WHERE school_id = ?");
        $stmt_students->bind_param("i", $schoolId);
        $stmt_students->execute();
        $students = $stmt_students->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_students->close();

        // 5. Send notifications to teachers
        $teacher_link = "/pages/teacher/view_exam_timetable.php";
        foreach ($teachers as $teacher) {
            $teacher_id = $teacher['id'];
            $stmt_notify->bind_param("isss", $teacher_id, $notification_message, $teacher_link, $notification_type);
            $stmt_notify->execute();
        }

        // 6. Send notifications to students
        $student_link = "/pages/student/view_exam_timetable.php";
        foreach ($students as $student) {
            $student_id = $student['id'];
            $stmt_notify->bind_param("isss", $student_id, $notification_message, $student_link, $notification_type);
            $stmt_notify->execute();
        }

        $stmt_notify->close();
        $conn->commit();
        header("Location: send_exam_timetable.php?success=Timetable sent successfully!");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Failed to send timetable: " . $e->getMessage());
    }
}

$pageTitle = 'Send Exam Timetable';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <!-- <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet"> -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

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
                                <div class="form-group">
                                    <label for="description">Description (Optional)</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="timetable_file">Timetable File (PDF, JPG, PNG)</label>
                                    <input type="file" class="form-control-file" id="timetable_file" name="timetable_file" required>
                                </div>
                                <button type="submit" name="send_timetable" class="btn btn-primary">Send to All</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5><button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a></div>
            </div>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>