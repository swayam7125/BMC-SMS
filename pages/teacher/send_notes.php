<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;
$schoolId = null;
$availableStandards = [];

// This block is based on your dashboard.php logic to get the logged-in user's info
if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Redirect if user is not properly logged in
if (!$role || !$userId) {
    header("Location: ../login.php");
    exit;
}

// Fetch available standards based on the user's role
switch ($role) {
    case 'teacher':
        // A teacher can send notes to the standards they are assigned to teach
        $stmt = $conn->prepare("SELECT school_id, std FROM teacher WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $schoolId = $row['school_id'];
            if (!empty($row['std'])) {
                $availableStandards = explode(',', $row['std']);
            }
        }
        $stmt->close();
        break;

    case 'schooladmin':
        // A principal can send notes to any standard in their school
        $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $schoolId = $row['school_id'];
            // Fetch all unique standards that have students in that school
            $std_stmt = $conn->prepare("SELECT DISTINCT std FROM student WHERE school_id = ? ORDER BY std");
            $std_stmt->bind_param("i", $schoolId);
            $std_stmt->execute();
            $std_result = $std_stmt->get_result();
            while ($std_row = $std_result->fetch_assoc()) {
                $availableStandards[] = $std_row['std'];
            }
            $std_stmt->close();
        }
        $stmt->close();
        break;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_note'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $target_standard = $_POST['target_standard'];
    $filePathForDB = null;
    $originalFilename = null;

    if (empty($target_standard)) {
        die("Error: Please select a standard to send the note to.");
    }

    if (isset($_FILES['note_file']) && $_FILES['note_file']['error'] == 0) {
        $originalFilename = basename($_FILES["note_file"]["name"]);
        $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/teacher/uploads/';
        $uploadDirWeb = '/BMC-SMS/pages/teacher/uploads/';

        if (!is_dir($uploadDirServer)) {
            mkdir($uploadDirServer, 0777, true);
        }

        $storageFilename = uniqid('note_', true) . '_' . $originalFilename;
        $serverFilePath = $uploadDirServer . $storageFilename;

        if (move_uploaded_file($_FILES["note_file"]["tmp_name"], $serverFilePath)) {
            $filePathForDB = $uploadDirWeb . $storageFilename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO notes (user_id, school_id, target_standard, title, content, file_path, original_filename) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $userId, $schoolId, $target_standard, $title, $content, $filePathForDB, $originalFilename);
    $stmt->execute();
    $stmt->close();

    header("Location: send_notes.php?success=1");
    exit();
}

// Fetch history of the last 5 notes sent by this user
$notesHistory = [];
$stmt_history = $conn->prepare("SELECT title, target_standard, created_at FROM notes WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt_history->bind_param("i", $userId);
$stmt_history->execute();
$result_history = $stmt_history->get_result();
while ($row_history = $result_history->fetch_assoc()) {
    $notesHistory[] = $row_history;
}
$stmt_history->close();
$pageTitle = 'Send Notes';
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
                    <h1 class="h3 mb-4 text-gray-800">Send a Note</h1>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">New Note</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="send_notes.php" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label for="target_standard">Send to Standard</label>
                                            <select class="form-control" id="target_standard" name="target_standard" required>
                                                <option value="">-- Select a Standard --</option>
                                                <?php foreach ($availableStandards as $standard): ?>
                                                    <option value="<?php echo htmlspecialchars(trim($standard)); ?>">
                                                        Standard <?php echo htmlspecialchars(trim($standard)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                <?php if (empty($availableStandards)): ?>
                                                    <option disabled>No standards available for you to send notes to.</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="title">Title</label>
                                            <input type="text" class="form-control" id="title" name="title" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="content">Content</label>
                                            <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="note_file">Attach File (Optional)</label>
                                            <input type="file" class="form-control-file" id="note_file" name="note_file">
                                        </div>
                                        <button type="submit" name="send_note" class="btn btn-primary">Send Note</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
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
                                                <?php foreach ($notesHistory as $note): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($note['title']); ?></td>
                                                        <td><?php echo htmlspecialchars($note['target_standard']); ?></td>
                                                        <td><?php echo date('d-m-Y H:i', strtotime($note['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($notesHistory)): ?>
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