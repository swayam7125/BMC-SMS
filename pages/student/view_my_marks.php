<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

$role = null;
$student_id = null;
$student_std = null;

if (isset($_COOKIE['encrypted_user_role'])) $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id'])) $student_id = decrypt_id($_COOKIE['encrypted_user_id']);

if ($role !== 'student' || !$student_id) {
    header("Location: ../../login.php");
    exit;
}

try {
    // --- START: This code can mark a specific notification as read if an ID is passed in the URL ---
    if (isset($_GET['notif_id']) && is_numeric($_GET['notif_id'])) {
        $notification_id = $_GET['notif_id'];
        $update_stmt = $conn->prepare("UPDATE notifications SET is_read = true WHERE id = ? AND user_id = ?");
        $update_stmt->execute([$notification_id, $student_id]);
    }
    // --- END: Mark notification as read ---

    // --- START: MARK ALL 'marks_uploaded' NOTIFICATIONS AS READ upon visiting the page ---
    $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = true WHERE user_id = ? AND type = 'marks_uploaded' AND is_read = false");
    $stmt_mark_read->execute([$student_id]);
    // --- END: MARK ALL RESULT NOTIFICATIONS AS READ ---

    // Fetch the student's standard (std) to disable the final exam option for board classes
    $query_std = "SELECT std FROM student WHERE id = ?";
    $stmt_std = $conn->prepare($query_std);
    $stmt_std->execute([$student_id]);

    if ($student_data = $stmt_std->fetch(PDO::FETCH_ASSOC)) {
        $student_std = $student_data['std'];
    } else {
        // Redirect if student profile is not found
        header("Location: ../../dashboard.php?error=Student profile not found.");
        exit;
    }
} catch (PDOException $e) {
    // For a live environment, log the error and show a generic message.
    error_log("DB Error in view_my_marks.php: " . $e->getMessage());
    header("Location: ../../dashboard.php?error=A database error occurred.");
    exit;
}

$current_year = date('Y');
$academic_year_suggestion = $current_year . '-' . ($current_year + 1);

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Marks Report - School Management System</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/view_my_marks.css">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">

</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
<?php
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">My Marks Report</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Criteria to View Your Report</h6>
                        </div>
                        <div class="card-body">
                            <form id="marks-form">
                                <div class="form-row">
                                    <div class="form-group col-md-5">
                                        <label for="exam_type">Exam Type *</label>
                                        <select class="form-control" id="exam_type" required>
                                            <option value="">-- Select Exam --</option>
                                            <option value="term_1">Term 1</option>
                                            <option value="term_2">Term 2</option>
                                            <?php $final_exam_disabled = (in_array($student_std, ['10', '12'])) ? 'disabled' : ''; ?>
                                            <option value="final_exam" <?php echo $final_exam_disabled; ?>>
                                                Final Exam <?php if ($final_exam_disabled) echo '(Board Exam)'; ?>
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label for="academic_year">Academic Year *</label>
                                        <input type="text" class="form-control" id="academic_year" value="<?php echo htmlspecialchars($academic_year_suggestion); ?>" required>
                                    </div>
                                    <div class="form-group col-md-2 d-flex align-items-end">
                                        <button type="submit" id="viewReportBtn" class="btn btn-info btn-block">
                                            <i class="fas fa-eye mr-1"></i> View Report
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <hr>

                            <div id="loader" class="loader"></div>

                            <div id="marks-report-container" style="display:none;">
                                <h4 id="student-name-header" class="mb-3 text-center text-gray-800"></h4>
                                <div id="result-summary" class="alert mb-4 text-center" style="display:none;"></div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Subject</th>
                                                <th>Marks Obtained</th>
                                                <th>Total Marks</th>
                                            </tr>
                                        </thead>
                                        <tbody id="marks-report-body"></tbody>
                                        <tfoot class="font-weight-bold">
                                            <tr id="total-row">
                                                <td>Total</td>
                                                <td id="total-obtained"></td>
                                                <td id="total-possible"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
<?php
if (!is_ajax_request()) {
?>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>

    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <script src="../../assets/js/view_my_marks.js"></script>
</body>

</html>
<?php
}
?>