<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

$role = null;
$students = [];
$availableStandards = [];
$selectedStd = 'all';

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$role) {
    header("Location: ../login.php");
    exit;
}

try {
    $query = "SELECT s.id, s.student_name, s.rollno, s.std, s.email, sc.school_name, u.account_status
            FROM student s 
            LEFT JOIN school sc ON s.school_id = sc.id
            LEFT JOIN users u ON s.id = u.id";

    $params = [];
    $where_clauses = [];
    $school_id = null;
    
    if ($role === 'teacher') {
        $user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
        if ($user_id) {
            $stmt_teacher_info = $conn->prepare("SELECT school_id, std FROM teacher WHERE id = ? LIMIT 1");
            $stmt_teacher_info->execute([$user_id]);
            $teacher_info = $stmt_teacher_info->fetch(PDO::FETCH_ASSOC);

            $school_id = $teacher_info['school_id'] ?? null;
            if (!empty($teacher_info['std'])) {
                $std_string_from_db = trim($teacher_info['std'], '{}');
                if (!empty($std_string_from_db)) {
                    $availableStandards = explode(',', $std_string_from_db);
                }
            }
        }
        if ($school_id) {
            $where_clauses[] = "s.school_id = ?";
            $params[] = $school_id;
        }

        $selectedStd = $_GET['std'] ?? 'all';
        if (!empty($availableStandards)) {
            if ($selectedStd !== 'all' && in_array($selectedStd, $availableStandards)) {
                $where_clauses[] = "s.std = ?";
                $params[] = $selectedStd;
            } else {
                $placeholders = implode(',', array_fill(0, count($availableStandards), '?'));
                $where_clauses[] = "s.std IN ({$placeholders})";
                $params = array_merge($params, $availableStandards);
            }
        }
    } elseif ($role === 'principal') {
        $user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

        if ($user_id) {
            $stmt_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ? LIMIT 1");
            $stmt_school->execute([$user_id]);
            $user_data = $stmt_school->fetch(PDO::FETCH_ASSOC);
            $school_id = $user_data['school_id'] ?? null;
        }

        if ($school_id) {
            $stmt_standards = $conn->prepare("SELECT DISTINCT std FROM student WHERE school_id = ? ORDER BY std");
            $stmt_standards->execute([$school_id]);
            $all_school_standards = $stmt_standards->fetchAll(PDO::FETCH_COLUMN, 0);
            $availableStandards = $all_school_standards;
            $where_clauses[] = "s.school_id = ?";
            $params[] = $school_id;
        }
        
        $selectedStd = $_GET['std'] ?? 'all';
        if ($selectedStd !== 'all' && $school_id) {
            $where_clauses[] = "s.std = ?";
            $params[] = $selectedStd;
        }
    }

    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(' AND ', $where_clauses);
    }

    $query .= " ORDER BY
        CASE
            WHEN s.std = 'Nursery' THEN 1
            WHEN s.std = 'Junior' THEN 2
            WHEN s.std = 'Senior' THEN 3
            WHEN s.std ~ '^[0-9]+$' THEN CAST(s.std AS INTEGER) + 3
            ELSE 999
        END, 
        s.rollno ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Student List Error: " . $e->getMessage());
    die("A database error occurred while fetching the student list.");
}

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Student Management - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
<?php
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Student Management</h1>
                    <p class="mb-4">List of all students in <?php echo ($role === 'principal') ? 'your school' : 'your standard'; ?>.</p>

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
                            <h6 class="m-0 font-weight-bold text-primary">Student List</h6>
                            <?php if ($role === 'principal'): ?>
                                <a href="/BMC-SMS/includes/forms/student_enrollment.php" class="btn btn-primary btn-icon-split btn-sm">
                                    <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                                    <span class="text">Add New Student</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="studentListTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Roll No</th>
                                            <th>Standard</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($students)): ?>
                                            <?php foreach ($students as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                    <td><a href="view.php?id=<?php echo $row['id']; ?>&std=<?php echo htmlspecialchars($selectedStd); ?>"><?php echo htmlspecialchars($row['student_name'] ?? 'N/A'); ?></a></td>
                                                    <td><?php echo htmlspecialchars($row['rollno'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['std'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <?php if ($row['account_status'] === 'active'): ?>
                                                            <span class="badge badge-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Suspended</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="view.php?id=<?php echo $row['id']; ?>&std=<?php echo htmlspecialchars($selectedStd); ?>" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>

                                                        <?php if ($role === 'principal'): ?>
                                                            <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                                            <?php
                                                            $return_url = urlencode('/BMC-SMS/pages/student/student_list.php');
                                                            if ($row['account_status'] === 'active'):
                                                                $suspendUrl = "../../includes/actions/update_user_status.php?id={$row['id']}&status=suspended&return={$return_url}";
                                                            ?>
                                                                <a href="#" onclick="confirmAction('<?php echo $suspendUrl; ?>', 'suspend this student')" class="btn btn-warning btn-sm" title="Suspend"><i class="fas fa-ban"></i></a>
                                                            <?php else:
                                                                $reactivateUrl = "../../includes/actions/update_user_status.php?id={$row['id']}&status=active&return={$return_url}";
                                                            ?>
                                                                <a href="#" onclick="confirmAction('<?php echo $reactivateUrl; ?>', 'reactivate this student')" class="btn btn-success btn-sm" title="Reactivate"><i class="fas fa-check-circle"></i></a>
                                                            <?php endif; ?>
                                                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No students found</td>
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
            <?php include '../../includes/footer.php'; ?>
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
    <?php if (($role === 'principal' || $role === 'teacher') && !empty($availableStandards)): ?>
        <div id="standardFilterWrapper" class="d-none">
            <label class="mr-3">Filter by Standard: 
                <select id="standardFilter" class="form-control form-control-sm d-inline-block w-auto">
                    <option value="all">All</option>
                    <?php foreach ($availableStandards as $std): ?>
                        <option value="<?php echo htmlspecialchars(trim($std)); ?>" <?php echo ($selectedStd == trim($std)) ? 'selected' : ''; ?>> <?php echo htmlspecialchars(trim($std)); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    <?php endif; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#studentListTable').DataTable({
                "order": []
            });

            var filterContainer = $('.dataTables_filter');
            var standardFilterWrapper = $('#standardFilterWrapper');

            if (filterContainer.length > 0 && standardFilterWrapper.length > 0) {
                // Extract the content (the label) from the wrapper div
                var filterContent = standardFilterWrapper.html();
                
                // Prepend the actual content, not the div, to the search area
                filterContainer.prepend(filterContent);

                // Remove the now-empty wrapper from the page
                standardFilterWrapper.remove();
                
                // Attach the event handler to the newly added dropdown
                $('#standardFilter').on('change', function() {
                    window.location.href = 'student_list.php?std=' + this.value;
                });
            }
        });

        function confirmAction(url, actionText) {
            $('#actionModalBody').text('Are you sure you want to ' + actionText + '?');
            $('#confirmActionBtn').attr('href', url);
            $('#actionModal').modal('show');
        }

        function confirmDelete(id) {
            var deleteUrl = `../../pages/student/delete.php?id=${id}`;
            $('#confirmDeleteBtn').attr('href', deleteUrl);
            $('#deleteModal').modal('show');
        }
    </script>
</body>

</html>
<?php
}
?>