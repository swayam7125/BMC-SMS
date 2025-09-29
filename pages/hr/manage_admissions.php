<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // ADDED: Log system dependency

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// --- Authorization Check (ensure only HR can access) ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$hr_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null; // ADDED: Get user ID for logging
$hr_user_name = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'Unknown'; // ADDED: Get user name for logging

if ($role !== 'hr') {
    header("Location: ../../login.php?error=Unauthorized");
    exit;
}

// --- LOG PAGE VIEW ---
log_interaction($role, $hr_user_id, 'Viewed the admission applications management page.', $hr_user_name);
// ---------------------

$message = '';

// --- 1. Handle Status Update Form Submission (Accept/Reject/Pending) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'])) {
    $application_id = filter_var($_POST['application_id'], FILTER_SANITIZE_NUMBER_INT);
    $status_action = filter_var($_POST['status_action'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $comment = trim($_POST['comment'] ?? '');

    // Fields for Acceptance (Mandatory)
    $student_roll_no = filter_var($_POST['student_roll_no'] ?? null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $student_class = filter_var($_POST['student_class'] ?? null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Fields for Meeting (Mandatory when Accepted)
    $meeting_date = filter_var($_POST['meeting_date'] ?? null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $meeting_time = filter_var($_POST['meeting_time'] ?? null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Input validation: Check for mandatory fields only if Accepted is chosen
    if (!in_array($status_action, ['Accepted', 'Rejected', 'Pending'])) {
        $message = '<div class="alert alert-danger">Invalid status action.</div>';
    }
    // UPDATED VALIDATION: Now only checks for meeting date/time if Accepted
    else if ($status_action === 'Accepted' && (empty($meeting_date) || empty($meeting_time))) {
        $message = '<div class="alert alert-danger">The Acceptance action requires Meeting Date and Time.</div>';
    } else {
        try {
            $conn->beginTransaction();

            // REMOVED 'roll_no' and 'class' columns from the update statement
            $update_sql = "UPDATE admission_applications SET
                           status = :status, remarks = :remarks,
                           roll_no = NULL, class = NULL,
                           meeting_date = :meeting_date, meeting_time = :meeting_time
                           WHERE id = :id";
            $stmt = $conn->prepare($update_sql);

            $success = $stmt->execute([
                ':status' => $status_action,
                ':remarks' => $comment,
                ':roll_no' => ($status_action === 'Accepted' ? $student_roll_no : null),
                ':class' => ($status_action === 'Accepted' ? $student_class : null),
                ':meeting_date' => ($status_action === 'Accepted' ? $meeting_date : null),
                ':meeting_time' => ($status_action === 'Accepted' ? $meeting_time : null),
                ':id' => $application_id
            ]);

            if ($success) {
                // --- LOG SUCCESSFUL STATUS UPDATE ---
                $log_message = "Updated admission application ID {$application_id} to status: {$status_action}.";
                log_interaction($role, $hr_user_id, $log_message, $hr_user_name);
                // ------------------------------------

                if ($hr_user_id) {
                    $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = true WHERE user_id = ? AND type = 'new_admission_request'");
                    $stmt_mark_read->execute([$hr_user_id]);
                }

                $message = '<div class="alert alert-success">Application ID ' . $application_id . ' status updated to ' . htmlspecialchars($status_action) . '.</div>';
            }

            $conn->commit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $message = '<div class="alert alert-danger">Database Error: ' . $e->getMessage() . '</div>';
            error_log("Admission status update error: " . $e->getMessage());

            // --- LOG DATABASE ERROR ---
            $error_log_message = "Failed to update status for application ID {$application_id}. Error: " . $e->getMessage();
            log_interaction($role, $hr_user_id, $error_log_message, $hr_user_name);
            // --------------------------
        }

        $redirect_msg = urlencode(strip_tags($message));
        header("Location: manage_admissions.php?message={$redirect_msg}");
        exit;
    }
}


// --- 2. Fetch all admission inquiries from the database ---
try {
    $stmt = $conn->query("SELECT id, admission_id, first_name, last_name, phone, status, created_at FROM admission_applications ORDER BY created_at DESC");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: Could not fetch applications. Ensure 'admission_applications' table exists. Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admission Applications - BMC-SMS</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css" />
    <style>
        /* Minimalist style for radio buttons */
        .status-radio-group .form-check {
            padding-left: 0;
            margin-bottom: 0.5rem;
        }
        .status-radio-group .form-check-label {
            margin-left: 0;
            cursor: pointer;
            padding: 0.6rem 1rem;
            border-radius: 0.35rem;
            transition: all 0.2s;
            display: flex; /* Use flex for icon/text alignment */
            align-items: center;
            border: 1px solid #e3e6f0; /* Light border */
            color: #5a5c69; /* Gray text */
        }
        .status-radio-group .form-check-input {
            /* Hide the default radio button */
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        .status-radio-group .form-check-label i {
            margin-right: 0.75rem;
        }

        /* Active/Checked state styles (Border and Text Color) */
        .status-radio-group input[type="radio"]:checked + .form-check-label {
            border-width: 2px;
            font-weight: 600;
        }
        #status_pending:checked + .form-check-label {
            border-color: #ffc107; /* Warning border */
            color: #ffc107;
        }
        #status_accepted:checked + .form-check-label {
            border-color: #28a745; /* Success border */
            color: #28a745;
        }
        #status_rejected:checked + .form-check-label {
            border-color: #dc3545; /* Danger border */
            color: #dc3545;
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
            <div id="content">
                <?php
                if (!$is_ajax_request) {
                    include '../../includes/header.php';
                }
                ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage Admission Applications</h1>

                    <?php
                    if (isset($_GET['message'])) {
                        $display_msg = htmlspecialchars(urldecode($_GET['message']));
                        echo "<div class='alert alert-info alert-dismissible fade show'>{$display_msg}<button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
                    }
                    echo $message;
                    ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Applications</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="admissionsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Admission ID</th>
                                            <th>Student Name</th>
                                            <th>Contact Phone</th>
                                            <th>Status</th>
                                            <th>Applied On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($app['admission_id']); ?></td>
                                                <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($app['phone']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                                                switch ($app['status']) {
                                                                                    case 'Accepted':
                                                                                        echo 'success';
                                                                                        break;
                                                                                    case 'Rejected':
                                                                                        echo 'danger';
                                                                                        break;
                                                                                    case 'Submitted':
                                                                                    case 'Pending':
                                                                                        echo 'warning';
                                                                                        break;
                                                                                    default:
                                                                                        echo 'primary';
                                                                                }
                                                                                ?>"><?php echo htmlspecialchars($app['status']); ?></span>
                                                </td>
                                                <td><?php echo date('Y-m-d', strtotime($app['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-info btn-sm view-details"
                                                        data-id="<?php echo $app['id']; ?>"
                                                        data-toggle="modal" data-target="#detailsModal">
                                                        <i class="fas fa-edit"></i> Review
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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

    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Review Admission Application: <span id="modalAppName"></span></h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="manage_admissions.php">
                    <div class="modal-body">
                        <input type="hidden" name="application_id" id="modalApplicationId">

                        <div id="fullApplicationDetails">
                            <p class="text-center">Loading application details...</p>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3 text-primary">Update Status</h5>

                        <div class="form-group status-radio-group">
                            <label class="text-muted mb-2">Action</label>

                            <div class="form-check">
                                <input class="form-check-input status-radio" type="radio" name="status_action" id="status_pending" value="Pending" required>
                                <label class="form-check-label" for="status_pending">
                                    <i class="fas fa-history"></i> Keep as Pending
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input status-radio" type="radio" name="status_action" id="status_accepted" value="Accepted" required>
                                <label class="form-check-label" for="status_accepted">
                                    <i class="fas fa-check-circle"></i> Accept Application
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input status-radio" type="radio" name="status_action" id="status_rejected" value="Rejected" required>
                                <label class="form-check-label" for="status_rejected">
                                    <i class="fas fa-times-circle"></i> Reject Application
                                </label>
                            </div>

                            <input type="radio" name="status_action" id="status_select" value="" style="display:none;" checked>
                        </div>
                        <div id="acceptFields" style="display:none;">
                            <hr>
                            <h6 class="text-success">Acceptance Details</h6>

                            <h6 class="text-info mt-4">Schedule Orientation/Sign-up Meeting (Mandatory)</h6>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="meeting_date">Meeting Date (Mandatory)</label>
                                    <input type="date" class="form-control" id="meeting_date" name="meeting_date">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="meeting_time">Meeting Time (Mandatory)</label>
                                    <input type="time" class="form-control" id="meeting_time" name="meeting_time">
                                </div>
                            </div>
                            <div class="alert alert-info">The student will see this date/time when checking their application.</div>
                        </div>
                        <div id="rejectFields" style="display:none;">
                            <hr>
                            <h6 class="text-danger">Rejection Details</h6>
                            <div class="alert alert-warning">The application will be marked as Rejected.</div>
                        </div>

                        <div class="form-group mt-3">
                            <label for="comment">Comment/Remarks (Optional)</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>

    <script>
        $(document).ready(function() {
            $('#admissionsTable').DataTable();

            $('.view-details').on('click', function() {
                var applicationId = $(this).data('id');

                $('#modalApplicationId').val(applicationId);
                $('#fullApplicationDetails').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading details...</p>');

                // Reset radio buttons
                $('.status-radio').prop('checked', false);
                $('#status_select').prop('checked', true); // Select the hidden "Select Status" option initially
                $('#acceptFields').hide();
                $('#rejectFields').hide();

                // Fetch full application details via AJAX
                $.ajax({
                    url: 'get_admission_details.php',
                    type: 'GET',
                    data: {
                        id: applicationId
                    },
                    success: function(response) {
                        $('#fullApplicationDetails').html(response);

                        var currentStatus = $('#fullApplicationDetails').find('span[data-current-status]').data('current-status');

                        // Select the correct radio button based on status
                        var radioId = '#status_' + currentStatus.toLowerCase().replace(/\s+/g, '_');
                        $(radioId).prop('checked', true);

                        $('#modalAppName').text($('#fullApplicationDetails').find('span[data-student-name]').data('student-name'));

                        // Set comment and meeting details if available
                        var currentComment = $('#fullApplicationDetails').find('span[data-current-comment]').data('current-comment');
                        var meetingDate = $('#fullApplicationDetails').find('span[data-meeting-date]').data('meeting-date');
                        var meetingTime = $('#fullApplicationDetails').find('span[data-meeting-time]').data('meeting-time');

                        $('#comment').val(currentComment);
                        $('#meeting_date').val(meetingDate);
                        $('#meeting_time').val(meetingTime);

                        // Trigger change handler to show/hide fields based on the selected radio
                        $('.status-radio:checked').trigger('change');
                    },
                    error: function() {
                        $('#fullApplicationDetails').html('<p class="text-center text-danger">Could not load full application details.</p>');
                    }
                });
            });

            // Logic to show/hide fields, targeting the radio buttons
            $(document).on('change', '.status-radio', function() {
                var action = $(this).val(); // Get the value of the selected radio button

                // Remove existing active styles (resets border/text color)
                $('.status-radio-group input[type="radio"] + .form-check-label').css({
                    'border-color': '#e3e6f0',
                    'color': '#5a5c69'
                }).removeClass('font-weight-bold');

                // Apply active style to the selected label
                if (action) {
                    var targetLabel = $(this).next('.form-check-label');
                    if (action === 'Accepted') {
                        targetLabel.css({'border-color': '#28a745', 'color': '#28a745'});
                    } else if (action === 'Rejected') {
                        targetLabel.css({'border-color': '#dc3545', 'color': '#dc3545'});
                    } else if (action === 'Pending') {
                         targetLabel.css({'border-color': '#ffc107', 'color': '#ffc107'});
                    }
                    targetLabel.addClass('font-weight-bold');
                }

                // Set required states for the mandatory fields (only meeting fields remain)
                $('#meeting_date, #meeting_time').prop('required', false);

                $('#acceptFields, #rejectFields').hide();

                if (action === 'Accepted') {
                    $('#acceptFields').show();

                    // ⭐ ALL 4 FIELDS ARE NOW SET TO REQUIRED:
                    $('#student_roll_no, #student_class, #meeting_date, #meeting_time').prop('required', true);

                    // Pre-fill roll/class if already accepted
                    var roll = $('#fullApplicationDetails').find('span[data-current-roll]').data('current-roll');
                    var class_name = $('#fullApplicationDetails').find('span[data-current-class]').data('current-class');
                    $('#student_roll_no').val(roll);
                    $('#student_class').val(class_name);

                } else if (action === 'Rejected') {
                    $('#rejectFields').show();

                } else {
                    // Default/Pending
                }
            });
        });
    </script>
</body>

</html>