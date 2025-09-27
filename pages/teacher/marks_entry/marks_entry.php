<?php
include_once "../../../includes/connect.php";
include_once "../../../encryption.php";
include_once "../../../includes/ajax_helpers.php";

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if this is an AJAX request
if (is_ajax_request()) {
    // Start output buffering to capture the HTML
    ob_start();
}

$role = null;
$teacher_id = null;
$class_teacher_std = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $teacher_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'teacher' || !$teacher_id) {
    header("Location: ../../../login.php");
    exit;
}

try {
    // FIX: Changed B'1' to TRUE for PostgreSQL boolean check.
    $query = "SELECT class_teacher_std FROM teacher WHERE id = ? AND class_teacher = TRUE";
    $stmt = $conn->prepare($query);
    $stmt->execute([$teacher_id]);

    if ($stmt->rowCount() > 0) {
        $teacher_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $class_teacher_std = $teacher_data['class_teacher_std'];
    } else {
        header("Location: ../../../dashboard.php?error=Access denied. Only class teachers can enter marks.");
        exit;
    }
} catch (PDOException $e) {
    error_log("Marks Entry Auth Error: " . $e->getMessage());
    die("A database error occurred.");
}


$current_year = date('Y');
$academic_year_suggestion = $current_year . '-' . ($current_year + 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Marks Entry - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/table-to-card.css">
</head>

<body id="page-top" data-class-std="<?php echo htmlspecialchars($class_teacher_std); ?>">
    <div id="wrapper">
        <?php
if (!$is_ajax_request) {
    include '../../../includes/sidebar.php';
}
?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
<?php
if (!$is_ajax_request) {
    include '../../../includes/header.php';
}
?>
?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Marks Entry: Class <?php echo htmlspecialchars($class_teacher_std); ?></h1>
                        <a href="view_marks.php" class="btn btn-info btn-sm">
                            <i class="fas fa-file-alt fa-sm"></i> View Marks Report
                        </a>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Exam and Academic Year</h6>
                        </div>
                        <div class="card-body">
                            <div id="message-container"></div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label for="exam_type">Exam Type *</label>
                                    <select class="form-control" id="exam_type" name="exam_type">
                                        <option value="">-- Select Exam --</option>
                                        <option value="term_1">Term 1</option>
                                        <option value="term_2">Term 2</option>
                                        <?php
                                        $final_exam_disabled = (in_array($class_teacher_std, ['10', '12'])) ? 'disabled' : '';
                                        ?>
                                        <option value="final_exam" <?php echo $final_exam_disabled; ?>>
                                            Final Exam <?php if ($final_exam_disabled) echo '(Not Applicable)'; ?>
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-5">
                                    <label for="academic_year">Academic Year *</label>
                                    <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo $academic_year_suggestion; ?>">
                                </div>
                                <div class="form-group col-md-2 d-flex align-items-end">
                                    <button type="button" id="loadStudentsBtn" class="btn btn-info btn-block">
                                        <i class="fas fa-search mr-1"></i> Load
                                    </button>
                                </div>
                            </div>
                            <hr>
                            <form id="marksForm">
                                <input type="hidden" name="class_std" value="<?php echo htmlspecialchars($class_teacher_std); ?>">
                                <input type="hidden" name="exam_type_hidden" id="exam_type_hidden">
                                <input type="hidden" name="academic_year_hidden" id="academic_year_hidden">

                                <div class="table-responsive" id="marks-table-container" style="display:none;">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead id="marks-table-header"></thead>
                                        <tbody id="students-list-body"></tbody>
                                    </table>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Save Marks</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
<?php
if (!$is_ajax_request) {
    include '../../../includes/footer.php';
}
?>        </div>
    </div>
    <?php include_once "../../../includes/logout_modal.php" ?>
    <script src="../../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../../assets/js/custom_marks_scripts.js"></script>
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