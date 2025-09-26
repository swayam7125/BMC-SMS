<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

$role = null;
$principals = [];
$current_user_id = null;
$user_school_id = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'superadmin' && $role !== 'hr') {
    header("Location: ../../login.php");
    exit;
}

try {
    $query = "SELECT p.id, p.principal_name, p.email, p.phone, p.batch, 
                    sc.school_name, u.account_status
            FROM principal p 
            LEFT JOIN school sc ON p.school_id = sc.id
            LEFT JOIN users u ON p.id = u.id";

    if ($role === 'hr') {
        $stmt_school = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
        $stmt_school->execute([$current_user_id]);
        $user_school_id = $stmt_school->fetchColumn();

        if (!$user_school_id) {
            die("Error: Could not determine the school for the HR user.");
        }
        $query .= " WHERE p.school_id = ?";
        $params = [$user_school_id];
    } else {
        $params = [];
    }

    $query .= " ORDER BY p.id ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $principals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Principal List Error: " . $e->getMessage());
    die("A database error occurred.");
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Principal Management - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
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
                    <h1 class="h3 mb-2 text-gray-800">Principal Management</h1>
                    <p class="mb-4">List of all principals in the system.</p>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_GET['success']); ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_GET['error']); ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Principal List</h6>
                            <?php if ($role === 'superadmin'): ?>
                                <a href="/BMC-SMS/includes/forms/principal_enrollment.php" class="btn btn-primary btn-icon-split btn-sm"><span class="icon text-white-50"><i class="fas fa-plus"></i></span><span class="text">Add New Principal</span></a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="principalListTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>School</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($principals)): foreach ($principals as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                    <td><a href="view.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['principal_name'] ?? 'N/A'); ?></a></td>
                                                    <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['school_name'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <?php if ($row['account_status'] === 'active'): ?>
                                                            <span class="badge badge-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Suspended</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                                        <?php
                                                        $return_url = urlencode(($role === 'hr') ? '/BMC-SMS/pages/hr/principal_list.php' : '/BMC-SMS/pages/principal/principal_list.php');
                                                        if ($row['account_status'] === 'active'):
                                                            $suspendUrl = "../../includes/actions/update_user_status.php?id={$row['id']}&status=suspended&return={$return_url}";
                                                        ?>
                                                            <a href="#" onclick="confirmAction('<?php echo $suspendUrl; ?>', 'suspend this principal')" class="btn btn-warning btn-sm" title="Suspend"><i class="fas fa-ban"></i></a>
                                                        <?php else:
                                                            $reactivateUrl = "../../includes/actions/update_user_status.php?id={$row['id']}&status=active&return={$return_url}";
                                                        ?>
                                                            <a href="#" onclick="confirmAction('<?php echo $reactivateUrl; ?>', 'reactivate this principal')" class="btn btn-success btn-sm" title="Reactivate"><i class="fas fa-check-circle"></i></a>
                                                        <?php endif; ?>
                                                        <?php if ($role === 'superadmin'): ?>
                                                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                        else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No principals found</td>
                                            </tr>
                                        <?php endif; ?>
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
    <?php include_once "../../includes/logout_modal.php" ?>
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5><button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">Are you sure you want to delete this record? This action cannot be undone.</div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><a class="btn btn-danger" id="confirmDeleteBtn" href="#">Delete</a></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="actionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5><button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body" id="actionModalBody">Are you sure you want to proceed?</div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><a class="btn btn-primary" id="confirmActionBtn" href="#">Confirm</a></div>
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
            $('#principalListTable').DataTable();
        });

        function confirmAction(url, actionText) {
            $('#actionModalBody').text('Are you sure you want to ' + actionText + '?');
            $('#confirmActionBtn').attr('href', url);
            $('#actionModal').modal('show');
        }

        function confirmDelete(id) {
            var deleteUrl = `delete.php?id=${id}`;
            $('#confirmDeleteBtn').attr('href', deleteUrl);
            $('#deleteModal').modal('show');
        }
    </script>
    <script>
        function initializePrincipalList() {
            // Add data-action attributes to action buttons
            $('.btn-warning[title="Suspend"]').attr('data-action', 'suspend')
                .attr('data-confirm', 'Are you sure you want to suspend this principal?');

            $('.btn-success[title="Reactivate"]').attr('data-action', 'reactivate')
                .attr('data-confirm', 'Are you sure you want to reactivate this principal?');

            $('.btn-danger[title="Delete"]').attr('data-action', 'delete')
                .attr('data-confirm', 'Are you sure you want to delete this principal? This action cannot be undone.');

            // Initialize DataTable with AJAX support
            if (!$.fn.DataTable.isDataTable('#principalListTable')) {
                $('#principalListTable').DataTable({
                    processing: true,
                    serverSide: false, // Set to true if you want server-side processing
                    responsive: true,
                    pageLength: 25,
                    order: [
                        [0, 'asc']
                    ],
                    columnDefs: [{
                            targets: [-1],
                            orderable: false
                        } // Disable sorting on Actions column
                    ]
                });
            }
        }

        // Call initialization function when page loads
        $(document).ready(function() {
            initializePrincipalList();
        });
    </script>
</body>
<?php
if (is_ajax_request()) {
    $content = ob_get_clean();

    // Extract just the container-fluid content
    if (preg_match('/<div class="container-fluid".*?>(.*?)<\/div>/s', $content, $matches)) {
        echo '<div class="container-fluid">' . $matches[1] . '</div>';
    } else {
        echo $content;
    }
    exit;
}
?>

</html>