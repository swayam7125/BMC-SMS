<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

// Get Principal's School ID
$school_id = null;
if($userId) {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
}
if (!$school_id) {
    die("Error: Could not determine your school.");
}

$errors = [];
$success = '';

// Handle Add/Edit Vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_vehicle'])) {
    $vehicle_id = $_POST['vehicle_id'] ? (int)$_POST['vehicle_id'] : null;
    $vehicle_number = trim($_POST['vehicle_number']);
    $model = trim($_POST['model']);
    $seating_capacity = (int)$_POST['seating_capacity'];
    $insurance_expiry_date = $_POST['insurance_expiry_date'] ?: null;

    if (empty($vehicle_number) || $seating_capacity <= 0) {
        $errors[] = "Vehicle Number and Seating Capacity are required.";
    }

    if (empty($errors)) {
        try {
            if ($vehicle_id) { // Update
                $sql = "UPDATE vehicles SET vehicle_number = ?, model = ?, seating_capacity = ?, insurance_expiry_date = ? WHERE id = ? AND school_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$vehicle_number, $model, $seating_capacity, $insurance_expiry_date, $vehicle_id, $school_id]);
                $success = "Vehicle updated successfully!";
            } else { // Insert
                $sql = "INSERT INTO vehicles (school_id, vehicle_number, model, seating_capacity, insurance_expiry_date) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$school_id, $vehicle_number, $model, $seating_capacity, $insurance_expiry_date]);
                $success = "Vehicle added successfully!";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23505') { // Unique constraint violation
                $errors[] = "A vehicle with this number already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch all vehicles for the school
$vehicles = [];
try {
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE school_id = ? ORDER BY vehicle_number");
    $stmt->execute([$school_id]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Could not fetch vehicle list: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Vehicles - School Management System</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Manage Vehicles</h1>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-plus-circle"></i> Add New Vehicle</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group col-md-3">
                                        <label for="vehicle_number">Vehicle Number *</label>
                                        <input type="text" class="form-control" name="vehicle_number" required>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="model">Model</label>
                                        <input type="text" class="form-control" name="model">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="seating_capacity">Seating Capacity *</label>
                                        <input type="number" class="form-control" name="seating_capacity" required>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="insurance_expiry_date">Insurance Expiry</label>
                                        <input type="date" class="form-control" name="insurance_expiry_date">
                                    </div>
                                </div>
                                <button type="submit" name="save_vehicle" class="btn btn-primary">Save Vehicle</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bus"></i> Existing Vehicles</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Vehicle Number</th>
                                            <th>Model</th>
                                            <th>Capacity</th>
                                            <th>Insurance Expiry</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></td>
                                                <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                                <td><?php echo htmlspecialchars($vehicle['seating_capacity']); ?></td>
                                                <td><?php echo $vehicle['insurance_expiry_date'] ? date('d M, Y', strtotime($vehicle['insurance_expiry_date'])) : 'N/A'; ?></td>
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