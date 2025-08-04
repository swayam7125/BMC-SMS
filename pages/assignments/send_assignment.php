<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/email_functions.php"; // Include email functions

$role = null;
$userId = null;
$schoolId = null;
$teacherName = 'Teacher';
$availableStandards = [];
$availableSubjects = [];

// Get user info from cookies, ensure user is a teacher
if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$role || !$userId || $role !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

// Fetch the teacher's info (school, name, standards, subject)
$stmt_teacher_info = $conn->prepare("SELECT school_id, teacher_name, std, subject FROM teacher WHERE id = ?");
$stmt_teacher_info->bind_param("i", $userId);
$stmt_teacher_info->execute();
$result_teacher_info = $stmt_teacher_info->get_result();
if ($row = $result_teacher_info->fetch_assoc()) {
    $schoolId = $row['school_id'];
    $teacherName = $row['teacher_name'];
    if (!empty($row['std'])) {
        $availableStandards = explode(',', $row['std']);
    }
    if (!empty($row['subject'])) {
        $availableSubjects = explode(',', $row['subject']);
    }
}
$stmt_teacher_info->close();

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
        $uploadDirServer = __DIR__ . '/uploads/';
        $uploadDirWeb = '/BMC-SMS/pages/assignments/uploads/';

        if (!is_dir($uploadDirServer)) {
            mkdir($uploadDirServer, 0777, true);
        }

        $storageFilename = uniqid('assign_', true) . '_' . $originalFilename;
        $serverFilePath = $uploadDirServer . $storageFilename;

        if (move_uploaded_file($_FILES["assignment_file"]["tmp_name"], $serverFilePath)) {
            $filePathForDB = $uploadDirWeb . $storageFilename;
        }
    }

    $insert_stmt = $conn->prepare("INSERT INTO assignments (teacher_id, school_id, standard, subject, title, description, due_date, file_path, original_filename) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("iisssssss", $userId, $schoolId, $standard, $subject, $title, $description, $due_date, $filePathForDB, $originalFilename);

    if ($insert_stmt->execute()) {
        $assignmentId = $conn->insert_id; // Get the ID of the new assignment

        // --- START: Notification & Email Logic ---
        // Get all students of the target standard
        $stmt_students = $conn->prepare("SELECT id, email, student_name FROM student WHERE school_id = ? AND std = ?");
        $stmt_students->bind_param("is", $schoolId, $standard);
        $stmt_students->execute();
        $result_students = $stmt_students->get_result();

        // Prepare notification components
        $notification_message = "New Assignment: " . substr($title, 0, 50) . "...";
        $notification_link = "/pages/assignments/view_assignments.php";
        $notification_type = "new_assignment";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");

        // Prepare email components
        $email_subject = "New Assignment Posted: " . htmlspecialchars($title);
        $email_content_base = "
            <p>A new assignment has been posted by your teacher, " . htmlspecialchars($teacherName) . ".</p>
            <ul>
                <li><strong>Title:</strong> " . htmlspecialchars($title) . "</li>
                <li><strong>Subject:</strong> " . htmlspecialchars($subject) . "</li>
                <li><strong>Due Date:</strong> " . htmlspecialchars($due_date) . "</li>
            </ul>
            <p><strong>Description:</strong><br>" . nl2br(htmlspecialchars($description)) . "</p>
            <p>Please log in to the portal to view the details and submit your work.</p>
        ";

        while ($student = $result_students->fetch_assoc()) {
            // 1. Send In-App Notification
            $stmt_notify->bind_param("isss", $student['id'], $notification_message, $notification_link, $notification_type);
            $stmt_notify->execute();

            // 2. Send Email
            $email_body = "<p>Dear " . htmlspecialchars($student['student_name']) . ",</p>" . $email_content_base;
            send_email($student['email'], $email_subject, $email_body);
        }
        $stmt_students->close();
        $stmt_notify->close();
        // --- END: Notification & Email Logic ---
    }
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
                    <h1 class="h3 mb-4 text-gray-800">Send New Assignment</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Assignment Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" action="send_assignment.php">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="standard">For Standard</label>
                                        <select class="form-control" id="standard" name="standard" required>
                                            <option value="">-- Select Standard --</option>
                                            <?php foreach ($availableStandards as $std): ?>
                                            <option value="<?php echo htmlspecialchars(trim($std)); ?>">Standard
                                                <?php echo htmlspecialchars(trim($std)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="subject">Subject</label>
                                        <select class="form-control" id="subject" name="subject" required>
                                            <option value="">-- Select Subject --</option>
                                            <?php foreach ($availableSubjects as $sub): ?>
                                            <option value="<?php echo htmlspecialchars(trim($sub)); ?>">
                                                <?php echo htmlspecialchars(trim($sub)); ?></option>
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
                                    <textarea class="form-control" id="description" name="description"
                                        rows="4"></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="due_date">Due Date</label>
                                        <input type="date" class="form-control" id="due_date" name="due_date" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="assignment_file">Attach File (Optional)</label>
                                        <input type="file" class="form-control-file" id="assignment_file"
                                            name="assignment_file">
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
        <?php include_once "../../includes/logout_modal.php"?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>
<?php
$conn->close();
?>