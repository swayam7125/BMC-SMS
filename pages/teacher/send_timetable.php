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
$class_teacher_std = null;
$success_msg = '';
$error_msg = '';
$past_timetables = [];

try {
    // Fetch teacher's school_id and class teacher standard
    $stmt_teacher = $conn->prepare("SELECT school_id, class_teacher_std FROM teacher WHERE id = ? AND class_teacher = TRUE");
    $stmt_teacher->execute([$teacher_id]);
    $teacher_info = $stmt_teacher->fetch(PDO::FETCH_ASSOC);

    if ($teacher_info) {
        $school_id = $teacher_info['school_id'];
        $class_teacher_std = $teacher_info['class_teacher_std'];
    } else {
        // This page is only for class teachers, so if no standard is assigned, they can't use it.
        $error_msg = "You are not assigned as a class teacher for any standard. This feature is unavailable.";
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $class_teacher_std) {
        $standard = $_POST['standard'] ?? null;
        $file_path = null;
        $original_filename = null;

        if (empty($standard)) {
            $error_msg = "Standard is a required field.";
        } elseif ($standard !== $class_teacher_std) {
            $error_msg = "You are not authorized to send timetables for the selected standard.";
        } else {
            if (isset($_FILES['timetable_file']) && $_FILES['timetable_file']['error'] == 0) {
                $upload_dir = 'uploads/timetables/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $filename = 'tt_' . uniqid() . '_' . basename($_FILES['timetable_file']['name']);
                $file_path = $upload_dir . $filename;
                $original_filename = basename($_FILES['timetable_file']['name']);

                if (move_uploaded_file($_FILES['timetable_file']['tmp_name'], $file_path)) {
                    $full_file_path = "/BMC-SMS/pages/teacher/" . $file_path;
                    
                    $conn->beginTransaction();

                    $stmt = $conn->prepare(
                        "INSERT INTO timetables (school_id, standard, class_teacher_id, timetable_file, original_filename) VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([$school_id, $standard, $teacher_id, $full_file_path, $original_filename]);

                    // Notify students of the standard
                    $stmt_students = $conn->prepare("SELECT id FROM student WHERE school_id = ? AND std = ?");
                    $stmt_students->execute([$school_id, $standard]);
                    $student_ids = $stmt_students->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($student_ids)) {
                        $notification_msg = "A new timetable has been uploaded for your class.";
                        $notification_link = "pages/student/view_timetable.php";
                        $notification_type = "timetable";
                        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
                        foreach ($student_ids as $student_id) {
                            $stmt_notify->execute([$student_id, $notification_msg, $notification_link, $notification_type]);
                        }
                    }

                    $conn->commit();
                    $success_msg = "Timetable sent successfully!";
                    // Log the successful action
                    log_interaction($role, $userId, "TIMETABLE: Sent timetable for Standard {$standard}.", $userName);

                } else {
                    $error_msg = "Failed to upload file.";
                    log_interaction($role, $userId, "TIMETABLE ERROR: File upload failed for Standard {$standard}.", $userName);
                }
            } else {
                $error_msg = "File upload is required.";
            }
        }
    }

    // Fetch past timetables sent by this teacher
    if ($class_teacher_std) {
        $stmt_past = $conn->prepare("SELECT standard, original_filename, timetable_file, created_at FROM timetables WHERE class_teacher_id = ? ORDER BY created_at DESC");
        $stmt_past->execute([$teacher_id]);
        $past_timetables = $stmt_past->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $error_msg = "Database Error: " . $e->getMessage();
    log_interaction($role, $userId, "TIMETABLE ERROR: An error occurred on the Send Timetable page. DB Error: " . $e->getMessage(), $userName);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Timetable</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Send Timetable to Class</h1>
                    <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
                    <?php if ($error_msg): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
                    
                    <?php if ($class_teacher_std): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Upload Timetable</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="standard">Standard</label>
                                    <input type="text" class="form-control" id="standard" name="standard" value="<?php echo htmlspecialchars($class_teacher_std); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="timetable_file">Attach Timetable File *</label>
                                    <input type="file" class="form-control-file" id="timetable_file" name="timetable_file" required>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Timetable</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Past Timetable History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date Sent</th>
                                            <th>Standard</th>
                                            <th>File Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($past_timetables)): ?>
                                            <tr><td colspan="4" class="text-center">You have not sent any timetables yet.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($past_timetables as $timetable): ?>
                                                <tr>
                                                    <td><?php echo date('d-M-Y h:i A', strtotime($timetable['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($timetable['standard']); ?></td>
                                                    <td><?php echo htmlspecialchars($timetable['original_filename']); ?></td>
                                                    <td>
                                                        <a href="<?php echo htmlspecialchars($timetable['timetable_file']); ?>" target="_blank" class="btn btn-sm btn-info">
                                                            <i class="fas fa-download"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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