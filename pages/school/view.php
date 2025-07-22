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
$query = "SELECT s.*, p.id as principal_id, p.principal_name, p.principal_image 
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

function getImagePath($image_path, $sub_folder = 'school')
{
    if (empty($image_path)) {
        return null;
    }
    $photo_directories = ["../../pages/{$sub_folder}/uploads/", "../../uploads/{$sub_folder}s/"];
    if (strpos($image_path, '../../') === 0) {
        if (file_exists($image_path) && is_file($image_path)) {
            return $image_path;
        }
    }
    foreach ($photo_directories as $dir) {
        if (file_exists($dir . $image_path) && is_file($dir . $image_path)) {
            return $dir . $image_path;
        }
    }
    return null;
}

function getDefaultImagePath($type = 'school')
{
    $default_path = $type === 'school' ? "../../assets/img/default-school.png" : "../../assets/img/default-user.jpg";
    if (file_exists($default_path)) {
        return $default_path;
    }
    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150' viewBox='0 0 150 150'%3E%3Crect width='150' height='150' fill='%23f8f9fc'/%3E%3Ctext x='75' y='75' text-anchor='middle' dy='0.35em' fill='%23858796' font-family='Arial' font-size='14'%3ENo Image%3C/text%3E%3C/svg%3E";
}

// Get paths for school logo
$school_logo_path = getImagePath($school['school_logo'], 'school');
$default_school_logo = getDefaultImagePath('school');
$show_default_logo = ($school_logo_path === null);

// Get paths for principal photo
$principal_photo_path = getImagePath($school['principal_image'], 'principal');
$default_principal_photo = getDefaultImagePath('principal');
$show_default_principal_photo = ($principal_photo_path === null);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>View School - <?php echo htmlspecialchars($school['school_name']); ?></title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
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
        <?php include_once '../../includes/sidebar/BMC_sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- top bar code -->
                <?php include_once '../../includes/header.php'; ?>
                <!-- end of top bar code -->
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
                                        <?php if (!$show_default_logo): ?>
                                            <img src="<?php echo htmlspecialchars($school_logo_path); ?>" alt="School Logo" class="view-image view-logo" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_school_logo); ?>';">
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($default_school_logo); ?>" alt="Default School Logo" class="view-image view-logo" style="opacity: 0.8;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-center">
                                        <small class="text-muted"><?php echo !$show_default_logo ? 'School Logo' : 'Default Logo'; ?></small>
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
                                    <div class="row info-row">
                                        <div class="col-sm-5 font-weight-bold">Standards:</div>
                                        <div class="col-sm-7"><?php echo htmlspecialchars(str_replace(',', ', ', $school['school_std'])); ?></div>
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
                                    <?php if (!empty($school['principal_id'])): ?>
                                        <div class="photo-container">
                                            <?php if (!$show_default_principal_photo): ?>
                                                <img src="<?php echo htmlspecialchars($principal_photo_path); ?>" alt="<?php echo htmlspecialchars($school['principal_name']); ?>" class="view-image view-photo" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_principal_photo); ?>';">
                                            <?php else: ?>
                                                <img src="<?php echo htmlspecialchars($default_principal_photo); ?>" alt="Default Principal Photo" class="view-image view-photo" style="opacity: 0.8;">
                                            <?php endif; ?>
                                        </div>
                                        <h5 class="font-weight-bold text-gray-800 mt-2">
                                            <a href="../principal/view.php?id=<?php echo $school['principal_id']; ?>">
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
            <!-- Footer -->
            <?php
            include '../../includes/footer.php';
            ?>
            <!-- End of Footer -->
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