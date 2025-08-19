<?php
// Assuming 'session_start()' is in 'connect.php' or another global include.
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

date_default_timezone_set('Asia/Kolkata');

// --- User Authentication and Authorization ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'principal' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
$errorMessage = '';
$successMessage = '';
$holidays = [];
$available_years = [];

try {
    // Fetch the principal's assigned school ID.
    $stmt_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt_school->execute([$userId]);
    $principalDetails = $stmt_school->fetch(PDO::FETCH_ASSOC);
    $school_id = $principalDetails['school_id'] ?? null;

    if (!$school_id) {
        throw new Exception("Access Denied: You are not assigned to a school.");
    }

    // --- HANDLE FORM SUBMISSIONS (ADD/DELETE) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        $holiday_date = $_POST['holiday_date'];
        $description = trim($_POST['description']);

        if (empty($holiday_date) || empty($description)) {
            $errorMessage = "Both date and description are required.";
        } else {
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

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $holiday_id = $_GET['id'];
        $deleteStmt = $conn->prepare("DELETE FROM holidays WHERE id = ? AND school_id = ?");
        if ($deleteStmt->execute([$holiday_id, $school_id])) {
            $successMessage = "Holiday deleted successfully!";
        } else {
            $errorMessage = "Failed to delete holiday.";
        }
    }

    // --- FETCH DATA FOR DISPLAY ---

    // Get all unique years that have holidays, for the filter dropdown (sorted chronologically).
    $stmt_years = $conn->prepare("SELECT DISTINCT EXTRACT(YEAR FROM holiday_date) as year FROM holidays WHERE school_id = ? ORDER BY year ASC");
    $stmt_years->execute([$school_id]);
    $available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN, 0);

    // Determine the selected year for filtering. Default to the current year if available, otherwise 'all'.
    $current_year = date('Y');
    $selected_year = isset($_GET['year']) ? $_GET['year'] : (in_array($current_year, $available_years) ? $current_year : 'all');

    // Build the main query to fetch holidays.
    $params = [$school_id];
    $query = "SELECT id, holiday_date, description FROM holidays WHERE school_id = ?";

    if ($selected_year !== 'all' && is_numeric($selected_year)) {
        $query .= " AND EXTRACT(YEAR FROM holiday_date) = ?";
        $params[] = $selected_year;
    }

    // Sort from Jan 1 to Dec 31 by ordering by date in ASCENDING order.
    $query .= " ORDER BY holiday_date ASC";
    $stmt_holidays = $conn->prepare($query);
    $stmt_holidays->execute($params);
    $holidays = $stmt_holidays->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
}

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Holiday Management - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-4 text-gray-800">Holiday Management</h1>

                    <?php if ($successMessage): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($successMessage); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
                    <?php if ($errorMessage): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($errorMessage); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add a New Holiday</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="manage_holidays.php">
                                <input type="hidden" name="action" value="add">
                                <div class="form-row">
                                    <div class="form-group col-md-5"><label for="holiday_date">Holiday Date</label><input type="date" class="form-control" id="holiday_date" name="holiday_date" required></div>
                                    <div class="form-group col-md-5"><label for="description">Description</label><input type="text" class="form-control" id="description" name="description" placeholder="e.g., Ram Navami" required></div>
                                    <div class="form-group col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-success btn-block"><i class="fas fa-plus"></i> Add</button></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">List of Festivals</h6>
                            <form action="manage_holidays.php" method="GET" class="form-inline">
                                <div class="form-group">
                                    <label for="year" class="mr-2">Year:</label>
                                    <select name="year" id="year" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value="all" <?php echo ($selected_year == 'all') ? 'selected' : ''; ?>>All Years</option>
                                        <?php foreach ($available_years as $year): ?>
                                            <option value="<?php echo $year; ?>" <?php echo ($selected_year == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($holidays)): ?>
                                            <tr><td colspan="3" class="text-center">No holidays found for the selected year.</td></tr>
                                        <?php else: ?>
                                            <?php
                                            $last_year = null;
                                            foreach ($holidays as $holiday):
                                                $current_year_for_row = date('Y', strtotime($holiday['holiday_date']));
                                                // If viewing 'All Years' and the year changes, print a year header row.
                                                if ($selected_year === 'all' && $current_year_for_row !== $last_year) {
                                                    echo '<tr><td colspan="3" class="text-center font-weight-bold bg-light">' . $current_year_for_row . '</td></tr>';
                                                    $last_year = $current_year_for_row;
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo date('d M, Y', strtotime($holiday['holiday_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($holiday['description']); ?></td>
                                                    <td><a href="manage_holidays.php?action=delete&id=<?php echo $holiday['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i></a></td>
                                                </tr>
                                            <?php endforeach; ?>
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
    
    <?php include_once "../../includes/logout_modal.php"?>
    
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
<?php
}
?>