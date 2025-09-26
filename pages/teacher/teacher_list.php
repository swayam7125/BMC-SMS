<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if this is an AJAX request
if (is_ajax_request()) {
    // Start output buffering to capture the HTML
    ob_start();
}

$role = null;
$teachers = [];
$selected_standard = $_GET['std'] ?? '';
$current_user_id = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'principal' && $role !== 'hr' && $role !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

try {
    $user_school_id = null;
    $is_class_teacher = false;
    
    if ($role === 'principal') {
        $stmt_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
        $stmt_school->execute([$current_user_id]);
        $user_school_id = $stmt_school->fetchColumn();
    } elseif ($role === 'hr') {
        $stmt_school = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
        $stmt_school->execute([$current_user_id]);
        $user_school_id = $stmt_school->fetchColumn();
    } elseif ($role === 'teacher') {
        $stmt_teacher_info = $conn->prepare("SELECT school_id, std, class_teacher FROM teacher WHERE id = ? LIMIT 1");
        $stmt_teacher_info->execute([$current_user_id]);
        $teacher_info = $stmt_teacher_info->fetch(PDO::FETCH_ASSOC);

        $user_school_id = $teacher_info['school_id'] ?? null;
        $is_class_teacher = $teacher_info['class_teacher'] ?? false;
        
        if (!empty($teacher_info['std'])) {
            $std_string_from_db = trim($teacher_info['std'], '{}');
            if (!empty($std_string_from_db)) {
                $availableStandards = explode(',', $std_string_from_db);
            }
        }
    }


    if (!$user_school_id) {
        die("Error: Could not determine the school for the user.");
    }
    

    $query = "SELECT t.id, t.teacher_name, t.email, t.phone, t.subject, t.std, t.batch,
                     sc.school_name, u.account_status
              FROM teacher t 
              LEFT JOIN school sc ON t.school_id = sc.id
              LEFT JOIN users u ON t.id = u.id";

    $conditions = [];
    $params = [];

    $conditions[] = "t.school_id = ?";
    $params[] = $user_school_id;

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

    $standards_query = "SELECT DISTINCT unnest(std) as standard FROM teacher";
    $standards_query .= " WHERE school_id = ?";
    $stmt_standards = $conn->prepare($standards_query);
    $stmt_standards->execute([$user_school_id]);
    
    $all_standards = $stmt_standards->fetchAll(PDO::FETCH_COLUMN, 0);
    usort($all_standards, function($a, $b) {
        return (int)$a <=> (int)$b;
    });
    
} catch (PDOException $e) {
    error_log("Teacher List Error: " . $e->getMessage());
    die("A database error occurred while fetching the teacher list.");
}

$filter_html = '<label class="mr-3">Filter by Standard: ';
$filter_html .= '<select class="form-control form-control-sm d-inline-block w-auto" id="standard-filter" name="std" onchange="window.location.href=\'teacher_list.php?std=\' + this.value">';
$filter_html .= '<option value="">All</option>';
foreach ($all_standards as $standard) {
    $selected = ($standard == $selected_standard) ? 'selected' : '';
    $filter_html .= "<option value='" . htmlspecialchars($standard) . "' $selected>" . htmlspecialchars($standard) . "</option>";
}
$filter_html .= '</select></label>';

if (!is_ajax_request()) 
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
        <?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?> <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?> <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Teacher Management</h1>
                    <p class="mb-4">List of all teachers in your school.</p>
                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?><button type="button" class="close"
                            data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?><button type="button" class="close"
                            data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Teacher List</h6>
                            <?php if ($role === 'principal' || $role === 'hr'): ?>
                            <a href="/BMC-SMS/includes/forms/teacher_enrollment.php"
                                class="btn btn-primary btn-icon-split btn-sm"><span class="icon text-white-50"><i
                                        class="fas fa-plus"></i></span><span class="text">Add New Teacher</span></a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
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
                                        <?php if (!empty($teachers)): foreach ($teachers as $row):
                                            $teacher_std_for_url = htmlspecialchars(trim(str_replace(['{', '}'], '', $row['std'])));
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><a
                                                    href="view.php?id=<?php echo $row['id']; ?>&std=<?php echo $teacher_std_for_url; ?>&from_list_filter=<?php echo urlencode($selected_standard); ?>"><?php echo htmlspecialchars($row['teacher_name'] ?? 'N/A'); ?></a>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars(trim(str_replace(['{', '}'], '', $row['std'])) ?? 'N/A'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['school_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if ($row['account_status'] === 'active'): ?>
                                                <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                <span class="badge badge-danger">Suspended</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view.php?id=<?php echo $row['id']; ?>&std=<?php echo $teacher_std_for_url; ?>&from_list_filter=<?php echo urlencode($selected_standard); ?>"
                                                    class="btn btn-info btn-sm" title="View"><i
                                                        class="fas fa-eye"></i></a>
                                                <a href="edit.php?id=<?php echo $row['id']; ?>"
                                                    class="btn btn-primary btn-sm" title="Edit"><i
                                                        class="fas fa-edit"></i></a>
                                                <?php if ($role === 'principal' || $role === 'hr'):
                                                            $return_url = urlencode('/BMC-SMS/pages/teacher/teacher_list.php');
                                                            if ($row['account_status'] === 'active'):
                                                                $suspendUrl = "../../includes/actions/update_user_status.php?id={$row['id']}&status=suspended&return={$return_url}";
                                                        ?>
                                                <a href="#"
                                                    onclick="confirmAction('<?php echo $suspendUrl; ?>', 'suspend this teacher')"
                                                    class="btn btn-warning btn-sm" title="Suspend"><i
                                                        class="fas fa-ban"></i></a>
                                                <?php else:
                                                                $reactivateUrl = "../../includes/actions/update_user_status.php?id={$row['id']}&status=active&return={$return_url}";
                                                            ?>
                                                <a href="#"
                                                    onclick="confirmAction('<?php echo $reactivateUrl; ?>', 'reactivate this teacher')"
                                                    class="btn btn-success btn-sm" title="Reactivate"><i
                                                        class="fas fa-check-circle"></i></a>
                                                <?php endif;
                                                        endif; ?>
                                                <?php if ($role === 'principal'): ?>
                                                <button class="btn btn-danger btn-sm"
                                                    onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Delete"><i
                                                        class="fas fa-trash"></i></button>
                                                <?php endif; ?>
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
                    <h5 class="modal-title">Confirm Delete</h5><button class="close" type="button" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">Are you sure you want to delete this record? This action cannot be undone.</div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button"
                        data-dismiss="modal">Cancel</button><a class="btn btn-danger" id="confirmDeleteBtn"
                        href="#">Delete</a></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="actionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5><button class="close" type="button" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body" id="actionModalBody">Are you sure you want to proceed?</div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button"
                        data-dismiss="modal">Cancel</button><a class="btn btn-primary" id="confirmActionBtn"
                        href="#">Confirm</a></div>
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

        var filterHtml = '<?php echo addslashes($filter_html); ?>';
        $('#teacherListTable_filter').prepend(filterHtml);
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
<?php
// Add this block at the very end of the file
if (is_ajax_request()) {
    // Get the captured HTML
    $content = ob_get_clean();
    
    // Extract just the main content area for the AJAX response
    if (preg_match('/<div class="container-fluid".*?>(.*?)<\ /div>/s', $content, $matches)) {
    echo '<div class="container-fluid">' . $matches[1] . '</div>';
    } else {
    // Fallback if the main container isn't found
    echo $content;
    }
    // Stop the script for AJAX requests
    exit;
    }
    ?>

</html>