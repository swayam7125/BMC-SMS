<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
| This section handles all server-side operations.
*/

// Core Includes
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

// --- Authorization ---
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if ($role !== 'principal') { // Only principals can access this page
    header("Location: ../../login.php");
    exit;
}

// --- Initialization ---
$errors = [];
$success_message = '';
$all_subjects = [];
$all_assignments = [];
$standards = ['Nursery', 'Junior', 'Senior', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];

try {
    // --- Form Submission Handling ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_subjects'])) {
        $standard = filter_input(INPUT_POST, 'standard', FILTER_SANITIZE_STRING);
        $subject_ids = $_POST['subject_ids'] ?? [];

        if (empty($standard) || empty($subject_ids)) {
            $errors[] = "Please select a standard and at least one subject.";
        } else {
            // Start a transaction for atomicity
            $conn->beginTransaction();

            // First, remove all existing assignments for this standard to prevent duplicates
            $stmt_delete = $conn->prepare("DELETE FROM standard_subjects WHERE standard = ?");
            $stmt_delete->execute([$standard]);

            // Now, insert the new assignments
            $stmt_insert = $conn->prepare("INSERT INTO standard_subjects (standard, subject_id) VALUES (?, ?)");
            foreach ($subject_ids as $subject_id) {
                $stmt_insert->execute([$standard, filter_var($subject_id, FILTER_VALIDATE_INT)]);
            }

            $conn->commit();
            $success_message = "Subjects have been successfully assigned to Standard {$standard}.";
        }
    }

    // --- Data Fetching ---
    // Fetch all available subjects
    $stmt_subjects = $conn->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
    $all_subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all existing standard-subject assignments to display in the table
    $stmt_assignments = $conn->query("
        SELECT ss.standard, s.subject_name
        FROM standard_subjects ss
        JOIN subjects s ON ss.subject_id = s.subject_id
        ORDER BY ss.standard, s.subject_name
    ");
    $raw_assignments = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);

    // Group assignments by standard for easier display
    foreach ($raw_assignments as $assignment) {
        $all_assignments[$assignment['standard']][] = $assignment['subject_name'];
    }
} catch (PDOException $e) {
    // Log the error and show a generic message
    error_log("Database Error in manage_subjects.php: " . $e->getMessage());
    $errors[] = "A database error occurred. Please try again later.";
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .select2-container--bootstrap4 .select2-selection--multiple {
            min-height: calc(1.5em + .75rem + 2px);
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
            <?php
            if (!$is_ajax_request) {
                include '../../includes/header.php';
            }
            ?>
            <div id="content">

                <div id="main-content">
                    <div class="container-fluid">

                        <h1 class="h3 mb-4 text-gray-800">Manage Subjects</h1>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Assign Subjects to a Standard</h6>
                            </div>
                            <div class="card-body">
                                <form action="" method="post">
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="standard">Select Standard</label>
                                            <select name="standard" id="standard" class="form-control" required>
                                                <option value="">-- Choose a Standard --</option>
                                                <?php foreach ($standards as $std): ?>
                                                    <option value="<?php echo htmlspecialchars($std); ?>">
                                                        <?php echo htmlspecialchars($std); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-8">
                                            <label for="subject_ids">Assign Subjects</label>
                                            <div class="input-group">
                                                <select name="subject_ids[]" id="subject_ids"
                                                    class="form-control select2-multiple" multiple="multiple" required>
                                                    <?php foreach ($all_subjects as $subject): ?>
                                                        <option
                                                            value="<?php echo htmlspecialchars($subject['subject_id']); ?>">
                                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="button" data-toggle="modal"
                                                        data-target="#addSubjectModal" title="Add New Subject">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="assign_subjects" class="btn btn-success">
                                        <i class="fas fa-check-circle mr-2"></i>Assign Subjects
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Current Subject Assignments</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Standard</th>
                                                <th>Assigned Subjects</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($all_assignments as $standard => $subjects): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($standard); ?></strong></td>
                                                    <td><?php echo htmlspecialchars(implode(', ', $subjects)); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal fade" id="addSubjectModal" tabindex="-1" role="dialog"
                        aria-labelledby="addSubjectModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addSubjectModalLabel">Add a New Subject</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div id="modal-message"></div>
                                    <div class="form-group">
                                        <label for="new_subject_name">Subject Name</label>
                                        <input type="text" id="new_subject_name" class="form-control"
                                            placeholder="e.g., Environmental Science">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="button" id="saveNewSubjectBtn" class="btn btn-primary">Save
                                        Subject</button>
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
            <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
            <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

            <script>
                // Using jQuery's ready function for consistency with plugins
                $(document).ready(function() {
                    // Initialize Select2
                    $('.select2-multiple').select2({
                        placeholder: "Click to select subjects",
                        theme: 'bootstrap4',
                        width: '100%'
                    });

                    // Handle Add New Subject via Fetch
                    $('#saveNewSubjectBtn').on('click', function() {
                        const subjectNameInput = $('#new_subject_name');
                        const modalMessage = $('#modal-message');
                        const subjectName = subjectNameInput.val().trim();

                        if (subjectName) {
                            const formData = new FormData();
                            formData.append('action', 'add_subject');
                            formData.append('subject_name', subjectName);

                            fetch('../academics/ajax_handler.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! Status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.success) {
                                        // Use jQuery to append and trigger the change for Select2
                                        const newOption = new Option(data.subject.subject_name, data.subject.subject_id, false, false);
                                        $('#subject_ids').append(newOption).trigger('change');

                                        // Hide the modal and clear the input using jQuery
                                        $('#addSubjectModal').modal('hide');
                                        subjectNameInput.val('');
                                    } else {
                                        modalMessage.html(`<div class="alert alert-danger">${data.message || 'An unknown error occurred.'}</div>`);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error adding subject:', error);
                                    modalMessage.html('<div class="alert alert-danger">An error occurred while adding the subject. Please try again.</div>');
                                });
                        } else {
                            alert('Please enter a valid subject name.');
                        }
                    });

                    // Clear modal message when it's opened
                    $('#addSubjectModal').on('show.bs.modal', function() {
                        $('#modal-message').html('');
                        $('#new_subject_name').val('');
                    });
                });
            </script>
</body>

</html>