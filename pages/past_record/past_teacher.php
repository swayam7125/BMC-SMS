<?php

include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if not logged in
if (!$role) {
    header("Location: ../login.php");
    exit;
}

// Fetch deleted teacher data with school name
$query = "SELECT dt.*, s.school_name 
          FROM deleted_teachers dt
          LEFT JOIN school s ON dt.school_id = s.id
          ORDER BY dt.deleted_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Deleted Teacher Records - School Management System</title>

    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
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
                    <h1 class="h3 mb-2 text-gray-800">Deleted Teacher Records</h1>
                    <p class="mb-4">A complete log of all teachers that have been deleted from the system.</p>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Deleted Teachers Log (All Fields)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Gender</th>
                                            <th>DOB</th>
                                            <th>Blood Group</th>
                                            <th>Address</th>
                                            <th>School ID</th>
                                            <th>Qualification</th>
                                            <th>Subject</th>
                                            <th>Languages</th>
                                            <th>Salary</th>
                                            <th>Standards</th>
                                            <th>Experience</th>
                                            <th>Batch</th>
                                            <th>Class Teacher</th>
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
                                                echo "<td>" . htmlspecialchars($row['teacher_name'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['phone'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['gender'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['dob'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['blood_group'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['address'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['school_id'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['qualification'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['subject'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['language_known'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['salary'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['std'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['experience'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['batch'] ?? 'N/A') . "</td>";
                                                // Display "Yes" or "No" for boolean value
                                                $is_class_teacher = ($row['class_teacher'] == 1) ? 'Yes' : 'No';
                                                echo "<td>" . $is_class_teacher . "</td>";
                                                echo "<td>" . htmlspecialchars($row['deleted_by_role'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['deleted_at'] ?? 'N/A') . "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            // The colspan must match the total number of columns
                                            echo "<tr><td colspan='19' class='text-center'>No deleted teachers found</td></tr>";
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

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
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

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "pageLength": 10,
                "order": [
                    [18, "desc"] // Sort by "Deleted At" (19th column, index 18)
                ]
            });
        });
    </script>

</body>
</html>