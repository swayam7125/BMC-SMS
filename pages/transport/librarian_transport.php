<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

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

// Check for success/error messages from URL parameters
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
if (isset($_GET['errors'])) {
    $errors = json_decode(urldecode($_GET['errors']), true);
}

// Handle updating librarian transport
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_transport'])) {
    $librarian_transport_data = $_POST['librarian_transport'] ?? [];
    $current_section = $_POST['current_section'] ?? 'school';

    // A flag to determine if a mode change occurred that requires a tab switch
    $redirect_section = $current_section;

    try {
        $conn->beginTransaction();

        $stmt_update = $conn->prepare("UPDATE librarian SET transport_mode = ?, stop_id = ?, self_transport_mode = ?, vehicle_number = ?, license_number = ? WHERE id = ? AND school_id = ?");
        $stmt_fetch_original = $conn->prepare("SELECT transport_mode FROM librarian WHERE id = ?");

        foreach ($librarian_transport_data as $librarian_id => $data) {
            $transport_mode = $data['transport_mode'] ?? null;
            $stop_id = null;
            $self_transport_mode = null;
            $vehicle_number = null;
            $license_number = null;
            
            // Check if the transport mode was changed
            $stmt_fetch_original->execute([(int)$librarian_id]);
            $original_mode = $stmt_fetch_original->fetchColumn();

            if ($original_mode !== $transport_mode) {
                // If the mode changed, set the redirect section to the new mode
                if ($transport_mode === 'Self Transport') {
                    $redirect_section = 'self';
                } else if ($transport_mode === 'School Transport') {
                    $redirect_section = 'school';
                }
            }

            if ($transport_mode === 'School Transport') {
                $stop_id = !empty($data['stop_id']) ? (int)$data['stop_id'] : null;
            } else if ($transport_mode === 'Self Transport' || $transport_mode === 'Self') {
                $self_transport_mode = !empty($data['self_transport_mode']) ? htmlspecialchars($data['self_transport_mode']) : null;
                // Only set vehicle number and license number if the mode requires it
                if ($self_transport_mode === 'Bike' || $self_transport_mode === 'Car') {
                    $vehicle_number = !empty($data['vehicle_number']) ? htmlspecialchars($data['vehicle_number']) : null;
                    $license_number = !empty($data['license_number']) ? htmlspecialchars($data['license_number']) : null;
                }
            }

            $stmt_update->execute([$transport_mode, $stop_id, $self_transport_mode, $vehicle_number, $license_number, (int)$librarian_id, $school_id]);
        }
        $conn->commit();
        $success = "Librarian transport information updated successfully!";
    } catch (PDOException $e) {
        $conn->rollBack();
        $errors[] = "Database update failed: " . $e->getMessage();
    }

    // Redirect to the determined section after form submission, passing success/error messages
    $redirect_url = "librarian_transport.php?section=" . urlencode($redirect_section);
    if (!empty($success)) {
        $redirect_url .= "&success=" . urlencode($success);
    }
    if (!empty($errors)) {
        $redirect_url .= "&errors=" . urlencode(json_encode($errors));
    }

    header("Location: " . $redirect_url);
    exit();
}

// Fetch Data for Display
$stops_query = $conn->prepare("SELECT s.id, s.stop_name, r.route_name FROM stops s JOIN routes r ON s.route_id = r.id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name");
$stops_query->execute([$school_id]);
$all_stops = $stops_query->fetchAll(PDO::FETCH_ASSOC);

$librarians_query = $conn->prepare("SELECT id, librarian_name, phone, transport_mode, self_transport_mode, vehicle_number, license_number, stop_id FROM librarian WHERE school_id = ? ORDER BY librarian_name");
$librarians_query->execute([$school_id]);
$librarians = $librarians_query->fetchAll(PDO::FETCH_ASSOC);

// Get available self-transport modes from the enum type
$self_transport_modes_query = $conn->query("SELECT unnest(enum_range(NULL::public.self_transport_mode))");
$self_transport_modes = $self_transport_modes_query->fetchAll(PDO::FETCH_COLUMN);

// Group librarians by transport mode
$school_transport_librarians = array_filter($librarians, function($l) {
    return $l['transport_mode'] === 'School Transport';
});
$self_transport_librarians = array_filter($librarians, function($l) {
    return $l['transport_mode'] === 'Self Transport' || $l['transport_mode'] === 'Self';
});
// Determine which section to show on page load
$active_section = isset($_GET['section']) ? $_GET['section'] : 'school';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Librarian Transport - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .transport-details {
            display: none;
        }
    </style>
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
                    <h1 class="h3 mb-4 text-gray-800">Manage Librarian Transport</h1>
                    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $error): echo "<p class='mb-0'>".htmlspecialchars($error)."</p>"; endforeach; ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Assign Transport Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <button type="button" id="school-transport-btn" class="btn btn-primary" onclick="showSection('school')">School Transport</button>
                                <button type="button" id="self-transport-btn" class="btn btn-secondary" onclick="showSection('self')">Self Transport</button>
                            </div>

                            <form method="POST">
                                <input type="hidden" id="current-section" name="current_section" value="<?php echo htmlspecialchars(isset($_GET['section']) ? $_GET['section'] : 'school'); ?>">
                                <input type="hidden" name="update_transport" value="1">

                                <div id="school-section" style="display: none;">
                                    <h5 class="my-3">School Transport Librarians</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Librarian Name</th>
                                                    <th>Phone</th>
                                                    <th>Change Mode</th>
                                                    <th>Assign Stop</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($school_transport_librarians)): ?>
                                                    <?php foreach ($school_transport_librarians as $librarian): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($librarian['librarian_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($librarian['phone']); ?></td>
                                                            <td>
                                                                <select class="form-control form-control-sm transport-mode-select" name="librarian_transport[<?php echo $librarian['id']; ?>][transport_mode]" onchange="toggleInputs(this)">
                                                                    <option value="School Transport" selected>School Transport</option>
                                                                    <option value="Self Transport">Self Transport</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <div class="school-details">
                                                                    <select class="form-control form-control-sm" name="librarian_transport[<?php echo $librarian['id']; ?>][stop_id]">
                                                                        <option value="">-- No Stop --</option>
                                                                        <?php
                                                                            $current_route = '';
                                                                            foreach($all_stops as $stop) {
                                                                                if ($stop['route_name'] !== $current_route) {
                                                                                    if($current_route !== '') echo '</optgroup>';
                                                                                    $current_route = $stop['route_name'];
                                                                                    echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                                                }
                                                                                $selected = ($librarian['stop_id'] == $stop['id']) ? 'selected' : '';
                                                                                echo "<option value='{$stop['id']}' {$selected}>" . htmlspecialchars($stop['stop_name']) . "</option>";
                                                                            }
                                                                            if($current_route !== '') echo '</optgroup>';
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="self-details" style="display:none;">
                                                                    <select class="form-control form-control-sm mb-2" name="librarian_transport[<?php echo $librarian['id']; ?>][self_transport_mode]" onchange="toggleSelfDetails(this)">
                                                                        <option value="">-- Select Mode --</option>
                                                                        <?php foreach ($self_transport_modes as $mode): ?>
                                                                            <option value="<?php echo htmlspecialchars($mode); ?>">
                                                                                <?php echo htmlspecialchars($mode); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <div class="self-details-fields" style="display:none;">
                                                                        <input type="text" class="form-control form-control-sm mb-2" name="librarian_transport[<?php echo $librarian['id']; ?>][vehicle_number]" placeholder="Vehicle Number">
                                                                        <input type="text" class="form-control form-control-sm" name="librarian_transport[<?php echo $librarian['id']; ?>][license_number]" placeholder="License Number">
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center">No librarians found with School Transport.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div id="self-section" style="display: none;">
                                    <h5 class="my-3">Self Transport Librarians</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Librarian Name</th>
                                                    <th>Phone</th>
                                                    <th>Change Mode</th>
                                                    <th>Transport Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($self_transport_librarians)): ?>
                                                    <?php foreach ($self_transport_librarians as $librarian): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($librarian['librarian_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($librarian['phone']); ?></td>
                                                            <td>
                                                                <select class="form-control form-control-sm" name="librarian_transport[<?php echo $librarian['id']; ?>][transport_mode]" onchange="toggleInputs(this)">
                                                                    <option value="Self Transport" selected>Self Transport</option>
                                                                    <option value="School Transport">School Transport</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <div class="school-details" style="display:none;">
                                                                    <select class="form-control form-control-sm" name="librarian_transport[<?php echo $librarian['id']; ?>][stop_id]">
                                                                        <option value="">-- No Stop --</option>
                                                                        <?php
                                                                            $current_route = '';
                                                                            foreach($all_stops as $stop) {
                                                                                if ($stop['route_name'] !== $current_route) {
                                                                                    if($current_route !== '') echo '</optgroup>';
                                                                                    $current_route = $stop['route_name'];
                                                                                    echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                                                }
                                                                                $selected = ($librarian['stop_id'] == $stop['id']) ? 'selected' : '';
                                                                                echo "<option value='{$stop['id']}' {$selected}>" . htmlspecialchars($stop['stop_name']) . "</option>";
                                                                            }
                                                                            if($current_route !== '') echo '</optgroup>';
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="self-details">
                                                                    <select class="form-control form-control-sm mb-2" name="librarian_transport[<?php echo $librarian['id']; ?>][self_transport_mode]" onchange="toggleSelfDetails(this)">
                                                                        <option value="">-- Select Mode --</option>
                                                                        <?php foreach ($self_transport_modes as $mode): ?>
                                                                            <option value="<?php echo htmlspecialchars($mode); ?>" <?php echo ($librarian['self_transport_mode'] === $mode) ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($mode); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <div class="self-details-fields" style="<?php echo ($librarian['self_transport_mode'] === 'Bike' || $librarian['self_transport_mode'] === 'Car') ? 'display:block;' : 'display:none;'; ?>">
                                                                        <input type="text" class="form-control form-control-sm mb-2" name="librarian_transport[<?php echo $librarian['id']; ?>][vehicle_number]" placeholder="Vehicle Number" value="<?php echo htmlspecialchars($librarian['vehicle_number'] ?? ''); ?>">
                                                                        <input type="text" class="form-control form-control-sm" name="librarian_transport[<?php echo $librarian['id']; ?>][license_number]" placeholder="License Number" value="<?php echo htmlspecialchars($librarian['license_number'] ?? ''); ?>">
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center">No librarians found with Self Transport.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <button type="submit" name="update_transport" class="btn btn-primary mt-3">Save Changes</button>
                            </form>
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
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php"; ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        function showSection(section) {
            document.getElementById('school-section').style.display = 'none';
            document.getElementById('self-section').style.display = 'none';

            if (section === 'school') {
                document.getElementById('school-section').style.display = 'block';
                document.getElementById('school-transport-btn').className = 'btn btn-primary';
                document.getElementById('self-transport-btn').className = 'btn btn-secondary';
                document.getElementById('current-section').value = 'school';
            } else if (section === 'self') {
                document.getElementById('self-section').style.display = 'block';
                document.getElementById('self-transport-btn').className = 'btn btn-primary';
                document.getElementById('school-transport-btn').className = 'btn btn-secondary';
                document.getElementById('current-section').value = 'self';
            }
        }

        function toggleInputs(selectElement) {
            const row = selectElement.closest('tr');
            const schoolDetails = row.querySelector('.school-details');
            const selfDetails = row.querySelector('.self-details');

            if (selectElement.value === 'School Transport') {
                if (schoolDetails) {
                    schoolDetails.style.display = 'block';
                }
                if (selfDetails) {
                    selfDetails.style.display = 'none';
                    selfDetails.querySelectorAll('input, select').forEach(field => {
                        field.value = '';
                    });
                }
            } else if (selectElement.value === 'Self Transport') {
                if (schoolDetails) {
                    schoolDetails.style.display = 'none';
                    const stopSelect = schoolDetails.querySelector('[name*="stop_id"]');
                    if (stopSelect) stopSelect.value = '';
                }
                if (selfDetails) {
                    selfDetails.style.display = 'block';
                    const selfModeSelect = selfDetails.querySelector('[name*="self_transport_mode"]');
                    if(selfModeSelect) {
                         toggleSelfDetails(selfModeSelect);
                    }
                }
            }
        }

        function toggleSelfDetails(selectElement) {
            const row = selectElement.closest('tr');
            const selfDetailsFields = row.querySelector('.self-details-fields');
            const selectedMode = selectElement.value;

            if (selectedMode === 'Bike' || selectedMode === 'Car') {
                if (selfDetailsFields) selfDetailsFields.style.display = 'block';
            } else {
                if (selfDetailsFields) {
                    selfDetailsFields.style.display = 'none';
                    const vehicleNumberInput = selfDetailsFields.querySelector('[name*="vehicle_number"]');
                    const licenseNumberInput = selfDetailsFields.querySelector('[name*="license_number"]');
                    if (vehicleNumberInput) vehicleNumberInput.value = '';
                    if (licenseNumberInput) licenseNumberInput.value = '';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const initialSection = '<?php echo htmlspecialchars(isset($_GET['section']) ? $_GET['section'] : 'school'); ?>';
            showSection(initialSection);

            document.querySelectorAll('tbody tr').forEach(row => {
                const transportModeSelect = row.querySelector('.transport-mode-select');
                if (transportModeSelect) {
                    toggleInputs(transportModeSelect);
                }
            });

            document.querySelectorAll('.self-details select').forEach(select => {
                toggleSelfDetails(select);
            });
        });
    </script>
</body>
</html>