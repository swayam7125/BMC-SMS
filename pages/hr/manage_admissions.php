<?php
// pages/hr/manage_admissions.php

// Adjust the paths to your existing project structure
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

// --- Authorization Check (ensure only HR can access) ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
if ($role !== 'hr') {
    header("Location: ../../login.php?error=Unauthorized");
    exit;
}

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
    } else if ($status_action === 'Accepted' && (empty($student_roll_no) || empty($student_class) || empty($meeting_date) || empty($meeting_time))) {
        $message = '<div class="alert alert-danger">All Acceptance fields (Roll, Class, Date, Time) are required.</div>';
    } else {
        try {
            $conn->beginTransaction();

            $update_sql = "UPDATE admission_applications SET 
                           status = :status, remarks = :remarks, 
                           roll_no = :roll_no, class = :class, 
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
                $hr_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
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
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
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
                        <div class="form-group">
                            <label for="status_action">Action</label>
                            <select class="form-control" id="status_action" name="status_action" required>
                                <option value="">Select Status</option>
                                <option value="Pending">Keep as Pending</option>
                                <option value="Accepted">Accept Application</option>
                                <option value="Rejected">Reject Application</option>
                            </select>
                        </div>

                        <div id="acceptFields" style="display:none;">
                            <hr>
                            <h6 class="text-success">Acceptance Details</h6>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="student_roll_no">Student Roll Number (Mandatory)</label>
                                    <input type="text" class="form-control" id="student_roll_no" name="student_roll_no" placeholder="e.g., S2024001">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="student_class">Assign to Class/Grade (Mandatory)</label>
                                    <input type="text" class="form-control" id="student_class" name="student_class" placeholder="e.g., Grade 8">
                                </div>
                            </div>

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


    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#admissionsTable').DataTable();

            $('.view-details').on('click', function() {
                var applicationId = $(this).data('id');

                $('#modalApplicationId').val(applicationId);
                $('#fullApplicationDetails').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading details...</p>');

                // Reset status fields
                $('#status_action').val('');
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
                        $('#status_action').val(currentStatus);
                        $('#modalAppName').text($('#fullApplicationDetails').find('span[data-student-name]').data('student-name'));

                        // Set comment and meeting details if available
                        var currentComment = $('#fullApplicationDetails').find('span[data-current-comment]').data('current-comment');
                        var meetingDate = $('#fullApplicationDetails').find('span[data-meeting-date]').data('meeting-date');
                        var meetingTime = $('#fullApplicationDetails').find('span[data-meeting-time]').data('meeting-time');

                        $('#comment').val(currentComment);
                        $('#meeting_date').val(meetingDate);
                        $('#meeting_time').val(meetingTime);

                        $('#status_action').trigger('change');
                    },
                    error: function() {
                        $('#fullApplicationDetails').html('<p class="text-center text-danger">Could not load full application details.</p>');
                    }
                });
            });

            // Logic to show/hide fields in the modal form based on the selected action
            $(document).on('change', '#status_action', function() {
                var action = $(this).val();

                // Reset required states for all Acceptance fields
                $('#student_roll_no, #student_class, #meeting_date, #meeting_time').prop('required', false);
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