<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'principal') { header("Location: ../../login.php"); exit; }

$school_id = null;
if($userId) {
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $school_id = $stmt->fetchColumn();
}
if (!$school_id) die("Error: Could not determine your school.");

$errors = []; 
$success = '';
$edit_route = null;

// Handle Delete Route Request
if (isset($_GET['delete_route_id'])) {
    $route_id_to_delete = (int)$_GET['delete_route_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM routes WHERE id = ? AND school_id = ?");
        $stmt->execute([$route_id_to_delete, $school_id]);
        header("Location: manage_routes.php?success=" . urlencode("Route deleted successfully!"));
        exit;
    } catch (PDOException $e) {
        $errors[] = "Error deleting route: " . $e->getMessage();
    }
}

// Handle Delete Stop Request
if (isset($_GET['delete_stop_id'])) {
    $stop_id_to_delete = (int)$_GET['delete_stop_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM stops WHERE id = ? AND route_id IN (SELECT id FROM routes WHERE school_id = ?)");
        $stmt->execute([$stop_id_to_delete, $school_id]);
        header("Location: manage_routes.php?success=" . urlencode("Stop deleted successfully!"));
        exit;
    } catch (PDOException $e) {
        $errors[] = "Error deleting stop: " . $e->getMessage();
    }
}

// Handle Edit Route Request (Fetch data for the form)
if (isset($_GET['edit_route_id'])) {
    $route_id_to_edit = (int)$_GET['edit_route_id'];
    $stmt = $conn->prepare("SELECT * FROM routes WHERE id = ? AND school_id = ?");
    $stmt->execute([$route_id_to_edit, $school_id]);
    $edit_route = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle Form Submissions (Add, Update, Add Stop)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['save_route'])) {
            $route_id = !empty($_POST['route_id']) ? (int)$_POST['route_id'] : null;
            $route_name = trim($_POST['route_name']);
            $vehicle_id = (int)$_POST['vehicle_id'];
            $driver_id = (int)$_POST['driver_id'];

            if(empty($route_name) || empty($vehicle_id) || empty($driver_id)) {
                $errors[] = "Route Name, Vehicle, and Driver are required.";
            } else {
                if ($route_id) { // This is an UPDATE
                    $stmt = $conn->prepare("UPDATE routes SET route_name = ?, vehicle_id = ?, driver_id = ? WHERE id = ? AND school_id = ?");
                    $stmt->execute([$route_name, $vehicle_id, $driver_id, $route_id, $school_id]);
                    $success = "Route updated successfully!";
                } else { // This is an INSERT
                    $stmt = $conn->prepare("INSERT INTO routes (school_id, route_name, vehicle_id, driver_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$school_id, $route_name, $vehicle_id, $driver_id]);
                    $success = "Route created successfully!";
                }
                header("Location: manage_routes.php?success=" . urlencode($success));
                exit;
            }
        } elseif (isset($_POST['add_stop'])) {
            $route_id = (int)$_POST['route_id'];
            $stop_name = trim($_POST['stop_name']);
            $stop_fee = (float)$_POST['stop_fee'];
            if(empty($stop_name)) {
                $errors[] = "Stop Name is required.";
            } else {
                $stmt = $conn->prepare("INSERT INTO stops (route_id, stop_name, stop_fee) VALUES (?, ?, ?)");
                $stmt->execute([$route_id, $stop_name, $stop_fee]);
                $success = "Stop added successfully!";
                header("Location: manage_routes.php?success=" . urlencode($success));
                exit;
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Database Error: " . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}

// Fetch Data for Display
$vehicles = $conn->prepare("SELECT id, vehicle_number FROM vehicles WHERE school_id = ?");
$vehicles->execute([$school_id]);
$vehicles = $vehicles->fetchAll(PDO::FETCH_ASSOC);

$drivers = $conn->prepare("SELECT id, driver_name FROM drivers WHERE school_id = ?");
$drivers->execute([$school_id]);
$drivers = $drivers->fetchAll(PDO::FETCH_ASSOC);

$routes = $conn->prepare("SELECT r.*, v.vehicle_number, d.driver_name FROM routes r JOIN vehicles v ON r.vehicle_id = v.id JOIN drivers d ON r.driver_id = d.id WHERE r.school_id = ? ORDER BY r.route_name");
$routes->execute([$school_id]);
$routes = $routes->fetchAll(PDO::FETCH_ASSOC);

$stops = $conn->prepare("SELECT s.*, r.route_name FROM stops s JOIN routes r ON s.route_id = r.id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name");
$stops->execute([$school_id]);
$stops_by_route = [];
while ($stop = $stops->fetch(PDO::FETCH_ASSOC)) {
    $stops_by_route[$stop['route_name']][] = $stop;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Routes & Stops - School Management System</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Manage Routes & Stops</h1>
                    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $error): echo "<p class='mb-0'>".htmlspecialchars($error)."</p>"; endforeach; ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?php echo $edit_route ? 'Edit Route' : 'Create New Route'; ?></h6></div>
                                <div class="card-body">
                                    <form method="POST" action="manage_routes.php">
                                        <?php if ($edit_route): ?>
                                            <input type="hidden" name="route_id" value="<?php echo $edit_route['id']; ?>">
                                        <?php endif; ?>
                                        <div class="form-group"><label>Route Name *</label><input type="text" name="route_name" class="form-control" value="<?php echo htmlspecialchars($edit_route['route_name'] ?? ''); ?>" required></div>
                                        <div class="form-group"><label>Assign Vehicle *</label><select name="vehicle_id" class="form-control" required><option value="">-- Select --</option><?php foreach($vehicles as $v) { $selected = (isset($edit_route) && $edit_route['vehicle_id'] == $v['id']) ? 'selected' : ''; echo "<option value='{$v['id']}' {$selected}>{$v['vehicle_number']}</option>"; } ?></select></div>
                                        <div class="form-group"><label>Assign Driver *</label><select name="driver_id" class="form-control" required><option value="">-- Select --</option><?php foreach($drivers as $d) { $selected = (isset($edit_route) && $edit_route['driver_id'] == $d['id']) ? 'selected' : ''; echo "<option value='{$d['id']}' {$selected}>{$d['driver_name']}</option>"; } ?></select></div>
                                        <button type="submit" name="save_route" class="btn btn-primary"><?php echo $edit_route ? 'Update Route' : 'Save Route'; ?></button>
                                        <?php if ($edit_route): ?>
                                            <a href="manage_routes.php" class="btn btn-secondary">Cancel Edit</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Add Stop to Route</h6></div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="form-group"><label>Select Route *</label><select name="route_id" class="form-control" required><option value="">-- Select --</option><?php foreach($routes as $r) echo "<option value='{$r['id']}'>{$r['route_name']}</option>"; ?></select></div>
                                        <div class="form-group"><label>Stop Name *</label><input type="text" name="stop_name" class="form-control" required></div>
                                        <div class="form-group"><label>Stop Fee (Monthly)</label><input type="number" step="0.01" name="stop_fee" class="form-control" value="0.00"></div>
                                        <button type="submit" name="add_stop" class="btn btn-info">Add Stop</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Existing Routes & Stops</h6></div>
                        <div class="card-body">
                            <?php foreach($routes as $route): ?>
                                <h5 class="mt-4 d-flex justify-content-between align-items-center">
                                    <span>
                                        <strong>Route:</strong> <?php echo htmlspecialchars($route['route_name']); ?> 
                                        <small class="text-muted">(Vehicle: <?php echo htmlspecialchars($route['vehicle_number']); ?>, Driver: <?php echo htmlspecialchars($route['driver_name']); ?>)</small>
                                    </span>
                                    <span>
                                        <a href="?edit_route_id=<?php echo $route['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="#" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal" data-url="?delete_route_id=<?php echo $route['id']; ?>"><i class="fas fa-trash"></i> Delete</a>
                                    </span>
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead><tr><th>Stop Name</th><th>Fee</th><th class="text-right">Actions</th></tr></thead>
                                        <tbody>
                                            <?php if(isset($stops_by_route[$route['route_name']])): foreach($stops_by_route[$route['route_name']] as $stop): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($stop['stop_name']); ?></td>
                                                <td>₹<?php echo number_format($stop['stop_fee'], 2); ?></td>
                                                <td class="text-right">
                                                    <a href="#" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteModal" data-url="?delete_stop_id=<?php echo $stop['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; else: ?>
                                            <tr><td colspan="3" class="text-center">No stops added yet.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    
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
                    Are you sure you want to delete this? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#deleteModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var url = button.data('url');
            var modal = $(this);
            modal.find('#confirmDeleteBtn').attr('href', url);
        });
    });
    </script>
</body>
</html>