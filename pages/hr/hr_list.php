<?php
// Include necessary files
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

// Check for user role and get user ID from cookies
$role = null;
$userId = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security: Only principals can access this page
if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

// Get the principal's school ID to filter the list
$school_id = null;
if ($userId) {
    $stmt = $conn->prepare('SELECT "school_id" FROM "principal" WHERE "id" = ?');
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
}

// Get the selected batch from the URL for filtering
$selected_batch = $_GET['batch'] ?? '';

// Fetch the list of HR users for the principal's school
$payroll_users = [];
if ($school_id) {
    try {
        $query = '
            SELECT u.id, u.email, h.hr_name, h.batch, u.account_status, h.phone
            FROM users u
            JOIN hr h ON u.id = h.id
            WHERE h.school_id = ? AND u.role = \'hr\'
        ';
        $params = [$school_id];

        // Add batch filtering condition if a batch is selected
        if (!empty($selected_batch)) {
            $query .= " AND h.batch = ?";
            $params[] = $selected_batch;
        }

        $query .= ' ORDER BY h.hr_name';
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $payroll_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error and display a friendly message if something goes wrong
        error_log("Database error fetching HR list: " . $e->getMessage());
        die("An error occurred while fetching the user list. Please try again later.");
    }
}

// Get all distinct batches for the filter dropdown
$batches = ['Morning', 'Evening'];
$filter_html = '<label class="mr-3">Filter by Batch: ';
$filter_html .= '<select class="form-control form-control-sm d-inline-block w-auto" id="batch-filter" name="batch" onchange="window.location.href=\'hr_list.php?batch=\' + this.value">';
$filter_html .= '<option value="">All</option>';
foreach ($batches as $batch) {
    $selected = ($batch == $selected_batch) ? 'selected' : '';
    $filter_html .= "<option value='" . htmlspecialchars($batch) . "' $selected>" . htmlspecialchars($batch) . "</option>";
}
$filter_html .= '</select></label>';

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>HR User List - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">HR User List</h1>
                        <a href="../../includes/forms/hr_enrollment.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-user-plus fa-sm text-white-50"></i> Enroll New HR User
                        </a>
                    </div>
                    
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
                            <h6 class="m-0 font-weight-bold text-primary">Manage HR Staff</h6>
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
                                            <th>Batch</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($payroll_users)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No HR users found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($payroll_users as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                                    <td><a href="view.php?id=<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['hr_name']); ?></a></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['batch']); ?></td>
                                                    <td>
                                                        <?php if ($user['account_status'] === 'active'): ?>
                                                            <span class="badge badge-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Suspended</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                                        <a href="edit_hr.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php 
                                                            $return_url = urlencode('/BMC-SMS/pages/hr/hr_list.php');
                                                            if ($user['account_status'] === 'active'):
                                                                $suspendUrl = "../../includes/actions/update_user_status.php?id={$user['id']}&status=suspended&return={$return_url}";
                                                        ?>
                                                                <a href="#" onclick="confirmAction('<?php echo $suspendUrl; ?>', 'suspend this HR')" class="btn btn-warning btn-sm" title="Suspend"><i class="fas fa-ban"></i></a>
                                                            <?php else:
                                                                $reactivateUrl = "../../includes/actions/update_user_status.php?id={$user['id']}&status=active&return={$return_url}";
                                                            ?>
                                                                <a href="#" onclick="confirmAction('<?php echo $reactivateUrl; ?>', 'reactivate this HR user')" class="btn btn-success btn-sm" title="Reactivate"><i class="fas fa-check-circle"></i></a>
                                                        <?php endif; ?>
                                                        <a href="#" class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $user['id']; ?>" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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

    <?php include_once "../../includes/logout_modal.php"; ?>

    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Are you sure you want to delete this HR user? This action cannot be undone.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-danger" id="confirmDeleteBtn">Delete</a>
                </div>
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
            $('#dataTable').DataTable();
            
            var filterHtml = '<?php echo addslashes($filter_html); ?>';
            $('#dataTable_filter').prepend(filterHtml);
            
            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                var userId = $(this).data('id');
                var deleteUrl = 'delete_hr.php?id=' + userId;
                
                $('#confirmDeleteBtn').attr('href', deleteUrl);
                $('#deleteModal').modal('show');
            });
        });

        function confirmAction(url, actionText) {
            $('#actionModalBody').text('Are you sure you want to ' + actionText + '?');
            $('#confirmActionBtn').attr('href', url);
            $('#actionModal').modal('show');
        }
    </script>
</body>
</html>
<?php
}
?>