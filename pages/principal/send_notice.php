<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;
$schoolId = null;
$availableStandards = [];
$availableTeachers = [];

// Get user info from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security check
if ($role !== 'schooladmin' || !$userId) {
    header("Location: ../login.php");
    exit;
}

// Get School ID, all available standards, and all teachers for the school
$stmt_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
$stmt_school->bind_param("i", $userId);
$stmt_school->execute();
$result_school = $stmt_school->get_result();
if ($row_school = $result_school->fetch_assoc()) {
    $schoolId = $row_school['school_id'];

    // Fetch standards
    $std_stmt = $conn->prepare("SELECT DISTINCT std FROM student WHERE school_id = ? ORDER BY std");
    $std_stmt->bind_param("i", $schoolId);
    $std_stmt->execute();
    $std_result = $std_stmt->get_result();
    while ($std_row = $std_result->fetch_assoc()) {
        $availableStandards[] = $std_row['std'];
    }
    $std_stmt->close();

    // Fetch teachers
    $teacher_stmt = $conn->prepare("SELECT id, teacher_name FROM teacher WHERE school_id = ? ORDER BY teacher_name");
    $teacher_stmt->bind_param("i", $schoolId);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();
    while ($teacher_row = $teacher_result->fetch_assoc()) {
        $availableTeachers[] = $teacher_row;
    }
    $teacher_stmt->close();
}
$stmt_school->close();

// --- FORM PROCESSING ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_notice'])) {

    if (empty($_POST['send_to_group'])) {
        die("Error: Please select a recipient group from the 'Send To' dropdown.");
    }

    $title = $_POST['title'];
    $content = $_POST['content'];
    $send_to_group = $_POST['send_to_group'];

    // --- FILE UPLOAD ---
    $filePathForDB = null;
    $originalFilename = null;
    if (isset($_FILES['notice_file']) && $_FILES['notice_file']['error'] == 0) {
        $originalFilename = basename($_FILES["notice_file"]["name"]);
        $uploadDirServer = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/pages/principal/uploads/';
        $uploadDirWeb = '/BMC-SMS/pages/principal/uploads/';
        if (!is_dir($uploadDirServer)) mkdir($uploadDirServer, 0777, true);

        $storageFilename = uniqid('notice_', true) . '_' . $originalFilename;
        $serverFilePath = $uploadDirServer . $storageFilename;
        if (move_uploaded_file($_FILES["notice_file"]["tmp_name"], $serverFilePath)) {
            $filePathForDB = $uploadDirWeb . $storageFilename;
        }
    }

    // --- DATABASE INSERTION ---
    $conn->begin_transaction();
    try {
        // 1. Insert the notice content
        $stmt_content = $conn->prepare("INSERT INTO school_notices_content (user_id, school_id, title, content, file_path, original_filename) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_content->bind_param("iissss", $userId, $schoolId, $title, $content, $filePathForDB, $originalFilename);
        $stmt_content->execute();
        $noticeId = $conn->insert_id;
        $stmt_content->close();

        // --- FIX STARTS HERE ---

        // 2. Prepare the recipient statement ONCE.
        $stmt_recipient = $conn->prepare("INSERT INTO school_notice_recipients (notice_id, recipient_type, recipient_identifier) VALUES (?, ?, ?)");

        // 3. Define variables that will be bound by reference.
        $recipient_type = '';
        $recipient_identifier = '';
        $stmt_recipient->bind_param("iss", $noticeId, $recipient_type, $recipient_identifier);

        // 4. Loop and execute with the updated variables.
        if ($send_to_group == 'both') {
            // Send to ALL teachers
            $recipient_type = 'teacher';
            foreach ($availableTeachers as $teacher) {
                $recipient_identifier = $teacher['id'];
                $stmt_recipient->execute();
            }
            // Send to ALL standards
            $recipient_type = 'standard';
            foreach ($availableStandards as $standard) {
                $recipient_identifier = $standard;
                $stmt_recipient->execute();
            }
        } elseif ($send_to_group == 'teacher' && !empty($_POST['teacher_ids'])) {
            $recipient_type = 'teacher';
            if (in_array('all', $_POST['teacher_ids'])) {
                foreach ($availableTeachers as $teacher) {
                    $recipient_identifier = $teacher['id'];
                    $stmt_recipient->execute();
                }
            } else {
                foreach ($_POST['teacher_ids'] as $teacher_id) {
                    $recipient_identifier = $teacher_id;
                    $stmt_recipient->execute();
                }
            }
        } elseif ($send_to_group == 'student' && !empty($_POST['standard_ids'])) {
            $recipient_type = 'standard';
            if (in_array('all', $_POST['standard_ids'])) {
                foreach ($availableStandards as $standard) {
                    $recipient_identifier = $standard;
                    $stmt_recipient->execute();
                }
            } else {
                foreach ($_POST['standard_ids'] as $standard_id) {
                    $recipient_identifier = $standard_id;
                    $stmt_recipient->execute();
                }
            }
        }

        // --- FIX ENDS HERE ---

        $stmt_recipient->close();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Failed to send notice: " . $e->getMessage());
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
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400i,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">

    <style>
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #d1d3e2;
            height: auto;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send a Notice</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">New Notice</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="send_notice.php" enctype="multipart/form-data">

                                <div class="form-group">
                                    <label for="send_to_group">Send To</label>
                                    <select class="form-control" id="send_to_group" name="send_to_group" required>
                                        <option value="">-- Select a Group --</option>
                                        <option value="both">Both (All Teachers & All Students)</option>
                                        <option value="teacher">Teacher</option>
                                        <option value="student">Student</option>
                                    </select>
                                </div>

                                <div class="form-group" id="teacher_group" style="display:none;">
                                    <label for="teacher_ids">Select Teachers</label>
                                    <select class="form-control multi-select" id="teacher_ids" name="teacher_ids[]" multiple="multiple">
                                        <option value="all">All Teachers</option>
                                        <?php foreach ($availableTeachers as $teacher): ?>
                                            <option value="<?php echo htmlspecialchars($teacher['id']); ?>"><?php echo htmlspecialchars($teacher['teacher_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group" id="student_group" style="display:none;">
                                    <label for="standard_ids">Select Standards</label>
                                    <select class="form-control multi-select" id="standard_ids" name="standard_ids[]" multiple="multiple">
                                        <option value="all">All Standards</option>
                                        <?php foreach ($availableStandards as $standard): ?>
                                            <option value="<?php echo htmlspecialchars($standard); ?>">Standard <?php echo htmlspecialchars($standard); ?></option>
                                        <?php endforeach; ?>
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
                                    <label for="notice_file">Attach File (Optional)</label>
                                    <input type="file" class="form-control-file" id="notice_file" name="notice_file">
                                </div>
                                <button type="submit" name="send_notice" class="btn btn-primary">Send Notice</button>
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
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize all multi-select dropdowns with Select2
            $('.multi-select').select2({
                placeholder: "Select one or more options",
                allowClear: true
            });

            // Handle showing/hiding the specific recipient dropdowns
            $('#send_to_group').on('change', function() {
                var selectedGroup = $(this).val();
                var teacherGroup = $('#teacher_group');
                var studentGroup = $('#student_group');

                switch (selectedGroup) {
                    case 'both':
                        teacherGroup.hide();
                        studentGroup.hide();
                        break;
                    case 'teacher':
                        teacherGroup.show();
                        studentGroup.hide();
                        break;
                    case 'student':
                        teacherGroup.hide();
                        studentGroup.show();
                        break;
                    default:
                        teacherGroup.hide();
                        studentGroup.hide();
                }
            });

            function handleAllSelection(selector) {
                $(selector).on('change', function() {
                    var selected = $(this).val() || [];
                    if (selected.includes('all') && selected.length > 1) {
                        $(this).val('all').trigger('change.select2');
                    }
                });
            }

            // Apply the 'all' selection logic to both dropdowns
            handleAllSelection('#teacher_ids');
            handleAllSelection('#standard_ids');
        });
    </script>
</body>

</html>