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

// Fetch student data with school information
$query = "SELECT s.id, s.student_name, s.rollno, s.std, s.email, s.academic_year, 
                s.dob, s.gender, s.blood_group, s.address, s.father_name, s.father_phone, 
                s.mother_name, s.mother_phone, sc.school_name 
        FROM student s 
        LEFT JOIN school sc ON s.school_id = sc.id
        ORDER BY s.id ASC";  // Changed from DESC to ASC
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Student Tables - School Management System</title>

    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Corrected Font Awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <!-- DataTables CSS -->
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

            <link rel="stylesheet" href="../../assets/css/sidebar.css">

</head>

<body id="page-top">
    <div id="wrapper">

    <?php include '../../includes/sidebar.php';?>
        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">

                    <h1 class="h3 mb-2 text-gray-800">Student Tables</h1>
                    <p class="mb-4">Complete list of all students in the school management system.
                    </p>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Student DataTable</h6>
                            <a href="/BMC-SMS/includes/forms/student_enrollment.php" class="btn btn-primary btn-icon-split btn-sm">
                                <span class="icon text-white-50">
                                    <i class="fas fa-plus"></i>
                                </span>
                                <span class="text">Add New Student</span>
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Roll No</th>
                                            <th>Standard</th>
                                            <th>Gender</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php
                                        if ($result && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                                echo "<td>";
                                                echo "<a href='view.php?id=" . $row['id'] . "' class='text-decoration-none'>";
                                                echo htmlspecialchars($row['student_name'] ?? 'N/A');
                                                echo "</a>";
                                                echo "</td>";
                                                echo "<td>" . htmlspecialchars($row['rollno'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['std'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars(ucfirst($row['gender'] ?? 'N/A')) . "</td>";
                                                echo "<td>";
                                                echo "<a href='view.php?id=" . $row['id'] . "' class='btn btn-info btn-sm mr-2'>";
                                                echo "<i class='fas fa-eye'></i>";
                                                echo "</a>";
                                                echo "<a href='edit.php?id=" . $row['id'] . "' class='btn btn-primary btn-sm mr-2'>";
                                                echo "<i class='fas fa-edit'></i>";
                                                echo "</a>";
                                                echo "<button class='btn btn-danger btn-sm' onclick='confirmDelete(" . $row['id'] . ")'>";
                                                echo "<i class='fas fa-trash'></i>";
                                                echo "</button>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center'>No students found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                </div>
            <?php
            include '../../includes/footer.php';
            ?>
            </div>
        </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

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

    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Are you sure you want to delete this student? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-danger" id="confirmDeleteBtn" href="#">Delete</a>
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
        // Call the dataTables jQuery plugin
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "pageLength": 25,
                "order": [
                    [0, "asc"]
                ]
            });
        });

        // Delete confirmation function
        function confirmDelete(id) {
            $('#confirmDeleteBtn').attr('href', 'delete.php?id=' + id);
            $('#deleteModal').modal('show');
        }
    </script>

</body>

</html>