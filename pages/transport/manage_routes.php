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

$errors = []; $success = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['save_route'])) {
            $route_name = trim($_POST['route_name']);
            $vehicle_id = (int)$_POST['vehicle_id'];
            $driver_id = (int)$_POST['driver_id'];
            if(empty($route_name) || empty($vehicle_id) || empty($driver_id)) {
                $errors[] = "Route Name, Vehicle, and Driver are required.";
            } else {
                $stmt = $conn->prepare("INSERT INTO routes (school_id, route_name, vehicle_id, driver_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$school_id, $route_name, $vehicle_id, $driver_id]);
                $success = "Route created successfully!";
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
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Database Error: " . $e->getMessage();
    }
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
                    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Create New Route</h6></div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="form-group"><label>Route Name *</label><input type="text" name="route_name" class="form-control" required></div>
                                        <div class="form-group"><label>Assign Vehicle *</label><select name="vehicle_id" class="form-control" required><option value="">-- Select --</option><?php foreach($vehicles as $v) echo "<option value='{$v['id']}'>{$v['vehicle_number']}</option>"; ?></select></div>
                                        <div class="form-group"><label>Assign Driver *</label><select name="driver_id" class="form-control" required><option value="">-- Select --</option><?php foreach($drivers as $d) echo "<option value='{$d['id']}'>{$d['driver_name']}</option>"; ?></select></div>
                                        <button type="submit" name="save_route" class="btn btn-primary">Save Route</button>
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
                                <h5 class="mt-4"><strong>Route:</strong> <?php echo htmlspecialchars($route['route_name']); ?> <small class="text-muted">(Vehicle: <?php echo htmlspecialchars($route['vehicle_number']); ?>, Driver: <?php echo htmlspecialchars($route['driver_name']); ?>)</small></h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead><tr><th>Stop Name</th><th>Fee</th><th>Actions</th></tr></thead>
                                        <tbody>
                                            <?php if(isset($stops_by_route[$route['route_name']])): foreach($stops_by_route[$route['route_name']] as $stop): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($stop['stop_name']); ?></td>
                                                <td>₹<?php echo number_format($stop['stop_fee'], 2); ?></td>
                                                <td><a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a></td>
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
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>