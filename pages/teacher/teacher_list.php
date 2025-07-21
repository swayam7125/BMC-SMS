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

// UPDATED: Query now fetches batch, and does not fetch gender
$query = "SELECT t.id, t.teacher_name, t.email, t.phone, t.subject, t.std, t.batch,
                sc.school_name 
        FROM teacher t 
        LEFT JOIN school sc ON t.school_id = sc.id
        ORDER BY t.id ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Teacher Tables - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include_once '../../includes/sidebar/BMC_sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header/BMC_header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Teacher Tables</h1>
                    <p class="mb-4">Complete list of all teachers in the school management system.</p>

                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Teacher DataTable</h6>
                            <a href="/BMC-SMS/includes/forms/teacher_enrollment.php" class="btn btn-primary btn-icon-split btn-sm">
                                <span class="icon text-white-50"><i class="fas fa-plus"></i></span><span class="text">Add New Teacher</span>
                            </a>
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
                                            <th>School</th>
                                            <th>Batch</th>
                                            <th>Subject</th>
                                            <th>Standards</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                                echo "<td><a href='view.php?id=" . $row['id'] . "'>" . htmlspecialchars($row['teacher_name'] ?? 'N/A') . "</a></td>";
                                                echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['phone'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['school_name'] ?? 'N/A') . "</td>";
                                                // ADDED: Batch column
                                                echo "<td>" . htmlspecialchars($row['batch'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['subject'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['std'] ?? 'N/A') . "</td>";
                                                echo "<td>";
                                                echo "<a href='view.php?id=" . $row['id'] . "' class='btn btn-info btn-sm mr-1' title='View'><i class='fas fa-eye'></i></a>";
                                                echo "<a href='edit.php?id=" . $row['id'] . "' class='btn btn-primary btn-sm mr-1' title='Edit'><i class='fas fa-edit'></i></a>";
                                                echo "<button class='btn btn-danger btn-sm' onclick='confirmDelete(" . $row['id'] . ")' title='Delete'><i class='fas fa-trash'></i></button>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            // UPDATED: Colspan is now 9
                                            echo "<tr><td colspan='9' class='text-center'>No teachers found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer/BMC_footer.php'; ?>
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
        $('#dataTable').DataTable({ "pageLength": 25, "order": [[0, "asc"]] });
    });
    function confirmDelete(id) {
        $('#confirmDeleteBtn').attr('href', 'delete.php?id=' + id);
        $('#deleteModal').modal('show');
    }
    </script>

    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Confirm Delete</h5><button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button></div><div class="modal-body">Are you sure you want to delete this teacher? This action cannot be undone.</div><div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><a class="btn btn-danger" id="confirmDeleteBtn" href="#">Delete</a></div></div></div></div>
</body>
</html>