<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

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

// Get the payroll user ID from the URL
$payroll_user_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
if (!$payroll_user_id) {
    header("Location: payroll_list.php?error=Invalid user ID.");
    exit;
}

// Get the principal's school ID for security checks
$admin_school_id = null;
if ($userId) {
    $stmt = $conn->prepare('SELECT "school_id" FROM "principal" WHERE "id" = ?');
    $stmt->execute([$userId]);
    $admin_school_id = $stmt->fetchColumn();
}

// Fetch the payroll user's data
$user_data = null;
try {
    $stmt = $conn->prepare('SELECT u.email, py.payroll_name, py.school_id FROM users u JOIN payroll py ON u.id = py.id WHERE u.id = ?');
    $stmt->execute([$payroll_user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header("Location: payroll_list.php?error=Database error.");
    exit;
}

// Security check: Ensure the payroll user belongs to the principal's school
if (!$user_data || $user_data['school_id'] != $admin_school_id) {
    header("Location: payroll_list.php?error=You do not have permission to edit this user.");
    exit;
}

$errors = [];

// Handle form submission for updating the user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payroll_name = trim($_POST['payroll_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($payroll_name)) $errors[] = "Full Name is required.";
    if (empty($email)) $errors[] = "Email is required.";

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Update the 'payroll' table
            $stmt_payroll = $conn->prepare('UPDATE payroll SET payroll_name = ? WHERE id = ?');
            $stmt_payroll->execute([$payroll_name, $payroll_user_id]);

            // Update the 'users' table
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_user = $conn->prepare('UPDATE users SET email = ?, password = ? WHERE id = ?');
                $stmt_user->execute([$email, $hashed_password, $payroll_user_id]);
            } else {
                $stmt_user = $conn->prepare('UPDATE users SET email = ? WHERE id = ?');
                $stmt_user->execute([$email, $payroll_user_id]);
            }

            $conn->commit();
            header("Location: payroll_list.php?success=Payroll user updated successfully.");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            if ($e->getCode() == 23505) {
                $errors[] = "A user with this email already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Payroll User - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Payroll User</h1>
                        <a href="payroll_list.php" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Update Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="payroll_name">Full Name *</label>
                                    <input type="text" class="form-control" id="payroll_name" name="payroll_name" value="<?php echo htmlspecialchars($user_data['payroll_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="password">New Password (leave blank to keep current password)</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                </div>
                                <hr>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>
                            </form>
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
