<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";

// Get user info from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (!$role || !$userId || $role !== 'teacher') {
    die("Access Denied. Only teachers can access this page.");
}

// Check if the teacher is a class teacher
$isClassTeacher = false;
$classTeacherStd = '';
$schoolId = null;

$stmt = $conn->prepare("SELECT class_teacher, class_teacher_std, school_id FROM teacher WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($teacher_info = $result->fetch_assoc()) {
    if ($teacher_info['class_teacher'] == 1 && !empty($teacher_info['class_teacher_std'])) {
        $isClassTeacher = true;
        $classTeacherStd = $teacher_info['class_teacher_std'];
        $schoolId = $teacher_info['school_id'];
    }
}
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $isClassTeacher) {
    if (isset($_FILES['timetable_file']) && $_FILES['timetable_file']['error'] == 0) {
        $originalFilename = basename($_FILES["timetable_file"]["name"]);

        // --- MODIFIED: File path changed to /pages/teacher/uploads/timetables/ ---
        $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/teacher/uploads/timetables/';
        $uploadDirWeb = '/BMC-SMS/pages/teacher/uploads/timetables/';

        if (!is_dir($uploadDirServer)) {
            mkdir($uploadDirServer, 0777, true);
        }

        $storageFilename = uniqid('tt_', true) . '_' . $originalFilename;
        $serverFilePath = $uploadDirServer . $storageFilename;

        if (move_uploaded_file($_FILES["timetable_file"]["tmp_name"], $serverFilePath)) {
            $filePathForDB = $uploadDirWeb . $storageFilename;

            // Insert timetable record into the database
            $insert_stmt = $conn->prepare("INSERT INTO timetables (school_id, standard, class_teacher_id, timetable_file, original_filename) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("isiss", $schoolId, $classTeacherStd, $userId, $filePathForDB, $originalFilename);
            $insert_stmt->execute();
            $insert_stmt->close();

            header("Location: send_timetable.php?success=1");
            exit();
        } else {
            $error = "Failed to move the uploaded file.";
        }
    } else {
        $error = "File upload failed. Please select a valid file.";
    }
}
$pageTitle = 'Send Timetable';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400i,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">

</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send Timetable</h1>

                    <?php if (isset($error)): ?>
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
                                    <div class="form-group">
                                        <label for="target_standard">Standard</label>
                                        <input type="text" class="form-control" id="target_standard" name="target_standard" value="Standard <?php echo htmlspecialchars($classTeacherStd); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="timetable_file">Upload Timetable File (PDF, PNG, JPG)</label>
                                        <input type="file" class="form-control-file" id="timetable_file" name="timetable_file" required>
                                    </div>
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
</body>

</html>
<?php
$conn->close();
?>