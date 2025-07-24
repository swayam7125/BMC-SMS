<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";

// (Get user info from cookies - same as your view_notes.php)
if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (!$role || !$userId) {
    header("Location: ./login.php");
    exit;
}

// --- FETCH USER-SPECIFIC DATA (school_id, std) ---
$schoolId = null;
$studentStd = null;
switch ($role) {
    case 'student':
        $stmt = $conn->prepare("SELECT school_id, std FROM student WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $schoolId = $row['school_id'];
            $studentStd = $row['std'];
        }
        $stmt->close();
        break;
    case 'teacher':
        $stmt = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) $schoolId = $row['school_id'];
        $stmt->close();
        break;
    case 'schooladmin':
        $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) $schoolId = $row['school_id'];
        $stmt->close();
        break;
}

// Build the SQL query to fetch timetables based on the user's role
$timetables = [];
// --- MODIFIED: Query now joins with the 'teacher' table to get the name ---
$base_sql = "SELECT tt.standard, tt.timetable_file, tt.original_filename, tt.created_at, t.teacher_name as uploader
             FROM timetables tt
             JOIN teacher t ON tt.class_teacher_id = t.id";
$params = [];
$types = '';

switch ($role) {
    case 'student':
        $base_sql .= " WHERE tt.school_id = ? AND tt.standard = ?";
        $params = [$schoolId, $studentStd];
        $types = "is";
        break;
    case 'teacher':
    case 'schooladmin':
        $base_sql .= " WHERE tt.school_id = ?";
        $params = [$schoolId];
        $types = "i";
        break;
    case 'bmc':
        // No filter for BMC admin
        break;
}
$base_sql .= " ORDER BY tt.created_at DESC";

$stmt = $conn->prepare($base_sql);
if ($stmt && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}
if($stmt){
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $timetables[] = $row;
    }
    $stmt->close();
}
$conn->close();
$pageTitle = 'View Timetable';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400i,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Class Timetables</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Available Timetables</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Standard</th>
                                            <th>Uploaded By</th>
                                            <th>Date Uploaded</th>
                                            <th>Download</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($timetables as $tt): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($tt['standard']); ?></td>
                                                <td><?php echo htmlspecialchars($tt['uploader']); ?></td>
                                                <td><?php echo date('d-m-Y H:i', strtotime($tt['created_at'])); ?></td>
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($tt['timetable_file']); ?>" class="btn btn-success btn-sm" download="<?php echo htmlspecialchars($tt['original_filename']); ?>">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($timetables)): ?>
                                            <tr><td colspan="4" class="text-center">No timetable has been uploaded for your class yet.</td></tr>
                                        <?php endif; ?>
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
    
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                 "order": [[ 2, "desc" ]] // Sort by 'Date Uploaded' column
            });
        });
    </script>
</body>
</html>