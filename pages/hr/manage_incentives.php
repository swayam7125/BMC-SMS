<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/log_system.php'; // Log system included

/**
 * Calculates the total incentive amount for a staff member
 */
function calculate_total_incentive_for_staff($conn, $staff_id, $staff_role, $school_id)
{
    try {
        // First get the base salary for the staff member
        $salary_column = $staff_role . '_salary';
        $query = "SELECT {$salary_column} FROM {$staff_role} WHERE id = ? AND school_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$staff_id, $school_id]);
        $base_salary = (float) $stmt->fetchColumn();

        // Then calculate total incentive percentage
        $query = "SELECT COALESCE(SUM(i.percentage), 0)
                 FROM staff_incentives si
                 JOIN incentives i ON i.id = si.incentive_id
                 WHERE si.staff_id = ? 
                 AND si.user_role = ? 
                 AND i.school_id = ? 
                 AND i.is_active = true";
        $stmt = $conn->prepare($query);
        $stmt->execute([$staff_id, $staff_role, $school_id]);
        $total_percentage = (float) $stmt->fetchColumn();

        // Calculate final amount
        return $base_salary * ($total_percentage / 100);
    } catch (Exception $e) {
        // In case of an error, return 0 and log the error for debugging
        error_log("Error in calculate_total_incentive_for_staff: " . $e->getMessage());
        return 0;
    }
}

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Authorization check for HR user
session_start();
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A'; // Get username for logging


if ($role !== 'hr' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// Fetch the HR user's associated school_id
$school_id = null;
try {
    $stmt = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
} catch (Exception $e) {
    // Log error
    log_interaction($role, $userId, "INCENTIVE_ERROR: Failed to fetch HR school ID. Error: " . $e->getMessage(), $userName);
    die("Error fetching user data: " . $e->getMessage());
}

if (!$school_id) {
    die("Error: HR user is not associated with any school.");
}

// Initialize feedback messages
$errorMessage = '';
$successMessage = '';

/**
 * Calculates the final incentive amount for a given staff member.
 *
 * @param PDO $conn The database connection object.
 * @param int $staff_id The ID of the staff member.
 * @param string $staff_role The role of the staff member ('teacher', 'librarian', etc.).
 * @param int $school_id The ID of the school.
 * @return float The total calculated incentive amount.
 */
// Commented out legacy PostgreSQL-specific implementation
// function calculate_total_incentive_for_staff($conn, $staff_id, $staff_role, $school_id)
// {
//     try {
//         $stmt = $conn->prepare("SELECT calculate_staff_incentive(?, ?)");
//         $stmt->execute([$staff_id, $staff_role]);
//         return (float) $stmt->fetchColumn();
//     } catch (Exception $e) {
//         // In case of an error, return 0 and log the error for debugging
//         error_log("Error in calculate_total_incentive_for_staff: " . $e->getMessage());
//         return 0;
//     }
// }


/**
 * Handles AJAX requests for adding, editing, and deleting incentives and their assignments.
 *
 * @param PDO $conn The database connection object.
 * @param int $school_id The ID of the school.
 */
function handle_incentive_action($conn, $school_id, $role, $userId, $userName)
{
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'An unknown error occurred.'];

    try {
        switch ($action) {
            case 'add':
                $name = trim($_POST['incentive_name']);
                $percentage = filter_var($_POST['percentage'], FILTER_VALIDATE_FLOAT);
                if ($name && $percentage !== false && $percentage >= 0) {
                    $stmt = $conn->prepare("INSERT INTO incentives (school_id, incentive_name, percentage, is_active) VALUES (?, ?, ?, TRUE)");
                    $stmt->execute([$school_id, $name, $percentage]);
                    $response = ['success' => true, 'message' => 'Incentive created successfully.'];
                    log_interaction($role, $userId, "INCENTIVE_ADD: Created incentive '{$name}' with {$percentage}%.", $userName);
                } else {
                    $response['message'] = 'Invalid name or percentage provided.';
                }
                break;

            case 'edit':
                $id = filter_var($_POST['incentive_id'], FILTER_VALIDATE_INT);
                $name = trim($_POST['incentive_name']);
                $percentage = filter_var($_POST['percentage'], FILTER_VALIDATE_FLOAT);
                if ($id && $name && $percentage !== false && $percentage >= 0) {
                    $stmt = $conn->prepare("UPDATE incentives SET incentive_name = ?, percentage = ? WHERE id = ? AND school_id = ?");
                    $stmt->execute([$name, $percentage, $id, $school_id]);
                    $response = ['success' => true, 'message' => 'Incentive updated successfully.'];
                    log_interaction($role, $userId, "INCENTIVE_EDIT: Updated incentive ID {$id} to '{$name}' with {$percentage}%.", $userName);
                } else {
                    $response['message'] = 'Invalid data provided for update.';
                }
                break;

            case 'delete':
                $id = filter_var($_POST['incentive_id'], FILTER_VALIDATE_INT);
                if ($id) {
                    $conn->beginTransaction();
                    $stmt_del_assign = $conn->prepare("DELETE FROM staff_incentives WHERE incentive_id = ?");
                    $stmt_del_assign->execute([$id]);
                    $stmt_del_inc = $conn->prepare("DELETE FROM incentives WHERE id = ? AND school_id = ?");
                    $stmt_del_inc->execute([$id, $school_id]);
                    $conn->commit();
                    $response = ['success' => true, 'message' => 'Incentive and its assignments deleted successfully.'];
                    log_interaction($role, $userId, "INCENTIVE_DELETE: Deleted incentive ID {$id}.", $userName);
                } else {
                    $response['message'] = 'Invalid incentive ID.';
                }
                break;

            case 'assign':
                $incentive_id = filter_var($_POST['incentive_id'], FILTER_VALIDATE_INT);
                $staff_id = filter_var($_POST['staff_id'], FILTER_VALIDATE_INT);
                $staff_role = $_POST['staff_role'];
                if ($incentive_id && $staff_id && in_array($staff_role, ['teacher', 'librarian', 'hr', 'principal'])) {
                    $stmt = $conn->prepare("INSERT INTO staff_incentives (incentive_id, staff_id, staff_role) VALUES (?, ?, ?)");
                    $stmt->execute([$incentive_id, $staff_id, $staff_role]);
                    $response = ['success' => true, 'message' => 'Incentive assigned successfully.'];
                } else {
                    $response['message'] = 'Invalid data for assignment.';
                }
                break;

            case 'unassign':
                $assignment_id = filter_var($_POST['assignment_id'], FILTER_VALIDATE_INT);
                if ($assignment_id) {
                    $stmt = $conn->prepare("DELETE FROM staff_incentives WHERE id = ?");
                    $stmt->execute([$assignment_id]);
                    $response = ['success' => true, 'message' => 'Incentive unassigned successfully.'];
                } else {
                    $response['message'] = 'Invalid assignment ID.';
                }
                break;
        }
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $response['message'] = 'Database operation failed: ' . $e->getMessage();
        log_interaction($role, $userId, "INCENTIVE_DB_ERROR: Action '{$action}' failed. Error: " . $e->getMessage(), $userName);

    }
    echo json_encode($response);
    exit;
}

if ($is_ajax_request) {
    handle_incentive_action($conn, $school_id, $role, $userId, $userName);
}

// Fetch all necessary data for the page render
try {
    // Fetch existing incentives for the school
    $stmt_incentives = $conn->prepare("SELECT * FROM incentives WHERE school_id = ? ORDER BY incentive_name");
    $stmt_incentives->execute([$school_id]);
    $incentives = $stmt_incentives->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all staff members (teachers, librarians, principals, hr)
    $staff = [];
    $roles_to_fetch = ['teacher', 'librarian', 'hr', 'principal'];
    foreach ($roles_to_fetch as $staff_role) {
        $name_col = $staff_role . '_name';
        $stmt_staff = $conn->prepare("SELECT id, {$name_col} as name FROM {$staff_role} WHERE school_id = ? ORDER BY {$name_col}");
        $stmt_staff->execute([$school_id]);
        $results = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $s) {
            $staff[] = array_merge($s, ['role' => $staff_role]);
        }
    }

    // Fetch all incentive assignments and calculate current incentive amounts
    $stmt_assignments = $conn->prepare(
        "WITH staff_names AS (
            SELECT id, teacher_name as name, 'teacher'::user_role as role FROM teacher WHERE school_id = ?
            UNION ALL
            SELECT id, librarian_name, 'librarian'::user_role FROM librarian WHERE school_id = ?
            UNION ALL
            SELECT id, hr_name, 'hr'::user_role FROM hr WHERE school_id = ?
            UNION ALL
            SELECT id, principal_name, 'principal'::user_role FROM principal WHERE school_id = ?
        )
        SELECT 
            ia.id,
            ia.staff_id,
            ia.staff_role,
            i.incentive_name,
            COALESCE(sn.name, 'Unknown') as staff_name
        FROM staff_incentives ia
        JOIN incentives i ON ia.incentive_id = i.id
        LEFT JOIN staff_names sn ON ia.staff_id = sn.id AND ia.staff_role = sn.role
        WHERE i.school_id = ?"
    );
    $stmt_assignments->execute([$school_id, $school_id, $school_id, $school_id, $school_id]);
    $assignments = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total incentive for each assigned staff member
    $assigned_staff_incentives = [];
    foreach ($assignments as $assignment) {
        $staff_key = $assignment['staff_role'] . '-' . $assignment['staff_id'];
        if (!isset($assigned_staff_incentives[$staff_key])) {
            $total_incentive = calculate_total_incentive_for_staff($conn, $assignment['staff_id'], $assignment['staff_role'], $school_id);
            $assigned_staff_incentives[$staff_key] = [
                'staff_name' => $assignment['staff_name'],
                'staff_role' => $assignment['staff_role'],
                'total_incentive' => $total_incentive
            ];
        }
    }


} catch (Exception $e) {
    $errorMessage = "Error loading page data: " . $e->getMessage();
    log_interaction($role, $userId, "INCENTIVE_PAGE_LOAD_ERROR: " . $e->getMessage(), $userName);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Incentives</title>
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/responsive.css" />
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">

                    <h1 class="h3 mb-4 text-gray-800">Manage Staff Incentives</h1>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Create New Incentive</h6>
                                </div>
                                <div class="card-body">
                                    <form id="addIncentiveForm">
                                        <input type="hidden" name="action" value="add">
                                        <div class="form-group">
                                            <label for="incentive_name">Incentive Name</label>
                                            <input type="text" class="form-control" name="incentive_name"
                                                placeholder="e.g., Performance Bonus" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="percentage">Percentage of Base Salary (%)</label>
                                            <input type="number" step="0.01" class="form-control" name="percentage"
                                                placeholder="e.g., 10.5" required>
                                        </div>
                                        <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Create
                                            Incentive</button>
                                    </form>
                                </div>
                            </div>

                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Existing Incentives</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="incentivesTable">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Percentage</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($incentives as $incentive): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($incentive['incentive_name']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($incentive['percentage']); ?>%</td>
                                                        <td>
                                                            <button class="btn btn-sm btn-info edit-incentive-btn"
                                                                data-id="<?php echo $incentive['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($incentive['incentive_name']); ?>"
                                                                data-percentage="<?php echo $incentive['percentage']; ?>"><i
                                                                    class="fas fa-edit"></i></button>
                                                            <button class="btn btn-sm btn-danger delete-incentive-btn"
                                                                data-id="<?php echo $incentive['id']; ?>"><i
                                                                    class="fas fa-trash"></i></button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Assign Incentive to Staff</h6>
                                </div>
                                <div class="card-body">
                                    <form id="assignIncentiveForm">
                                        <input type="hidden" name="action" value="assign">
                                        <div class="form-group">
                                            <label for="staff_id">Select Staff Member</label>
                                            <select class="form-control" name="staff_id" required>
                                                <option value="">-- Select Staff --</option>
                                                <?php foreach ($staff as $s): ?>
                                                    <option value="<?php echo $s['id']; ?>"
                                                        data-role="<?php echo $s['role']; ?>">
                                                        <?php echo htmlspecialchars($s['name']) . " (" . ucfirst($s['role']) . ")"; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="staff_role" id="staff_role_hidden">
                                        </div>
                                        <div class="form-group">
                                            <label for="incentive_id">Select Incentive</label>
                                            <select class="form-control" name="incentive_id" required>
                                                <option value="">-- Select Incentive --</option>
                                                <?php foreach ($incentives as $incentive): ?>
                                                    <option value="<?php echo $incentive['id']; ?>">
                                                        <?php echo htmlspecialchars($incentive['incentive_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-link"></i> Assign
                                            Incentive</button>
                                    </form>
                                </div>
                            </div>

                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Assigned Incentives Summary</h6>
                                    <div class="btn-group btn-group-sm" id="roleFilterTabs">
                                        <a href="#" class="btn btn-outline-secondary active" data-role="all">All</a>
                                        <a href="#" class="btn btn-outline-secondary" data-role="teacher">Teachers</a>
                                        <a href="#" class="btn btn-outline-secondary"
                                            data-role="librarian">Librarians</a>
                                        <a href="#" class="btn btn-outline-secondary" data-role="hr">HR</a>
                                        <a href="#" class="btn btn-outline-secondary"
                                            data-role="principal">Principals</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="assignedIncentivesTable">
                                            <thead>
                                                <tr>
                                                    <th>Staff Name</th>
                                                    <th>Role</th>
                                                    <th>Total Incentive Amount</th>
                                                    <th>Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assigned_staff_incentives as $key => $data):
                                                    list($role, $staff_id) = explode('-', $key);
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($data['staff_name']); ?></td>
                                                        <td><?php echo ucfirst(htmlspecialchars($data['staff_role'])); ?>
                                                        </td>
                                                        <td>₹<?php echo number_format($data['total_incentive'], 2); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info view-details-btn"
                                                                data-staff-id="<?php echo $staff_id; ?>"
                                                                data-staff-role="<?php echo $role; ?>"
                                                                data-staff-name="<?php echo htmlspecialchars($data['staff_name']); ?>">
                                                                <i class="fas fa-eye"></i> View
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>

    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalTitle">Incentive Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="details-content-placeholder">
                        <div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editIncentiveModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Incentive</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editIncentiveForm">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="incentive_id" id="edit_incentive_id">
                        <div class="form-group">
                            <label for="edit_incentive_name">Incentive Name</label>
                            <input type="text" class="form-control" id="edit_incentive_name" name="incentive_name"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="edit_percentage">Percentage</label>
                            <input type="number" step="0.01" class="form-control" id="edit_percentage" name="percentage"
                                required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function () {
            const incentivesTable = $('#incentivesTable').DataTable();
            const assignedTable = $('#assignedIncentivesTable').DataTable({
                "order": [
                    [2, "desc"]
                ]
            });

            function handleFormSubmit(formId, successCallback) {
                $(formId).on('submit', function (e) {
                    e.preventDefault();
                    const formData = $(this).serialize();
                    $.post('manage_incentives.php', formData, function (response) {
                        if (response.success) {
                            alert(response.message);
                            if (successCallback) successCallback();
                            location.reload(); // Simple reload for now
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }, 'json');
                });
            }

            handleFormSubmit('#addIncentiveForm');
            handleFormSubmit('#assignIncentiveForm');
            handleFormSubmit('#editIncentiveForm');


            $('select[name="staff_id"]').on('change', function () {
                const selectedRole = $(this).find('option:selected').data('role');
                $('#staff_role_hidden').val(selectedRole);
            });

            $('#incentivesTable').on('click', '.edit-incentive-btn', function () {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const percentage = $(this).data('percentage');
                $('#edit_incentive_id').val(id);
                $('#edit_incentive_name').val(name);
                $('#edit_percentage').val(percentage);
                $('#editIncentiveModal').modal('show');
            });

            $('#incentivesTable').on('click', '.delete-incentive-btn', function () {
                if (confirm('Are you sure you want to delete this incentive and all its assignments?')) {
                    const id = $(this).data('id');
                    $.post('manage_incentives.php', {
                        action: 'delete',
                        incentive_id: id
                    }, function (response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }, 'json');
                }
            });

            $('#assignedIncentivesTable').on('click', '.view-details-btn', function () {
                const staffId = $(this).data('staff-id');
                const staffRole = $(this).data('staff-role');
                const staffName = $(this).data('staff-name');

                $('#detailsModalTitle').text(`Incentive Details for ${staffName}`);
                $('#detailsModal').modal('show');
                $('#details-content-placeholder').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

                // Fetch details via another AJAX call - this part is conceptual
                // You would need a new PHP endpoint or an action in the current one
                // to fetch specific assignments for a user.
                // For this example, let's just show a placeholder.
                // In a real app, you would replace this with a $.get() or $.post()
                let detailsHtml = `
            <h5>Base Salary and Assigned Incentives</h5>
            <p>This section shows a breakdown of the incentives assigned to ${staffName}.</p>
            `;

                // Let's create a dummy breakdown for now. You'd fetch this from the server.
                const allAssignments = <?php echo json_encode($assignments); ?>;
                const allIncentives = <?php echo json_encode($incentives); ?>;

                const staffAssignments = allAssignments.filter(a => a.user_id == staffId && a.user_role == staffRole);

                detailsHtml += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Incentive Name</th><th>Amount (Percentage)</th><th>Period</th><th>Actions</th></tr></thead><tbody>';

                if (staffAssignments.length > 0) {
                    staffAssignments.forEach(inc => {
                        // Dummy calculation for demonstration
                        // In a real scenario, you'd fetch the base salary and calculate properly.
                        const baseSalary = 50000; // Example base salary
                        const currentIncentive = allIncentives.find(i => i.incentive_name === inc.incentive_name);
                        const currentPercentage = currentIncentive ? parseFloat(currentIncentive.percentage) : 0;
                        const incentiveAmount = baseSalary * (currentPercentage / 100);
                        const formattedAmount = '₹' + incentiveAmount.toLocaleString('en-IN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        const amountClass = incentiveAmount > 0 ? 'text-success' : 'text-secondary';
                        const periodString = 'Monthly'; // Example period

                        detailsHtml += `
                    <tr>
                        <td>${inc.incentive_name}</td>
                        <td><strong class="${amountClass}">${formattedAmount}</strong> <small class="text-muted">(${currentPercentage.toFixed(2)}%)</small></td>
                        <td>${periodString}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-assignment-btn" data-assignment-id="${inc.id}" data-percentage="${currentPercentage.toFixed(2)}"><i class="fas fa-pencil-alt"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-assignment-btn" data-assignment-id="${inc.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                    });
                } else {
                    detailsHtml += '<tr><td colspan="4" class="text-center">No incentive details found.</td></tr>';
                }
                detailsHtml += '</tbody></table></div>';

                $('#details-content-placeholder').html(detailsHtml);
            });

            $('#roleFilterTabs a').on('click', function (e) {
                e.preventDefault();
                const role = $(this).data('role');
                $('#roleFilterTabs a').removeClass('active');
                $(this).addClass('active');
                assignedTable.column(1).search(role === 'all' ? '' : '^' + role + '$', true, false).draw();
            });
        });
    </script>
</body>

</html>