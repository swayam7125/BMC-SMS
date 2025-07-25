<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if not logged in
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($teacher_id <= 0) {
    header("Location: teacher_list.php?error=Invalid teacher ID");
    exit;
}

// --- CHANGE 1: FETCH TEACHER AND TIMINGS DATA ---

// Fetch teacher data with school information
$query_teacher = "SELECT t.*, s.school_name, s.address as school_address, s.phone as school_phone, s.email as school_email
          FROM teacher t
          LEFT JOIN school s ON t.school_id = s.id
          WHERE t.id = ?";
$stmt_teacher = mysqli_prepare($conn, $query_teacher);
mysqli_stmt_bind_param($stmt_teacher, "i", $teacher_id);
mysqli_stmt_execute($stmt_teacher);
$result_teacher = mysqli_stmt_get_result($stmt_teacher);

if (!$result_teacher || mysqli_num_rows($result_teacher) == 0) {
    header("Location: teacher_list.php?error=Teacher not found");
    exit;
}
$teacher = mysqli_fetch_assoc($result_teacher);
mysqli_stmt_close($stmt_teacher);

// Fetch timings data for the teacher
$timings = [];
$query_timings = "SELECT * FROM teacher_timings WHERE teacher_id = ?";
$stmt_timings = mysqli_prepare($conn, $query_timings);
mysqli_stmt_bind_param($stmt_timings, "i", $teacher_id);
mysqli_stmt_execute($stmt_timings);
$result_timings = mysqli_stmt_get_result($stmt_timings);
while($row = mysqli_fetch_assoc($result_timings)){
    $timings[$row['day_of_week']] = $row;
}
mysqli_stmt_close($stmt_timings);

// --- END OF CHANGE 1 ---

// Photo path logic
$photo_path = $teacher['teacher_image'];
$default_photo = "../../assets/img/default-user.jpg";
if (!empty($photo_path) && !file_exists($photo_path)) {
    $photo_path = $default_photo;
} elseif (empty($photo_path)) {
    $photo_path = $default_photo;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Teacher - <?php echo htmlspecialchars($teacher['teacher_name']); ?></title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .view-photo { width: 150px; height: 150px; object-fit: cover; border-radius: 10px; border: 3px solid #e3e6f0; }
        .info-label { font-weight: bold; color: #5a5c69; }
        .info-value { color: #3a3b45; }
        .card-body hr { margin-top: 0.75rem; margin-bottom: 0.75rem; }
        .table-timings th { width: 35%; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
    <?php include '../../includes/sidebar.php';?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Teacher Details</h1>
                        <div>
                            <a href="teacher_list.php" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                            <a href="edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit fa-sm"></i> Edit Teacher</a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-camera"></i> Teacher Photo</h6></div>
                                <div class="card-body text-center">
                                    <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="<?php echo htmlspecialchars($teacher['teacher_name']); ?>" class="view-photo">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-tie"></i> Basic Information</h6></div>
                                <div class="card-body">
                                    <div class="row"><div class="col-sm-4 info-label">Name:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['teacher_name'] ?? 'N/A'); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Email:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['email']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Phone:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['phone']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">DOB:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars(date("d M Y", strtotime($teacher['dob']))); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Gender:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['gender']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Blood Group:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['blood_group']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Address:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($teacher['address']); ?></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-info"><i class="fas fa-briefcase"></i> Professional Details</h6></div>
                                <div class="card-body">
                                    <div class="row"><div class="col-sm-5 info-label">School Name:</div><div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['school_name']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-5 info-label">Qualification:</div><div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['qualification']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-5 info-label">Subject:</div><div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['subject']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-5 info-label">Teaching Standards:</div><div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['std']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-5 info-label">Experience:</div><div class="col-sm-7 info-value"><?php echo htmlspecialchars($teacher['experience']); ?> Years</div></div><hr>
                                    <div class="row"><div class="col-sm-5 info-label">Is Class Teacher:</div><div class="col-sm-7 info-value"><?php if ($teacher['class_teacher'] == 1): ?><span class="badge badge-success">Yes</span><small class="text-muted"> (Std: <?php echo htmlspecialchars($teacher['class_teacher_std']); ?>)</small><?php else: ?><span class="badge badge-secondary">No</span><?php endif; ?></div></div><hr>
                                    <div class="row"><div class="col-sm-5 info-label">Salary:</div><div class="col-sm-7 info-value font-weight-bold text-success">₹<?php echo number_format($teacher['salary'], 2); ?></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success"><i class="fas fa-clock"></i> Batch & Timings</h6></div>
                                <div class="card-body">
                                    <div class="row mb-3"><div class="col-sm-4 info-label">Assigned Batch:</div><div class="col-sm-8 info-value"><span class="badge badge-<?php echo ($teacher['batch'] == 'Morning') ? 'primary' : 'warning'; ?> p-2"><?php echo htmlspecialchars($teacher['batch'] ?? 'N/A'); ?></span></div></div>
                                    <hr>
                                    <!-- --- CHANGE 2: DISPLAY TIMINGS IN A TABLE --- -->
                                    <h6 class="info-label mb-2">Weekly Schedule:</h6>
                                    <?php if (!empty($timings)): ?>
                                        <table class="table table-sm table-bordered table-striped table-timings">
                                            <tbody>
                                                <?php
                                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                foreach ($days as $day):
                                                    $day_timing = $timings[$day] ?? null;
                                                ?>
                                                <tr>
                                                    <th><?php echo $day; ?></th>
                                                    <td>
                                                        <?php if ($day_timing && !empty($day_timing['is_closed'])): ?>
                                                            <span class="badge badge-secondary">Closed</span>
                                                        <?php elseif ($day_timing && !empty($day_timing['opens_at'])): ?>
                                                            <?php echo date("g:i A", strtotime($day_timing['opens_at'])); ?> - <?php echo date("g:i A", strtotime($day_timing['closes_at'])); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not Set</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <div class="alert alert-warning small">No weekly schedule has been set for this teacher.</div>
                                    <?php endif; ?>
                                    <!-- --- END OF CHANGE 2 --- -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <!-- Scroll to Top & Logout Modal -->
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5><button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button></div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button><a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a></div>
            </div>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
