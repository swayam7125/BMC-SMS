<?php
include_once '../../includes/connect.php'; 
include_once '../../encryption.php';
include_once '../../includes/log_system.php';

// Define BASE_WEB_PATH
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// --- Authorization Check (ensure only Super Admin can access) ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$sa_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'Super Admin';

if ($role !== 'superadmin' || !$sa_id) {
    header("Location: " . BASE_WEB_PATH . "login.php?error=Unauthorized");
    exit;
}

$message = '';
$requests = [];

// --- Handle Request Action (Approve/Reject) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = filter_var($_POST['request_id'], FILTER_SANITIZE_NUMBER_INT);
    $action = filter_var($_POST['action'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $comment = trim($_POST['comment'] ?? '');

    // The frontend JS will enforce 'required' for rejection, but we check server-side too.
    if ($action === 'Reject' && empty($comment)) {
        $message = '<div class="alert alert-danger">Comment is required when rejecting a request.</div>';
    } elseif (!in_array($action, ['Approve', 'Reject'])) {
        $message = '<div class="alert alert-danger">Invalid action specified.</div>';
    } else {
        try {
            // NOTE: Assuming $conn is the PDO object defined in connect.php
            $conn->beginTransaction();

            // 1. Fetch the request details
            $stmt_fetch = $conn->prepare("SELECT * FROM school_update_requests WHERE request_id = ? AND status = 'Pending'");
            $stmt_fetch->execute([$request_id]);
            $request = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $message = '<div class="alert alert-warning">Request not found or already handled.</div>';
                $conn->rollBack();
            } else {
                $request_data = json_decode($request['request_data'], true);
                $school_id = $request['school_id'];
                
                if ($action === 'Approve') {
                    // 2. Perform the update on the original 'school' table
                    $update_fields = [];
                    $update_values = [];
                    
                    // Build the dynamic update query from JSONB data
                    foreach ($request_data as $key => $value) {
                        
                        // Skip internal fields if they were accidentally included
                        if (in_array($key, ['school_id_to_edit', 'reason'])) continue;
                        
                        // --- FIX: Correctly format array types for PostgreSQL ---
                        if (is_string($value) && in_array($key, ['education_board', 'school_medium', 'school_category'])) {
                            $arr_values = explode(',', $value);
                            $quoted_values = array_map(function($val) {
                                $clean_val = trim($val, ' "');
                                return strpos($clean_val, ' ') !== false ? '"' . $clean_val . '"' : $clean_val;
                            }, $arr_values);
                            $value_to_insert = '{' . implode(',', $quoted_values) . '}';
                        } else {
                            $value_to_insert = $value;
                        }
                        
                        $update_fields[] = "\"$key\" = ?";
                        $update_values[] = $value_to_insert;
                    }

                    if (!empty($update_fields)) {
                        $update_values[] = $school_id;
                        $sql_update_school = "UPDATE school SET " . implode(', ', $update_fields) . " WHERE id = ?";
                        $stmt_update_school = $conn->prepare($sql_update_school);
                        $stmt_update_school->execute($update_values);
                    }
                    
                    // 3. Update request status to Approved
                    $final_status = 'Approved';
                    $message_text = "School update request (ID: {$request_id}) for {$request_data['school_name']} has been APPROVED.";
                } else {
                    // 3. Update request status to Rejected
                    $final_status = 'Rejected';
                    $message_text = "School update request (ID: {$request_id}) for {$request_data['school_name']} has been REJECTED.";
                }

                // 4. Update the request in the tracking table
                $sql_update_request = "UPDATE school_update_requests 
                                       SET status = ?, action_by = ?, action_at = NOW(), reason = COALESCE(reason, '') || ? 
                                       WHERE request_id = ?";
                $stmt_update_request = $conn->prepare($sql_update_request);
                $stmt_update_request->execute([
                    $final_status,
                    $sa_id,
                    "\n--- SA Comment ---\n" . ($comment ?: 'No comment provided.'),
                    $request_id
                ]);

                // 5. Notify the HR user about the outcome
                $hr_notification_message = "Your school update request (ID: {$request_id}) was {$final_status}.";
                $hr_notification_link = BASE_WEB_PATH . "dashboard.php"; // Generic link to dashboard
                
                $stmt_notify_hr = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
                $stmt_notify_hr->execute([
                    $request['hr_id'], 
                    $hr_notification_message, 
                    $hr_notification_link, 
                    'school_update_status'
                ]);

                $conn->commit();
                // NOTE: Assuming log_interaction function exists in includes/log_system.php
                // Replace log_interaction with your actual logging function if different
                // log_interaction($role, $sa_id, $message_text, $acting_user_name); 
                $message = '<div class="alert alert-success">' . htmlspecialchars($message_text) . '</div>';
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $message = '<div class="alert alert-danger">Database Error during action: ' . $e->getMessage() . '</div>';
            error_log("School update approval error: " . $e->getMessage());
        }
        
        $redirect_msg = urlencode(strip_tags($message));
        header("Location: manage_school_requests.php?message={$redirect_msg}");
        exit;
    }
}

// --- Fetch all requests ---
try {
    // NOTE: Using $conn to be consistent with the rest of the file logic
    $sql = "SELECT sur.*, s.school_name, u.email as hr_email
            FROM school_update_requests sur
            JOIN school s ON sur.school_id = s.id
            JOIN users u ON sur.hr_id = u.id
            ORDER BY sur.status ASC, sur.requested_at DESC";
    $stmt = $conn->query($sql);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: Could not fetch update requests. Error: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage School Update Requests - BMC-SMS</title>
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/responsive.css" />
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?> 
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage School Update Requests</h1>

                    <?php 
                    if (isset($_GET['message'])) {
                        $display_msg = htmlspecialchars(urldecode($_GET['message']));
                        echo "<div class='alert alert-info alert-dismissible fade show'>{$display_msg}<button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
                    }
                    echo $message; 
                    ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Update Requests (Pending First)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="requestsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>School</th>
                                            <th>Requested By (HR)</th>
                                            <th>Status</th>
                                            <th>Requested At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($requests)): ?>
                                            <tr><td colspan="6" class="text-center text-gray-500">No school update requests found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($requests as $req): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($req['request_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($req['school_name']); ?> (ID: <?php echo $req['school_id']; ?>)</td>
                                                    <td><?php echo htmlspecialchars($req['hr_email']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            if ($req['status'] === 'Approved') echo 'success';
                                                            else if ($req['status'] === 'Rejected') echo 'danger';
                                                            else echo 'warning';
                                                        ?>"><?php echo htmlspecialchars($req['status']); ?></span>
                                                    </td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($req['requested_at'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-info btn-sm view-request" 
                                                            data-id="<?php echo $req['request_id']; ?>" 
                                                            data-school-name="<?php echo htmlspecialchars($req['school_name']); ?>"
                                                            data-status="<?php echo htmlspecialchars($req['status']); ?>"
                                                            data-reason="<?php echo htmlspecialchars($req['reason']); ?>"
                                                            data-data='<?php echo htmlspecialchars(json_encode(json_decode($req['request_data'])), ENT_QUOTES, 'UTF-8'); ?>'
                                                            data-toggle="modal" data-target="#requestModal">
                                                            <i class="fas fa-eye"></i> Review
                                                        </button>
                                                    </td>
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
    
    <div class="modal fade" id="requestModal" tabindex="-1" role="dialog" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestModalLabel">Review Update Request: <span id="modalRequestID"></span></h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="manage_school_requests.php">
                    <input type="hidden" name="request_id" id="modalRequestIDInput">
                    <div class="modal-body">
                        
                        <h6 class="text-primary">Requested Changes:</h6>
                        <table class="table table-sm table-bordered table-striped" id="changesTable">
                            <thead><tr><th>Field</th><th>New Value</th></tr></thead>
                            <tbody></tbody>
                        </table>

                        <h6 class="text-danger mt-4">Reason for Request:</h6>
                        <p class="alert alert-light" id="modalReason"></p>

                        <div id="actionFields">
                            <hr>
                            <h6 class="text-primary">Take Action (Update Status)</h6>
                            
                            <div class="form-group">
                                <label for="action_status">Status Action</label>
                                <select class="form-control" id="action_status" name="action" required>
                                    <option value="" selected disabled>Select Action to Proceed</option>
                                    <option value="Approve" class="text-success">Approve and Apply Changes</option>
                                    <option value="Reject" class="text-danger">Reject Request</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="comment">Super Admin Comment <span id="commentRequiredText">(Optional)</span></label>
                                <textarea class="form-control" id="comment" name="comment" rows="2"></textarea>
                            </div>
                            </div>
                        <div id="statusHandled" style="display:none;">
                             <p class="alert alert-secondary text-center font-weight-bold mt-3">This request has already been handled.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Close</button>
                        <button class="btn btn-primary" type="submit" id="submitActionButton" style="display:none;">Submit Action</button>
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
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>
    <script>
        $(document).ready(function() {
            $('#requestsTable').DataTable({
                "order": [[3, "asc"], [4, "desc"]] // Order by Status (Pending first) then Date
            });

            // Function to format keys for display
            function formatKey(key) {
                 return key.replace(/_/g, ' ')
                          .replace(/\b\w/g, c => c.toUpperCase());
            }

            $('.view-request').on('click', function() {
                var id = $(this).data('id');
                var status = $(this).data('status');
                var reason = $(this).data('reason');
                var data = $(this).data('data'); // This is the JSON object of changes
                
                $('#modalRequestID').text('ID: ' + id);
                $('#modalRequestIDInput').val(id);
                $('#modalReason').text(reason);
                $('#changesTable tbody').empty();
                $('#action_status').val(''); // Reset dropdown value
                $('#comment').val(''); // Clear comment field
                $('#comment').removeAttr('required'); // Reset required state
                $('#commentRequiredText').text('(Optional)'); // Reset text

                // Populate Changes Table
                for (const key in data) {
                    // Skip internal fields from showing in the changes table
                    if (key === 'school_id_to_edit' || key === 'reason') continue;

                    if (data.hasOwnProperty(key)) {
                        let formattedValue = data[key];

                        // Handle array literals or comma-separated strings
                        if (formattedValue && typeof formattedValue === 'string') {
                             if (formattedValue.startsWith('{') && formattedValue.endsWith('}')) {
                                formattedValue = formattedValue.substring(1, formattedValue.length - 1).replace(/,/g, ', ');
                             } else if (formattedValue.includes(',')) {
                                formattedValue = formattedValue.replace(/,/g, ', ');
                             }
                        }
                        
                        // Simple capitalization and space for display name
                        let displayKey = formatKey(key);
                        
                        // Display blank if value is null
                        if (formattedValue === null || formattedValue === '') {
                            formattedValue = '—';
                        }

                        $('#changesTable tbody').append(`
                            <tr>
                                <td class="font-weight-bold">${displayKey}</td>
                                <td>${formattedValue}</td>
                            </tr>
                        `);
                    }
                }
                
                // Show/Hide action fields based on status
                if (status === 'Pending') {
                    $('#actionFields').show();
                    $('#statusHandled').hide();
                    $('#submitActionButton').hide(); // Hide the button initially
                } else {
                    $('#actionFields').hide();
                    $('#statusHandled').show();
                    $('#submitActionButton').hide();
                }
            });
            
            // Toggle submit button appearance and validation based on action selected
            $('#action_status').on('change', function() {
                var action = $(this).val();
                var button = $('#submitActionButton');
                var commentField = $('#comment');
                var commentRequiredText = $('#commentRequiredText');
                
                // Reset button and comment state
                button.hide().removeClass('btn-success btn-danger');
                commentField.removeAttr('required');
                commentRequiredText.text('(Optional)');


                if (action === 'Approve') {
                    button.addClass('btn-success').html('<i class="fas fa-check"></i> Approve Changes');
                    button.show();
                } else if (action === 'Reject') {
                    button.addClass('btn-danger').html('<i class="fas fa-times"></i> Reject Request');
                    
                    // Make comment required for rejection
                    commentField.attr('required', 'required');
                    commentRequiredText.text('(Required)');
                    
                    button.show();
                }
            });
        });
    </script>
</body>
</html>