<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";
include_once "../../includes/ajax_helpers.php";

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// --- Authorization & Initialization ---
$role = null;
$userId = null;
$schoolId = null;
$teacherName = 'Teacher';
$availableStandards = [];
$availableSubjects = [];
$errors = [];
$successMessage = '';

if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$role || !$userId || $role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

try {
    // Fetch teacher's details (school, name, standards, subjects)
    $stmt_teacher_info = $conn->prepare('SELECT "school_id", "teacher_name", "std", "subject" FROM "teacher" WHERE "id" = ?');
    $stmt_teacher_info->execute([$userId]);
    if ($row = $stmt_teacher_info->fetch(PDO::FETCH_ASSOC)) {
        $schoolId = $row['school_id'];
        $teacherName = $row['teacher_name'];
        if (!empty($row['std'])) {
            $availableStandards = explode(',', trim($row['std'], '{}'));
        }
        if (!empty($row['subject'])) {
            $availableSubjects = explode(',', trim($row['subject'], '{}'));
        }
    }

    // --- Form Submission Handling ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $std = filter_input(INPUT_POST, 'std', FILTER_SANITIZE_STRING);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $due_date = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);
        $file_path = null;

        // File Upload Handling
        if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == 0) {
            $upload_dir = '../../uploads/assignments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = time() . '_' . basename($_FILES['assignment_file']['name']);
            $file_path = 'uploads/assignments/' . $file_name;
            if (!move_uploaded_file($_FILES['assignment_file']['tmp_name'], '../../' . $file_path)) {
                $errors[] = "Sorry, there was an error uploading your file.";
                $file_path = null;
            }
        }

        if (empty($errors)) {
            $stmt = $conn->prepare(
                'INSERT INTO "assignments" ("school_id", "teacher_id", "title", "description", "std", "subject", "due_date", "file_path")
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$schoolId, $userId, $title, $description, $std, $subject, $due_date, $file_path]);

            // Create notifications for all students in the selected standard
            $stmt_students = $conn->prepare('SELECT "id" FROM "student" WHERE "std" = ? AND "school_id" = ?');
            $stmt_students->execute([$std, $schoolId]);
            $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

            if ($students) {
                $notification_message = "New assignment posted: " . $title;
                $notification_link = "pages/assignments/view_assignments.php";
                $notification_type = "new_assignment";
                $stmt_notify = $conn->prepare(
                    'INSERT INTO "notifications" ("user_id", "user_role", "message", "link", "type")
                     VALUES (?, \'student\', ?, ?, ?)'
                );
                foreach ($students as $student) {
                    $stmt_notify->execute([$student['id'], $notification_message, $notification_link, $notification_type]);
                }
            }
            $successMessage = "Assignment has been successfully sent!";
        }
    }
} catch (PDOException $e) {
    error_log("Database Error in send_assignment.php: " . $e->getMessage());
    $errors[] = "A database error occurred. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Send New Assignment</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css" />
</head>

<body id="page-top">
    <div id="wrapper">
        <?php if (!$is_ajax_request) {
            include '../../includes/sidebar.php';
        } ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php if (!$is_ajax_request) {
                    include '../../includes/header.php';
                } ?>
                <div id="main-content">

                    <div class="container-fluid">
                        <h1 class="h3 mb-4 text-gray-800">Send New Assignment</h1>

                        <?php if (!empty($successMessage)): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <form action="" method="post" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="title">Assignment Title</label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="std">Standard</label>
                                            <select class="form-control" id="std" name="std" required>
                                                <option value="">Select Standard...</option>
                                                <?php foreach ($availableStandards as $standard): ?>
                                                    <option value="<?php echo htmlspecialchars($standard); ?>"><?php echo htmlspecialchars($standard); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="subject">Subject</label>
                                            <select class="form-control" id="subject" name="subject" required>
                                                <option value="">Select Subject...</option>
                                                <?php foreach ($availableSubjects as $subject): ?>
                                                    <option value="<?php echo htmlspecialchars($subject); ?>"><?php echo htmlspecialchars($subject); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="due_date">Due Date</label>
                                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Attach File (Optional)</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="assignment_file" name="assignment_file">
                                            <label class="custom-file-label" for="assignment_file">Choose file...</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane mr-2"></i>Send Assignment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!$is_ajax_request) {
                include '../../includes/footer.php';
            } ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>

    <script src="../../assets/js/send_assignment.js"></script>
</body>

</html>
<?php
$conn = null;
?>