<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';
include_once '../../includes/log_system.php'; // Log system included

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

$principal_id = $userId;
$school_id = null;
$success_msg = '';
$error_msg = '';
$past_timetables = [];

try {
    // Fetch principal's school_id
    $stmt_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt_school->execute([$principal_id]);
    $school_id = $stmt_school->fetchColumn();
    if (!$school_id) {
        die("Could not retrieve principal's school information.");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        
        if (empty($title)) {
            $error_msg = "Title is required.";
        } elseif (isset($_FILES['timetable_file']) && $_FILES['timetable_file']['error'] == 0) {
            $upload_dir = '../../uploads/timetables/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $filename = 'examtt_' . uniqid() . '_' . basename($_FILES['timetable_file']['name']);
            $file_path = $upload_dir . $filename;
            $original_filename = basename($_FILES['timetable_file']['name']);

            if (move_uploaded_file($_FILES['timetable_file']['tmp_name'], $file_path)) {
                $full_file_path = "/BMC-SMS/uploads/timetables/" . $filename;

                $conn->beginTransaction();
                $stmt = $conn->prepare("INSERT INTO exam_timetables (principal_id, school_id, title, description, file_path, original_filename) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$principal_id, $school_id, $title, $description, $full_file_path, $original_filename]);

                // Notify all teachers and students in the school
                $stmt_users = $conn->prepare("(SELECT id FROM teacher WHERE school_id = ?) UNION (SELECT id FROM student WHERE school_id = ?)");
                $stmt_users->execute([$school_id, $school_id]);
                $user_ids = $stmt_users->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($user_ids)) {
                    $notification_msg = "New Exam Timetable: " . htmlspecialchars($title);
                    $notification_link_teacher = "pages/teacher/view_exam_timetable.php";
                    $notification_link_student = "pages/student/view_exam_timetable.php";
                    $notification_type = "exam_timetable";
                    
                    $stmt_notify_teacher = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
                    $stmt_notify_student = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
                    
                    // Fetch roles to send correct links
                    $stmt_roles = $conn->prepare("SELECT id, role FROM users WHERE id = ANY(?)");
                    $stmt_roles->execute([$user_ids]);
                    $users_with_roles = $stmt_roles->fetchAll(PDO::FETCH_KEY_PAIR);

                    foreach ($user_ids as $user_id) {
                        if (isset($users_with_roles[$user_id])) {
                            if ($users_with_roles[$user_id] == 'teacher') {
                                $stmt_notify_teacher->execute([$user_id, $notification_msg, $notification_link_teacher, $notification_type]);
                            } elseif ($users_with_roles[$user_id] == 'student') {
                                $stmt_notify_student->execute([$user_id, $notification_msg, $notification_link_student, $notification_type]);
                            }
                        }
                    }
                }
                $conn->commit();
                $success_msg = "Exam timetable sent successfully!";
                // Log the action
                log_interaction($role, $userId, "EXAM TIMETABLE: Sent exam timetable titled '{$title}'.", $userName);

            } else {
                $error_msg = "Failed to upload file.";
                log_interaction($role, $userId, "EXAM TIMETABLE ERROR: File upload failed for timetable titled '{$title}'.", $userName);
            }
        } else {
            $error_msg = "File upload is required.";
        }
    }
    
    // Fetch past timetables
    $stmt_past = $conn->prepare("SELECT title, description, original_filename, file_path, created_at FROM exam_timetables WHERE principal_id = ? ORDER BY created_at DESC");
    $stmt_past->execute([$principal_id]);
    $past_timetables = $stmt_past->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $error_msg = "Database Error: " . $e->getMessage();
    log_interaction($role, $userId, "EXAM TIMETABLE ERROR: A database error occurred. " . $e->getMessage(), $userName);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Exam Timetable</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Send Exam Timetable</h1>
                    <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
                    <?php if ($error_msg): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Upload Timetable</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="title">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Final Exam Timetable">
                                </div>
                                <div class="form-group">
                                    <label for="description">Description (Optional)</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="timetable_file">Attach Timetable File (PDF, JPG, PNG) *</label>
                                    <input type="file" class="form-control-file" id="timetable_file" name="timetable_file" required>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Timetable</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Past Exam Timetables</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date Sent</th>
                                            <th>Title</th>
                                            <th>File Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($past_timetables as $timetable): ?>
                                            <tr>
                                                <td><?php echo date('d-M-Y h:i A', strtotime($timetable['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($timetable['title']); ?></td>
                                                <td><?php echo htmlspecialchars($timetable['original_filename']); ?></td>
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($timetable['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">
                                                        <i class="fas fa-download"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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
    <?php include_once "../../includes/logout_modal.php"; ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [[ 0, "desc" ]]
            });
        });
    </script>
</body>
</html>