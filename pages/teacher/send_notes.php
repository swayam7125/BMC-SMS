<?php
session_start();
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

if (!$role || !$userId) {
    header("Location: ./login.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_note'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $filePath = null;

    if (isset($_FILES['note_file']) && $_FILES['note_file']['error'] == 0) {
        $targetDir = "uploads/notes/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = basename($_FILES["note_file"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["note_file"]["tmp_name"], $targetFilePath)) {
            $filePath = $targetFilePath;
        }
    }

    $stmt = $conn->prepare("INSERT INTO notes (user_id, title, content, file_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $title, $content, $filePath);
    $stmt->execute();
    $stmt->close();

    // Redirect to avoid form resubmission
    header("Location: send_notes.php");
    exit();
}

// Fetch last 5 notes
$notesHistory = [];
$stmt = $conn->prepare("SELECT title, content, file_path, created_at FROM notes WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notesHistory[] = $row;
}
$stmt->close();

$pageTitle = 'Send Notes';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
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
                                                    <th>Date</th>
                                                    <th>File</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($notesHistory as $note): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($note['title']); ?></td>
                                                        <td><?php echo date('d-m-Y H:i', strtotime($note['created_at'])); ?></td>
                                                        <td>
                                                            <?php if ($note['file_path']): ?>
                                                                <a href="<?php echo htmlspecialchars($note['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">View</a>
                                                            <?php else: ?>
                                                                No File
                                                            <?php endif; ?>
                                                        </td>
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
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>