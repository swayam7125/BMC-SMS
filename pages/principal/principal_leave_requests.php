<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// --- START: Mark notification as read ---
if (isset($_GET['notif_id']) && is_numeric($_GET['notif_id'])) {
    $notification_id = $_GET['notif_id'];
    $current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
    if ($current_user_id) {
        $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt_mark_read->bind_param("ii", $notification_id, $current_user_id);
        $stmt_mark_read->execute();
        $stmt_mark_read->close();
    }
}
// --- END: Mark notification as read ---

// Ensure user is a schooladmin
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : '';
if ($role !== 'schooladmin') {
    // Redirect non-admins away
    header("Location: /BMC-SMS/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Teacher Leave Requests</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">

</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Teacher Leave Requests</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Pending Applications</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Teacher Name</th>
                                            <th>From Date</th>
                                            <th>To Date</th>
                                            <th>Reason</th>
                                            <th>Applied On</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Join leave_applications and teacher tables to get the teacher's name
                                        $query = "SELECT l.id, t.teacher_name, l.from_date, l.to_date, l.reason, l.applied_on
                                                  FROM leave_applications l
                                                  JOIN teacher t ON l.teacher_id = t.id
                                                  WHERE l.status = 'Pending'
                                                  ORDER BY l.applied_on ASC";
                                        $result = $conn->query($query);
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['teacher_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['from_date']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['to_date']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['applied_on']) . "</td>";
                                                // Action Buttons
                                                echo '<td>
                                                        <a href="update_leave_status.php?id=' . $row['id'] . '&action=approve" class="btn btn-success btn-sm">Approve</a>
                                                        <button type="button" class="btn btn-danger btn-sm reject-btn" data-toggle="modal" data-target="#rejectionModal" data-id="' . $row['id'] . '">Reject</button>
                                                      </td>';
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo '<tr><td colspan="6" class="text-center">No pending leave requests.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
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

    <div class="modal fade" id="rejectionModal" tabindex="-1" role="dialog" aria-labelledby="rejectionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectionModalLabel">Reason for Rejection</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form action="update_leave_status.php" method="POST">
                    <div class="modal-body">
                        <p>Please provide a reason for rejecting this leave application.</p>
                        <input type="hidden" name="leave_id" id="leave_id_input">
                        <input type="hidden" name="action" value="reject">
                        <div class="form-group">
                            <label for="rejection_reason_textarea">Rejection Reason</label>
                            <textarea class="form-control" id="rejection_reason_textarea" name="rejection_reason"
                                rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button class="btn btn-danger" type="submit">Submit Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <script>
    // Pass the leave application ID to the hidden input field in the rejection modal
    $(document).on("click", ".reject-btn", function() {
        var leaveId = $(this).data('id');
        $("#rejectionModal .modal-body #leave_id_input").val(leaveId);
    });
    </script>
</body>

</html>