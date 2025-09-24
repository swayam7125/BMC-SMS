<?php
/*
|--------------------------------------------------------------------------
| 1. INCLUDES & SETUP
|--------------------------------------------------------------------------
| Includes required files and performs initial authorization and data setup.
*/
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Authorization check for HR user
session_start();
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

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
    die("Error fetching user data: " . $e->getMessage());
}

if (!$school_id) {
    die("Error: HR user is not associated with any school.");
}

// Initialize feedback messages
$errorMessage = '';
$successMessage = '';

/*
|--------------------------------------------------------------------------
| 2. HELPER FUNCTIONS
|--------------------------------------------------------------------------
| Reusable functions to avoid code duplication.
*/

/**
 * Calculates the final incentive amount for a given staff member.
 *
 * @param PDO $conn The database connection object.
 * @param int $staff_id The ID of the staff member.
 * @param string $staff_role The role of the staff member ('teacher', 'principal', 'librarian', 'hr').
 * @param int $incentive_id The ID of the incentive.
 * @return float|null The calculated amount or null on error.
 */
function calculateIncentiveAmount($conn, $staff_id, $staff_role, $incentive_id)
{
    // *** ADDED 'hr' to the list of allowed roles.
    $allowed_roles = ['teacher', 'principal', 'librarian', 'hr'];
    if (!in_array($staff_role, $allowed_roles)) {
        return null;
    }

    $incStmt = $conn->prepare("SELECT percentage, type FROM incentives WHERE id = ?");
    $incStmt->execute([$incentive_id]);
    $incentive = $incStmt->fetch(PDO::FETCH_ASSOC);

    $salaryStmt = $conn->prepare("SELECT salary FROM {$staff_role} WHERE id = ?");
    $salaryStmt->execute([$staff_id]);
    $base_salary = $salaryStmt->fetchColumn();

    if ($incentive && $base_salary !== false) {
        $amount = ($base_salary * $incentive['percentage']) / 100;
        if ($incentive['type'] === 'Subtraction') {
            $amount *= -1;
        }
        return $amount;
    }
    return null;
}


/*
|--------------------------------------------------------------------------
| 3. FORM HANDLERS (POST REQUESTS)
|--------------------------------------------------------------------------
| This block processes all form submissions for the page.
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handler for Defining a New Incentive Type
    if (isset($_POST['save_incentive'])) {
        $incentive_name = filter_input(INPUT_POST, 'incentive_name', FILTER_SANITIZE_STRING);
        $percentage = filter_input(INPUT_POST, 'percentage', FILTER_VALIDATE_FLOAT);
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        try {
            $stmt = $conn->prepare("INSERT INTO incentives (school_id, incentive_name, percentage, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$school_id, $incentive_name, $percentage, $type]);
            $successMessage = "Incentive type added successfully!";
        } catch (Exception $e) {
            $errorMessage = "Error adding incentive type: " . $e->getMessage();
        }
    }

    // Handler for Updating an Incentive Type
    if (isset($_POST['update_incentive'])) {
        $incentive_id = filter_input(INPUT_POST, 'incentive_id', FILTER_VALIDATE_INT);
        $incentive_name = filter_input(INPUT_POST, 'incentive_name', FILTER_SANITIZE_STRING);
        $percentage = filter_input(INPUT_POST, 'percentage', FILTER_VALIDATE_FLOAT);
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        if ($incentive_id) {
            try {
                $stmt = $conn->prepare("UPDATE incentives SET incentive_name = ?, percentage = ?, type = ? WHERE id = ? AND school_id = ?");
                $stmt->execute([$incentive_name, $percentage, $type, $incentive_id, $school_id]);
                $successMessage = "Incentive type updated successfully!";
            } catch (Exception $e) {
                $errorMessage = "Error updating incentive type: " . $e->getMessage();
            }
        } else {
            $errorMessage = "Invalid data provided for update.";
        }
    }

    // Handler for Deleting an Incentive Type
    if (isset($_POST['delete_incentive'])) {
        $incentive_id = filter_input(INPUT_POST, 'incentive_id', FILTER_VALIDATE_INT);
        if ($incentive_id) {
            try {
                $stmt = $conn->prepare("DELETE FROM incentives WHERE id = ? AND school_id = ?");
                $stmt->execute([$incentive_id, $school_id]);
                $successMessage = "Incentive type deleted successfully!";
            } catch (Exception $e) {
                $errorMessage = "Error deleting incentive type: " . $e->getMessage();
            }
        } else {
            $errorMessage = "Invalid incentive ID for deletion.";
        }
    }

    // Handler for Group Assignment of Incentives
    if (isset($_POST['assign_incentive'])) {
        $assign_to = $_POST['assign_to'] ?? [];
        $incentive_ids = $_POST['incentive_id'] ?? [];
        list($salary_year, $salary_month) = explode('-', $_POST['period']);
        if (empty($assign_to) || empty($incentive_ids)) {
            $errorMessage = "Please select at least one staff group and one incentive type.";
        } else {
            try {
                $conn->beginTransaction();
                $total_assignments = 0;
                foreach ($incentive_ids as $incentive_id) {
                    foreach ($assign_to as $role_to_assign) {
                        // *** ADDED 'all_hr' to the role map.
                        $role_map = ['all_teachers' => 'teacher', 'all_librarians' => 'librarian', 'all_principals' => 'principal', 'all_hr' => 'hr'];
                        $staff_role_name = $role_map[$role_to_assign] ?? null;
                        if ($staff_role_name) {
                            $staff_stmt = $conn->prepare("SELECT id FROM {$staff_role_name} WHERE school_id = ?");
                            $staff_stmt->execute([$school_id]);
                            $staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($staff_list as $staff) {
                                $amount = calculateIncentiveAmount($conn, $staff['id'], $staff_role_name, $incentive_id);
                                if ($amount !== null) {
                                    $assignStmt = $conn->prepare("INSERT INTO staff_incentives (staff_id, staff_role, incentive_id, salary_month, salary_year, amount, assigned_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                    $assignStmt->execute([$staff['id'], $staff_role_name, $incentive_id, $salary_month, $salary_year, $amount, $userId]);
                                    $total_assignments++;
                                }
                            }
                        }
                    }
                }
                $conn->commit();
                $successMessage = "Total of " . $total_assignments . " incentive assignments processed successfully!";
            } catch (Exception $e) {
                $conn->rollBack();
                $errorMessage = "Error assigning incentives: " . $e->getMessage();
            }
        }
    }

    // Handler for Single/Multi Staff Assignment of Incentives
    if (isset($_POST['assign_incentive_single'])) {
        $staff_member_keys = $_POST['staff_member'] ?? [];
        $incentive_ids = $_POST['incentive_id'] ?? [];
        list($salary_year, $salary_month) = explode('-', $_POST['period']);
        if (!empty($staff_member_keys) && !empty($incentive_ids)) {
            try {
                $conn->beginTransaction();
                $total_assignments = 0;
                foreach ($staff_member_keys as $staff_key) {
                    list($staff_role, $staff_id) = explode('-', $staff_key);
                    foreach ($incentive_ids as $incentive_id) {
                        $amount = calculateIncentiveAmount($conn, (int)$staff_id, $staff_role, (int)$incentive_id);
                        if ($amount !== null) {
                            $assignStmt = $conn->prepare("INSERT INTO staff_incentives (staff_id, staff_role, incentive_id, salary_month, salary_year, amount, assigned_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $assignStmt->execute([(int)$staff_id, $staff_role, (int)$incentive_id, $salary_month, $salary_year, $amount, $userId]);
                            $total_assignments++;
                        }
                    }
                }
                $conn->commit();
                $successMessage = "Total of " . $total_assignments . " incentive assignments processed successfully!";
            } catch (Exception $e) {
                $conn->rollBack();
                $errorMessage = "Error assigning incentive: " . $e->getMessage();
            }
        } else {
            $errorMessage = "Please select at least one staff member and one incentive.";
        }
    }

    // Handler for Updating a Specific Staff Incentive Assignment
    if (isset($_POST['update_staff_incentive'])) {
        $assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
        $new_percentage = filter_input(INPUT_POST, 'percentage', FILTER_VALIDATE_FLOAT);
        if ($assignment_id && $new_percentage !== false) {
            try {
                $assignmentStmt = $conn->prepare("SELECT si.staff_id, si.staff_role FROM staff_incentives si WHERE si.id = ?");
                $assignmentStmt->execute([$assignment_id]);
                $assignment_details = $assignmentStmt->fetch(PDO::FETCH_ASSOC);
                if ($assignment_details) {
                    $incentive_id = $conn->query("SELECT incentive_id FROM staff_incentives WHERE id = $assignment_id")->fetchColumn();
                    $new_amount = calculateIncentiveAmount($conn, $assignment_details['staff_id'], $assignment_details['staff_role'], $incentive_id);
                    // This is slightly flawed as it doesn't use the *new* percentage. Let's fix this.
                    // The original logic was better. I will revert to that.
                    $assignmentStmt = $conn->prepare("SELECT si.staff_id, si.staff_role, i.type FROM staff_incentives si JOIN incentives i ON si.incentive_id = i.id WHERE si.id = ?");
                    $assignmentStmt->execute([$assignment_id]);
                    $assignment_details = $assignmentStmt->fetch(PDO::FETCH_ASSOC);
                    $base_salary = $conn->query("SELECT salary FROM {$assignment_details['staff_role']} WHERE id = {$assignment_details['staff_id']}")->fetchColumn();
                    $new_amount = ($base_salary * $new_percentage) / 100;
                    if ($assignment_details['type'] === 'Subtraction') {
                        $new_amount *= -1;
                    }

                    $updateStmt = $conn->prepare("UPDATE staff_incentives SET amount = ? WHERE id = ?");
                    $updateStmt->execute([$new_amount, $assignment_id]);
                    $successMessage = "Incentive assignment updated successfully!";
                }
            } catch (Exception $e) {
                $errorMessage = "Error updating assignment: " . $e->getMessage();
            }
        } else {
            $errorMessage = "Invalid data for update.";
        }
    }

    // Handler for Deleting a Specific Staff Incentive Assignment
    if (isset($_POST['delete_staff_incentive'])) {
        $assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
        if ($assignment_id) {
            try {
                $stmt = $conn->prepare("DELETE FROM staff_incentives WHERE id = ?");
                $stmt->execute([$assignment_id]);
                $successMessage = "Incentive assignment deleted successfully!";
            } catch (Exception $e) {
                $errorMessage = "Error deleting assignment: " . $e->getMessage();
            }
        } else {
            $errorMessage = "Invalid assignment ID for deletion.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| 4. DATA FETCHING FOR PAGE DISPLAY
|--------------------------------------------------------------------------
| Retrieves all necessary data from the database to render the page.
*/

// Fetch all defined incentive types for the school
$incentives = $conn->prepare("SELECT * FROM incentives WHERE school_id = ? ORDER BY incentive_name");
$incentives->execute([$school_id]);
$incentive_list = $incentives->fetchAll(PDO::FETCH_ASSOC);

// Fetch all staff for dropdowns
// *** ADDED the HR staff to the main query, excluding the logged-in user.
$all_staff_query = "
    (SELECT id, teacher_name AS name, 'teacher' AS role FROM teacher WHERE school_id = ?)
    UNION ALL
    (SELECT id, principal_name AS name, 'principal' AS role FROM principal WHERE school_id = ?)
    UNION ALL
    (SELECT id, librarian_name AS name, 'librarian' AS role FROM librarian WHERE school_id = ?)
    UNION ALL
    (SELECT id, hr_name AS name, 'hr' AS role FROM hr WHERE school_id = ? AND id != ?)
    ORDER BY role, name";
$all_staff_stmt = $conn->prepare($all_staff_query);
$all_staff_stmt->execute([$school_id, $school_id, $school_id, $school_id, $userId]);
$all_staff_list = $all_staff_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all incentive assignments for the school
// *** ADDED a LEFT JOIN for the HR table to get HR staff names and salaries.
$assigned_incentives_query = "
    SELECT si.id, si.staff_id, si.staff_role, si.amount, si.salary_month, si.salary_year, si.assigned_at, i.incentive_name,
        CASE
            WHEN si.staff_role = 'teacher' THEN t.teacher_name
            WHEN si.staff_role = 'principal' THEN p.principal_name
            WHEN si.staff_role = 'librarian' THEN l.librarian_name
            WHEN si.staff_role = 'hr' THEN h.hr_name
        END AS staff_name,
        CASE
            WHEN si.staff_role = 'teacher' THEN t.salary
            WHEN si.staff_role = 'principal' THEN p.salary
            WHEN si.staff_role = 'librarian' THEN l.salary
            WHEN si.staff_role = 'hr' THEN h.salary
        END AS base_salary
    FROM staff_incentives si
    JOIN incentives i ON si.incentive_id = i.id
    LEFT JOIN teacher t ON si.staff_id = t.id AND si.staff_role = 'teacher'
    LEFT JOIN principal p ON si.staff_id = p.id AND si.staff_role = 'principal'
    LEFT JOIN librarian l ON si.staff_id = l.id AND si.staff_role = 'librarian'
    LEFT JOIN hr h ON si.staff_id = h.id AND si.staff_role = 'hr'
    WHERE i.school_id = ? ORDER BY si.assigned_at DESC";
$assigned_stmt = $conn->prepare($assigned_incentives_query);
$assigned_stmt->execute([$school_id]);
$assigned_incentive_list = $assigned_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| 5. DATA PROCESSING FOR FRONTEND
|--------------------------------------------------------------------------
| Processes fetched data into formats suitable for JavaScript.
*/

// Group staff by role for the dropdown optgroups
$staff_by_role = [];
foreach ($all_staff_list as $staff) {
    $staff_by_role[$staff['role']][] = $staff;
}

// Create a map of incentives grouped by staff for the details modal
$staff_incentives_map = [];
$unique_staff_list = [];
foreach ($assigned_incentive_list as $assignment) {
    $staff_key = $assignment['staff_role'] . '-' . $assignment['staff_id'];
    if (!isset($staff_incentives_map[$staff_key])) {
        $staff_incentives_map[$staff_key] = [];
    }
    $staff_incentives_map[$staff_key][] = $assignment;
    if (!isset($unique_staff_list[$staff_key])) {
        $unique_staff_list[$staff_key] = ['staff_name' => $assignment['staff_name'], 'staff_role' => $assignment['staff_role']];
    }
}
$staff_incentives_json = json_encode($staff_incentives_map);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Manage Incentives</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <style>
        .searchable-dropdown .dropdown-menu {
            display: none;
            position: absolute;
            width: 100%;
            z-index: 1000;
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid #d1d3e2;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            background-color: #fff;
        }

        .searchable-dropdown .form-check:hover {
            background-color: #f8f9fa;
        }

        .searchable-dropdown .form-check-label {
            width: 100%;
            cursor: pointer;
        }

        .searchable-dropdown strong {
            display: block;
            padding: 0.5rem 1rem 0.25rem;
            color: #858796;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .input-group-icon {
            position: relative;
        }

        .input-group-icon .form-control {
            padding-left: 2.375rem;
        }

        .input-group-icon .input-icon {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 3;
            width: 2.375rem;
            height: calc(1.5em + 0.75rem + 2px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #858796;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage Incentives</h1>

                    <?php if ($successMessage): ?><div class="alert alert-success"><?php echo $successMessage; ?></div><?php endif; ?>
                    <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo $errorMessage; ?></div><?php endif; ?>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Define Incentive Types</h6>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-group"><label for="incentive_name">Incentive Name</label><input type="text" name="incentive_name" class="form-control" required></div>
                                        <div class="form-group"><label for="percentage">Percentage of Base Salary (%)</label><input type="number" step="0.01" name="percentage" class="form-control" required></div>
                                        <div class="form-group"><label for="type">Type</label><select name="type" class="form-control" required>
                                                <option value="Addition">Addition (Bonus)</option>
                                                <option value="Subtraction">Subtraction (Deduction)</option>
                                            </select></div>
                                        <button type="submit" name="save_incentive" class="btn btn-primary">Add Incentive</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Assign Incentives</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="nav nav-tabs" id="assignTabs" role="tablist">
                                        <li class="nav-item"><a class="nav-link active" id="group-assign-tab" data-toggle="tab" href="#groupAssign" role="tab">Group Assign</a></li>
                                        <li class="nav-item"><a class="nav-link" id="single-assign-tab" data-toggle="tab" href="#singleAssign" role="tab">Single Staff Assign</a></li>
                                    </ul>
                                    <div class="tab-content" id="assignTabsContent">
                                        <div class="tab-pane fade show active" id="groupAssign" role="tabpanel" style="padding-top: 1.5rem;">
                                            <form action="" method="POST">
                                                <div class="row">
                                                    <div class="form-group col-lg-6"><label for="period">For Period</label>
                                                        <div class="input-group-icon"><input type="month" name="period" class="form-control" value="<?php echo date('Y-m'); ?>" required><span class="input-icon"><i class="fas fa-calendar-alt"></i></span></div>
                                                    </div>
                                                    <div class="form-group searchable-dropdown col-lg-6" id="assignToDropdown">
                                                        <label for="assignToDisplay">Assign To</label>
                                                        <input type="text" id="assignToDisplay" class="form-control" placeholder="Select one or more staff groups">
                                                        <div id="assignToOptions" class="dropdown-menu p-2 w-100">
                                                            <div class="form-check"><input class="form-check-input" type="checkbox" name="assign_to[]" value="all_teachers" id="assign_teachers"><label class="form-check-label" for="assign_teachers">All Teachers</label></div>
                                                            <div class="form-check"><input class="form-check-input" type="checkbox" name="assign_to[]" value="all_librarians" id="assign_librarians"><label class="form-check-label" for="assign_librarians">All Librarians</label></div>
                                                            <div class="form-check"><input class="form-check-input" type="checkbox" name="assign_to[]" value="all_principals" id="assign_principals"><label class="form-check-label" for="assign_principals">All Principals</label></div>
                                                            <div class="form-check"><input class="form-check-input" type="checkbox" name="assign_to[]" value="all_hr" id="assign_hr"><label class="form-check-label" for="assign_hr">All HR</label></div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group searchable-dropdown col-lg-12" id="groupIncentiveDropdown">
                                                        <label for="groupIncentiveDisplay">Incentive Type(s)</label>
                                                        <div class="input-group-icon">
                                                            <input type="text" id="groupIncentiveDisplay" class="form-control" placeholder="Search & select incentives...">
                                                            <span class="input-icon"><i class="fas fa-search"></i></span>
                                                        </div>
                                                        <div id="groupIncentiveOptions" class="dropdown-menu p-2 w-100">
                                                            <?php foreach ($incentive_list as $inc): ?>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="incentive_id[]" value="<?php echo $inc['id']; ?>" id="group_inc_<?php echo $inc['id']; ?>">
                                                                    <label class="form-check-label" for="group_inc_<?php echo $inc['id']; ?>"><?php echo htmlspecialchars($inc['incentive_name']); ?></label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" name="assign_incentive" class="btn btn-success">Assign to Group</button>
                                            </form>
                                        </div>
                                        <div class="tab-pane fade" id="singleAssign" role="tabpanel" style="padding-top: 1.5rem;">
                                            <form action="" method="POST">
                                                <div class="row">
                                                    <div class="form-group col-lg-6"><label for="period_single">For Period</label>
                                                        <div class="input-group-icon"><input type="month" name="period" id="period_single" class="form-control" value="<?php echo date('Y-m'); ?>" required><span class="input-icon"><i class="fas fa-calendar-alt"></i></span></div>
                                                    </div>
                                                    <div class="form-group searchable-dropdown col-lg-6" id="singleIncentiveDropdown">
                                                    <label for="singleIncentiveDisplay">Incentive Type(s)</label>
                                                    <div class="input-group-icon">
                                                        <input type="text" id="singleIncentiveDisplay" class="form-control" placeholder="Search & select incentives...">
                                                        <span class="input-icon"><i class="fas fa-search"></i></span>
                                                    </div>
                                                    <div id="singleIncentiveOptions" class="dropdown-menu p-2 w-100">
                                                        <?php foreach ($incentive_list as $inc): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="incentive_id[]" value="<?php echo $inc['id']; ?>" id="single_inc_<?php echo $inc['id']; ?>">
                                                                <label class="form-check-label" for="single_inc_<?php echo $inc['id']; ?>"><?php echo htmlspecialchars($inc['incentive_name']); ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                    <div class="form-group searchable-dropdown col-lg-12" id="singleStaffDropdown">
                                                        <label for="singleStaffDisplay">Select Staff Member(s)</label>
                                                        <div class="input-group-icon">
                                                            <input type="text" id="singleStaffDisplay" class="form-control" placeholder="Search & select staff...">
                                                            <span class="input-icon"><i class="fas fa-search"></i></span>
                                                        </div>
                                                        <div id="singleStaffOptions" class="dropdown-menu p-2 w-100">
                                                            <?php foreach ($staff_by_role as $role => $staff_members): ?>
                                                                <strong><?php echo ucfirst($role) . 's'; ?></strong>
                                                                <?php foreach ($staff_members as $staff): ?>
                                                                    <div class="form-check ml-2">
                                                                        <input class="form-check-input" type="checkbox" name="staff_member[]" value="<?php echo $role . '-' . $staff['id']; ?>" id="staff_<?php echo $staff['id']; ?>">
                                                                        <label class="form-check-label" for="staff_<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['name']); ?></label>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" name="assign_incentive_single" class="btn btn-info">Assign to Staff Member(s)</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Existing Incentives</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="incentivesTable_display" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Incentive Name</th>
                                                    <th>Type</th>
                                                    <th>Percentage</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($incentive_list as $inc): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($inc['incentive_name']); ?></td>
                                                        <td><span class="badge badge-<?php echo $inc['type'] === 'Addition' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($inc['type']); ?></span></td>
                                                        <td><?php echo rtrim(rtrim($inc['percentage'], '0'), '.'); ?>%</td>
                                                        <td>
                                                            <a href="#" class="btn btn-warning btn-sm edit-btn" data-toggle="modal" data-target="#editIncentiveModal" data-id="<?php echo $inc['id']; ?>" data-name="<?php echo htmlspecialchars($inc['incentive_name']); ?>" data-percentage="<?php echo $inc['percentage']; ?>" data-type="<?php echo $inc['type']; ?>"><i class="fas fa-edit"></i> Edit</a>
                                                            <a href="#" class="btn btn-danger btn-sm delete-btn" data-toggle="modal" data-target="#deleteIncentiveModal" data-id="<?php echo $inc['id']; ?>"><i class="fas fa-trash"></i> Delete</a>
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

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Staff Incentive Summary</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="nav nav-tabs mb-3" id="roleFilterTabs">
                                        <li class="nav-item"><a class="nav-link active" href="#" data-role="all">All</a></li>
                                        <li class="nav-item"><a class="nav-link" href="#" data-role="Teacher">Teachers</a></li>
                                        <li class="nav-item"><a class="nav-link" href="#" data-role="Principal">Principals</a></li>
                                        <li class="nav-item"><a class="nav-link" href="#" data-role="Librarian">Librarians</a></li>
                                        <li class="nav-item"><a class="nav-link" href="#" data-role="Hr">HR</a></li>
                                    </ul>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="assignedIncentivesTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Staff Name</th>
                                                    <th>Role</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($unique_staff_list as $staff_key => $staff_details): ?>
                                                    <tr class="clickable-row" data-toggle="modal" data-target="#incentiveDetailsModal" data-staff-key="<?php echo $staff_key; ?>" data-staff-name="<?php echo htmlspecialchars($staff_details['staff_name']); ?>" style="cursor: pointer;">
                                                        <td><?php echo htmlspecialchars($staff_details['staff_name']); ?></td>
                                                        <td><?php echo htmlspecialchars(ucfirst($staff_details['staff_role'])); ?></td>
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
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>

    <?php include_once "../../includes/logout_modal.php"; ?>
    <div class="modal fade" id="editIncentiveModal" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Incentive</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body"><input type="hidden" name="incentive_id" id="edit_incentive_id">
                        <div class="form-group"><label>Incentive Name</label><input type="text" name="incentive_name" id="edit_incentive_name" class="form-control" required></div>
                        <div class="form-group"><label>Percentage (%)</label><input type="number" step="0.01" name="percentage" id="edit_percentage" class="form-control" required></div>
                        <div class="form-group"><label>Type</label><select name="type" id="edit_type" class="form-control" required>
                                <option value="Addition">Addition</option>
                                <option value="Subtraction">Subtraction</option>
                            </select></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button><button type="submit" name="update_incentive" class="btn btn-primary">Save Changes</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteIncentiveModal" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">Are you sure you want to delete this incentive type? This cannot be undone.</div>
                <form action="" method="POST">
                    <div class="modal-footer"><input type="hidden" name="incentive_id" id="delete_incentive_id"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" name="delete_incentive" class="btn btn-danger">Delete</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="incentiveDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Incentive Details for <strong id="modalStaffName"></strong></h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="details-content-placeholder"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button><button type="button" id="addNewIncentiveBtn" class="btn btn-primary">Add New Incentive</button></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addStaffIncentiveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Incentive</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body"><input type="hidden" name="staff_id" id="add_staff_id"><input type="hidden" name="staff_role" id="add_staff_role">
                        <div class="form-group"><label>Incentive Type</label><select name="incentive_id" class="form-control" required>
                                <option value="">-- Select --</option><?php foreach ($incentive_list as $inc) {
                                                                            echo "<option value='{$inc['id']}'>" . htmlspecialchars($inc['incentive_name']) . "</option>";
                                                                        } ?>
                            </select></div>
                        <div class="form-group"><label>For Period</label><input type="month" name="period" class="form-control" value="<?php echo date('Y-m'); ?>" required></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" name="add_incentive_to_staff" class="btn btn-success">Add</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editStaffIncentiveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Assigned Incentive</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body"><input type="hidden" name="assignment_id" id="edit_assignment_id">
                        <div class="form-group"><label for="edit_percentage">New Percentage (%)</label><input type="number" step="0.01" name="percentage" id="edit_percentage" class="form-control" required></div><small class="form-text text-muted">The final amount will be recalculated based on this percentage.</small>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" name="update_staff_incentive" class="btn btn-primary">Update</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteStaffIncentiveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">Are you sure you want to delete this specific incentive assignment?</div>
                <form action="" method="POST">
                    <div class="modal-footer"><input type="hidden" name="assignment_id" id="delete_assignment_id"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" name="delete_staff_incentive" class="btn btn-danger">Delete</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            const staffIncentivesData = <?php echo $staff_incentives_json; ?>;
            $('#incentivesTable_display').DataTable();
            var assignedTable = $('#assignedIncentivesTable').DataTable({
                "order": [],
                "columnDefs": [{
                    "orderable": false,
                    "targets": [0, 1]
                }]
            });

            // Helper function for custom searchable dropdowns
            function setupCustomDropdown(wrapper) {
                const displayInput = wrapper.find('input[type="text"]');
                const optionsContainer = wrapper.find('.dropdown-menu');
                const checkboxes = optionsContainer.find('.form-check-input');
                const placeholder = displayInput.attr('placeholder');

                displayInput.on('focus', function() {
                    optionsContainer.show();
                });

                displayInput.on('keyup', function() {
                    const searchTerm = $(this).val().toLowerCase();
                    optionsContainer.find('.form-check').each(function() {
                        const label = $(this).find('label').text().toLowerCase();
                        $(this).toggle(label.includes(searchTerm));
                    });
                    if (optionsContainer.attr('id') === 'singleStaffOptions') {
                        optionsContainer.find('strong').each(function() {
                            const visibleCheckboxes = $(this).nextUntil('strong').filter('.form-check:visible');
                            $(this).toggle(visibleCheckboxes.length > 0);
                        });
                    }
                });

                checkboxes.on('change', function() {
                    const selectedLabels = [];
                    checkboxes.filter(':checked').each(function() {
                        selectedLabels.push($(this).siblings('label').text().trim());
                    });
                    if (selectedLabels.length > 0) {
                        displayInput.val(selectedLabels.join(', '));
                    } else {
                        displayInput.val('');
                        displayInput.attr('placeholder', placeholder);
                    }
                });
            }

            // Initialize all custom dropdowns
            setupCustomDropdown($('#assignToDropdown'));
            setupCustomDropdown($('#groupIncentiveDropdown'));
            setupCustomDropdown($('#singleStaffDropdown'));
            setupCustomDropdown($('#singleIncentiveDropdown'));

            // Global click listener to close dropdowns
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.searchable-dropdown').length) {
                    $('.dropdown-menu').hide();
                }
            });

            $('#incentivesTable_display').on('click', '.edit-btn', function() {
                $('#edit_incentive_id').val($(this).data('id'));
                $('#edit_incentive_name').val($(this).data('name'));
                $('#edit_percentage').val($(this).data('percentage'));
                $('#edit_type').val($(this).data('type'));
            });
            $('#incentivesTable_display').on('click', '.delete-btn', function() {
                $('#delete_incentive_id').val($(this).data('id'));
            });

            $('#details-content-placeholder').on('click', '.edit-assignment-btn', function() {
                $('#edit_assignment_id').val($(this).data('assignment-id'));
                $('#edit_percentage').val($(this).data('percentage'));
                $('#incentiveDetailsModal').modal('hide');
                $('#editStaffIncentiveModal').modal('show');
            });

            $('#details-content-placeholder').on('click', '.delete-assignment-btn', function() {
                $('#delete_assignment_id').val($(this).data('assignment-id'));
                $('#incentiveDetailsModal').modal('hide');
                $('#deleteStaffIncentiveModal').modal('show');
            });

            $('#addNewIncentiveBtn').on('click', function() {
                const staffKey = $(this).data('staff-key');
                if (staffKey) {
                    const [role, id] = staffKey.split('-');
                    $('#add_staff_id').val(id);
                    $('#add_staff_role').val(role);
                    $('#incentiveDetailsModal').modal('hide');
                    $('#addStaffIncentiveModal').modal('show');
                }
            });

            $('#assignedIncentivesTable tbody').on('click', '.clickable-row', function() {
                const staffKey = $(this).data('staff-key');
                const staffName = $(this).data('staff-name');
                const incentives = staffIncentivesData[staffKey] || [];

                $('#modalStaffName').text(staffName);
                $('#addNewIncentiveBtn').data('staff-key', staffKey);

                let detailsHtml = '<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Incentive Name</th><th>Amount (Effective %)</th><th>For Period</th><th>Actions</th></tr></thead><tbody>';
                if (incentives.length > 0) {
                    incentives.forEach(function(inc) {
                        const periodDate = new Date(inc.salary_year, inc.salary_month - 1);
                        const periodString = periodDate.toLocaleString('default', {
                            month: 'long',
                            year: 'numeric'
                        });
                        const amount = parseFloat(inc.amount);
                        const baseSalary = parseFloat(inc.base_salary);
                        let currentPercentage = 0;
                        if (baseSalary > 0) {
                            currentPercentage = (Math.abs(amount) / baseSalary) * 100;
                        }
                        const amountClass = amount >= 0 ? 'text-success' : 'text-danger';
                        const formattedAmount = '₹' + amount.toLocaleString('en-IN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });

                        detailsHtml += `<tr>
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

            $('#roleFilterTabs a').on('click', function(e) {
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