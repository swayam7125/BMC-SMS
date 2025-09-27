<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$schools = [];
try {
    // Corrected query using STRING_AGG to group principals by school and include batch information
    $query =  'SELECT s.id, s.school_name, s.email, s.phone, s.address, 
               STRING_AGG(p.principal_name || \' (\' || p.batch || \')\', \', \' ORDER BY p.principal_name) AS principal_names 
               FROM "school" s 
               LEFT JOIN "principal" p ON s.id = p.school_id
               GROUP BY s.id, s.school_name, s.email, s.phone, s.address
               ORDER BY s.id ASC';
    $stmt = $conn->query($query);
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>School Tables - School Management System</title>

    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php
        if (!$is_ajax_request) {
            include '../../includes/sidebar.php';
        }
        ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
                if (!$is_ajax_request) {
                    include '../../includes/header.php';
                }
                ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">School Tables</h1>
                    <p class="mb-4">Complete list of all schools in the school management system.</p>

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
                            <h6 class="m-0 font-weight-bold text-primary">School DataTable</h6>
                            <a href="/BMC-SMS/includes/forms/school_enrollment.php"
                                class="btn btn-primary btn-icon-split btn-sm">
                                <span class="icon text-white-50">
                                    <i class="fas fa-plus"></i>
                                </span>
                                <span class="text">Add New School</span>
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="schoolListTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>School ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Address</th>
                                            <th>Principal Name <sup>*with batch</sup></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (!empty($schools)) {
                                            foreach ($schools as $row) {
                                                // Handle the aggregated principal names, showing "Not Assigned" if none exist.
                                                $principalName = $row['principal_names'] ? htmlspecialchars($row['principal_names']) : '<span class="text-danger">Not Assigned</span>';

                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                                echo "<td>";
                                                echo "<a href='view.php?id=" . htmlspecialchars($row['id']) . "' class='text-decoration-none'>";
                                                echo htmlspecialchars($row['school_name'] ?? 'N/A');
                                                echo "</a>";
                                                echo "</td>";
                                                echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['phone'] ?? 'N/A') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['address'] ?? 'N/A') . "</td>";
                                                // Display the aggregated principal names with batch info or the "Not Assigned" message.
                                                echo "<td>" . $principalName . "</td>";
                                                echo "<td>";
                                                echo "<a href='view.php?id=" . htmlspecialchars($row['id']) . "' class='btn btn-info btn-sm mr-2' title='View'>";
                                                echo "<i class='fas fa-eye'></i>";
                                                echo "</a>";
                                                echo "<a href='edit.php?id=" . htmlspecialchars($row['id']) . "' class='btn btn-primary btn-sm mr-2' title='Edit'>";
                                                echo "<i class='fas fa-edit'></i>";
                                                echo "</a>";
                                                echo "<button class='btn btn-danger btn-sm' onclick='confirmDelete(" . htmlspecialchars($row['id']) . ")' title='Delete'>";
                                                echo "<i class='fas fa-trash'></i>";
                                                echo "</button>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7' class='text-center'>No schools found</td></tr>";
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
            if (!$is_ajax_request) {
                include '../../includes/footer.php';
            }
            ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <?php include_once "../../includes/logout_modal.php" ?>
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
                <div class="modal-body">Are you sure you want to delete this school? This action cannot be undone.</div>
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
    <script src="/BMC-SMS/assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/custom_school_scripts.js"></script>
</body>

</html>
<?php
$conn = null;
?>