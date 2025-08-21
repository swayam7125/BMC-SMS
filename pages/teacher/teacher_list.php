<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

$role = null;
$teachers = [];
$selected_standard = $_GET['std'] ?? '';

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

try {
    $principal_school_id = null;
    if ($role === 'principal') {
        $user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
        if ($user_id) {
            $stmt_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
            $stmt_school->execute([$user_id]);
            $user_data = $stmt_school->fetch(PDO::FETCH_ASSOC);
            $principal_school_id = $user_data['school_id'] ?? null;
        }
        if (!$principal_school_id) {
            die("Error: Could not determine the school for the principal.");
        }
    }

    $query = "SELECT t.id, t.teacher_name, t.email, t.phone, t.subject, t.std, t.batch,
                     sc.school_name, u.account_status
              FROM teacher t 
              LEFT JOIN school sc ON t.school_id = sc.id
              LEFT JOIN users u ON t.id = u.id";

    $conditions = [];
    $params = [];

    if ($role === 'principal' && $principal_school_id) {
        $conditions[] = "t.school_id = ?";
        $params[] = $principal_school_id;
    }

    if (!empty($selected_standard)) {
        $conditions[] = "? = ANY(t.std)";
        $params[] = $selected_standard;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    $query .= " ORDER BY t.id ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all unique standards for the filter dropdown
    $standards_query = "SELECT DISTINCT unnest(std) as standard FROM teacher";
    if ($role === 'principal' && $principal_school_id) {
        $standards_query .= " WHERE school_id = ?";
        $stmt_standards = $conn->prepare($standards_query);
        $stmt_standards->execute([$principal_school_id]);
    } else {
        $stmt_standards = $conn->query($standards_query);
    }
    $all_standards = $stmt_standards->fetchAll(PDO::FETCH_COLUMN, 0);
    usort($all_standards, function($a, $b) {
        return (int)$a <=> (int)$b;
    });
} catch (PDOException $e) {
    error_log("Teacher List Error: " . $e->getMessage());
    die("A database error occurred while fetching the teacher list.");
}

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Teacher Management - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
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
<?php
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Teacher Management</h1>
                    <p class="mb-4">List of all teachers in <?php echo ($role === 'principal') ? 'your school' : 'the system'; ?>.</p>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_GET['success']); ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_GET['error']); ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Teacher List</h6>
                            <a href="/BMC-SMS/includes/forms/teacher_enrollment.php" class="btn btn-primary btn-icon-split btn-sm"><span class="icon text-white-50"><i class="fas fa-plus"></i></span><span class="text">Add New Teacher</span></a>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-end align-items-center mb-3">
                                <form method="GET" action="teacher_list.php" class="form-inline">
                                    <label for="standard-filter" class="mr-2">Filter by Standard:</label>
                                    <select class="form-control" id="standard-filter" name="std" onchange="this.form.submit()">
                                        <option value="">All</option>
                                        <?php
                                            foreach ($all_standards as $standard) {
                                                $selected = ($standard == $selected_standard) ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($standard) . "' $selected>" . htmlspecialchars($standard) . "</option>";
                                            }
                                        ?>
                                    </select>
                                </form>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="teacherListTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Std</th>
                                            <th>School</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($teachers)): foreach ($teachers as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                    <td><a href="view.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['teacher_name'] ?? 'N/A'); ?></a></td>
                                                    <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars(trim(str_replace(['{', '}'], '', $row['std'])) ?? 'N/A'); ?></td>
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
                                                        <?php if ($role === 'principal'):
                                                            $return_url = urlencode('/BMC-SMS/pages/teacher/teacher_list.php');
                                                            if ($row['account_status'] === 'active'):
                                                                $suspendUrl = "../../includes/actions/update_user_status.php?id={$row['id']}&status=suspended&return={$return_url}";
                                                        ?>
                                                                <a href="#" onclick="confirmAction('<?php echo $suspendUrl; ?>', 'suspend this teacher')" class="btn btn-warning btn-sm" title="Suspend"><i class="fas fa-ban"></i></a>
                                                            <?php else:
                                                                $reactivateUrl = "../../includes/actions/update_user_status.php?id={$row['id']}&status=active&return={$return_url}";
                                                            ?>
                                                                <a href="#" onclick="confirmAction('<?php echo $reactivateUrl; ?>', 'reactivate this teacher')" class="btn btn-success btn-sm" title="Reactivate"><i class="fas fa-check-circle"></i></a>
                                                        <?php endif;
                                                        endif; ?>
                                                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                        else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No teachers found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
<?php
if (!is_ajax_request()) {
?>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
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
            $('#teacherListTable').DataTable();
        });

        function confirmAction(url, actionText) {
            $('#actionModalBody').text('Are you sure you want to ' + actionText + '?');
            $('#confirmActionBtn').attr('href', url);
            $('#actionModal').modal('show');
        }

        function confirmDelete(id) {
            var deleteUrl = `../../pages/teacher/delete.php?id=${id}`;
            $('#confirmDeleteBtn').attr('href', deleteUrl);
            $('#deleteModal').modal('show');
        }
    </script>
</body>

</html>
<?php
}
?>