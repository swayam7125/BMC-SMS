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
    // Handle Add Route
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_route'])) {
        $route_name = trim($_POST['route_name']);
        $vehicle_id = !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
        $driver_id = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;

        if (empty($route_name) || empty($vehicle_id) || empty($driver_id)) {
            $errors[] = "All fields are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO routes (school_id, route_name, vehicle_id, driver_id) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$school_id, $route_name, $vehicle_id, $driver_id])) {
                set_message('success', "Route '{$route_name}' added successfully!");
                // Log action
                log_interaction($role, $userId, "TRANSPORT: Added new route '{$route_name}'.", $userName);
            } else {
                set_message('danger', "Failed to add route.");
                log_interaction($role, $userId, "TRANSPORT ERROR: Failed to add route '{$route_name}'.", $userName);
            }
        }
    }
    // Handle Edit Route
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_route'])) {
        $route_id = (int)$_POST['edit_route_id'];
        $route_name = trim($_POST['edit_route_name']);
        $vehicle_id = !empty($_POST['edit_vehicle_id']) ? (int)$_POST['edit_vehicle_id'] : null;
        $driver_id = !empty($_POST['edit_driver_id']) ? (int)$_POST['edit_driver_id'] : null;

        if (empty($route_name) || empty($vehicle_id) || empty($driver_id)) {
            $errors[] = "All fields are required.";
        } else {
            $stmt = $conn->prepare("UPDATE routes SET route_name = ?, vehicle_id = ?, driver_id = ? WHERE id = ? AND school_id = ?");
            if ($stmt->execute([$route_name, $vehicle_id, $driver_id, $route_id, $school_id])) {
                set_message('success', "Route '{$route_name}' updated successfully!");
                // Log action
                log_interaction($role, $userId, "TRANSPORT: Updated route '{$route_name}' (ID: {$route_id}).", $userName);
            } else {
                set_message('danger', "Failed to update route.");
                log_interaction($role, $userId, "TRANSPORT ERROR: Failed to update route '{$route_name}' (ID: {$route_id}).", $userName);
            }
        }
    }
    // Handle Delete Route
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_route'])) {
        $route_id = (int)$_POST['delete_route_id'];
        $stmt_get_name = $conn->prepare("SELECT route_name FROM routes WHERE id = ?");
        $stmt_get_name->execute([$route_id]);
        $route_name = $stmt_get_name->fetchColumn();
        
        $stmt = $conn->prepare("DELETE FROM routes WHERE id = ? AND school_id = ?");
        if ($stmt->execute([$route_id, $school_id])) {
            set_message('success', "Route '{$route_name}' deleted successfully!");
            // Log action
            log_interaction($role, $userId, "TRANSPORT: Deleted route '{$route_name}' (ID: {$route_id}).", $userName);
        } else {
            set_message('danger', "Failed to delete route.");
            log_interaction($role, $userId, "TRANSPORT ERROR: Failed to delete route '{$route_name}' (ID: {$route_id}).", $userName);
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header("Location: manage_routes.php");
        exit();
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    set_message('danger', $error_message);
    // Log database error
    log_interaction($role, $userId, "DATABASE ERROR on route management page: " . $e->getMessage(), $userName);
    header("Location: manage_routes.php");
    exit();
}

if(isset($_SESSION['form_errors'])) {
    $errors = $_SESSION['form_errors'];
    unset($_SESSION['form_errors']);
}

// Fetch Data for Display
$routes_query = $conn->prepare("
    SELECT r.id, r.route_name, r.vehicle_id, r.driver_id, v.vehicle_number, v.model, d.driver_name
    FROM routes r
    LEFT JOIN vehicles v ON r.vehicle_id = v.id
    LEFT JOIN drivers d ON r.driver_id = d.id
    WHERE r.school_id = ?
    ORDER BY r.route_name
");
$routes_query->execute([$school_id]);
$routes = $routes_query->fetchAll(PDO::FETCH_ASSOC);

$vehicles_query = $conn->prepare("SELECT id, vehicle_number, model FROM vehicles WHERE school_id = ? ORDER BY vehicle_number");
$vehicles_query->execute([$school_id]);
$vehicles = $vehicles_query->fetchAll(PDO::FETCH_ASSOC);

$drivers_query = $conn->prepare("SELECT id, driver_name FROM drivers WHERE school_id = ? ORDER BY driver_name");
$drivers_query->execute([$school_id]);
$drivers = $drivers_query->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Routes - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
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
                    <h1 class="h3 mb-4 text-gray-800">Manage Routes</h1>
                    <?php display_message(); ?>
                    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $error): echo "<p class='mb-0'>".htmlspecialchars($error)."</p>"; endforeach; ?></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add New Route</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="manage_routes.php">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <input type="text" class="form-control" name="route_name" placeholder="Route Name" required>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <select name="vehicle_id" class="form-control" required>
                                            <option value="">-- Select Vehicle --</option>
                                            <?php foreach ($vehicles as $vehicle): ?>
                                                <option value="<?php echo $vehicle['id']; ?>"><?php echo htmlspecialchars($vehicle['vehicle_number'] . " - " . $vehicle['model']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <select name="driver_id" class="form-control" required>
                                            <option value="">-- Select Driver --</option>
                                            <?php foreach ($drivers as $driver): ?>
                                                <option value="<?php echo $driver['id']; ?>"><?php echo htmlspecialchars($driver['driver_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <button type="submit" name="add_route" class="btn btn-primary btn-block">Add Route</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Existing Routes</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Route Name</th>
                                            <th>Assigned Vehicle</th>
                                            <th>Assigned Driver</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($routes as $route): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                                            <td><?php echo $route['vehicle_number'] ? htmlspecialchars($route['vehicle_number'] . " (" . $route['model'] . ")") : '<em>N/A</em>'; ?></td>
                                            <td><?php echo $route['driver_name'] ? htmlspecialchars($route['driver_name']) : '<em>N/A</em>'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editModal"
                                                        data-id="<?php echo $route['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($route['route_name']); ?>"
                                                        data-vehicle-id="<?php echo $route['vehicle_id']; ?>"
                                                        data-driver-id="<?php echo $route['driver_id']; ?>">
                                                    Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal"
                                                        data-id="<?php echo $route['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($route['route_name']); ?>">
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
                <form method="POST" action="manage_routes.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Route</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="edit_route_id" id="edit_route_id">
                        <div class="form-group">
                            <label>Route Name</label>
                            <input type="text" class="form-control" name="edit_route_name" id="edit_route_name" required>
                        </div>
                        <div class="form-group">
                            <label>Assign Vehicle</label>
                            <select name="edit_vehicle_id" id="edit_vehicle_id" class="form-control" required>
                                <option value="">-- Select Vehicle --</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?php echo $vehicle['id']; ?>"><?php echo htmlspecialchars($vehicle['vehicle_number'] . " - " . $vehicle['model']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Assign Driver</label>
                            <select name="edit_driver_id" id="edit_driver_id" class="form-control" required>
                                <option value="">-- Select Driver --</option>
                                <?php foreach ($drivers as $driver): ?>
                                    <option value="<?php echo $driver['id']; ?>"><?php echo htmlspecialchars($driver['driver_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="edit_route" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="manage_routes.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Route</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the route <strong id="delete_route_name"></strong>?</p>
                        <input type="hidden" name="delete_route_id" id="delete_route_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_route" class="btn btn-danger">Delete</button>
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
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });

        $('#editModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var vehicleId = button.data('vehicle-id');
            var driverId = button.data('driver-id');

            var modal = $(this);
            modal.find('#edit_route_id').val(id);
            modal.find('#edit_route_name').val(name);
            modal.find('#edit_vehicle_id').val(vehicleId);
            modal.find('#edit_driver_id').val(driverId);
        });

        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var modal = $(this);
            modal.find('#delete_route_id').val(id);
            modal.find('#delete_route_name').text(name);
        });
    </script>
</body>
</html>