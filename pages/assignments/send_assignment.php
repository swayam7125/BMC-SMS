<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";

// Get user info from cookies, ensure user is a teacher
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

// Fetch the teacher's info (school, standards, subject)
$schoolId = null;
$availableStandards = [];
$availableSubjects = [];
$stmt = $conn->prepare("SELECT school_id, std, subject FROM teacher WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $schoolId = $row['school_id'];
    if (!empty($row['std'])) {
        $availableStandards = explode(',', $row['std']);
    }
    if (!empty($row['subject'])) {
        $availableSubjects = explode(',', $row['subject']);
    }
}
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $standard = $_POST['standard'];
    $subject = $_POST['subject'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    
    $filePathForDB = null;
    $originalFilename = null;

    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == 0) {
        $originalFilename = basename($_FILES["assignment_file"]["name"]);

        // --- CORRECTED & MORE ROBUST FILE PATH LOGIC ---
        // __DIR__ gives the absolute path of the current file's directory
        $uploadDirServer = __DIR__ . '/uploads/';
        // The web path for the database link
        $uploadDirWeb = '/BMC-SMS/pages/assignments/uploads/';

        if (!is_dir($uploadDirServer)) {
            // This will now correctly create the 'uploads' directory inside 'pages/assignments/'
            mkdir($uploadDirServer, 0777, true);
        }
        
        $storageFilename = uniqid('assign_', true) . '_' . $originalFilename;
        $serverFilePath = $uploadDirServer . $storageFilename;

        if (move_uploaded_file($_FILES["assignment_file"]["tmp_name"], $serverFilePath)) {
            $filePathForDB = $uploadDirWeb . $storageFilename;
        }
    }
    
    // CORRECTED INSERT statement to match the columns you need to add
    $insert_stmt = $conn->prepare("INSERT INTO assignments (teacher_id, school_id, standard, subject, title, description, due_date, file_path, original_filename) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("iisssssss", $userId, $schoolId, $standard, $subject, $title, $description, $due_date, $filePathForDB, $originalFilename);
    $insert_stmt->execute();
    $insert_stmt->close();

    header("Location: assignment_history.php?success=1");
    exit();
}
$pageTitle = 'Send Assignment';
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
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send New Assignment</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Assignment Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="standard">For Standard</label>
                                        <select class="form-control" id="standard" name="standard" required>
                                            <option value="">-- Select Standard --</option>
                                            <?php foreach ($availableStandards as $std): ?>
                                            <option value="<?php echo htmlspecialchars(trim($std)); ?>">Standard <?php echo htmlspecialchars(trim($std)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="subject">Subject</label>
                                        <select class="form-control" id="subject" name="subject" required>
                                            <option value="">-- Select Subject --</option>
                                            <?php foreach ($availableSubjects as $sub): ?>
                                            <option value="<?php echo htmlspecialchars(trim($sub)); ?>"><?php echo htmlspecialchars(trim($sub)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="title">Assignment Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label for="description">Description / Instructions</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="due_date">Due Date</label>
                                        <input type="date" class="form-control" id="due_date" name="due_date" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="assignment_file">Attach File (Optional)</label>
                                        <input type="file" class="form-control-file" id="assignment_file" name="assignment_file">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Send Assignment</button>
                            </form>
                        </div>
                    </div>
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