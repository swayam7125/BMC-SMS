<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
include_once '../../includes/log_system.php'; // Log system included

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacher_id = $userId;
$school_id = null;
$teacher_standards = [];
$past_notes = [];
$success_msg = '';
$error_msg = '';

try {
    // Fetch teacher's school_id and assigned standards
    $stmt_teacher = $conn->prepare("SELECT school_id, std FROM teacher WHERE id = ?");
    $stmt_teacher->execute([$teacher_id]);
    $teacher_info = $stmt_teacher->fetch(PDO::FETCH_ASSOC);
    if ($teacher_info) {
        $school_id = $teacher_info['school_id'];
        $teacher_standards = $teacher_info['std'] ? explode(',', trim($teacher_info['std'], '{}')) : [];
    } else {
        die("Could not retrieve teacher information.");
    }

    // Handle form submission to send new notes
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $standards = $_POST['standards'] ?? [];
        $file_path = null;
        $original_filename = null;

        if (empty($title) || empty($content) || empty($standards)) {
            $error_msg = "Title, content, and at least one standard are required.";
        } else {
            if (isset($_FILES['notes_file']) && $_FILES['notes_file']['error'] == 0) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $filename = 'note_' . uniqid() . '_' . basename($_FILES['notes_file']['name']);
                $file_path = $upload_dir . $filename;
                $original_filename = basename($_FILES['notes_file']['name']);
                if (!move_uploaded_file($_FILES['notes_file']['tmp_name'], $file_path)) {
                    $error_msg = "Failed to upload file.";
                    $file_path = null;
                    $original_filename = null;
                }
                 $file_path = "/BMC-SMS/pages/teacher/" . $file_path;
            }

            if (empty($error_msg)) {
                $conn->beginTransaction();
                // Insert notes for each selected standard
                $stmt = $conn->prepare(
                    "INSERT INTO notes (user_id, school_id, target_standard, title, content, file_path, original_filename) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                foreach ($standards as $standard) {
                    $stmt->execute([$teacher_id, $school_id, $standard, $title, $content, $file_path, $original_filename]);
                }
                
                // Notify students in the selected standards
                $placeholders = implode(',', array_fill(0, count($standards), '?'));
                $stmt_students = $conn->prepare("SELECT id FROM student WHERE school_id = ? AND std IN ($placeholders)");
                $stmt_students->execute(array_merge([$school_id], $standards));
                $student_ids = $stmt_students->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($student_ids)) {
                    $notification_msg = "New notes available: " . htmlspecialchars($title);
                    $notification_link = "pages/student/view_notes.php";
                    $notification_type = "notes";
                    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
                    foreach ($student_ids as $student_id) {
                        $stmt_notify->execute([$student_id, $notification_msg, $notification_link, $notification_type]);
                    }
                }
                
                $conn->commit();
                $success_msg = "Notes have been successfully sent!";
                $standards_string = implode(', ', $standards);
                log_interaction($role, $userId, "NOTES: Sent notes titled '{$title}' to Standard(s): {$standards_string}.", $userName);
            }
        }
    }

    // Fetch past notes sent by the teacher
    $stmt_notes = $conn->prepare(
        "SELECT title, string_agg(target_standard, ', ') as standards, created_at, file_path
         FROM notes 
         WHERE user_id = ? 
         GROUP BY title, content, created_at, file_path
         ORDER BY created_at DESC"
    );
    $stmt_notes->execute([$teacher_id]);
    $past_notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $error_msg = "Database Error: " . $e->getMessage();
    log_interaction($role, $userId, "NOTES ERROR: An error occurred on the Send Notes page. DB Error: " . $e->getMessage(), $userName);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Notes</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>
<body id="page-top">
    <div id="wrapper">
<?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?>        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
<?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send Notes to Students</h1>
                    <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
                    <?php if ($error_msg): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Compose Notes</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="title">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label for="content">Content *</label>
                                    <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Send to Standards *</label>
                                    <div>
                                        <?php foreach ($teacher_standards as $standard): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="standards[]" id="std_<?php echo htmlspecialchars($standard); ?>" value="<?php echo htmlspecialchars($standard); ?>">
                                                <label class="form-check-label" for="std_<?php echo htmlspecialchars($standard); ?>"><?php echo htmlspecialchars($standard); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="notes_file">Attach File (Optional)</label>
                                    <input type="file" class="form-control-file" id="notes_file" name="notes_file">
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Notes</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Past Notes History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date Sent</th>
                                            <th>Title</th>
                                            <th>Sent To (Standards)</th>
                                            <th>Attachment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($past_notes)): ?>
                                            <tr><td colspan="4" class="text-center">You have not sent any notes yet.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($past_notes as $note): ?>
                                                <tr>
                                                    <td><?php echo date('d-M-Y h:i A', strtotime($note['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($note['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($note['standards']); ?></td>
                                                    <td>
                                                        <?php if ($note['file_path']): ?>
                                                            <a href="<?php echo htmlspecialchars($note['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">
                                                                <i class="fas fa-download"></i> View
                                                            </a>
                                                        <?php else: ?>
                                                            No Attachment
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
<?php
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?>        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                 "order": [[ 0, "desc" ]] // Sort by the first column (Date Sent) in descending order
            });
        });
    </script>
</body>
</html>