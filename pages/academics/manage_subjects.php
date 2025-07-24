<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$errors = [];
$success_message = '';

// Handle form submission for assigning subjects to a standard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_subjects'])) {
    $standard = $_POST['standard'];
    $subject_ids = isset($_POST['subject_ids']) ? $_POST['subject_ids'] : [];

    if (empty($standard)) {
        $errors[] = "Please select a standard.";
    }
    if (empty($subject_ids)) {
        $errors[] = "Please select at least one subject to assign.";
    }

    if (empty($errors)) {
        try {
            // First, clear existing assignments for this standard to handle deselection
            $delete_query = "DELETE FROM standard_subjects WHERE standard = ?";
            $stmt_delete = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($stmt_delete, "s", $standard);
            mysqli_stmt_execute($stmt_delete);
            mysqli_stmt_close($stmt_delete);

            // Now, insert the new set of subjects
            $insert_query = "INSERT INTO standard_subjects (standard, subject_id) VALUES (?, ?)";
            $stmt_insert = mysqli_prepare($conn, $insert_query);
            
            foreach ($subject_ids as $subject_id) {
                mysqli_stmt_bind_param($stmt_insert, "si", $standard, $subject_id);
                mysqli_stmt_execute($stmt_insert);
            }
            mysqli_stmt_close($stmt_insert);
            $success_message = "Subjects for standard '{$standard}' have been updated successfully!";
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all subjects for the multi-select dropdown
$subjects_query = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name";
$subjects_result = mysqli_query($conn, $subjects_query);
$all_subjects = mysqli_fetch_all($subjects_result, MYSQLI_ASSOC);

// Fetch all existing assignments to display in the table
// UPDATE: Modified the ORDER BY clause to sort standards logically (Nursery, Junior, Senior, 1, 2, ... 12)
$assignments_query = "SELECT ss.standard, GROUP_CONCAT(s.subject_name ORDER BY s.subject_name SEPARATOR ', ') as assigned_subjects
                      FROM standard_subjects ss
                      JOIN subjects s ON ss.subject_id = s.subject_id
                      GROUP BY ss.standard
                      ORDER BY 
                        CASE
                            WHEN ss.standard = 'Nursery' THEN -3
                            WHEN ss.standard = 'Junior' THEN -2
                            WHEN ss.standard = 'Senior' THEN -1
                            ELSE CAST(ss.standard AS UNSIGNED)
                        END, 
                        ss.standard";
$assignments_result = mysqli_query($conn, $assignments_query);
$all_assignments = mysqli_fetch_all($assignments_result, MYSQLI_ASSOC);

$standards = ['Nursery', 'Junior', 'Senior', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Subjects - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage Standard Subjects</h1>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>".htmlspecialchars($error)."</p>"; ?></div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Assign Subjects to a Standard</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="standard">Select Standard *</label>
                                            <select class="form-control" id="standard" name="standard" required>
                                                <option value="">-- Choose a Standard --</option>
                                                <?php foreach ($standards as $std): ?>
                                                    <option value="<?php echo $std; ?>"><?php echo $std; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="subject_ids">Select Subjects *</label>
                                            <select class="form-control multi-select" id="subject_ids" name="subject_ids[]" multiple="multiple" required>
                                                <?php foreach ($all_subjects as $subject): ?>
                                                    <option value="<?php echo $subject['subject_id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#addSubjectModal">
                                                <i class="fas fa-plus"></i> Add New Subject
                                            </button>
                                        </div>
                                        <hr>
                                        <button type="submit" name="assign_subjects" class="btn btn-primary">
                                            <i class="fas fa-save mr-2"></i>Save Assignment
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Current Subject Assignments</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Standard</th>
                                                    <th>Assigned Subjects</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($all_assignments)): ?>
                                                    <tr><td colspan="2" class="text-center">No subjects have been assigned yet.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($all_assignments as $assignment): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($assignment['standard']); ?></td>
                                                            <td><?php echo htmlspecialchars($assignment['assigned_subjects']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Add New Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add a New Subject</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="modal-message"></div>
                    <div class="form-group">
                        <label for="new_subject_name">Subject Name *</label>
                        <input type="text" class="form-control" id="new_subject_name" placeholder="e.g., Physics">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" id="saveNewSubjectBtn">Save Subject</button>
                </div>
            </div>
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

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.multi-select').select2({
            placeholder: "Choose subjects...",
            width: '100%'
        });

        // When a standard is selected, fetch its currently assigned subjects
        $('#standard').change(function() {
            const selectedStandard = $(this).val();
            if (selectedStandard) {
                $.ajax({
                    url: 'ajax_handler.php',
                    type: 'POST',
                    data: { action: 'get_subjects_for_standard', standard: selectedStandard },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#subject_ids').val(response.subject_ids).trigger('change');
                        }
                    }
                });
            } else {
                $('#subject_ids').val(null).trigger('change');
            }
        });

        // Handle saving a new subject from the modal
        $('#saveNewSubjectBtn').click(function() {
            const subjectName = $('#new_subject_name').val().trim();
            if (subjectName) {
                $.ajax({
                    url: 'ajax_handler.php',
                    type: 'POST',
                    data: { action: 'add_subject', subject_name: subjectName },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Add the new subject to the dropdown
                            const newOption = new Option(response.subject.subject_name, response.subject.subject_id, false, false);
                            $('#subject_ids').append(newOption);
                            $('#addSubjectModal').modal('hide');
                            $('#new_subject_name').val('');
                        } else {
                            $('#modal-message').html(`<div class="alert alert-danger">${response.message}</div>`);
                        }
                    },
                    error: function() {
                        $('#modal-message').html('<div class="alert alert-danger">An error occurred.</div>');
                    }
                });
            } else {
                alert('Please enter a subject name.');
            }
        });
    });
    </script>
</body>
</html>
