<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

if (!defined('BASE_URL')) {
    define('BASE_URL', '/BMC-SMS/');
}

function getWebAccessibleImagePath($db_image_path, $base_web_path, $default_sub_folder = '')
{
    if (empty($db_image_path)) return null;
    $full_web_path = $base_web_path . ltrim($db_image_path, '/');
    $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path;
    if (@file_exists($filesystem_path) && @is_file($filesystem_path)) return $full_web_path;
    $possible_locations = ["pages/{$default_sub_folder}/uploads/", "uploads/{$default_sub_folder}s/", "uploads/"];
    foreach ($possible_locations as $location) {
        $test_path = $base_web_path . $location . basename($db_image_path);
        $test_filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $test_path;
        if (@file_exists($test_filesystem_path) && @is_file($test_filesystem_path)) return $test_path;
    }
    return null;
}

function getDefaultImagePath($base_web_path)
{
    $default_path = $base_web_path . 'assets/img/default-user.jpg';
    $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $default_path;
    if (file_exists($filesystem_path)) return $default_path;
    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150' viewBox='0 0 150 150'%3E%3Crect width='150' height='150' fill='%23f8f9fc'/%3E%3Ctext x='75' y='75' text-anchor='middle' dy='0.35em' fill='%23858796' font-family='Arial' font-size='14'%3ENo Photo%3C/text%3E%3C/svg%3E";
}

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$principal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($principal_id <= 0) {
    header("Location: principal_list.php?error=Invalid principal ID");
    exit;
}

$principal = null;
$timings = [];

try {
    $query_principal = "SELECT p.*, s.school_name, s.address as school_address, s.phone as school_phone, s.email as school_email
                      FROM principal p 
                      LEFT JOIN school s ON p.school_id = s.id
                      WHERE p.id = ?";
    $stmt_principal = $conn->prepare($query_principal);
    $stmt_principal->execute([$principal_id]);
    $principal = $stmt_principal->fetch(PDO::FETCH_ASSOC);

    if (!$principal) {
        header("Location: principal_list.php?error=Principal not found");
        exit;
    }

    $query_timings = "SELECT * FROM principal_timings WHERE principal_id = ?";
    $stmt_timings = $conn->prepare($query_timings);
    $stmt_timings->execute([$principal_id]);
    while ($row = $stmt_timings->fetch(PDO::FETCH_ASSOC)) {
        $timings[$row['day_of_week']] = $row;
    }
} catch (PDOException $e) {
    error_log("View Principal Error: " . $e->getMessage());
    die("A database error occurred.");
}

$photo_path = getWebAccessibleImagePath($principal['principal_image'], BASE_URL, 'principal');
$default_photo = getDefaultImagePath(BASE_URL);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>View Principal - <?php echo htmlspecialchars($principal['principal_name']); ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/principal_view.css">
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Principal Details</h1>
                        <div>
                            <a href="principal_list.php" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i> Back to List</a>
                            <a href="edit.php?id=<?php echo $principal['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit Principal</a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-camera"></i> Principal Photo</h6>
                                </div>
                                <div class="card-body text-center"><img src="<?php echo htmlspecialchars($photo_path ?? $default_photo); ?>" alt="<?php echo htmlspecialchars($principal['principal_name']); ?>" class="principal-photo" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_photo); ?>';"></div>
                            </div>
                        </div>
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-tie"></i> Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Name:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($principal['principal_name'] ?? 'N/A'); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Email:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($principal['email']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Phone:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($principal['phone']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">DOB:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars(date("d M Y", strtotime($principal['dob']))); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Gender:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($principal['gender']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Blood Group:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($principal['blood_group']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Qualification:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($principal['qualification']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Salary:</div>
                                        <div class="col-sm-8 info-value font-weight-bold text-success">₹<?php echo number_format($principal['salary'], 2); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Address:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($principal['address']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-school"></i> School Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4 info-label">School Name:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($principal['school_name']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">School Email:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($principal['school_email']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">School Phone:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($principal['school_phone']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">School Address:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($principal['school_address']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-clock"></i> Batch & Timings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-sm-4 info-label">Assigned Batch:</div>
                                        <div class="col-sm-8 info-value"><span class="badge badge-<?php echo ($principal['batch'] == 'Morning') ? 'primary' : 'warning'; ?> p-2"><?php echo htmlspecialchars($principal['batch'] ?? 'N/A'); ?></span></div>
                                    </div>
                                    <hr>
                                    <h6 class="info-label mb-2">Weekly Schedule:</h6>
                                    <?php if (!empty($timings)): ?>
                                        <table class="table table-sm table-bordered table-striped table-timings">
                                            <tbody>
                                                <?php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                foreach ($days as $day): $day_timing = $timings[$day] ?? null; ?>
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
                                        <div class="alert alert-warning small">No weekly schedule has been set for this principal.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>