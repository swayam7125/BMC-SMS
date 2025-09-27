<?php
// --- SETUP AND INITIALIZATION ---
// NO session_start() as per user request.
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// --- Authorization ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
if ($role !== 'principal') {
    if ($is_ajax_request) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    } else {
        header("Location: ../../login.php");
    }
    exit;
}

// --- STATELESS CSRF PROTECTION (Double Submit Cookie Method) ---
// 1. Check if a CSRF cookie exists. If not, generate one.
if (empty($_COOKIE['csrf_token'])) {
    // Generate a secure, random token.
    $csrf_token = bin2hex(random_bytes(32));
    // Set the cookie. 'HttpOnly' is false so JS can't access it. 'SameSite=Lax' is a good default.
    setcookie('csrf_token', $csrf_token, ['expires' => time() + 3600, 'path' => '/', 'samesite' => 'Lax']);
} else {
    // Use the existing token from the cookie.
    $csrf_token = $_COOKIE['csrf_token'];
}

// --- Data & Variables ---
$errors = [];
$standards = ['Nursery', 'Junior', 'Senior', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
$all_subjects = [];
$all_assignments = [];

try {
    // --- AJAX FORM SUBMISSION HANDLING ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_subjects'])) {
        header('Content-Type: application/json');

        // 2. Validate the CSRF token on submission.
        // The token from the POST data must exist and match the token in the cookie.
        if (!isset($_POST['csrf_token']) || !isset($_COOKIE['csrf_token']) || !hash_equals($_COOKIE['csrf_token'], $_POST['csrf_token'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
            exit;
        }

        $standard = $_POST['standard'] ?? '';
        $subject_ids = $_POST['subject_ids'] ?? [];

        // IMPROVEMENT: More robust validation instead of deprecated filter.
        if (empty($standard) || !in_array($standard, $standards)) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid standard.']);
            exit;
        }

        $conn->beginTransaction();
        
        $stmt_delete = $conn->prepare("DELETE FROM standard_subjects WHERE standard = ?");
        $stmt_delete->execute([$standard]);

        if (!empty($subject_ids)) {
            $stmt_insert = $conn->prepare("INSERT INTO standard_subjects (standard, subject_id) VALUES (?, ?)");
            foreach ($subject_ids as $subject_id) {
                $stmt_insert->execute([$standard, filter_var($subject_id, FILTER_VALIDATE_INT)]);
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Subject assignments for Standard {$standard} updated successfully."]);
        exit;
    }

    // --- DATA FETCHING FOR INITIAL PAGE LOAD ---
    $stmt_subjects = $conn->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
    $all_subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

    $stmt_assignments = $conn->query("
        SELECT ss.standard, s.subject_name FROM standard_subjects ss
        JOIN subjects s ON ss.subject_id = s.subject_id ORDER BY ss.standard, s.subject_name
    ");
    $raw_assignments = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);

    foreach ($raw_assignments as $assignment) {
        $all_assignments[$assignment['standard']][] = $assignment['subject_name'];
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log("Database Error in manage_subjects.php: " . $e->getMessage());
    if ($is_ajax_request) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
        exit;
    } else {
        $errors[] = "A database error occurred. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        /* UI FIX: Improved styles for the searchable dropdown */
        .searchable-dropdown .dropdown-menu {
            display: none; position: absolute; width: 100%; z-index: 1000;
            max-height: 250px; overflow-y: auto; border: 1px solid #d1d3e2;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); background-color: #fff;
            padding: 0.5rem;
        }
        .searchable-dropdown .form-check-label { width: 100%; cursor: pointer; }
        .searchable-dropdown .form-check:hover { background-color: #f8f9fa; }
        .dropdown-options-container { max-height: 180px; overflow-y: auto; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage Subjects</h1>

                    <div id="main-message-container"></div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Assign Subjects to a Standard</h6></div>
                        <div class="card-body">
                            <form id="assign-subjects-form" action="" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="standard">Select Standard</label>
                                        <select name="standard" id="standard" class="form-control" required>
                                            <option value="">-- Choose a Standard --</option>
                                            <?php foreach ($standards as $std): ?><option value="<?php echo htmlspecialchars($std); ?>"><?php echo htmlspecialchars($std); ?></option><?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <label for="subjectDisplay">Assign Subjects</label>
                                        <div class="input-group searchable-dropdown" id="subjectDropdown">
                                            <input type="text" id="subjectDisplay" class="form-control" placeholder="Select a standard first..." readonly disabled>
                                            <div id="subjectOptions" class="dropdown-menu p-2 w-100">
                                                 <input type="text" id="subjectSearch" class="form-control mb-2" placeholder="Search subjects...">
                                                <div class="dropdown-options-container">
                                                    <?php foreach ($all_subjects as $subject): ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="subject_ids[]" value="<?php echo $subject['subject_id']; ?>" id="subject_<?php echo $subject['subject_id']; ?>">
                                                            <label class="form-check-label" for="subject_<?php echo $subject['subject_id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="button" data-toggle="modal" data-target="#addSubjectModal" title="Add New Subject"><i class="fas fa-plus"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="assign_subjects" id="saveChangesBtn" class="btn btn-success" disabled><i class="fas fa-check-circle mr-2"></i>Save Changes</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Current Subject Assignments</h6></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead><tr><th>Standard</th><th>Assigned Subjects</th></tr></thead>
                                    <tbody id="assignments-table-body">
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
            </div>
            <div class="modal fade" id="addSubjectModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Add a New Subject</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                        <div class="modal-body">
                            <div id="modal-message"></div>
                            <div class="form-group"><label for="new_subject_name">Subject Name</label><input type="text" id="new_subject_name" class="form-control" placeholder="e.g., Environmental Science"></div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button><button type="button" id="saveNewSubjectBtn" class="btn btn-primary">Save Subject</button></div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
$(document).ready(function() {
    let dataTable = $('#dataTable').DataTable();
    let initialAssignedSubjects = []; // UX FIX: Store initial state to check for changes

    // --- Custom Searchable Dropdown Logic ---
    const subjectDropdown = (function(wrapperId) {
        const wrapper = $(wrapperId);
        const displayInput = wrapper.find('#subjectDisplay');
        const optionsContainer = wrapper.find('.dropdown-menu');
        const searchInput = wrapper.find('#subjectSearch');
        
        function updateDisplay() {
            const selectedLabels = [];
            wrapper.find('.form-check-input:checked').each(function() {
                selectedLabels.push($(this).siblings('label').text().trim());
            });
            displayInput.val(selectedLabels.join(', ') || '');
        }

        displayInput.on('click', () => {
             if (!displayInput.prop('disabled')) optionsContainer.show();
        });
        
        // UI FIX: Search logic now tied to the dedicated search input
        searchInput.on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            optionsContainer.find('.form-check').each(function() {
                const labelText = $(this).find('label').text().toLowerCase();
                $(this).toggle(labelText.includes(searchTerm));
            });
        });

        optionsContainer.on('change', '.form-check-input', () => {
            updateDisplay();
            checkIfChanged(); // UX FIX: Check for changes to enable/disable save button
        });
        
        $(document).on('click', (e) => { 
            if (!$(e.target).closest(wrapperId).length) optionsContainer.hide(); 
        });

        return {
            reset: () => {
                wrapper.find('.form-check-input').prop('checked', false);
                searchInput.val('');
                optionsContainer.find('.form-check').show();
                updateDisplay();
            },
            setAssigned: (subjectIds) => {
                subjectDropdown.reset();
                subjectIds.forEach(id => wrapper.find(`input[value="${id}"]`).prop('checked', true));
                updateDisplay();
            }
        };
    })('#subjectDropdown');

    // --- Form and State Logic ---
    function checkIfChanged() {
        const currentSelections = $('.form-check-input:checked').map((_, el) => $(el).val()).get().sort();
        const initialSelections = [...initialAssignedSubjects].sort();
        
        const hasChanged = JSON.stringify(currentSelections) !== JSON.stringify(initialSelections);
        $('#saveChangesBtn').prop('disabled', !hasChanged);
    }

    $('#standard').on('change', function() {
        const standard = $(this).val();
        const $subjectDisplay = $('#subjectDisplay');
        
        $('#saveChangesBtn').prop('disabled', true); // Always disable on change

        if (standard) {
            $subjectDisplay.prop('disabled', false).attr('placeholder', 'Loading subjects...');
            $.ajax({
                url: '../academics/ajax_handler.php',
                type: 'POST',
                data: { action: 'get_assigned_subjects', standard: standard },
                dataType: 'json',
                success: function(response) {
                    initialAssignedSubjects = response.success ? response.assigned_subjects : [];
                    subjectDropdown.setAssigned(initialAssignedSubjects); 
                    $subjectDisplay.attr('placeholder', 'Search & select subjects...');
                },
                error: () => {
                    alert('Error loading subjects.');
                    $subjectDisplay.attr('placeholder', 'Error loading subjects.');
                    initialAssignedSubjects = [];
                }
            });
        } else {
            $subjectDisplay.prop('disabled', true).attr('placeholder', 'Select a standard first...');
            subjectDropdown.reset();
            initialAssignedSubjects = [];
        }
    });

    // --- AJAX Form Submission ---
    $('#assign-subjects-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $('#saveChangesBtn');
        const initialBtnText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');

        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method'),
            data: $form.serialize() + '&assign_subjects=1', // Ensure our POST flag is sent
            dataType: 'json',
            success: function(response) {
                const messageClass = response.success ? 'alert-success' : 'alert-danger';
                $('#main-message-container').html(`<div class="alert ${messageClass} alert-dismissible fade show" role="alert">
                    ${response.message}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>`);
                if (response.success) {
                    refreshAssignmentsTable();
                    // Update initial state after successful save
                    initialAssignedSubjects = $('.form-check-input:checked').map((_, el) => $(el).val()).get();
                }
            },
            error: function() {
                 $('#main-message-container').html(`<div class="alert alert-danger">A server error occurred. Please try again.</div>`);
            },
            complete: function() {
                $btn.prop('disabled', true).html(initialBtnText);
            }
        });
    });

    function refreshAssignmentsTable() {
        $.ajax({
            url: '../academics/ajax_handler.php',
            type: 'POST',
            data: { action: 'get_all_assignments' }, // This new action needs to be created
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    dataTable.clear();
                    $.each(response.assignments, function(standard, subjects) {
                        dataTable.row.add([
                            `<strong>${standard}</strong>`,
                            subjects.join(', ')
                        ]);
                    });
                    dataTable.draw();
                }
            }
        });
    }

    // --- Add New Subject Modal Logic ---
    $('#saveNewSubjectBtn').on('click', function() {
        const subjectName = $('#new_subject_name').val().trim();
        if (subjectName) {
            $.ajax({
                url: '../academics/ajax_handler.php',
                type: 'POST',
                data: { action: 'add_subject', subject_name: subjectName },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const newId = response.subject.subject_id;
                        const newName = response.subject.subject_name;
                        const newCheckboxHTML = `<div class="form-check">
                            <input class="form-check-input" type="checkbox" name="subject_ids[]" value="${newId}" id="subject_${newId}">
                            <label class="form-check-label" for="subject_${newId}">${newName}</label>
                        </div>`;
                        $('.dropdown-options-container').append(newCheckboxHTML);
                        $('#addSubjectModal').modal('hide');
                    } else {
                        $('#modal-message').html(`<div class="alert alert-danger">${response.message}</div>`);
                    }
                },
                error: () => $('#modal-message').html('<div class="alert alert-danger">An error occurred.</div>')
            });
        }
    });

    $('#addSubjectModal').on('show.bs.modal', () => $('#modal-message').html('').add('#new_subject_name').val(''));
});
</script>
</body>
</html>