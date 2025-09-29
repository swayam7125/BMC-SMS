<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/log_system.php"; // Correctly include the log system

// Start session to store messages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

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
$success = '';

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
    // Handle Add Driver
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_driver'])) {
        $driver_name = trim($_POST['driver_name']);
        // CORRECTED: The column name in the database is phone_number
        $phone = trim($_POST['phone_number']);
        $license_number = trim($_POST['license_number']);

        if (empty($driver_name) || empty($phone) || empty($license_number)) {
            $errors[] = "All fields are required.";
        } else {
            // CORRECTED: Removed vehicle_id from the INSERT statement
            $stmt = $conn->prepare("INSERT INTO drivers (school_id, driver_name, phone_number, license_number) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$school_id, $driver_name, $phone, $license_number])) {
                set_message('success', "Driver '{$driver_name}' added successfully!");
                log_interaction($role, $userId, "TRANSPORT: Added new driver '{$driver_name}'.", $userName);
            } else {
                set_message('danger', "Failed to add driver.");
                log_interaction($role, $userId, "TRANSPORT ERROR: Failed to add new driver '{$driver_name}'.", $userName);
            }
        }
    }
    // Handle Edit Driver
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_driver'])) {
        $driver_id = (int)$_POST['edit_driver_id'];
        $driver_name = trim($_POST['edit_driver_name']);
         // CORRECTED: The column name in the database is phone_number
        $phone = trim($_POST['edit_phone_number']);
        $license_number = trim($_POST['edit_license_number']);

        if (empty($driver_name) || empty($phone) || empty($license_number)) {
            $errors[] = "All fields are required.";
        } else {
            // CORRECTED: Removed vehicle_id from the UPDATE statement
            $stmt = $conn->prepare("UPDATE drivers SET driver_name = ?, phone_number = ?, license_number = ? WHERE id = ? AND school_id = ?");
            if ($stmt->execute([$driver_name, $phone, $license_number, $driver_id, $school_id])) {
                set_message('success', "Driver '{$driver_name}' updated successfully!");
                log_interaction($role, $userId, "TRANSPORT: Updated driver details for '{$driver_name}' (ID: {$driver_id}).", $userName);
            } else {
                set_message('danger', "Failed to update driver.");
                log_interaction($role, $userId, "TRANSPORT ERROR: Failed to update driver '{$driver_name}' (ID: {$driver_id}).", $userName);
            }
        }
    }
    // Handle Delete Driver
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_driver'])) {
        $driver_id = (int)$_POST['delete_driver_id'];
        $stmt_get_name = $conn->prepare("SELECT driver_name FROM drivers WHERE id = ?");
        $stmt_get_name->execute([$driver_id]);
        $driver_name = $stmt_get_name->fetchColumn();

        $stmt = $conn->prepare("DELETE FROM drivers WHERE id = ? AND school_id = ?");
        if ($stmt->execute([$driver_id, $school_id])) {
            set_message('success', "Driver '{$driver_name}' deleted successfully!");
            log_interaction($role, $userId, "TRANSPORT: Deleted driver '{$driver_name}' (ID: {$driver_id}).", $userName);
        } else {
            set_message('danger', "Failed to delete driver.");
            log_interaction($role, $userId, "TRANSPORT ERROR: Failed to delete driver '{$driver_name}' (ID: {$driver_id}).", $userName);
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header("Location: manage_drivers.php");
        exit();
    }

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    set_message('danger', $error_message);
    log_interaction($role, $userId, "DATABASE ERROR on driver management page: " . $e->getMessage(), $userName);
    header("Location: manage_drivers.php");
    exit();
}

if(isset($_SESSION['form_errors'])) {
    $errors = $_SESSION['form_errors'];
    unset($_SESSION['form_errors']);
}

// Fetch Data for Display
// CORRECTED: Removed the LEFT JOIN with the vehicles table as there is no direct link.
$drivers_query = $conn->prepare("SELECT * FROM drivers WHERE school_id = ? ORDER BY driver_name");
$drivers_query->execute([$school_id]);
$drivers = $drivers_query->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Drivers - School Management System</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Manage Drivers</h1>
                    <?php display_message(); ?>
                    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $error): echo "<p class='mb-0'>".htmlspecialchars($error)."</p>"; endforeach; ?></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add New Driver</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="manage_drivers.php">
                                <div class="form-row">
                                    <div class="form-group col-md-4"><input type="text" class="form-control" name="driver_name" placeholder="Driver Name" required></div>
                                    <div class="form-group col-md-3"><input type="text" class="form-control" name="phone_number" placeholder="Phone Number" required></div>
                                    <div class="form-group col-md-3"><input type="text" class="form-control" name="license_number" placeholder="License Number" required></div>
                                    <div class="form-group col-md-2"><button type="submit" name="add_driver" class="btn btn-primary btn-block">Add Driver</button></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Existing Drivers</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>License Number</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($drivers as $driver): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($driver['driver_name']); ?></td>
                                            <td><?php echo htmlspecialchars($driver['phone_number']); ?></td>
                                            <td><?php echo htmlspecialchars($driver['license_number']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editModal" data-id="<?php echo $driver['id']; ?>" data-name="<?php echo htmlspecialchars($driver['driver_name']); ?>" data-phone="<?php echo htmlspecialchars($driver['phone_number']); ?>" data-license="<?php echo htmlspecialchars($driver['license_number']); ?>">Edit</button>
                                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo $driver['id']; ?>" data-name="<?php echo htmlspecialchars($driver['driver_name']); ?>">Delete</button>
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
                <form method="POST" action="manage_drivers.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Driver</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="edit_driver_id" id="edit_driver_id">
                        <div class="form-group"><label>Name</label><input type="text" class="form-control" name="edit_driver_name" id="edit_driver_name" required></div>
                        <div class="form-group"><label>Phone</label><input type="text" class="form-control" name="edit_phone_number" id="edit_phone_number" required></div>
                        <div class="form-group"><label>License Number</label><input type="text" class="form-control" name="edit_license_number" id="edit_license_number" required></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="edit_driver" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="manage_drivers.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Driver</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the driver <strong id="delete_driver_name"></strong>?</p>
                        <input type="hidden" name="delete_driver_id" id="delete_driver_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_driver" class="btn btn-danger">Delete</button>
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
            var name = button.data('name');
            var phone = button.data('phone');
            var license = button.data('license');
            var modal = $(this);
            modal.find('#edit_driver_id').val(id);
            modal.find('#edit_driver_name').val(name);
            // CORRECTED: The ID of the phone input is edit_phone_number
            modal.find('#edit_phone_number').val(phone);
            modal.find('#edit_license_number').val(license);
        });
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var modal = $(this);
            modal.find('#delete_driver_id').val(id);
            modal.find('#delete_driver_name').text(name);
        });
    </script>
</body>
</html>