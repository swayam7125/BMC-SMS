<?php
// pages/hr/manage_fees.php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";
require_once "../../includes/log_system.php";

$role = null;
$userId = null;
$hr_school_id = null;
$hr_school_name = null;
$standards = [];

// --- 1. User Authentication and Authorization ---
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Check if user is logged in and has the 'hr' role
if ($role !== 'hr' || !$userId) {
    header("Location: ../../login.php");
    exit;
}

// --- 2. Fetch HR's School and Available Standards ---
try {
    // Get the school ID and name for the logged-in HR user
    $stmt_school = $conn->prepare('SELECT s.id, s.school_name FROM hr h JOIN school s ON h.school_id = s.id WHERE h.id = ?');
    $stmt_school->execute([$userId]);
    $hr_data = $stmt_school->fetch(PDO::FETCH_ASSOC);

    if ($hr_data) {
        $hr_school_id = $hr_data['id'];
        $hr_school_name = $hr_data['school_name'];

        // Fetch standards based on the HR's school categories
        $stmt_standards = $conn->prepare('
            SELECT DISTINCT scm.standard_name
            FROM "school" s
            JOIN "standard_categories_mapping" scm ON scm.category_name = ANY(s.school_category)
            WHERE s.id = ?
            ORDER BY scm.standard_name
        ');
        $stmt_standards->execute([$hr_school_id]);
        $standards = $stmt_standards->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Log the error and display a user-friendly message
    error_log("Database error fetching data for HR Fee Management: " . $e->getMessage());
    $errors[] = "A database error occurred. Please try again later.";
}

// Check if the request is for an AJAX call or a full page load
if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Student Fees - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Add Student Fees</h1>
                    </div>
                    <div id="fee-alert-placeholder"></div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Fee Information</h6>
                        </div>
                        <div class="card-body">
                            <form id="addFeeForm" method="POST" action="process_add_fees.php" data-ajax-form>
                                <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($hr_school_id); ?>">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="standard">Standard / Class *</label>
                                        <select class="form-control" id="standard" name="standard" required>
                                            <option value="">-- Select Standard --</option>
                                            <?php foreach ($standards as $standard): ?>
                                                <option value="<?php echo htmlspecialchars($standard['standard_name']); ?>"><?php echo htmlspecialchars($standard['standard_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="academic_year">Academic Year *</label>
                                        <select class="form-control" id="academic_year" name="academic_year" required>
                                            <option value="">-- Select Year --</option>
                                            <?php
                                            $currentYear = date('Y');
                                            for ($i = -1; $i <= 2; $i++):
                                                $year = $currentYear + $i;
                                                $academicYear = $year . '-' . ($year + 1);
                                                echo "<option value='" . $academicYear . "'>" . $academicYear . "</option>";
                                            endfor;
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="fee_type">Fee Type (e.g., School Fee, Activity Fee) *</label>
                                        <input type="text" class="form-control" id="fee_type" name="fee_type" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="fee_amount">Fee Amount (per student) *</label>
                                        <input type="number" step="0.01" class="form-control" id="fee_amount" name="fee_amount" required min="0">
                                    </div>
                                </div>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Fees</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/js/ajax-forms.js"></script>
</body>
</html>
<?php
}
?>