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

// Handle updating student transport
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_transport'])) {
    $student_transport_data = $_POST['student_transport'] ?? [];
    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE student SET transport_mode = ?, stop_id = ?, self_transport_mode = ?, vehicle_number = ?, license_number = ? WHERE id = ? AND school_id = ?");

        foreach ($student_transport_data as $student_id => $data) {
            $transport_mode = $data['transport_mode'] ?? null;
            $stop_id = null;
            $self_transport_mode = null;
            $vehicle_number = null;
            $license_number = null;

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

            $stmt->execute([$transport_mode, $stop_id, $self_transport_mode, $vehicle_number, $license_number, (int)$student_id, $school_id]);
        }
        $conn->commit();
        $success = "Student transport information updated successfully!";
    } catch (PDOException $e) {
        $conn->rollBack();
        $errors[] = "Database update failed: " . $e->getMessage();
    }
}

// Fetch Data for Display
$stops_query = $conn->prepare("SELECT s.id, s.stop_name, r.route_name FROM stops s JOIN routes r ON s.route_id = r.id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name");
$stops_query->execute([$school_id]);
$all_stops = $stops_query->fetchAll(PDO::FETCH_ASSOC);

$students_query = $conn->prepare("SELECT id, student_name, rollno, std, stop_id, transport_mode, self_transport_mode, vehicle_number, license_number FROM student WHERE school_id = ? ORDER BY std, rollno");
$students_query->execute([$school_id]);
$students = $students_query->fetchAll(PDO::FETCH_ASSOC);

// Get available self-transport modes from the enum type
$self_transport_modes_query = $conn->query("SELECT unnest(enum_range(NULL::public.self_transport_mode))");
$self_transport_modes = $self_transport_modes_query->fetchAll(PDO::FETCH_COLUMN);

// Group students by transport mode
$school_transport_students = array_filter($students, function($s) {
    return $s['transport_mode'] === 'School Transport';
});
$self_transport_students = array_filter($students, function($s) {
    return $s['transport_mode'] === 'Self Transport' || $s['transport_mode'] === 'Self';
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Student Transport - School Management System</title>
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
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage Student Transport</h1>
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
                                <div id="school-section" style="display: block;">
                                    <h5 class="my-3">School Transport Students</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th>Roll No</th>
                                                    <th>Standard</th>
                                                    <th>Change Mode</th>
                                                    <th>Assign Stop</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($school_transport_students)): ?>
                                                    <?php foreach ($school_transport_students as $student): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['rollno']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['std']); ?></td>
                                                            <td>
                                                                <select class="form-control form-control-sm" name="student_transport[<?php echo $student['id']; ?>][transport_mode]" onchange="toggleInputs(this)">
                                                                    <option value="School Transport" selected>School Transport</option>
                                                                    <option value="Self Transport">Self Transport</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <div class="school-details">
                                                                    <select class="form-control form-control-sm" name="student_transport[<?php echo $student['id']; ?>][stop_id]">
                                                                        <option value="">-- No Stop --</option>
                                                                        <?php
                                                                            $current_route = '';
                                                                            foreach($all_stops as $stop) {
                                                                                if ($stop['route_name'] !== $current_route) {
                                                                                    if($current_route !== '') echo '</optgroup>';
                                                                                    $current_route = $stop['route_name'];
                                                                                    echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                                                }
                                                                                $selected = ($student['stop_id'] == $stop['id']) ? 'selected' : '';
                                                                                echo "<option value='{$stop['id']}' {$selected}>" . htmlspecialchars($stop['stop_name']) . "</option>";
                                                                            }
                                                                            if($current_route !== '') echo '</optgroup>';
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="self-details" style="display:none;">
                                                                    <select class="form-control form-control-sm mb-2" name="student_transport[<?php echo $student['id']; ?>][self_transport_mode]" onchange="toggleSelfDetails(this)">
                                                                        <option value="">-- Select Mode --</option>
                                                                        <?php foreach ($self_transport_modes as $mode): ?>
                                                                            <option value="<?php echo htmlspecialchars($mode); ?>">
                                                                                <?php echo htmlspecialchars($mode); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <div class="self-details-fields" style="display:none;">
                                                                        <input type="text" class="form-control form-control-sm mb-2" name="student_transport[<?php echo $student['id']; ?>][vehicle_number]" placeholder="Vehicle Number">
                                                                        <input type="text" class="form-control form-control-sm" name="student_transport[<?php echo $student['id']; ?>][license_number]" placeholder="License Number">
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center">No students found with School Transport.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div id="self-section" style="display: none;">
                                    <h5 class="my-3">Self Transport Students</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th>Roll No</th>
                                                    <th>Standard</th>
                                                    <th>Change Mode</th>
                                                    <th>Transport Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($self_transport_students)): ?>
                                                    <?php foreach ($self_transport_students as $student): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['rollno']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['std']); ?></td>
                                                            <td>
                                                                <select class="form-control form-control-sm" name="student_transport[<?php echo $student['id']; ?>][transport_mode]" onchange="toggleInputs(this)">
                                                                    <option value="Self Transport" selected>Self Transport</option>
                                                                    <option value="School Transport">School Transport</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <div class="school-details" style="display:none;">
                                                                    <select class="form-control form-control-sm" name="student_transport[<?php echo $student['id']; ?>][stop_id]">
                                                                        <option value="">-- No Stop --</option>
                                                                        <?php
                                                                            $current_route = '';
                                                                            foreach($all_stops as $stop) {
                                                                                if ($stop['route_name'] !== $current_route) {
                                                                                    if($current_route !== '') echo '</optgroup>';
                                                                                    $current_route = $stop['route_name'];
                                                                                    echo '<optgroup label="' . htmlspecialchars($current_route) . '">';
                                                                                }
                                                                                $selected = ($student['stop_id'] == $stop['id']) ? 'selected' : '';
                                                                                echo "<option value='{$stop['id']}' {$selected}>" . htmlspecialchars($stop['stop_name']) . "</option>";
                                                                            }
                                                                            if($current_route !== '') echo '</optgroup>';
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="self-details">
                                                                    <select class="form-control form-control-sm mb-2" name="student_transport[<?php echo $student['id']; ?>][self_transport_mode]" onchange="toggleSelfDetails(this)">
                                                                        <option value="">-- Select Mode --</option>
                                                                        <?php foreach ($self_transport_modes as $mode): ?>
                                                                            <option value="<?php echo htmlspecialchars($mode); ?>" <?php echo ($student['self_transport_mode'] === $mode) ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($mode); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <div class="self-details-fields" style="<?php echo ($student['self_transport_mode'] === 'Bike' || $student['self_transport_mode'] === 'Car') ? 'display:block;' : 'display:none;'; ?>">
                                                                        <input type="text" class="form-control form-control-sm mb-2" name="student_transport[<?php echo $student['id']; ?>][vehicle_number]" placeholder="Vehicle Number" value="<?php echo htmlspecialchars($student['vehicle_number'] ?? ''); ?>">
                                                                        <input type="text" class="form-control form-control-sm" name="student_transport[<?php echo $student['id']; ?>][license_number]" placeholder="License Number" value="<?php echo htmlspecialchars($student['license_number'] ?? ''); ?>">
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center">No students found with Self Transport.</td>
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
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>

    <?php include_once "../../includes/logout_modal.php"; ?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        function showSection(section) {
            document.getElementById('school-section').style.display = 'none';
            document.getElementById('self-section').style.display = 'none';

            if (section === 'school') {
                document.getElementById('school-section').style.display = 'block';
                document.getElementById('school-transport-btn').className = 'btn btn-primary';
                document.getElementById('self-transport-btn').className = 'btn btn-secondary';
            } else if (section === 'self') {
                document.getElementById('self-section').style.display = 'block';
                document.getElementById('self-transport-btn').className = 'btn btn-primary';
                document.getElementById('school-transport-btn').className = 'btn btn-secondary';
            }
        }

        // Toggles the visibility of School Transport and Self Transport details
        function toggleInputs(selectElement) {
            const row = selectElement.closest('tr');
            const schoolDetails = row.querySelector('.school-details');
            const selfDetails = row.querySelector('.self-details');

            if (selectElement.value === 'School Transport') {
                if (schoolDetails) schoolDetails.style.display = 'block';
                if (selfDetails) selfDetails.style.display = 'none';
            } else if (selectElement.value === 'Self Transport') {
                if (schoolDetails) schoolDetails.style.display = 'none';
                if (selfDetails) selfDetails.style.display = 'block';
            }
        }

        // Toggles the visibility of vehicle details within Self Transport mode
        function toggleSelfDetails(selectElement) {
            const row = selectElement.closest('tr');
            const selfDetailsFields = row.querySelector('.self-details-fields');
            const selectedMode = selectElement.value;

            if (selectedMode === 'Bike' || selectedMode === 'Car') {
                if (selfDetailsFields) selfDetailsFields.style.display = 'block';
            } else {
                if (selfDetailsFields) {
                    selfDetailsFields.style.display = 'none';
                    // Clear the values when the fields are hidden
                    const vehicleNumberInput = selfDetailsFields.querySelector('[name*="vehicle_number"]');
                    const licenseNumberInput = selfDetailsFields.querySelector('[name*="license_number"]');
                    if (vehicleNumberInput) vehicleNumberInput.value = '';
                    if (licenseNumberInput) licenseNumberInput.value = '';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            showSection('school');

            // Apply initial state on page load for all existing self-transport students
            document.querySelectorAll('#self-section .self-details select').forEach(select => {
                toggleSelfDetails(select);
            });
        });
    </script>
</body>
</html>