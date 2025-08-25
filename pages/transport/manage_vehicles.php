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
if ($userId) {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
}
if (!$school_id) {
    die("Error: Could not determine your school.");
}

$errors = [];
$success = '';
$edit_vehicle = null;

// Handle Delete Request
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ? AND school_id = ?");
        $stmt->execute([$delete_id, $school_id]);
        $success = "Vehicle deleted successfully!";
    } catch (PDOException $e) {
        $errors[] = "Error deleting vehicle: " . $e->getMessage();
    }
    header("Location: manage_vehicles.php?success=" . urlencode($success));
    exit;
}

// Handle Edit Request
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ? AND school_id = ?");
    $stmt->execute([$edit_id, $school_id]);
    $edit_vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle Add/Update Vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_vehicle'])) {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $vehicle_number = trim($_POST['vehicle_number']);
    $model = trim($_POST['model']);
    $seating_capacity = (int)$_POST['seating_capacity'];
    $insurance_expiry_date = $_POST['insurance_expiry_date'] ?: null;

    if (empty($vehicle_number) || $seating_capacity <= 0) {
        $errors[] = "Vehicle Number and Seating Capacity are required.";
    }

    if (empty($errors)) {
        try {
            if ($id) { // Update
                $sql = "UPDATE vehicles SET vehicle_number = ?, model = ?, seating_capacity = ?, insurance_expiry_date = ? WHERE id = ? AND school_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$vehicle_number, $model, $seating_capacity, $insurance_expiry_date, $id, $school_id]);
                $success = "Vehicle updated successfully!";
            } else { // Insert
                $sql = "INSERT INTO vehicles (school_id, vehicle_number, model, seating_capacity, insurance_expiry_date) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$school_id, $vehicle_number, $model, $seating_capacity, $insurance_expiry_date]);
                $success = "Vehicle added successfully!";
            }
            header("Location: manage_vehicles.php?success=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
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
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-plus-circle"></i> <?php echo $edit_vehicle ? 'Edit Vehicle' : 'Add New Vehicle'; ?></h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="manage_vehicles.php">
                                <?php if ($edit_vehicle): ?>
                                    <input type="hidden" name="id" value="<?php echo $edit_vehicle['id']; ?>">
                                <?php endif; ?>
                                <div class="form-row">
                                    <div class="form-group col-md-3">
                                        <label for="vehicle_number">Vehicle Number *</label>
                                        <input type="text" class="form-control" name="vehicle_number" placeholder="Format : GJ-05-AA-9999" value="<?php echo htmlspecialchars($edit_vehicle['vehicle_number'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="model">Model</label>
                                        <input type="text" class="form-control" name="model" value="<?php echo htmlspecialchars($edit_vehicle['model'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="seating_capacity">Seating Capacity *</label>
                                        <input type="number" class="form-control" name="seating_capacity" value="<?php echo htmlspecialchars($edit_vehicle['seating_capacity'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="insurance_expiry_date">Insurance Expiry</label>
                                        <input type="date" class="form-control" id="insurance_expiry_date" name="insurance_expiry_date" value="<?php echo htmlspecialchars($edit_vehicle['insurance_expiry_date'] ?? ''); ?>">
                                    </div>
                                </div>
                                <button type="submit" name="save_vehicle" class="btn btn-primary"><?php echo $edit_vehicle ? 'Update Vehicle' : 'Save Vehicle'; ?></button>
                                <?php if ($edit_vehicle): ?>
                                    <a href="manage_vehicles.php" class="btn btn-secondary">Cancel Edit</a>
                                <?php endif; ?>
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
                                                    <a href="?edit_id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                    <a href="#" data-toggle="modal" data-target="#deleteModal" data-url="?delete_id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
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

    <?php include_once "../../includes/logout_modal.php"; ?>
    
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this item? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#deleteModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var url = button.data('url');       // Extract URL from data-url attribute
            var modal = $(this);
            modal.find('#confirmDeleteBtn').attr('href', url);
        });

    // Blur past dates for "Insurance Expiry Date"
            const dateInput = document.getElementById('insurance_expiry_date');
            if (dateInput) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const formattedDate = `${year}-${month}-${day}`;
                dateInput.setAttribute('min', formattedDate);
            }
    });
    </script>
</body>
</html>