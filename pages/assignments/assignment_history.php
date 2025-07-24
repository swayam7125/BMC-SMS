<?php
// Standard setup from your dashboard.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security Check: Ensure user is logged in and is a teacher
if (!$role || $role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

// PHP to fetch assignment history from the database would go here.
// For example: $stmt = $conn->prepare("SELECT * FROM assignments WHERE teacher_id = ?");
// $stmt->bind_param("i", $userId); ... etc.
// For now, we use static data.

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Teacher - Assignment History</title>

    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <!-- Optional: Add datatables CSS if you want sorting/searching -->
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Sent Assignment History</h1>
                    <p class="mb-4">A record of all assignments you have sent. You can view submission status and details for each.</p>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Assignment Log</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Assigned To</th>
                                            <th>Sent Date</th>
                                            <th>Due Date</th>
                                            <th>Submissions</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- This part would be populated by a PHP loop -->
                                        <tr>
                                            <td>Chapter 5: Algebra</td>
                                            <td>Class 10 - A</td>
                                            <td>2025-07-24</td>
                                            <td>2025-08-01</td>
                                            <td>15 / 30</td>
                                            <td><a href="#" class="btn btn-info btn-sm btn-icon-split"><span class="icon"><i class="fas fa-eye"></i></span><span class="text">View</span></a></td>
                                        </tr>
                                        <tr>
                                            <td>History of Ancient Civilizations</td>
                                            <td>Class 11 - Arts</td>
                                            <td>2025-07-22</td>
                                            <td>2025-07-30</td>
                                            <td>25 / 28</td>
                                            <td><a href="#" class="btn btn-info btn-sm btn-icon-split"><span class="icon"><i class="fas fa-eye"></i></span><span class="text">View</span></a></td>
                                        </tr>
                                        <tr>
                                            <td>Lab Report: Photosynthesis</td>
                                            <td>Class 11 - Science</td>
                                            <td>2025-07-20</td>
                                            <td>2025-07-28</td>
                                            <td>32 / 32</td>
                                            <td><a href="#" class="btn btn-info btn-sm btn-icon-split"><span class="icon"><i class="fas fa-eye"></i></span><span class="text">View</span></a></td>
                                        </tr>
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

    <!-- Standard Modals and Scripts -->
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="../../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <!-- Optional: Add datatables for sorting/searching -->
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>$(document).ready(function() { $('#dataTable').DataTable(); });</script>
</body>
</html>
