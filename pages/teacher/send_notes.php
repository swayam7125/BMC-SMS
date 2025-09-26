<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/ajax_helpers.php";
// include_once "../../includes/email_functions.php"; // Uncomment if email is set up

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if this is an AJAX request
if (is_ajax_request()) {
    // Start output buffering to capture the HTML
    ob_start();
}

$role = null;
$userId = null;
$schoolId = null;
$senderName = 'the school';
$availableStandards = [];
$notesHistory = [];

if (isset($_COOKIE['encrypted_user_role'])) $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id'])) $userId = decrypt_id($_COOKIE['encrypted_user_id']);

if (!$role || !$userId || !in_array($role, ['teacher', 'principal'])) {
    header("Location: ../../login.php");
    exit;
}

try {
    switch ($role) {
        case 'teacher':
            $stmt = $conn->prepare("SELECT school_id, std, teacher_name FROM teacher WHERE id = ?");
            $stmt->execute([$userId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $schoolId = $row['school_id'];
                $senderName = $row['teacher_name'];
                if (!empty($row['std'])) $availableStandards = explode(',', $row['std']);
            }
            break;
        case 'principal':
            $stmt = $conn->prepare("SELECT school_id, principal_name FROM principal WHERE id = ?");
            $stmt->execute([$userId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $schoolId = $row['school_id'];
                $senderName = $row['principal_name'];
                // PostgreSQL Change: Cast to INTEGER for proper sorting
                $std_stmt = $conn->prepare("SELECT DISTINCT std FROM student WHERE school_id = ? ORDER BY CAST(std AS INTEGER)");
                $std_stmt->execute([$schoolId]);
                $availableStandards = $std_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            }
            break;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_note'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $target_standard = $_POST['target_standard'];
        $filePathForDB = null;
        $originalFilename = null;

        if (empty($target_standard)) {
            die("Error: Please select a standard.");
        }

        if (isset($_FILES['note_file']) && $_FILES['note_file']['error'] == 0) {
            $originalFilename = basename($_FILES["note_file"]["name"]);
            $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/teacher/uploads/notes/';
            $uploadDirWeb = '/pages/teacher/uploads/notes/';

            if (!is_dir($uploadDirServer)) {
                mkdir($uploadDirServer, 0777, true);
            }
            $storageFilename = uniqid('note_', true) . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $originalFilename);
            $serverFilePath = $uploadDirServer . $storageFilename;
            if (move_uploaded_file($_FILES["note_file"]["tmp_name"], $serverFilePath)) {
                $filePathForDB = $uploadDirWeb . $storageFilename;
            }
        }

        $conn->beginTransaction();

        $stmt_insert = $conn->prepare("INSERT INTO notes (user_id, school_id, target_standard, title, content, file_path, original_filename) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->execute([$userId, $schoolId, $target_standard, $title, $content, $filePathForDB, $originalFilename]);

        $stmt_students = $conn->prepare("SELECT id, student_name, email FROM student WHERE school_id = ? AND std = ?");
        $stmt_students->execute([$schoolId, $target_standard]);
        $students_to_notify = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

        if ($students_to_notify) {
            $notification_message = "New notes posted: " . substr($title, 0, 40) . "...";
            $notification_link = "pages/student/view_notes.php";
            $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, 'new_notes')");
            foreach ($students_to_notify as $student) {
                $stmt_notify->execute([$student['id'], $notification_message, $notification_link]);
                // Email logic would go here
            }
        }

        $conn->commit();
        header("Location: send_notes.php?success=1");
        exit();
    }

    $stmt_history = $conn->prepare("SELECT title, target_standard, created_at FROM notes WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt_history->execute([$userId]);
    $notesHistory = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Send Notes Error: " . $e->getMessage());
    die("A database error occurred.");
}

$pageTitle = 'Send Notes';
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
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?> <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send a Note</h1>
                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Note sent successfully!</div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">New Note Details</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="send_notes.php" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label for="target_standard">Send to Standard</label>
                                            <select class="form-control" id="target_standard" name="target_standard"
                                                required>
                                                <option value="">-- Select a Standard --</option>
                                                <?php foreach ($availableStandards as $standard): ?>
                                                <option value="<?php echo htmlspecialchars(trim($standard)); ?>">
                                                    Standard <?php echo htmlspecialchars(trim($standard)); ?></option>
                                                <?php endforeach; ?>
                                                <?php if (empty($availableStandards)): ?>
                                                <option disabled>No standards available.</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="form-group"><label for="title">Title</label><input type="text"
                                                class="form-control" id="title" name="title" required></div>
                                        <div class="form-group"><label for="content">Content</label><textarea
                                                class="form-control" id="content" name="content" rows="4"
                                                required></textarea></div>
                                        <div class="form-group"><label for="note_file">Attach File
                                                (Optional)</label><input type="file" class="form-control-file"
                                                id="note_file" name="note_file"></div>
                                        <button type="submit" name="send_note" class="btn btn-primary">Send
                                            Note</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Sent Notes History (Last 5)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>For Standard</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($notesHistory)): foreach ($notesHistory as $note): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($note['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($note['target_standard']); ?></td>
                                                    <td><?php echo date('d-m-Y H:i', strtotime($note['created_at'])); ?>
                                                    </td>

                                                </tr>
                                                <?php endforeach;
                                                else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No notes sent yet.</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
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
    if (preg_match('/<div class="container-fluid".*?>(.*?)<\ /div>/s', $content, $matches)) {
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