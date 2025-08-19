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
        $stmt = $conn->prepare("UPDATE student SET stop_id = ? WHERE id = ? AND school_id = ?");
        foreach($student_transport_data as $student_id => $stop_id) {
            $stop_id_for_db = !empty($stop_id) ? (int)$stop_id : null;
            $stmt->execute([$stop_id_for_db, (int)$student_id, $school_id]);
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

// MODIFIED: Added "AND transport_mode = 'School Transport'" to filter the list
$students_query = $conn->prepare("SELECT id, student_name, rollno, std, stop_id FROM student WHERE school_id = ? AND transport_mode = 'School Transport' ORDER BY std, rollno");
$students_query->execute([$school_id]);
$students = $students_query->fetchAll(PDO::FETCH_ASSOC);

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
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Assign Students to Transport Stops</h6></div>
                        <div class="card-body">
                            <p class="text-muted small">Only students who have opted for 'School Transport' are shown here. To change a student's transport mode, please edit their profile from the main student list.</p>
                            <form method="POST">
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead><tr><th>Student Name</th><th>Roll No</th><th>Standard</th><th>Assigned Stop</th></tr></thead>
                                        <tbody>
                                            <?php if (!empty($students)): ?>
                                                <?php foreach ($students as $student): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['rollno']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['std']); ?></td>
                                                        <td>
                                                            <select class="form-control form-control-sm" name="student_transport[<?php echo $student['id']; ?>]">
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
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No students have opted for school transport.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (!empty($students)): ?>
                                    <button type="submit" name="update_transport" class="btn btn-primary mt-3">Save Changes</button>
                                <?php endif; ?>
                            </form>
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