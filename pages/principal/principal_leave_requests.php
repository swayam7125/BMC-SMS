<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

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
    <!-- Corrected Font Awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
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
                                                        <a href="update_leave_status.php?id='. $row['id'] .'&action=approve" class="btn btn-success btn-sm">Approve</a>
                                                        <a href="update_leave_status.php?id='. $row['id'] .'&action=reject" class="btn btn-danger btn-sm">Reject</a>
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
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>