<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
$student_id = null;
$student_std = null;

if (isset($_COOKIE['encrypted_user_role'])) $role = decrypt_id($_COOKIE['encrypted_user_role']);
if (isset($_COOKIE['encrypted_user_id'])) $student_id = decrypt_id($_COOKIE['encrypted_user_id']);

if ($role !== 'student' || !$student_id) {
    header("Location: ../../login.php");
    exit;
}

$query_std = "SELECT std FROM student WHERE id = ?";
$stmt_std = mysqli_prepare($conn, $query_std);
mysqli_stmt_bind_param($stmt_std, "i", $student_id);
mysqli_stmt_execute($stmt_std);
$result_std = mysqli_stmt_get_result($stmt_std);
if ($student_data = mysqli_fetch_assoc($result_std)) {
    $student_std = $student_data['std'];
} else {
    header("Location: ../../dashboard.php?error=Student profile not found.");
    exit;
}
mysqli_stmt_close($stmt_std);

$current_year = date('Y');
$academic_year_suggestion = $current_year . '-' . ($current_year + 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Marks Report - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">My Marks Report</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Criteria to View Your Report</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label for="exam_type">Exam Type *</label>
                                    <select class="form-control" id="exam_type">
                                        <option value="">-- Select Exam --</option>
                                        <option value="term_1">Term 1</option>
                                        <option value="term_2">Term 2</option>
                                        <?php $final_exam_disabled = (in_array($student_std, ['10', '12'])) ? 'disabled' : ''; ?>
                                        <option value="final_exam" <?php echo $final_exam_disabled; ?>>
                                            Final Exam <?php if($final_exam_disabled) echo '(Board Exam)'; ?>
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-5">
                                    <label for="academic_year">Academic Year *</label>
                                    <input type="text" class="form-control" id="academic_year" value="<?php echo $academic_year_suggestion; ?>">
                                </div>
                                <div class="form-group col-md-2 d-flex align-items-end">
                                    <button type="button" id="viewReportBtn" class="btn btn-info btn-block">
                                        <i class="fas fa-eye mr-1"></i> View Report
                                    </button>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive" id="marks-report-container" style="display:none;">
                                <h4 id="student-name-header" class="mb-3"></h4>
                                
                                <div id="result-summary" class="alert mb-4" style="display:none;"></div>
                                
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Marks Obtained</th>
                                            <th>Total Marks</th>
                                        </tr>
                                    </thead>
                                    <tbody id="marks-report-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
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

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#viewReportBtn').click(function() {
            const examType = $('#exam_type').val();
            const academicYear = $('#academic_year').val();
            
            if (examType && academicYear) {
                $('#marks-report-body').html('<tr><td colspan="3" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading report...</td></tr>');
                $('#result-summary').hide(); 
                $('#marks-report-container').slideDown();

                $.ajax({
                    url: 'get_my_marks.php',
                    type: 'POST',
                    data: { exam_type: examType, academic_year: academicYear },
                    dataType: 'json',
                    success: function(response) {
                        $('#marks-report-body').empty();
                        if (response.success) {
                            $('#student-name-header').text('Report for: ' + (response.student_name || ''));

                            if (response.total_possible > 0) {
                                // --- NEW: Logic to set color based on Pass/Fail status ---
                                let summaryAlertClass = 'alert-info'; // Default
                                if (response.status === 'Pass') {
                                    summaryAlertClass = 'alert-success'; // Green for Pass
                                } else if (response.status === 'Fail') {
                                    summaryAlertClass = 'alert-danger'; // Red for Fail
                                }

                                const summaryHtml = `
                                    <h5 class="alert-heading">Result Summary</h5>
                                    <p class="mb-1"><strong>Overall Percentage:</strong> <span class="font-weight-bold">${response.percentage}%</span></p>
                                    <p class="mb-0"><strong>Total Marks:</strong> ${response.total_obtained} / ${response.total_possible}</p>
                                    <hr>
                                    <p class="mb-0"><strong>Status:</strong> <span class="font-weight-bold">${response.status}</span></p>
                                `;
                                
                                $('#result-summary')
                                    .removeClass('alert-info alert-success alert-danger') // Reset classes
                                    .addClass(summaryAlertClass) // Add the correct color class
                                    .html(summaryHtml)
                                    .show();
                            } else {
                                $('#result-summary').hide();
                            }

                            if (Object.keys(response.marks).length > 0) {
                                for (const subject in response.marks) {
                                    const mark_data = response.marks[subject];
                                    let row = `<tr>
                                                   <td>${subject}</td>
                                                   <td>${mark_data.marks_obtained}</td>
                                                   <td>${mark_data.total_marks}</td>
                                               </tr>`;
                                    $('#marks-report-body').append(row);
                                }
                            } else {
                                $('#marks-report-body').html(`<tr><td colspan="3" class="text-center">No marks have been entered for the selected criteria.</td></tr>`);
                            }
                        } else {
                            $('#result-summary').hide();
                            $('#marks-report-body').html(`<tr><td colspan="3" class="text-center text-danger">${response.message}</td></tr>`);
                        }
                    },
                    error: function() {
                        $('#marks-report-body').html('<tr><td colspan="3" class="text-center text-danger">An error occurred while fetching your marks.</td></tr>');
                    }
                });
            } else {
                alert('Please select both Exam Type and Academic Year.');
            }
        });
    });
    </script>
</body>
</html>