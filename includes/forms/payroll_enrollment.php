<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/ajax_helpers.php";

// Get user role and ID from cookies
$role = null;
$userId = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security: Only principals can access this page
if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

// Get the principal's school information
$admin_school_id = null;
$admin_school_name = null;
if ($userId) {
    $stmt = $conn->prepare('SELECT s."id", s."school_name" FROM "principal" p JOIN "school" s ON p."school_id" = s."id" WHERE p."id" = ?');
    $stmt->execute([$userId]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin_data) {
        $admin_school_id = $admin_data['id'];
        $admin_school_name = $admin_data['school_name'];
    }
}

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $hr_name = trim($_POST['hr_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $salary = trim($_POST['salary'] ?? ''); // New salary field
    $school_id = $admin_school_id; // The school is fixed to the principal's school

    // --- Validation ---
    if (empty($hr_name)) $errors[] = "HR user's name is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (!is_numeric($salary) || $salary < 0) $errors[] = "Please enter a valid salary."; // Validate salary
    if (empty($school_id)) $errors[] = "Could not determine the school. Please log in again.";

    // --- Database Insertion ---
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_role = 'hr';

            // 1. Insert into the main 'users' table
            $stmt_user = $conn->prepare('INSERT INTO "users" ("role", "email", "password") VALUES (?, ?, ?)');
            $stmt_user->execute([$user_role, $email, $hashed_password]);
            $new_user_id = $conn->lastInsertId();

            // 2. Insert into the 'hr' table with the school_id and new salary
            $stmt_payroll = $conn->prepare('INSERT INTO "hr" (id, school_id, hr_name, salary) VALUES (?, ?, ?, ?)');
            $stmt_payroll->execute([$new_user_id, $school_id, $hr_name, $salary]);

            $conn->commit();
            header("Location: ../../pages/payroll/payroll_list.php?success=HR user enrolled successfully");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            if ($e->getCode() == 23505) { // Unique constraint violation
                $errors[] = "A user with this email already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Enroll HR User - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Enroll New HR User</h1>
                        <a href="../../pages/payroll/payroll_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">HR User Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="hr_name">Full Name *</label>
                                        <input type="text" class="form-control" id="hr_name" name="hr_name" value="<?php echo htmlspecialchars($_POST['hr_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="salary">Salary (Monthly) *</label>
                                        <input type="number" class="form-control" id="salary" name="salary" step="0.01" value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="email">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="password">Set Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                     <div class="form-group col-md-12">
                                        <label for="school_id">Assign to School</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_school_name); ?>" disabled>
                                        <input type="hidden" name="school_id" value="<?php echo $admin_school_id; ?>">
                                    </div>
                                </div>
                                <hr>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll HR User</button>
                                    <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Reset Form</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    
    <?php include_once "../../includes/logout_modal.php" ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
<?php
}
?>