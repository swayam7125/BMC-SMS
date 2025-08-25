<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Authorization check for payroll user
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'payroll' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
try {
    $stmt = $conn->prepare("SELECT school_id FROM payroll WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
} catch (Exception $e) {
    die("Error fetching user data: " . $e->getMessage());
}

if (!$school_id) {
    die("Error: Payroll user is not associated with any school.");
}

// Handle Form Submissions
$errorMessage = '';
$successMessage = '';

// Add Incentive Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_incentive'])) {
    $incentive_name = filter_input(INPUT_POST, 'incentive_name', FILTER_SANITIZE_STRING);
    $percentage = filter_input(INPUT_POST, 'percentage', FILTER_VALIDATE_FLOAT);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING); // 'Addition' or 'Subtraction'

    try {
        $stmt = $conn->prepare("INSERT INTO incentives (school_id, incentive_name, percentage, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$school_id, $incentive_name, $percentage, $type]);
        $successMessage = "Incentive type added successfully!";
    } catch (Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Assign Incentive to Staff Groups
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_incentive'])) {
    $assign_to = $_POST['assign_to'] ?? [];
    $incentive_id = filter_input(INPUT_POST, 'incentive_id', FILTER_VALIDATE_INT);
    $period = explode('-', $_POST['period']);
    $salary_month = $period[1];
    $salary_year = $period[0];

    if (empty($assign_to) || !$incentive_id) {
        $errorMessage = "Please select at least one staff group and an incentive type.";
    } else {
        try {
            $conn->beginTransaction();

            $incentiveStmt = $conn->prepare("SELECT percentage, type FROM incentives WHERE id = ?");
            $incentiveStmt->execute([$incentive_id]);
            $incentive_details = $incentiveStmt->fetch(PDO::FETCH_ASSOC);
            $percentage = $incentive_details['percentage'];
            $incentive_type = $incentive_details['type'];

            $assignStmt = $conn->prepare("INSERT INTO staff_incentives (staff_id, staff_role, incentive_id, salary_month, salary_year, amount, assigned_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $total_assigned = 0;

            foreach ($assign_to as $role_to_assign) {
                $staff_list = [];
                $staff_role_name = '';

                if ($role_to_assign === 'all_teachers') {
                    $staff_role_name = 'teacher';
                    $staff_stmt = $conn->prepare("SELECT id, salary FROM teacher WHERE school_id = ?");
                } elseif ($role_to_assign === 'all_librarians') {
                    $staff_role_name = 'librarian';
                    $staff_stmt = $conn->prepare("SELECT id, salary FROM librarian WHERE school_id = ?");
                } elseif ($role_to_assign === 'all_principals') {
                    $staff_role_name = 'principal';
                    $staff_stmt = $conn->prepare("SELECT id, salary FROM principal WHERE school_id = ?");
                }

                if (!empty($staff_role_name)) {
                    $staff_stmt->execute([$school_id]);
                    $staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($staff_list as $staff) {
                        $base_salary = $staff['salary'];
                        $incentive_amount = ($base_salary * $percentage) / 100;
                        
                        if ($incentive_type === 'Subtraction') {
                            $incentive_amount *= -1;
                        }
                        
                        $assignStmt->execute([$staff['id'], $staff_role_name, $incentive_id, $salary_month, $salary_year, $incentive_amount, $userId]);
                        $total_assigned++;
                    }
                }
            }

            $conn->commit();
            $successMessage = "Incentive assigned successfully to " . $total_assigned . " staff member(s)!";

        } catch (Exception $e) {
            $conn->rollBack();
            $errorMessage = "Error assigning incentive: " . $e->getMessage();
        }
    }
}


// Fetch Data for Display
$incentives = $conn->prepare("SELECT * FROM incentives WHERE school_id = ? ORDER BY incentive_name");
$incentives->execute([$school_id]);
$incentive_list = $incentives->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                        <div class="col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Define Incentive Types</h6></div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <label for="incentive_name">Incentive Name</label>
                                            <input type="text" name="incentive_name" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="percentage">Percentage of Base Salary (%)</label>
                                            <input type="number" step="0.01" name="percentage" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="type">Type</label>
                                            <select name="type" class="form-control" required>
                                                <option value="Addition">Addition (Bonus)</option>
                                                <option value="Subtraction">Subtraction (Deduction)</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="save_incentive" class="btn btn-primary">Add Incentive</button>
                                    </form>
                                    <hr>
                                    <h6 class="mt-4">Existing Incentives</h6>
                                    <ul class="list-group">
                                        <?php foreach($incentive_list as $inc): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($inc['incentive_name']); ?>
                                                <span class="badge badge-<?php echo $inc['type'] === 'Addition' ? 'success' : 'danger'; ?> badge-pill">
                                                    <?php echo ($inc['type'] === 'Addition' ? '+' : '-'); ?><?php echo $inc['percentage']; ?>%
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Assign Incentive to Staff</h6></div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <label for="period">For Period (Month-Year)</label>
                                            <input type="month" name="period" class="form-control" value="<?php echo date('Y-m'); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Assign To</label>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="assign_all_teachers" name="assign_to[]" value="all_teachers">
                                                <label class="custom-control-label" for="assign_all_teachers">All Teachers</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="assign_all_librarians" name="assign_to[]" value="all_librarians">
                                                <label class="custom-control-label" for="assign_all_librarians">All Librarians</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="assign_all_principals" name="assign_to[]" value="all_principals">
                                                <label class="custom-control-label" for="assign_all_principals">All Principals</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="incentive_id">Incentive Type</label>
                                            <select name="incentive_id" class="form-control" required>
                                                <option value="">-- Select Incentive --</option>
                                                <?php foreach($incentive_list as $inc): ?>
                                                    <option value="<?php echo $inc['id']; ?>"><?php echo htmlspecialchars($inc['incentive_name']); ?> (<?php echo $inc['type'] === 'Addition' ? '+' : '-'; ?><?php echo $inc['percentage']; ?>%)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" name="assign_incentive" class="btn btn-success">Assign Incentive</button>
                                    </form>
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

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>