<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// FIX: Define the BASE_WEB_PATH constant if it's not already defined.
// This ensures that the script has access to the base URL for creating web-accessible paths.
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if not logged in
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// Get principal ID from URL
$principal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($principal_id <= 0) {
    header("Location: principal_list.php?error=Invalid principal ID");
    exit;
}

// Fetch principal data with school information
$query = "SELECT p.*, s.school_name, s.address as school_address, s.phone as school_phone, s.email as school_email
          FROM principal p 
          LEFT JOIN school s ON p.school_id = s.id
          WHERE p.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $principal_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: principal_list.php?error=Principal not found");
    exit;
}

$principal = mysqli_fetch_assoc($result);

function getWebAccessibleImagePath($db_image_path, $base_web_path, $default_sub_folder = '') {
    if (empty($db_image_path)) {
        return null;
    }
    $full_web_path = $base_web_path . ltrim($db_image_path, '/');
    $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path;
    if (@file_exists($filesystem_path) && @is_file($filesystem_path)) {
        return $full_web_path;
    }
    $possible_locations = ["pages/{$default_sub_folder}/uploads/", "uploads/{$default_sub_folder}s/", "uploads/"];
    foreach ($possible_locations as $location) {
        $test_path = $base_web_path . $location . basename($db_image_path);
        $test_filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $test_path;
        if (@file_exists($test_filesystem_path) && @is_file($test_filesystem_path)) {
            return $test_path;
        }
    }
    return null;
}

function getDefaultPhotoPath()
{
    $default_paths = [
        "../../assets/images/default-principal.jpg",
        "../../assets/img/default-principal.jpg",
        "../../assets/images/default-user.jpg",
        "../../assets/img/default-user.jpg",
    ];

    foreach ($default_paths as $path) {
        if (file_exists($path) && is_file($path)) {
            return $path;
        }
    }

    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150' viewBox='0 0 150 150'%3E%3Crect width='150' height='150' fill='%23f8f9fc'/%3E%3Ctext x='75' y='75' text-anchor='middle' dy='0.35em' fill='%23858796' font-family='Arial' font-size='14'%3ENo Photo%3C/text%3E%3C/svg%3E";
}


// Get the actual photo path
$photo_path = getWebAccessibleImagePath($principal['principal_image'], BASE_WEB_PATH, 'principal');
$default_photo = getDefaultPhotoPath();


// Define timings based on batch
$timings_html = '';
if (!empty($principal['batch'])) {
    if ($principal['batch'] === 'Morning') {
        $timings_html = "
            <div><strong>Mon-Sat:</strong> 7:00 AM - 2:00 PM</div>
            <div><strong>Sunday:</strong> 10:00 AM - 12:00 PM</div>
        ";
    } elseif ($principal['batch'] === 'Evening') {
        $timings_html = "
            <div><strong>Mon-Sat:</strong> 11:00 AM - 6:00 PM</div>
            <div><strong>Saturday:</strong> 11:00 AM - 4:00 PM</div>
            <div><strong>Sunday:</strong> 10:00 AM - 12:00 PM</div>
        ";
    }
} else {
    $timings_html = "<div class='text-muted'>Not specified</div>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Principal - <?php echo htmlspecialchars($principal['principal_name']); ?></title>

    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <!-- Corrected Font Awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .principal-photo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid #e3e6f0;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .photo-placeholder {
            width: 150px;
            height: 150px;
            background-color: #f8f9fc;
            border: 2px dashed #d1d3e2;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #858796;
            font-size: 14px;
            text-align: center;
        }

        .photo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .salary-display {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }
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
                        <h1 class="h3 mb-0 text-gray-800">Principal Details</h1>
                        <div>
                            <a href="principal_list.php" class="btn btn-secondary btn-sm mr-2">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <a href="edit.php?id=<?php echo $principal['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit Principal
                            </a>
                        </div>
                    </div>

                    <div class="row">

                        <div class="col-lg-4 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-camera"></i> Principal Photo
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="photo-container">
                                        <img src="<?php echo htmlspecialchars($photo_path ?? $default_photo); ?>"
                                             alt="<?php echo htmlspecialchars($principal['principal_name']); ?>"
                                             class="principal-photo"
                                             onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_photo); ?>';">
                                    </div>
                                    <div class="text-center">
                                        <small class="text-muted">
                                            <?php echo ($photo_path) ? 'Principal Photo' : 'Default Avatar'; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-user-tie"></i> Basic Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Principal ID:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($principal['id']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Name:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($principal['principal_name'] ?? 'N/A'); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Email:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($principal['email']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Phone:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($principal['phone']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">DOB:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($principal['principal_dob']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Gender:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($principal['gender']); ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Blood Group:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($principal['blood_group']); ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Qualification:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($principal['qualification']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Salary:</div>
                                        <div class="col-sm-8 salary-display">
                                            ₹<?php echo number_format($principal['salary'], 2); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Address:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($principal['address']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-info">
                                        <i class="fas fa-school"></i> School Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">School Name:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($principal['school_name']); ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">School Email:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($principal['school_email']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">School Phone:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($principal['school_phone']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">School Address:</div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($principal['school_address']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success">
                                        <i class="fas fa-clock"></i> Batch & Timings
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-sm-4 font-weight-bold">Assigned Batch:</div>
                                        <div class="col-sm-8">
                                            <span class="badge badge-<?php echo ($principal['batch'] == 'Morning') ? 'primary' : 'warning'; ?>">
                                                <?php echo htmlspecialchars($principal['batch'] ?? 'N/A'); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-4 font-weight-bold">Work Timings:</div>
                                        <div class="col-sm-8"><?php echo $timings_html; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <?php
            include '../../includes/footer.php';
            ?>
            </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
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
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

</body>

</html>