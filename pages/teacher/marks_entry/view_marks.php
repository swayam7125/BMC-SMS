<?php
include_once "../../../includes/connect.php";
include_once "../../../encryption.php";

$role = null;
$teacher_id = null;
$class_teacher_std = null;

if (isset($_COOKIE['encrypted_user_role'])) $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id'])) $teacher_id = decrypt_id($_COOKIE['encrypted_user_id']);

if ($role !== 'teacher' || !$teacher_id) {
    header("Location: ../../../login.php");
    exit;
}

$query = "SELECT class_teacher_std FROM teacher WHERE id = ? AND class_teacher = 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $teacher_data = mysqli_fetch_assoc($result);
    $class_teacher_std = $teacher_data['class_teacher_std'];
} else {
    header("Location: ../../../dashboard.php?error=Access denied. Only class teachers can view marks reports.");
    exit;
}
mysqli_stmt_close($stmt);

$current_year = date('Y');
$academic_year_suggestion = $current_year . '-' . ($current_year + 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Marks Report - School Management System</title>
    <link href="../../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../../assets/css/sb-admin-2.min.css" rel="stylesheet">
            <link rel="stylesheet" href="../../../assets/css/sidebar.css">

</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">View Marks Report for Class: <?php echo htmlspecialchars($class_teacher_std); ?></h1>
                        <a href="marks_entry.php" class="btn btn-primary btn-sm"><i class="fas fa-edit fa-sm"></i> Go to Marks Entry</a>
                    </div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Select Criteria to View Report</h6></div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-md-5"><label for="exam_type">Exam Type *</label><select class="form-control" id="exam_type"><option value="">-- Select Exam --</option><option value="term_1">Term 1</option><option value="term_2">Term 2</option><?php $final_exam_disabled = (in_array($class_teacher_std, ['10', '12'])) ? 'disabled' : ''; ?><option value="final_exam" <?php echo $final_exam_disabled; ?>>Final Exam <?php if($final_exam_disabled) echo '(Not Applicable)'; ?></option></select></div>
                                <div class="form-group col-md-5"><label for="academic_year">Academic Year *</label><input type="text" class="form-control" id="academic_year" value="<?php echo $academic_year_suggestion; ?>"></div>
                                <div class="form-group col-md-2 d-flex align-items-end"><button type="button" id="viewReportBtn" class="btn btn-info btn-block"><i class="fas fa-eye mr-1"></i> View Report</button></div>
                            </div>
                            <hr>
                            <div class="table-responsive" id="marks-report-container" style="display:none;"><table class="table table-bordered table-hover" width="100%" cellspacing="0"><thead id="marks-report-header"></thead><tbody id="marks-report-body"></tbody></table></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../../includes/footer.php'; ?>
        </div>
    </div>
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5><button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button></div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a></div>
            </div>
        </div>
    </div>
    <script src="../../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../../assets/js/sb-admin-2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#viewReportBtn').click(function() {
            const examType = $('#exam_type').val();
            const academicYear = $('#academic_year').val();
            const classStd = '<?php echo $class_teacher_std; ?>';
            if (examType && academicYear) {
                $('#marks-report-body').html('<tr><td colspan="15" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading report...</td></tr>');
                $('#marks-report-container').slideDown();
                $.ajax({
                    url: 'get_marks_report.php', type: 'POST', data: { class_std: classStd, exam_type: examType, academic_year: academicYear }, dataType: 'json',
                    success: function(response) {
                        $('#marks-report-header').empty(); $('#marks-report-body').empty();
                        if (response.success) {
                            // --- MODIFIED: Removed background colors from headers ---
                            let headerRow = '<tr><th>Roll No</th><th>Student Name</th>';
                            response.subjects.forEach(subject => { headerRow += `<th>${subject}</th>`; });
                            headerRow += '<th>Total Obtained</th><th>Total Possible</th><th>Percentage</th><th>Status</th></tr>';
                            $('#marks-report-header').html(headerRow);

                            if (response.students.length > 0) {
                                response.students.forEach(student => {
                                    let row = `<tr><td>${student.rollno}</td><td>${student.student_name}</td>`;
                                    response.subjects.forEach(subject => {
                                        const marks = student.marks[subject] !== undefined ? student.marks[subject] : '<span class="text-muted">N/A</span>';
                                        row += `<td>${marks}</td>`;
                                    });
                                    // --- MODIFIED: Removed background colors from cells ---
                                    row += `<td><strong>${student.total_obtained}</strong></td>`;
                                    row += `<td>${student.total_possible}</td>`;
                                    row += `<td><strong class="text-primary">${student.percentage}%</strong></td>`;
                                    
                                    let statusClass = 'badge-secondary'; // Default for N/A
                                    if (student.status === 'Pass') {
                                        statusClass = 'badge-success';
                                    } else if (student.status === 'Fail') {
                                        statusClass = 'badge-danger'; // Red for Fail
                                    }
                                    // --- MODIFIED: Removed background color from cell, keeping only the badge color ---
                                    row += `<td class="font-weight-bold"><span class="badge ${statusClass}" style="font-size: 0.9rem;">${student.status}</span></td>`;
                                    
                                    row += `</tr>`;
                                    $('#marks-report-body').append(row);
                                });
                            } else { 
                                $('#marks-report-body').html(`<tr><td colspan="${response.subjects.length + 6}" class="text-center">No marks found for the selected criteria.</td></tr>`); 
                            }
                        } else { $('#marks-report-body').html(`<tr><td colspan="15" class="text-center text-danger">${response.message}</td></tr>`); }
                    },
                    error: function() { $('#marks-report-header').empty(); $('#marks-report-body').html('<tr><td colspan="15" class="text-center text-danger">Error fetching marks data. Please try again.</td></tr>'); }
                });
            } else { alert('Please select both Exam Type and Academic Year.'); }
        });
    });
    </script>
</body>
</html>