<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (!$role) {
    header("Location: ../login.php");
    exit;
}

// Fetch all records from the deleted_schools table
$query = "SELECT * FROM deleted_schools ORDER BY deleted_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Deleted School Records - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include_once '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Deleted School Records</h1>
                    <p class="mb-4">A log of all schools that have been deleted from the system.</p>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Deleted Schools Log</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>School Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>School Opening</th>
                                            <th>School Type</th>
                                            <th>Education Board</th>
                                            <th>School Medium</th>
                                            <th>School Category</th>
                                            <th>Address</th>
                                            <th>Deleted By</th>
                                            <th>Deleted At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['school_name'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['phone'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['school_opening'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['school_type'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['education_board'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['school_medium'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['school_category'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['address'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['deleted_by_role'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['deleted_at'] ?? 'N/A') . "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7' class='text-center'>No deleted schools found</td></tr>";
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
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [[6, "desc"]] // Sort by "Deleted At" column
            });
        });
    </script>
</body>
</html>