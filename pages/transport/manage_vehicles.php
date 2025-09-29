<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/log_system.php"; // Log system included

// Start session to store messages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get user info for logging
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$userName = isset($_COOKIE['encrypted_user_name']) ? decrypt_id($_COOKIE['encrypted_user_name']) : 'N/A';

if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

$school_id = null;
if ($userId) {
    try {
        $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
        $stmt->execute([$userId]);
        $school_id = $stmt->fetchColumn();
    } catch (PDOException $e) {
        die("Error fetching school ID: " . $e->getMessage());
    }
}
if (!$school_id) {
    die("Error: Could not determine your school.");
}

$errors = [];

// Function to set messages in session
function set_message($type, $message) {
    $_SESSION['message'] = ['type' => $type, 'text' => $message];
}

// Function to display messages from session
function display_message() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message']['type'];
        $text = $_SESSION['message']['text'];
        echo "<div class='alert alert-{$type}'>{$text}</div>";
        unset($_SESSION['message']);
    }
}

try {
    // Handle Add Vehicle
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
        $vehicle_number = trim($_POST['vehicle_number']);
        $model = trim($_POST['model']);
        $seating_capacity = (int)$_POST['seating_capacity'];
        $insurance_expiry_date = $_POST['insurance_expiry_date'];

        if (empty($vehicle_number) || empty($model) || empty($seating_capacity) || empty($insurance_expiry_date)) {
            $errors[] = "All fields are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO vehicles (school_id, vehicle_number, model, seating_capacity, insurance_expiry_date) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$school_id, $vehicle_number, $model, $seating_capacity, $insurance_expiry_date])) {
                set_message('success', "Vehicle '{$vehicle_number}' added successfully!");
                // Log action
                log_interaction($role, $userId, "TRANSPORT: Added new vehicle '{$vehicle_number}'.", $userName);
            } else {
                set_message('danger', "Failed to add vehicle.");
                log_interaction($role, $userId, "TRANSPORT ERROR: Failed to add vehicle '{$vehicle_number}'.", $userName);
            }
        }
    }
    // Handle Edit Vehicle
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_vehicle'])) {
        $vehicle_id = (int)$_POST['edit_vehicle_id'];
        $vehicle_number = trim($_POST['edit_vehicle_number']);
        $model = trim($_POST['edit_model']);
        $seating_capacity = (int)$_POST['edit_seating_capacity'];
        $insurance_expiry_date = $_POST['edit_insurance_expiry_date'];

        if (empty($vehicle_number) || empty($model) || empty($seating_capacity) || empty($insurance_expiry_date)) {
            $errors[] = "All fields are required.";
        } else {
            $stmt = $conn->prepare("UPDATE vehicles SET vehicle_number = ?, model = ?, seating_capacity = ?, insurance_expiry_date = ? WHERE id = ? AND school_id = ?");
            if ($stmt->execute([$vehicle_number, $model, $seating_capacity, $insurance_expiry_date, $vehicle_id, $school_id])) {
                set_message('success', "Vehicle '{$vehicle_number}' updated successfully!");
                // Log action
                log_interaction($role, $userId, "TRANSPORT: Updated vehicle '{$vehicle_number}' (ID: {$vehicle_id}).", $userName);
            } else {
                set_message('danger', "Failed to update vehicle.");
                 log_interaction($role, $userId, "TRANSPORT ERROR: Failed to update vehicle '{$vehicle_number}' (ID: {$vehicle_id}).", $userName);
            }
        }
    }
    // Handle Delete Vehicle
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_vehicle'])) {
        $vehicle_id = (int)$_POST['delete_vehicle_id'];
        $stmt_get_name = $conn->prepare("SELECT vehicle_number FROM vehicles WHERE id = ?");
        $stmt_get_name->execute([$vehicle_id]);
        $vehicle_number = $stmt_get_name->fetchColumn();
        
        $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ? AND school_id = ?");
        if ($stmt->execute([$vehicle_id, $school_id])) {
            set_message('success', "Vehicle '{$vehicle_number}' deleted successfully!");
            // Log action
            log_interaction($role, $userId, "TRANSPORT: Deleted vehicle '{$vehicle_number}' (ID: {$vehicle_id}).", $userName);
        } else {
            set_message('danger', "Failed to delete vehicle.");
            log_interaction($role, $userId, "TRANSPORT ERROR: Failed to delete vehicle '{$vehicle_number}' (ID: {$vehicle_id}).", $userName);
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header("Location: manage_vehicles.php");
        exit();
    }

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    set_message('danger', $error_message);
    // Log database error
    log_interaction($role, $userId, "DATABASE ERROR on vehicle management page: " . $e->getMessage(), $userName);
    header("Location: manage_vehicles.php");
    exit();
}

if(isset($_SESSION['form_errors'])) {
    $errors = $_SESSION['form_errors'];
    unset($_SESSION['form_errors']);
}

// Fetch Data for Display
$vehicles_query = $conn->prepare("SELECT * FROM vehicles WHERE school_id = ? ORDER BY vehicle_number");
$vehicles_query->execute([$school_id]);
$vehicles = $vehicles_query->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Vehicles - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css" />

</head>
<body id="page-top">
    <div id="wrapper">
<?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?>        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
<?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?>                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage Vehicles</h1>
                    <?php display_message(); ?>
                    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $error): echo "<p class='mb-0'>".htmlspecialchars($error)."</p>"; endforeach; ?></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add New Vehicle</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="manage_vehicles.php">
                                <div class="form-row">
                                    <div class="form-group col-md-3"><input type="text" class="form-control" name="vehicle_number" placeholder="Vehicle Number" required></div>
                                    <div class="form-group col-md-3"><input type="text" class="form-control" name="model" placeholder="Model" required></div>
                                    <div class="form-group col-md-2"><input type="number" class="form-control" name="seating_capacity" placeholder="Seating Capacity" required></div>
                                    <div class="form-group col-md-2"><input type="date" class="form-control" name="insurance_expiry_date" placeholder="Insurance Expiry" required></div>
                                    <div class="form-group col-md-2"><button type="submit" name="add_vehicle" class="btn btn-primary btn-block">Add Vehicle</button></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Existing Vehicles</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Vehicle Number</th>
                                            <th>Model</th>
                                            <th>Seating Capacity</th>
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
                                            <td><?php echo date('d-M-Y', strtotime($vehicle['insurance_expiry_date'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editModal"
                                                        data-id="<?php echo $vehicle['id']; ?>"
                                                        data-number="<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>"
                                                        data-model="<?php echo htmlspecialchars($vehicle['model']); ?>"
                                                        data-capacity="<?php echo $vehicle['seating_capacity']; ?>"
                                                        data-expiry="<?php echo $vehicle['insurance_expiry_date']; ?>">
                                                    Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal"
                                                        data-id="<?php echo $vehicle['id']; ?>"
                                                        data-number="<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>">
                                                    Delete
                                                </button>
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
<?php
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?>        </div>
    </div>
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="manage_vehicles.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Vehicle</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="edit_vehicle_id" id="edit_vehicle_id">
                        <div class="form-group"><label>Vehicle Number</label><input type="text" class="form-control" name="edit_vehicle_number" id="edit_vehicle_number" required></div>
                        <div class="form-group"><label>Model</label><input type="text" class="form-control" name="edit_model" id="edit_model" required></div>
                        <div class="form-group"><label>Seating Capacity</label><input type="number" class="form-control" name="edit_seating_capacity" id="edit_seating_capacity" required></div>
                        <div class="form-group"><label>Insurance Expiry Date</label><input type="date" class="form-control" name="edit_insurance_expiry_date" id="edit_insurance_expiry_date" required></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="edit_vehicle" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="manage_vehicles.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Vehicle</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete vehicle <strong id="delete_vehicle_number"></strong>?</p>
                        <input type="hidden" name="delete_vehicle_id" id="delete_vehicle_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_vehicle" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"; ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>

    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });
        $('#editModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var number = button.data('number');
            var model = button.data('model');
            var capacity = button.data('capacity');
            var expiry = button.data('expiry');

            var modal = $(this);
            modal.find('#edit_vehicle_id').val(id);
            modal.find('#edit_vehicle_number').val(number);
            modal.find('#edit_model').val(model);
            modal.find('#edit_seating_capacity').val(capacity);
            modal.find('#edit_insurance_expiry_date').val(expiry);
        });
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var number = button.data('number');
            var modal = $(this);
            modal.find('#delete_vehicle_id').val(id);
            modal.find('#delete_vehicle_number').text(number);
        });
    </script>
</body>
</html>