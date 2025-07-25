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

// Fetch deleted student data with school name
$query = "SELECT ds.*, s.school_name 
          FROM deleted_students ds
          LEFT JOIN school s ON ds.school_id = s.id
          ORDER BY ds.deleted_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Deleted Student Records - School Management System</title>

    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">

</head>

<body id="page-top">
    <div id="wrapper">

        <?php include_once '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <?php include_once '../../includes/header.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Deleted Student Records</h1>
                    <p class="mb-4">A complete log of all students that have been deleted from the system.</p>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Deleted Students Log (All Fields)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Roll No</th>
                                            <th>Standard</th>
                                            <th>Academic Year</th>
                                            <th>DOB</th>
                                            <th>Gender</th>
                                            <th>Blood Group</th>
                                            <th>Father's Name</th>
                                            <th>Father's Phone</th>
                                            <th>Mother's Name</th>
                                            <th>Mother's Phone</th>
                                            <th>Address</th>
                                            <th>School ID</th>
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
                                                echo "<td>" . htmlspecialchars($row['student_name'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['rollno'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['std'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['academic_year'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['dob'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['gender'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['blood_group'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['father_name'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['father_phone'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['mother_name'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['mother_phone'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['address'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['school_id'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['deleted_by_role'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['deleted_at'] ?? 'N/A') . "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            // The colspan must match the total number of columns
                                            echo "<tr><td colspan='16' class='text-center'>No deleted students found</td></tr>";
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
                    [15, "desc"] // Sort by "Deleted At" (16th column, index 15)
                ]
            });
        });
    </script>

</body>

</html>