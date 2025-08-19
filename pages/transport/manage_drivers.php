<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

$school_id = null;
if($userId) {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
}
if (!$school_id) die("Error: Could not determine your school.");

$errors = [];
$success = '';

// Handle Add/Edit Driver
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_driver'])) {
    $driver_id = $_POST['driver_id'] ? (int)$_POST['driver_id'] : null;
    $driver_name = trim($_POST['driver_name']);
    $phone_number = trim($_POST['phone_number']);
    $license_number = trim($_POST['license_number']);

    if (empty($driver_name) || empty($phone_number) || empty($license_number)) {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {
        try {
            if ($driver_id) { // Update
                $sql = "UPDATE drivers SET driver_name = ?, phone_number = ?, license_number = ? WHERE id = ? AND school_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$driver_name, $phone_number, $license_number, $driver_id, $school_id]);
                $success = "Driver updated successfully!";
            } else { // Insert
                $sql = "INSERT INTO drivers (school_id, driver_name, phone_number, license_number) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$school_id, $driver_name, $phone_number, $license_number]);
                $success = "Driver added successfully!";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all drivers for the school
$drivers = [];
try {
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE school_id = ? ORDER BY driver_name");
    $stmt->execute([$school_id]);
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Could not fetch driver list: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Drivers - School Management System</title>
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
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage Drivers</h1>
                     <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><?php foreach ($errors as $error): ?><p class="mb-0"><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-plus-circle"></i> Add New Driver</h6></div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group col-md-4"><label for="driver_name">Driver Name *</label><input type="text" class="form-control" name="driver_name" required></div>
                                    <div class="form-group col-md-4"><label for="phone_number">Phone Number *</label><input type="text" class="form-control" name="phone_number" required></div>
                                    <div class="form-group col-md-4"><label for="license_number">License Number *</label><input type="text" class="form-control" name="license_number" required></div>
                                </div>
                                <button type="submit" name="save_driver" class="btn btn-primary">Save Driver</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-id-card-alt"></i> Existing Drivers</h6></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead><tr><th>Name</th><th>Phone</th><th>License No.</th><th>Actions</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($drivers as $driver): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($driver['driver_name']); ?></td>
                                                <td><?php echo htmlspecialchars($driver['phone_number']); ?></td>
                                                <td><?php echo htmlspecialchars($driver['license_number']); ?></td>
                                                <td>
                                                    <a href="#" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                    <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
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
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>