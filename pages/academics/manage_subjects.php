<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
|
| This section handles all server-side operations:
| 1. Includes necessary files for database connection and security.
| 2. Authenticates and authorizes the user based on their role.
| 3. Processes the form submission for assigning subjects to a standard.
| 4. Fetches all required data from the database to be displayed on the page.
|
*/

// Core Includes
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

// --- Authorization ---
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (!$role) {
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
        $standard = $_POST['standard'];
        $subject_ids = isset($_POST['subject_ids']) ? $_POST['subject_ids'] : [];

        if (empty($standard)) $errors[] = "Please select a standard.";
        if (empty($subject_ids)) $errors[] = "Please select at least one subject to assign.";

        if (empty($errors)) {
            $conn->beginTransaction();

            $delete_stmt = $conn->prepare('DELETE FROM "standard_subjects" WHERE "standard" = ?');
            $delete_stmt->execute([$standard]);

            $insert_stmt = $conn->prepare('INSERT INTO "standard_subjects" ("standard", "subject_id") VALUES (?, ?)');
            foreach ($subject_ids as $subject_id) {
                $insert_stmt->execute([$standard, $subject_id]);
            }

            $conn->commit();
            $success_message = "Subjects for standard '" . htmlspecialchars($standard) . "' have been updated successfully!";
        }
    }

    // --- Data Fetching ---
    $subjects_stmt = $conn->query('SELECT "subject_id", "subject_name" FROM "subjects" ORDER BY "subject_name"');
    $all_subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

    $assignments_query = "SELECT ss.standard, STRING_AGG(s.subject_name, ', ' ORDER BY s.subject_name) as assigned_subjects
                          FROM standard_subjects ss
                          JOIN subjects s ON ss.subject_id = s.subject_id
                          GROUP BY ss.standard
                          ORDER BY 
                            CASE
                                WHEN ss.standard = 'Nursery' THEN -3
                                WHEN ss.standard = 'Junior' THEN -2
                                WHEN ss.standard = 'Senior' THEN -1
                                ELSE CAST(ss.standard AS INTEGER)
                            END, 
                            ss.standard";
    $assignments_stmt = $conn->query($assignments_query);
    $all_assignments = $assignments_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    $errors[] = "Database error: " . $e->getMessage();
}

// The PHP logic ends here. The HTML part will now render the page using the variables defined above.
?>

<?php
/*
|--------------------------------------------------------------------------
| RESPONSIVE FRONTEND (VIEW)
|--------------------------------------------------------------------------
|
| This section contains the complete HTML structure for the page.
| - It uses Bootstrap's grid system (`row`, `col-*`) for a responsive layout.
| - The layout is a two-column design on desktops and stacks on mobile.
| - The table is wrapped in `.table-responsive` to prevent layout breaking.
|
*/
if (!is_ajax_request()): // This check prevents the HTML from being sent on AJAX calls
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Manage Standard Subjects - School Management System</title>

        <!-- Professional Font & Icons -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

        <!-- Core CSS -->
        <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="../../assets/css/sidebar.css">
        <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    </head>

    <body id="page-top">
        <div id="wrapper">
            <?php include '../../includes/sidebar.php'; ?>
            <div id="content-wrapper" class="d-flex flex-column">
                <div id="content">
                    <?php include_once '../../includes/header.php'; ?>

                    <div class="container-fluid">
                        <h1 class="h3 mb-4 text-gray-800">Manage Standard Subjects</h1>

                        <!-- Display Success or Error Messages -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error) echo "<p class='mb-0'>" . htmlspecialchars($error) . "</p>"; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                        <?php endif; ?>

                        <!-- Responsive Row: Stacks on medium screens and below -->
                        <div class="row">

                            <!-- Column 1: Assign Subjects Form -->
                            <div class="col-lg-5 mb-4">
                                <div class="card shadow h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Assign Subjects to a Standard</h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="form-group">
                                                <label for="standard">Select Standard <span class="text-danger">*</span></label>
                                                <select class="form-control" id="standard" name="standard" required>
                                                    <option value="">-- Choose a Standard --</option>
                                                    <?php foreach ($standards as $std): ?>
                                                        <option value="<?php echo htmlspecialchars($std); ?>"><?php echo htmlspecialchars($std); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="subject_ids">Select Subjects <span class="text-danger">*</span></label>
                                                <select class="form-control multi-select" id="subject_ids" name="subject_ids[]" multiple="multiple" required>
                                                    <?php foreach ($all_subjects as $subject): ?>
                                                        <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#addSubjectModal">
                                                    <i class="fas fa-plus"></i> Add New Subject
                                                </button>
                                            </div>
                                            <hr>
                                            <button type="submit" name="assign_subjects" class="btn btn-primary btn-block">
                                                <i class="fas fa-save mr-2"></i>Update Subjects
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Current Assignments Table -->
                            <div class="col-lg-7 mb-4">
                                <div class="card shadow h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Current Subject Assignments</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Standard</th>
                                                        <th>Assigned Subjects</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($all_assignments)): ?>
                                                        <tr>
                                                            <td colspan="2" class="text-center">No subjects have been assigned yet.</td>
                                                        </tr>
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
                            <label for="new_subject_name">Subject Name <span class="text-danger">*</span></label>
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

        <?php include_once "../../includes/logout_modal.php" ?>

        <!-- Core Scripts -->
        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>

        <!-- Page-specific Plugins -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <?php
        /*
    |--------------------------------------------------------------------------
    | JAVASCRIPT LOGIC
    |--------------------------------------------------------------------------
    |
    | This section contains all the client-side JavaScript for the page.
    | - It is placed at the end of the body for faster page loading.
    | - It handles the Select2 plugin and all AJAX interactions.
    |
    */
        ?>
        <script>
            $(document).ready(function() {
                // Initialize the Select2 plugin for a better multi-select experience
                $('.multi-select').select2({
                    placeholder: "Choose subjects...",
                    width: '100%' // Ensures the dropdown is responsive
                });

                // AJAX call to fetch assigned subjects when a standard is selected
                $('#standard').change(function() {
                    const selectedStandard = $(this).val();
                    if (selectedStandard) {
                        $.ajax({
                            url: 'ajax_handler.php',
                            type: 'POST',
                            data: {
                                action: 'get_subjects_for_standard',
                                standard: selectedStandard
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    $('#subject_ids').val(response.subject_ids).trigger('change');
                                }
                            },
                            error: function() {
                                console.error('AJAX error while fetching subjects.');
                            }
                        });
                    } else {
                        $('#subject_ids').val(null).trigger('change');
                    }
                });

                // AJAX call to save a new subject from the modal without reloading the page
                $('#saveNewSubjectBtn').click(function() {
                    const subjectName = $('#new_subject_name').val().trim();
                    if (subjectName) {
                        $.ajax({
                            url: 'ajax_handler.php',
                            type: 'POST',
                            data: {
                                action: 'add_subject',
                                subject_name: subjectName
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    // Add the new subject to the dropdown list dynamically
                                    const newOption = new Option(response.subject.subject_name, response.subject.subject_id, false, false);
                                    $('#subject_ids').append(newOption).trigger('change');

                                    // Hide the modal and clear the input field
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
<?php
endif; // End the is_ajax_request() check
$conn = null;
?>