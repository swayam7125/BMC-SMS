<?php
include_once "../../../includes/connect.php";
include_once "../../../encryption.php";

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

// Check if the teacher is a class teacher and get their assigned standard
$query = "SELECT class_teacher_std FROM teacher WHERE id = ? AND class_teacher = 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $teacher_data = mysqli_fetch_assoc($result);
    $class_teacher_std = $teacher_data['class_teacher_std'];
} else {
    header("Location: ../../../dashboard.php?error=Access denied. Only class teachers can enter marks.");
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
    <title>Marks Entry - School Management System</title>
    <link href="../../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../../includes/header.php'; ?>
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
                                            Final Exam <?php if($final_exam_disabled) echo '(Not Applicable)'; ?>
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
            <?php include '../../../includes/footer.php'; ?>
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
    <script src="../../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../../assets/js/sb-admin-2.min.js"></script>
    <script>
    function loadStudents() {
        const academicYear = $('#academic_year').val();
        const examType = $('#exam_type').val();
        const classStd = '<?php echo $class_teacher_std; ?>';
        
        $('#academic_year_hidden').val(academicYear);
        $('#exam_type_hidden').val(examType);

        if (academicYear && examType) {
            $('#students-list-body').html('<tr><td colspan="10" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
            $('#marks-table-container').slideDown();

            $.ajax({
                url: 'get_students_for_marks.php',
                type: 'POST',
                data: { class_std: classStd, exam_type: examType, academic_year: academicYear },
                dataType: 'json',
                success: function(response) {
                    $('#marks-table-header').empty();
                    $('#students-list-body').empty();

                    if (response.success) {
                        let headerRow = '<tr><th>Roll No</th><th>Student Name</th>';
                        response.subjects.forEach(subject => { headerRow += `<th>${subject}</th>`; });
                        headerRow += '</tr>';
                        $('#marks-table-header').html(headerRow);

                        if (response.students.length > 0) {
                            response.students.forEach(student => {
                                let row = `<tr><td>${student.rollno}</td><td>${student.student_name}</td>`;
                                response.subjects.forEach(subject => {
                                    const marks = student.marks[subject] || '';
                                    row += `<td><input type="number" class="form-control" name="marks[${student.id}][${subject}]" value="${marks}" min="0" max="100" placeholder="N/A"></td>`;
                                });
                                row += `</tr>`;
                                $('#students-list-body').append(row);
                            });
                        } else {
                             $('#students-list-body').html(`<tr><td colspan="${response.subjects.length + 2}" class="text-center">No students found for this class.</td></tr>`);
                        }
                    } else {
                         $('#students-list-body').html(`<tr><td colspan="10" class="text-center text-danger">${response.message}</td></tr>`);
                    }
                },
                error: function() {
                    $('#students-list-body').html('<tr><td colspan="10" class="text-center text-danger">Error fetching data. Please try again.</td></tr>');
                }
            });
        } else {
            alert('Please select an Exam Type and provide an Academic Year.');
            $('#marks-table-container').slideUp();
        }
    }

    $(document).ready(function() {
        $('#loadStudentsBtn').click(loadStudents);
        $('#marksForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'save_marks.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
                success: function(response) {
                    let messageBox = `<div class="alert alert-${response.success ? 'success' : 'danger'} alert-dismissible fade show" role="alert">${response.message}<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button></div>`;
                    $('#message-container').html(messageBox);
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                },
                error: function() { $('#message-container').html('<div class="alert alert-danger">An unknown error occurred.</div>'); }
            });
        });
    });
    </script>
</body>
</html>
