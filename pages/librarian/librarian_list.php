<?php
// --- Includes & Setup ---
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if ($role !== 'principal' && $role !== 'hr') {
    header("Location: ../../login.php");
    exit;
}

$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$user_school_id = null;
$librarians = [];

try {
    if ($role === 'principal') {
        $school_stmt = $conn->prepare('SELECT "school_id" FROM "principal" WHERE "id" = ? LIMIT 1');
        $school_stmt->execute([$current_user_id]);
        $user_school_id = $school_stmt->fetchColumn();
    } elseif ($role === 'hr') {
        $school_stmt = $conn->prepare('SELECT "school_id" FROM "hr" WHERE "id" = ? LIMIT 1');
        $school_stmt->execute([$current_user_id]);
        $user_school_id = $school_stmt->fetchColumn();
    }

    if (!$user_school_id) {
        die("Error: Could not determine the school for the user.");
    }

    // Fetch all librarians associated with the principal's school.
    $query = 'SELECT l.id, l.librarian_name, l.email, l.phone, u.account_status
              FROM "librarian" l
              LEFT JOIN "users" u ON l.id = u.id
              WHERE l.school_id = ? 
              ORDER BY l.id ASC';
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_school_id]);
    $librarians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Librarian Management - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
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
                    <h1 class="h3 mb-2 text-gray-800">Librarian Management</h1>
                    <p class="mb-4">List of all librarians in your school.</p>

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
                            <h6 class="m-0 font-weight-bold text-primary">Librarian List</h6>
                            <?php if ($role === 'principal' || $role === 'hr'): ?>
                                <a href="/BMC-SMS/includes/forms/librarian_enrollment.php" class="btn btn-primary btn-icon-split btn-sm">
                                    <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                                    <span class="text">Add New Librarian</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="librarianListTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($librarians)): ?>
                                            <?php foreach ($librarians as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                    <td><a href="view.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['librarian_name'] ?? 'N/A'); ?></a></td>
                                                    <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
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
                                                        $return_url = urlencode('/BMC-SMS/pages/librarian/librarian_list.php');
                                                        if ($row['account_status'] === 'active'):
                                                            $suspendUrl = "../../includes/actions/update_user_status.php?id={$row['id']}&status=suspended&return={$return_url}";
                                                        ?>
                                                            <a href="#" onclick="confirmAction('<?php echo $suspendUrl; ?>', 'suspend this librarian')" class="btn btn-warning btn-sm" title="Suspend"><i class="fas fa-ban"></i></a>
                                                        <?php else:
                                                            $reactivateUrl = "../../includes/actions/update_user_status.php?id={$row['id']}&status=active&return={$return_url}";
                                                        ?>
                                                            <a href="#" onclick="confirmAction('<?php echo $reactivateUrl; ?>', 'reactivate this librarian')" class="btn btn-success btn-sm" title="Reactivate"><i class="fas fa-check-circle"></i></a>
                                                        <?php endif; ?>
                                                        <?php if ($role === 'principal'): ?>
                                                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                        else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No librarians found</td>
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

        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Deletion</h5><button class="close" type="button" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">Are you sure you want to permanently delete this record? This action cannot be undone.</div>
                    <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><a class="btn btn-danger" id="confirmDeleteBtn" href="#">Delete</a></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="actionModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Action</h5><button class="close" type="button" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body" id="actionModalBody">Are you sure?</div>
                    <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><a class="btn btn-primary" id="confirmActionBtn" href="#">Confirm</a></div>
                </div>
            </div>
        </div>
        <?php include_once "../../includes/logout_modal.php" ?>

        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>
        <script src="/BMC-SMS/assets/vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="/BMC-SMS/assets/js/global-ajax-filters.js"></script>
        <script src="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>

        <script>
            $(document).ready(function() {
                // Initialize DataTables with the responsive extension
                $('#librarianListTable').DataTable({
                    "responsive": true,
                    "autoWidth": false,
                    "columnDefs": [{
                            "orderable": false,
                            "targets": 5
                        } // Disables sorting on the 'Actions' column
                    ]
                });
            });

            // Function to populate and show the confirmation modal for actions like suspend/reactivate
            function confirmAction(url, actionText) {
                $('#actionModalBody').text('Are you sure you want to ' + actionText + '?');
                $('#confirmActionBtn').attr('href', url);
                $('#actionModal').modal('show');
            }

            // Function to populate and show the delete confirmation modal
            function confirmDelete(id) {
                var deleteUrl = `../../pages/librarian/librarian_delete.php?id=${id}`;
                $('#confirmDeleteBtn').attr('href', deleteUrl);
                $('#deleteModal').modal('show');
            }
        </script>
</body>

</html>
<?php
$conn = null;
?>