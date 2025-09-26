<?php
// pages/hr/view_contact_messages.php

// Adjust the paths to your existing project structure
include_once '../../includes/connect.php'; 
include_once '../../encryption.php';

// Define BASE_WEB_PATH
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// --- Authorization Check (ensure only HR can access) ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'hr' || !$userId) {
    header("Location: " . BASE_WEB_PATH . "login.php?error=Unauthorized");
    exit;
}

$message = '';
$all_messages = [];
$hr_user_id = $userId; // The HR user's ID

// --- Handle Form Submission (Mark as Read) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $message_id = filter_var($_POST['message_id'], FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $conn->beginTransaction();

        // 1. Mark the contact message as read/handled
        $update_msg_sql = "UPDATE contact_messages SET is_read = true WHERE id = :id";
        $stmt = $conn->prepare($update_msg_sql);
        $success = $stmt->execute([':id' => $message_id]);

        // 2. Mark corresponding notifications for THIS HR user as read
        // This clears the badge/notification window entry for the current user
        $update_notif_sql = "UPDATE notifications 
                             SET is_read = true 
                             WHERE user_id = :user_id 
                             AND message LIKE :message_pattern 
                             AND type = 'new_contact_message'";
        $stmt_notif = $conn->prepare($update_notif_sql);
        $stmt_notif->execute([
            ':user_id' => $hr_user_id,
            ':message_pattern' => "%(ID: {$message_id})%" // Match notification by ID
        ]);

        if ($success) {
            $message = '<div class="alert alert-success">Message ID ' . $message_id . ' marked as read.</div>';
        } else {
            $message = '<div class="alert alert-warning">Message ID ' . $message_id . ' status not changed.</div>';
        }
        
        $conn->commit();

    } catch (PDOException $e) {
        $conn->rollBack();
        $message = '<div class="alert alert-danger">Database Error: ' . $e->getMessage() . '</div>';
        error_log("Contact message mark read error: " . $e->getMessage());
    }
    
    // Redirect to self to clear POST data and show message
    $redirect_msg = urlencode(strip_tags($message));
    header("Location: view_contact_messages.php?message={$redirect_msg}");
    exit;
}

// --- Fetch all contact messages from the database ---
try {
    // Fetch all messages, ordered by submission date (newest first)
    $stmt = $conn->query("SELECT id, sender_name, sender_email, message, submission_date, is_read 
                          FROM contact_messages 
                          ORDER BY is_read ASC, submission_date DESC");
    $all_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: Could not fetch contact messages. Error: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Contact Messages - BMC-SMS</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        .unread-row {
            font-weight: bold;
            background-color: #fff3cd; /* Light warning background for unread messages */
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?> 
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Contact Messages Inbox</h1>

                    <?php 
                    // Display success or error messages from POST submission/redirect
                    if (isset($_GET['message'])) {
                        $display_msg = htmlspecialchars(urldecode($_GET['message']));
                        echo "<div class='alert alert-info alert-dismissible fade show'>{$display_msg}<button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
                    }
                    echo $message; 
                    ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Contact Submissions</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="messagesTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Sender Name</th>
                                            <th>Email</th>
                                            <th>Submitted On</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($all_messages)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-gray-500">No contact messages have been submitted yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($all_messages as $msg): ?>
                                                <?php $row_class = $msg['is_read'] ? '' : 'unread-row'; ?>
                                                <tr class="<?php echo $row_class; ?>">
                                                    <td><?php echo htmlspecialchars($msg['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($msg['sender_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($msg['sender_email']); ?></td>
                                                    <td><?php echo date('Y-m-d H:i:s', strtotime($msg['submission_date'])); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $msg['is_read'] ? 'success' : 'warning'; ?>">
                                                            <?php echo $msg['is_read'] ? 'Read/Handled' : 'Unread'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-primary btn-sm view-message" 
                                                            data-id="<?php echo $msg['id']; ?>" 
                                                            data-name="<?php echo htmlspecialchars($msg['sender_name']); ?>"
                                                            data-email="<?php echo htmlspecialchars($msg['sender_email']); ?>"
                                                            data-message="<?php echo htmlspecialchars($msg['message']); ?>"
                                                            data-is-read="<?php echo (int)$msg['is_read']; ?>"
                                                            data-toggle="modal" data-target="#messageModal">
                                                            <i class="fas fa-eye"></i> View
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
    
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Message from <span id="modalSenderName"></span></h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="view_contact_messages.php" id="markReadForm">
                    <div class="modal-body">
                        <input type="hidden" name="message_id" id="modalMessageId">
                        
                        <p><strong>Email:</strong> <span id="modalSenderEmail"></span></p>
                        <hr>
                        <p><strong>Message:</strong></p>
                        <p id="modalMessageContent" class="alert alert-light"></p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Close</button>
                        <button class="btn btn-success" type="submit" id="markReadButton">
                            <i class="fas fa-check"></i> Mark as Handled
                        </button>
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
    <script>
        $(document).ready(function() {
            $('#messagesTable').DataTable({
                "order": [[4, "asc"], [3, "desc"]] // Order by Status (unread first) then Date (newest first)
            });

            $('.view-message').on('click', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var email = $(this).data('email');
                var message = $(this).data('message');
                var isRead = $(this).data('is-read');
                
                $('#modalMessageId').val(id);
                $('#modalSenderName').text(name);
                $('#modalSenderEmail').text(email);
                $('#modalMessageContent').text(message);
                
                // Toggle visibility of the Mark as Handled button
                if (isRead === 1) {
                    $('#markReadButton').hide();
                } else {
                    $('#markReadButton').show();
                }
            });
        });
    </script>
</body>

</html>