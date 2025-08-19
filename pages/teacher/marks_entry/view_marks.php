<?php
include_once "../../../includes/connect.php";
include_once "../../../encryption.php";
include_once "../../../includes/ajax_helpers.php";

$role = null;
$teacher_id = null;
$class_teacher_std = null;

if (isset($_COOKIE['encrypted_user_role'])) $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id'])) $teacher_id = decrypt_id($_COOKIE['encrypted_user_id']);

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
        header("Location: ../../../dashboard.php?error=Access denied. Only class teachers can view marks reports.");
        exit;
    }
} catch (PDOException $e) {
    error_log("View Marks Auth Error: " . $e->getMessage());
    die("A database error occurred.");
}

$current_year = date('Y');
$academic_year_suggestion = $current_year . '-' . ($current_year + 1);

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Marks Report - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top" data-class-std="<?php echo htmlspecialchars($class_teacher_std); ?>">
    <div id="wrapper">
        <?php include '../../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../../includes/header.php'; ?>
<?php
}
?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">View Marks Report for Class: <?php echo htmlspecialchars($class_teacher_std); ?></h1>
                        <a href="marks_entry.php" class="btn btn-primary btn-sm"><i class="fas fa-edit fa-sm"></i> Go to Marks Entry</a>
                    </div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Criteria to View Report</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-md-5"><label for="exam_type">Exam Type *</label><select class="form-control" id="exam_type">
                                        <option value="">-- Select Exam --</option>
                                        <option value="term_1">Term 1</option>
                                        <option value="term_2">Term 2</option><?php $final_exam_disabled = (in_array($class_teacher_std, ['10', '12'])) ? 'disabled' : ''; ?><option value="final_exam" <?php echo $final_exam_disabled; ?>>Final Exam <?php if ($final_exam_disabled) echo '(Not Applicable)'; ?></option>
                                    </select></div>
                                <div class="form-group col-md-5"><label for="academic_year">Academic Year *</label><input type="text" class="form-control" id="academic_year" value="<?php echo $academic_year_suggestion; ?>"></div>
                                <div class="form-group col-md-2 d-flex align-items-end"><button type="button" id="viewReportBtn" class="btn btn-info btn-block"><i class="fas fa-eye mr-1"></i> View Report</button></div>
                            </div>
                            <hr>
                            <div class="table-responsive" id="marks-report-container" style="display:none;">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead id="marks-report-header"></thead>
                                    <tbody id="marks-report-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
<?php
if (!is_ajax_request()) {
?>
            </div>
            <?php include '../../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../../includes/logout_modal.php" ?>
    <script src="../../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../../assets/js/custom_marks_scripts.js?v=1.1"></script>
</body>

</html>
<?php
}
?>