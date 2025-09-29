<?php
// Include necessary files
include_once "../../encryption.php";
include_once "../../includes/connect.php"; // Your PDO connection file
include_once "../../includes/email_functions.php"; // Email functions
include_once "../../includes/ajax_helpers.php";
include_once "../../includes/log_system.php"; // ADDED: Log system dependency

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

// Initialize variables
$role = null;
$userId = null;
$schoolId = null;
$principalName = 'Principal';
$availableStandards = [];
$availableTeachers = [];
$acting_user_name = null; // Initialize for logging

// Get user info from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
$acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'Principal'; // ADDED: Retrieve acting user name

// Security check: ensure user is a principal and logged in
if ($role !== 'principal' || !$userId) {
    header("Location: ../login.php");
    exit;
}

try {
    // Get School ID, principal name, all available standards, and all teachers for the school
    $sql_school = "SELECT school_id, principal_name FROM principal WHERE id = ?";
    $stmt_school = $conn->prepare($sql_school);
    $stmt_school->execute([$userId]);

    if ($row_school = $stmt_school->fetch(PDO::FETCH_ASSOC)) {
        $schoolId = $row_school['school_id'];
        $principalName = $row_school['principal_name'];

        $sql_std = "SELECT std FROM student WHERE school_id = ? GROUP BY std ORDER BY CAST(substring(std from '^\\d+') AS INTEGER)";
        $stmt_std = $conn->prepare($sql_std);
        $stmt_std->execute([$schoolId]);
        while ($std_row = $stmt_std->fetch(PDO::FETCH_ASSOC)) {
            $availableStandards[] = $std_row['std'];
        }

        // Fetch teachers
        $sql_teacher = "SELECT id, teacher_name FROM teacher WHERE school_id = ? ORDER BY teacher_name";
        $stmt_teacher = $conn->prepare($sql_teacher);
        $stmt_teacher->execute([$schoolId]);
        while ($teacher_row = $stmt_teacher->fetch(PDO::FETCH_ASSOC)) {
            $availableTeachers[] = $teacher_row;
        }
    }
} catch (PDOException $e) {
    die("Error fetching initial data: " . $e->getMessage());
}


// --- FORM PROCESSING ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_notice'])) {

    set_time_limit(0); // Allow the script to run as long as needed to send all emails

    if (empty($_POST['send_to_group'])) {
        die("Error: Please select a recipient group from the 'Send To' dropdown.");
    }

    $title = $_POST['title'];
    $content = $_POST['content'];
    $send_to_group = $_POST['send_to_group'];

    $teacher_ids_to_notify = [];
    $standards_to_notify = [];

    // --- FILE UPLOAD ---
    $filePathForDB = null;
    $originalFilename = null;
    if (isset($_FILES['notice_file']) && $_FILES['notice_file']['error'] == 0) {
        $originalFilename = basename($_FILES["notice_file"]["name"]);
        $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/principal/uploads/';
        $uploadDirWeb = '/BMC-SMS/pages/principal/uploads/';
        if (!is_dir($uploadDirServer))
            mkdir($uploadDirServer, 0777, true);

        $storageFilename = uniqid('notice_', true) . '_' . $originalFilename;
        $serverFilePath = $uploadDirServer . $storageFilename;
        if (move_uploaded_file($_FILES["notice_file"]["tmp_name"], $serverFilePath)) {
            $filePathForDB = $uploadDirWeb . $storageFilename;
        }
    }

    // --- DATABASE INSERTION ---
    try {
        $conn->beginTransaction();

        $sql_content = "INSERT INTO school_notices_content (user_id, school_id, title, content, file_path, original_filename) VALUES (?, ?, ?, ?, ?, ?) RETURNING id";
        $stmt_content = $conn->prepare($sql_content);
        $stmt_content->execute([$userId, $schoolId, $title, $content, $filePathForDB, $originalFilename]);
        $noticeId = $stmt_content->fetchColumn();

        $sql_recipient = "INSERT INTO school_notice_recipients (notice_id, recipient_type, recipient_identifier) VALUES (?, ?, ?)";
        $stmt_recipient = $conn->prepare($sql_recipient);

        if ($send_to_group == 'both') {
            foreach ($availableTeachers as $teacher) {
                $stmt_recipient->execute([$noticeId, 'teacher', $teacher['id']]);
                $teacher_ids_to_notify[] = $teacher['id'];
            }
            foreach ($availableStandards as $standard) {
                $stmt_recipient->execute([$noticeId, 'standard', $standard]);
                $standards_to_notify[] = $standard;
            }
        } elseif ($send_to_group == 'teacher' && !empty($_POST['teacher_ids'])) {
            $teacher_ids_to_notify = in_array('all', $_POST['teacher_ids']) ? array_column($availableTeachers, 'id') : $_POST['teacher_ids'];
            foreach ($teacher_ids_to_notify as $teacher_id) {
                $stmt_recipient->execute([$noticeId, 'teacher', $teacher_id]);
            }
        } elseif ($send_to_group == 'student' && !empty($_POST['standard_ids'])) {
            $standards_to_notify = in_array('all', $_POST['standard_ids']) ? $availableStandards : $_POST['standard_ids'];
            foreach ($standards_to_notify as $standard_id) {
                $stmt_recipient->execute([$noticeId, 'standard', $standard_id]);
            }
        }

        $conn->commit();

        // ⭐ LOGGING: Log the notice creation action
        $recipient_count = count($teacher_ids_to_notify) + count($standards_to_notify);
        $recipients = $send_to_group === 'both' ? 'All Teachers & Students' : ($send_to_group === 'teacher' ? 'Teachers' : 'Students/Standards');
        $log_message = "NOTICE SENT: Notice titled '{$title}' sent to {$recipient_count} recipients/groups ({$recipients}).";
        log_interaction($role, $userId, $log_message, $acting_user_name);
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        die("Failed to send notice: " . $e->getMessage());
    }

    // --- NOTIFICATION & EMAIL LOGIC ---
    try {
        $notification_message = "New notice from Principal: " . substr($title, 0, 40) . "...";
        $notification_type = "school_notice";
        $sql_notify = "INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)";
        $stmt_notify = $conn->prepare($sql_notify);

        $email_subject = "New Notice from Principal: " . htmlspecialchars($title);
        $email_content_base = "<p>A new notice titled '<strong>" . htmlspecialchars($title) . "</strong>' has been posted by the principal, " . htmlspecialchars($principalName) . ".</p>"
            . "<p><strong>Content:</strong><br>" . nl2br(htmlspecialchars($content)) . "</p>"
            . "<p>Please log in to the portal for more details.</p>";

        if (!empty($teacher_ids_to_notify)) {
            $placeholders = implode(',', array_fill(0, count($teacher_ids_to_notify), '?'));
            $sql_teachers = "SELECT id, email, teacher_name FROM teacher WHERE id IN ($placeholders)";
            $stmt_teachers = $conn->prepare($sql_teachers);
            $stmt_teachers->execute($teacher_ids_to_notify);
            $notification_link = "pages/teacher/view_notice.php";
            while ($teacher = $stmt_teachers->fetch(PDO::FETCH_ASSOC)) {
                $stmt_notify->execute([$teacher['id'], $notification_message, $notification_link, $notification_type]);
                send_email($teacher['email'], $email_subject, "<p>Dear " . htmlspecialchars($teacher['teacher_name']) . ",</p>" . $email_content_base);
            }
        }

        if (!empty($standards_to_notify)) {
            $placeholders = implode(',', array_fill(0, count($standards_to_notify), '?'));
            $sql_students = "SELECT id, email, student_name FROM student WHERE school_id = ? AND std IN ($placeholders)";
            $stmt_students = $conn->prepare($sql_students);
            $params = array_merge([$schoolId], $standards_to_notify);
            $stmt_students->execute($params);
            $notification_link = "pages/student/view_notice.php";
            while ($student = $stmt_students->fetch(PDO::FETCH_ASSOC)) {
                $stmt_notify->execute([$student['id'], $notification_message, $notification_link, $notification_type]);
                send_email($student['email'], $email_subject, "<p>Dear " . htmlspecialchars($student['student_name']) . ",</p>" . $email_content_base);
            }
        }
    } catch (PDOException $e) {
        error_log("Notification/Email Error: " . $e->getMessage());
    }

    header("Location: send_notice.php?success=1");
    exit();
}

$pageTitle = 'Send School Notice';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/responsive.css" />
    <style>
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #d1d3e2;
            height: auto;
            padding: .375rem .75rem;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php
        if (!$is_ajax_request) {
            include '../../includes/sidebar.php';
        }
        ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
                if (!$is_ajax_request) {
                    include '../../includes/header.php';
                }
                ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send a Notice</h1>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Notice sent successfully!</div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">New Notice Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="send_notice.php" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="send_to_group">Send To</label>
                                    <select class="form-control" id="send_to_group" name="send_to_group" required>
                                        <option value="">-- Select a Group --</option>
                                        <option value="both">Both (All Teachers & All Students)</option>
                                        <option value="teacher">Specific Teachers</option>
                                        <option value="student">Specific Standards</option>
                                    </select>
                                </div>
                                <div class="form-group" id="teacher_group" style="display:none;">
                                    <label for="teacher_ids">Select Teachers</label>
                                    <select class="form-control multi-select" id="teacher_ids" name="teacher_ids[]"
                                        multiple="multiple">
                                        <option value="all">All Teachers</option>
                                        <?php foreach ($availableTeachers as $teacher): ?>
                                            <option value="<?php echo htmlspecialchars($teacher['id']); ?>">
                                                <?php echo htmlspecialchars($teacher['teacher_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" id="student_group" style="display:none;">
                                    <label for="standard_ids">Select Standards</label>
                                    <select class="form-control multi-select" id="standard_ids" name="standard_ids[]"
                                        multiple="multiple">
                                        <option value="all">All Standards</option>
                                        <?php foreach ($availableStandards as $standard): ?>
                                            <option value="<?php echo htmlspecialchars($standard); ?>">Standard
                                                <?php echo htmlspecialchars($standard); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label for="content">Content</label>
                                    <textarea class="form-control" id="content" name="content" rows="4"
                                        required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="notice_file">Attach File (Optional)</label>
                                    <input type="file" class="form-control-file" id="notice_file" name="notice_file">
                                </div>
                                <button type="submit" name="send_notice" class="btn btn-primary">Send Notice</button>
                            </form>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>

    <script>
        $(document).ready(function() {
            $('.multi-select').select2({
                width: '100%'
            });

            $('#send_to_group').on('change', function() {
                var selected = $(this).val();
                $('#teacher_group').hide();
                $('#student_group').hide();
                if (selected === 'teacher') {
                    $('#teacher_group').show();
                } else if (selected === 'student') {
                    $('#student_group').show();
                }
            });
        });
    </script>
</body>
<?php
// Add this block at the very end of the file
if (is_ajax_request()) {
    // Get the captured HTML
    $content = ob_get_clean();

    // Extract just the main content area for the AJAX response
    if (preg_match('/<div class="container-fluid".*?>(.*?)<\/div>/s', $content, $matches)) {
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