<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$school_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($school_id <= 0) {
    header("Location: school_list.php?error=Invalid school ID");
    exit;
}

// Fetch school data with assigned principal's details
// Note: principal.id is now the same as users.id for a principal
$query = "SELECT s.*, p.id as principal_user_id, p.principal_name, p.principal_image 
          FROM school s 
          LEFT JOIN principal p ON s.id = p.school_id 
          WHERE s.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $school_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: school_list.php?error=School not found");
    exit;
}
$school = mysqli_fetch_assoc($result);

// --- Robust Photo/Logo Handling Logic ---
// This function assumes the image_path stored in the DB is relative to your project's web root (e.g., 'pages/school/uploads/logo.png')
function getWebAccessibleImagePath($db_image_path, $base_web_path, $default_sub_folder = '')
{
    if (empty($db_image_path)) {
        return null;
    }

    // Attempt to make it a full web-accessible path
    $full_web_path = $base_web_path . ltrim($db_image_path, '/');

    // Check if the file actually exists on the filesystem from the DOCUMENT_ROOT
    $filesystem_path = $_SERVER['DOCUMENT_ROOT'] . $full_web_path;

    if (file_exists($filesystem_path) && is_file($filesystem_path)) {
        return $full_web_path;
    }
    
    // Fallback: If DB path is just a filename, try common upload locations
    $possible_locations = [
        "pages/{$default_sub_folder}/uploads/",
        "uploads/{$default_sub_folder}s/",
        "uploads/",
    ];
    
    foreach ($possible_locations as $location) {
        $test_path = $base_web_path . $location . basename($db_image_path);
        $test_filesystem_path = $_SERVER['DOCUMENT_ROOT'] . $test_path;
        if (file_exists($test_filesystem_path) && is_file($test_filesystem_path)) {
            return $test_path;
        }
    }

    return null; // No photo found
}

function getDefaultImagePath($type = 'school', $base_web_path)
{
    if ($type === 'school') {
        return $base_web_path . "assets/img/default-school.png";
    } else { // 'principal' or 'user'
        return $base_web_path . "assets/img/default-user.jpg";
    }
}

// Ensure BASE_WEB_PATH is defined (it should be in header.php, but define for this script's standalone test)
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/'); // Adjust as per your actual project setup
}


// Get paths for school logo
$school_logo_web_path = getWebAccessibleImagePath($school['school_logo'], BASE_WEB_PATH, 'school');
$default_school_logo = getDefaultImagePath('school', BASE_WEB_PATH);


// Get paths for principal photo
$principal_photo_web_path = getWebAccessibleImagePath($school['principal_image'], BASE_WEB_PATH, 'principal');
$default_principal_photo = getDefaultImagePath('principal', BASE_WEB_PATH);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>View School - <?php echo htmlspecialchars($school['school_name']); ?></title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <!-- Corrected Font Awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .view-image {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            border: 3px solid #e3e6f0;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .view-photo {
            object-fit: cover;
        }

        .view-logo {
            object-fit: contain;
            padding: 5px;
        }

        .photo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .info-row {
            border-bottom: 1px solid #e3e6f0;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .info-row:last-child {
            border-bottom: none;
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
                        <h1 class="h3 mb-0 text-gray-800">School Details</h1>
                        <div>
                            <a href="school_list.php" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                            <a href="edit.php?id=<?php echo $school['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit fa-sm"></i> Edit School</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-image"></i> School Logo</h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="photo-container">
                                        <?php if ($school_logo_web_path): ?>
                                            <img src="<?php echo htmlspecialchars($school_logo_web_path); ?>" alt="School Logo" class="view-image view-logo" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_school_logo); ?>';">
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($default_school_logo); ?>" alt="Default School Logo" class="view-image view-logo" style="opacity: 0.8;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-center">
                                        <small class="text-muted"><?php echo $school_logo_web_path ? 'School Logo' : 'Default Logo'; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i> Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">School ID:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($school['id']); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Name:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($school['school_name']); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Email:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($school['email']); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Phone:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($school['phone']); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Opening Date:</div>
                                        <div class="col-sm-8"><?php echo date("d M, Y", strtotime($school['school_opening'])); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Address:</div>
                                        <div class="col-sm-8"><?php echo nl2br(htmlspecialchars($school['address'])); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-university"></i> Academic Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row info-row">
                                        <div class="col-sm-5 font-weight-bold">School Type:</div>
                                        <div class="col-sm-7"><?php echo htmlspecialchars($school['school_type']); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-5 font-weight-bold">Education Board(s):</div>
                                        <div class="col-sm-7"><?php echo htmlspecialchars(str_replace(',', ', ', $school['education_board'])); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-5 font-weight-bold">Medium(s):</div>
                                        <div class="col-sm-7"><?php echo htmlspecialchars(str_replace(',', ', ', $school['school_medium'])); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-5 font-weight-bold">Categories:</div>
                                        <div class="col-sm-7"><?php echo htmlspecialchars(str_replace(',', ', ', $school['school_category'])); ?></div>
                                    </div>
                                    </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-user-tie"></i> Principal Information</h6>
                                </div>
                                <div class="card-body text-center d-flex flex-column justify-content-center">
                                    <?php if (!empty($school['principal_user_id'])): // Use principal_user_id from the query result ?>
                                        <div class="photo-container">
                                            <?php if ($principal_photo_web_path): ?>
                                                <img src="<?php echo htmlspecialchars($principal_photo_web_path); ?>" alt="<?php echo htmlspecialchars($school['principal_name']); ?>" class="view-image view-photo" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_principal_photo); ?>';">
                                            <?php else: ?>
                                                <img src="<?php echo htmlspecialchars($default_principal_photo); ?>" alt="Default Principal Photo" class="view-image view-photo" style="opacity: 0.8;">
                                            <?php endif; ?>
                                        </div>
                                        <h5 class="font-weight-bold text-gray-800 mt-2">
                                            <a href="../principal/view.php?id=<?php echo $school['principal_user_id']; ?>">
                                                <?php echo htmlspecialchars($school['principal_name']); ?>
                                            </a>
                                        </h5>
                                        <p class="text-muted mb-0">Assigned Principal</p>
                                    <?php else: ?>
                                        <div class="text-center my-3">
                                            <i class="fas fa-user-slash fa-3x text-gray-400 mb-3"></i>
                                            <p class="text-muted">No Principal Assigned</p>
                                        </div>
                                    <?php endif; ?>
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
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>