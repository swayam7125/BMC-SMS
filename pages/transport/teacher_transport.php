<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/log_system.php"; // Log system included

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

// Handle updating teacher transport
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_transport'])) {
    $teacher_transport_data = $_POST['teacher_transport'] ?? [];
    $current_section = $_POST['current_section'] ?? 'school';
    $redirect_section = $current_section;

    $submitted_teacher_ids = array_keys($teacher_transport_data);
    $teachers_to_update = [];

    if (!empty($submitted_teacher_ids)) {
        // Fetch the original data for only the teachers submitted in the form
        $in_placeholders = implode(',', array_fill(0, count($submitted_teacher_ids), '?'));
        $stmt_original = $conn->prepare("SELECT id, transport_mode, stop_id, self_transport_mode, vehicle_number, license_number FROM teacher WHERE id IN ($in_placeholders) AND school_id = ?");
        $stmt_original->execute([...$submitted_teacher_ids, $school_id]);
        $original_data = $stmt_original->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        // Compare submitted data with original data to find actual changes
        foreach ($teacher_transport_data as $teacher_id => $submitted_data) {
            $original = $original_data[$teacher_id] ?? null;
            if (!$original) continue;

            // Normalize new data from the form submission
            $new_transport_mode = $submitted_data['transport_mode'] ?? null;
            $new_stop_id = null;
            $new_self_mode = null;
            $new_vehicle_no = null;
            $new_license_no = null;
            
            if ($original['transport_mode'] !== $new_transport_mode) {
                if ($new_transport_mode === 'Self Transport' || $new_transport_mode === 'Self') {
                    $redirect_section = 'self';
                } else if ($new_transport_mode === 'School Transport') {
                    $redirect_section = 'school';
                }
            }
            
            if ($new_transport_mode === 'School Transport') {
                $new_stop_id = !empty($submitted_data['stop_id']) ? (int)$submitted_data['stop_id'] : null;
            } else if ($new_transport_mode === 'Self Transport' || $new_transport_mode === 'Self') {
                $new_self_mode = !empty($submitted_data['self_transport_mode']) ? htmlspecialchars($submitted_data['self_transport_mode']) : null;
                if ($new_self_mode === 'Bike' || $new_self_mode === 'Car') {
                    $new_vehicle_no = !empty($submitted_data['vehicle_number']) ? htmlspecialchars($submitted_data['vehicle_number']) : null;
                    $new_license_no = !empty($submitted_data['license_number']) ? htmlspecialchars($submitted_data['license_number']) : null;
                }
            }

            // Check if any value has changed
            if ($original['transport_mode'] != $new_transport_mode || (int)$original['stop_id'] != $new_stop_id || $original['self_transport_mode'] != $new_self_mode || $original['vehicle_number'] != $new_vehicle_no || $original['license_number'] != $new_license_no) {
                $teachers_to_update[$teacher_id] = [
                    'transport_mode' => $new_transport_mode,
                    'stop_id' => $new_stop_id,
                    'self_transport_mode' => $new_self_mode,
                    'vehicle_number' => $new_vehicle_no,
                    'license_number' => $new_license_no,
                ];
            }
        }
    }

    $actually_updated_count = count($teachers_to_update);

    if ($actually_updated_count > 0) {
        try {
            $conn->beginTransaction();
            $stmt_update = $conn->prepare("UPDATE teacher SET transport_mode = ?, stop_id = ?, self_transport_mode = ?, vehicle_number = ?, license_number = ? WHERE id = ? AND school_id = ?");
            
            foreach ($teachers_to_update as $teacher_id => $update_data) {
                $stmt_update->execute([$update_data['transport_mode'], $update_data['stop_id'], $update_data['self_transport_mode'], $update_data['vehicle_number'], $update_data['license_number'], (int)$teacher_id, $school_id]);
            }
            
            $conn->commit();
            $plural = $actually_updated_count > 1 ? 's' : '';
            $success = "{$actually_updated_count} teacher{$plural}' transport information updated successfully!";
            log_interaction($role, $userId, "TRANSPORT: Updated transport details for {$actually_updated_count} teacher(s).", $userName);

        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Database update failed: " . $e->getMessage();
            log_interaction($role, $userId, "TRANSPORT ERROR: Failed to update teacher transport. " . $e->getMessage(), $userName);
        }
    } else {
        $success = "No changes were detected.";
    }

    $redirect_url = "teacher_transport.php?section=" . urlencode($redirect_section);
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

$teachers_query = $conn->prepare("SELECT id, teacher_name, phone, transport_mode, self_transport_mode, vehicle_number, license_number, stop_id FROM teacher WHERE school_id = ? ORDER BY teacher_name");
$teachers_query->execute([$school_id]);
$teachers = $teachers_query->fetchAll(PDO::FETCH_ASSOC);

$self_transport_modes_query = $conn->query("SELECT unnest(enum_range(NULL::public.self_transport_mode))");
$self_transport_modes = $self_transport_modes_query->fetchAll(PDO::FETCH_COLUMN);

$school_transport_teachers = array_filter($teachers, function($t) {
    return $t['transport_mode'] === 'School Transport';
});
$self_transport_teachers = array_filter($teachers, function($t) {
    return $t['transport_mode'] === 'Self Transport' || $t['transport_mode'] === 'Self';
});
$active_section = isset($_GET['section']) ? $_GET['section'] : 'school';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Teacher Transport - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-4 text-gray-800">Manage Teacher Transport</h1>
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
                                <input type="hidden" id="current-section" name="current_section" value="<?php echo htmlspecialchars($active_section); ?>">

                                <div id="school-section" style="display: none;">
                                    <h5 class="my-3">School Transport Teachers</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Teacher Name</th>
                                                    <th>Phone</th>
                                                    <th>Change Mode</th>
                                                    <th>Transport Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($school_transport_teachers as $teacher): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($teacher['teacher_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                                        <td>
                                                            <select class="form-control form-control-sm" name="teacher_transport[<?php echo $teacher['id']; ?>][transport_mode]" onchange="toggleInputs(this)">
                                                                <option value="School Transport" selected>School Transport</option>
                                                                <option value="Self Transport">Self Transport</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <div class="school-details">
                                                                <select class="form-control form-control-sm" name="teacher_transport[<?php echo $teacher['id']; ?>][stop_id]">
                                                                    <option value="">-- No Stop --</option>
                                                                    <?php
                                                                        $current_route = '';
                                                                        foreach($all_stops as $stop) {
                                                                            if ($stop['route_name'] !== $current_route) {
                                                                                if($current_route !== '') echo '</optgroup>';
                                                                                $current_route = $stop['route_name'];
                                                                                echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                                            }
                                                                            $selected = ($teacher['stop_id'] == $stop['id']) ? 'selected' : '';
                                                                            echo "<option value='{$stop['id']}' {$selected}>" . htmlspecialchars($stop['stop_name']) . "</option>";
                                                                        }
                                                                        if($current_route !== '') echo '</optgroup>';
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="self-details" style="display:none;">
                                                                <select class="form-control form-control-sm mb-2" name="teacher_transport[<?php echo $teacher['id']; ?>][self_transport_mode]" onchange="toggleSelfDetails(this)">
                                                                    <option value="">-- Select Mode --</option>
                                                                    <?php foreach ($self_transport_modes as $mode): ?><option value="<?php echo htmlspecialchars($mode); ?>"><?php echo htmlspecialchars($mode); ?></option><?php endforeach; ?>
                                                                </select>
                                                                <div class="self-details-fields" style="display:none;">
                                                                    <input type="text" class="form-control form-control-sm mb-2" name="teacher_transport[<?php echo $teacher['id']; ?>][vehicle_number]" placeholder="Vehicle Number">
                                                                    <input type="text" class="form-control form-control-sm" name="teacher_transport[<?php echo $teacher['id']; ?>][license_number]" placeholder="License Number">
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div id="self-section" style="display: none;">
                                    <h5 class="my-3">Self Transport Teachers</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Teacher Name</th>
                                                    <th>Phone</th>
                                                    <th>Change Mode</th>
                                                    <th>Transport Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($self_transport_teachers as $teacher): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($teacher['teacher_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                                        <td>
                                                            <select class="form-control form-control-sm" name="teacher_transport[<?php echo $teacher['id']; ?>][transport_mode]" onchange="toggleInputs(this)">
                                                                <option value="Self Transport" selected>Self Transport</option>
                                                                <option value="School Transport">School Transport</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <div class="school-details" style="display:none;">
                                                                <select class="form-control form-control-sm" name="teacher_transport[<?php echo $teacher['id']; ?>][stop_id]">
                                                                    <option value="">-- No Stop --</option>
                                                                    <?php
                                                                        $current_route = '';
                                                                        foreach($all_stops as $stop) {
                                                                            if ($stop['route_name'] !== $current_route) {
                                                                                if($current_route !== '') echo '</optgroup>';
                                                                                $current_route = $stop['route_name'];
                                                                                echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                                            }
                                                                            echo "<option value='{$stop['id']}'>" . htmlspecialchars($stop['stop_name']) . "</option>";
                                                                        }
                                                                        if($current_route !== '') echo '</optgroup>';
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="self-details">
                                                                <select class="form-control form-control-sm mb-2" name="teacher_transport[<?php echo $teacher['id']; ?>][self_transport_mode]" onchange="toggleSelfDetails(this)">
                                                                    <option value="">-- Select Mode --</option>
                                                                    <?php foreach ($self_transport_modes as $mode): ?>
                                                                        <option value="<?php echo htmlspecialchars($mode); ?>" <?php echo ($teacher['self_transport_mode'] === $mode) ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($mode); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="self-details-fields" style="<?php echo ($teacher['self_transport_mode'] === 'Bike' || $teacher['self_transport_mode'] === 'Car') ? 'display:block;' : 'display:none;'; ?>">
                                                                    <input type="text" class="form-control form-control-sm mb-2" name="teacher_transport[<?php echo $teacher['id']; ?>][vehicle_number]" placeholder="Vehicle Number" value="<?php echo htmlspecialchars($teacher['vehicle_number'] ?? ''); ?>">
                                                                    <input type="text" class="form-control form-control-sm" name="teacher_transport[<?php echo $teacher['id']; ?>][license_number]" placeholder="License Number" value="<?php echo htmlspecialchars($teacher['license_number'] ?? ''); ?>">
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
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
    <script src="../../assets/js/responsive-tables.js"></script>

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
                if (schoolDetails) schoolDetails.style.display = 'block';
                if (selfDetails) {
                    selfDetails.style.display = 'none';
                    selfDetails.querySelectorAll('input, select').forEach(input => {
                        if (input.type !== 'hidden') input.value = '';
                    });
                    toggleSelfDetails(selfDetails.querySelector('select'));
                }
            } else { // Self Transport
                if (schoolDetails) {
                    schoolDetails.style.display = 'none';
                    const stopSelect = schoolDetails.querySelector('select');
                    if(stopSelect) stopSelect.value = '';
                }
                if (selfDetails) selfDetails.style.display = 'block';
            }
        }

        function toggleSelfDetails(selectElement) {
            if (!selectElement) return;
            const container = selectElement.closest('.self-details');
            const selfDetailsFields = container.querySelector('.self-details-fields');
            const selectedMode = selectElement.value;

            if (selectedMode === 'Bike' || selectedMode === 'Car') {
                if (selfDetailsFields) selfDetailsFields.style.display = 'block';
            } else {
                if (selfDetailsFields) {
                    selfDetailsFields.style.display = 'none';
                    selfDetailsFields.querySelectorAll('input').forEach(input => input.value = '');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const initialSection = '<?php echo htmlspecialchars($active_section); ?>';
            showSection(initialSection);
        });
    </script>
</body>
</html>