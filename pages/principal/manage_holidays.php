<?php
session_start();
include_once '../../includes/connect.php';
include_once '../../encryption.php';

date_default_timezone_set('Asia/Kolkata');

// Authentication and Authorization
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'principal' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
$errorMessage = '';
$successMessage = '';

try {
    // Fetch principal's school_id
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $principalDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $school_id = $principalDetails['school_id'] ?? null;

    if (!$school_id) {
        throw new Exception("Access Denied: You are not assigned to a school.");
    }

    // --- HANDLE FORM SUBMISSIONS ---

    // Handle ADDING a new holiday
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        $holiday_date = $_POST['holiday_date'];
        $description = trim($_POST['description']);

        if (empty($holiday_date) || empty($description)) {
            $errorMessage = "Both date and description are required.";
        } else {
            // Check for duplicates
            $checkStmt = $conn->prepare("SELECT id FROM holidays WHERE school_id = ? AND holiday_date = ?");
            $checkStmt->execute([$school_id, $holiday_date]);
            if ($checkStmt->fetch()) {
                $errorMessage = "A holiday for this date already exists.";
            } else {
                $insertStmt = $conn->prepare("INSERT INTO holidays (school_id, holiday_date, description) VALUES (?, ?, ?)");
                if ($insertStmt->execute([$school_id, $holiday_date, $description])) {
                    $successMessage = "Holiday added successfully!";
                } else {
                    $errorMessage = "Failed to add holiday.";
                }
            }
        }
    }

    // Handle DELETING a holiday
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $holiday_id = $_GET['id'];
        // SECURITY: Ensure the principal can only delete holidays from their own school
        $deleteStmt = $conn->prepare("DELETE FROM holidays WHERE id = ? AND school_id = ?");
        if ($deleteStmt->execute([$holiday_id, $school_id])) {
            $successMessage = "Holiday deleted successfully!";
        } else {
            $errorMessage = "Failed to delete holiday.";
        }
    }

    // --- FETCH EXISTING HOLIDAYS ---
    $holidays = [];
    $stmt = $conn->prepare("SELECT id, holiday_date, description FROM holidays WHERE school_id = ? ORDER BY holiday_date DESC");
    $stmt->execute([$school_id]);
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Holiday Management - School Management System</title>
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
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Holiday Management</h1>

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($successMessage); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($errorMessage); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add a New Holiday</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="add">
                                <div class="form-row">
                                    <div class="form-group col-md-5">
                                        <label for="holiday_date">Holiday Date</label>
                                        <input type="date" class="form-control" id="holiday_date" name="holiday_date" required>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label for="description">Description</label>
                                        <input type="text" class="form-control" id="description" name="description" placeholder="e.g., Independence Day" required>
                                    </div>
                                    <div class="form-group col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-success btn-block"><i class="fas fa-plus"></i> Add Holiday</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">List of Holidays</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($holidays as $holiday): ?>
                                            <tr>
                                                <td><?php echo date('d M, Y', strtotime($holiday['holiday_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($holiday['description']); ?></td>
                                                <td>
                                                    <a href="manage_holidays.php?action=delete&id=<?php echo $holiday['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this holiday?');">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
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
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "order": [[ 0, "desc" ]] // Sort by date descending by default
        });
    });
    </script>
</body>
</html>