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

// Get principal ID from URL
$principal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($principal_id <= 0) {
    header("Location: principal_list.php?error=Invalid principal ID");
    exit;
}

// Fetch principal data with school information
// The existing query already selects all fields from principal (p.*), so it will include the new 'batch' field.
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

// Function to get principal photo path with multiple fallback options
function getPrincipalPhotoPath($principal_image, $principal_id = null)
{
    if (empty($principal_image)) {
        return null;
    }

    // Possible photo directories to check
    $photo_directories = [
        "../../pages/principal/uploads/", // Primary upload dir
        "../../assets/images/",
        "../../uploads/principals/",
        "../../uploads/",
        "../../assets/img/principals/",
        "../../images/principals/",
        "../../principal_photos/"
    ];

    // If it's already a full path (like starts with ../..), check if it exists
    // Note: This needs to be relative to the script's location on the filesystem.
    // For web paths, consider defining a base path.
    if (strpos($principal_image, '../../') === 0 || strpos($principal_image, '../') === 0) {
        // Attempt to resolve relative path to absolute filesystem path
        // __DIR__ gives the directory of the current script (e.g., /path/to/project/pages/principal/)
        // We need to go up to the project root and then follow the relative path.
        // This is tricky and often better handled by storing web-accessible paths in DB.
        // For simplicity, we'll assume direct web paths or paths relative to BMC-SMS root.
        $absolute_path_from_script = realpath(__DIR__ . '/' . $principal_image);
        if ($absolute_path_from_script && file_exists($absolute_path_from_script) && is_file($absolute_path_from_script)) {
            // Convert to web-accessible path if possible, or return as is if it's already relative to web root
            return $principal_image; // This might need further adjustment for web display
        }
    }
    
    // Try different directory combinations relative to current script's parent (BMC-SMS root generally)
    foreach ($photo_directories as $dir) {
        // Construct full filesystem path
        $full_filesystem_path = realpath(__DIR__ . '/../..' . substr($dir, 2) . basename($principal_image)); // Adjust path from current script
        if ($full_filesystem_path && file_exists($full_filesystem_path) && is_file($full_filesystem_path)) {
            // Return web-accessible path relative to BASE_WEB_PATH
            // Assuming BASE_WEB_PATH is '/BMC-SMS/'
            return str_replace($_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/', '/BMC-SMS/', $full_filesystem_path);
        }
    }

    return null; // No photo found
}

// Function to get default photo path
function getDefaultPhotoPath()
{
    $default_paths = [
        "../../assets/images/default-principal.jpg",
        "../../assets/img/default-principal.jpg",
        "../../assets/images/default-user.jpg",
        "../../assets/img/default-user.jpg",
        "../../assets/images/no-photo.jpg",
        "../../assets/img/no-photo.jpg"
    ];

    foreach ($default_paths as $path) {
        if (file_exists($path) && is_file($path)) {
            return $path;
        }
    }

    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150' viewBox='0 0 150 150'%3E%3Crect width='150' height='150' fill='%23f8f9fc'/%3E%3Ctext x='75' y='75' text-anchor='middle' dy='0.35em' fill='%23858796' font-family='Arial' font-size='14'%3ENo Photo%3C/text%3E%3C/svg%3E";
}


// Get the actual photo path
// When storing paths in the DB, it's generally best to store them as web-accessible relative paths from the document root, e.g., 'pages/principal/uploads/filename.jpg'
// This simplifies photo retrieval dramatically.
// Assuming getPrincipalPhotoPath now returns a web-accessible path (e.g. starting with /BMC-SMS/)
$photo_path = $principal['principal_image']; // Assume DB stores the path relative to project root directly (e.g., pages/principal/uploads/...)
if (!empty($photo_path)) {
    // Construct the full web-accessible URL
    $full_web_path = BASE_WEB_PATH . ltrim($photo_path, '/');
    // Check if the file actually exists on the filesystem
    $filesystem_path = $_SERVER['DOCUMENT_ROOT'] . $full_web_path;

    if (!file_exists($filesystem_path) || !is_file($filesystem_path)) {
        $photo_path = null; // Mark as not found if file doesn't exist
    } else {
        $photo_path = $full_web_path; // Use the full web-accessible path
    }
} else {
    $photo_path = null; // No image path in DB
}

$default_photo = getDefaultPhotoPath();
$show_default = ($photo_path === null);


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
                                        <?php if ($photo_path): // Check if a valid web-accessible path was found ?>
                                            <img src="<?php echo htmlspecialchars($photo_path); ?>"
                                                alt="<?php echo htmlspecialchars($principal['principal_name']); ?>"
                                                class="principal-photo"
                                                onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_photo); ?>';">
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($default_photo); ?>"
                                                alt="Default Principal Avatar" class="principal-photo"
                                                style="opacity: 0.7;">
                                        <?php endif; ?>
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