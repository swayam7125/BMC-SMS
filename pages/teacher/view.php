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

// Query will now also fetch 'batch', 'class_teacher', and 'class_teacher_std' fields
$query = "SELECT t.*, s.school_name, s.address as school_address, s.phone as school_phone, s.email as school_email
          FROM teacher t
          LEFT JOIN school s ON t.school_id = s.id
          WHERE t.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: teacher_list.php?error=Teacher not found");
    exit;
}

$teacher = mysqli_fetch_assoc($result);

// --- UPDATED PHOTO LOGIC (from principal/view.php) ---

function getTeacherPhotoPath($teacher_image, $teacher_id = null)
{
    if (empty($teacher_image)) {
        return null;
    }
    // Possible photo directories to check
    $photo_directories = ["../../pages/teacher/uploads/", "../../assets/images/", "../../uploads/teachers/"];
    // If it's already a full path, check if it exists
    if (strpos($teacher_image, '../../') === 0 || strpos($teacher_image, '/') === 0) {
        if (file_exists($teacher_image) && is_file($teacher_image)) {
            return $teacher_image;
        }
    }
    // Try different directory combinations
    foreach ($photo_directories as $dir) {
        if (file_exists($dir . $teacher_image) && is_file($dir . $teacher_image)) {
            return $dir . $teacher_image;
        }
    }
    return null; // No photo found
}

function getDefaultPhotoPath()
{
    $default_paths = ["../../assets/img/default-user.jpg", "../../assets/images/default-user.jpg"];
    foreach ($default_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150' viewBox='0 0 150 150'%3E%3Crect width='150' height='150' fill='%23f8f9fc'/%3E%3Ctext x='75' y='75' text-anchor='middle' dy='0.35em' fill='%23858796' font-family='Arial' font-size='14'%3ENo Photo%3C/text%3E%3C/svg%3E";
}

// Get the actual photo path
$photo_path = getTeacherPhotoPath($teacher['teacher_image'], $teacher['id']);
$default_photo = getDefaultPhotoPath();
$show_default = ($photo_path === null);


// Define timings based on batch
$timings_html = '';
if (!empty($teacher['batch'])) {
    if ($teacher['batch'] === 'Morning') {
        $timings_html = "
            <div><strong>Mon-Fri:</strong> 7:00 AM - 2:00 PM</div>
            <div><strong>Saturday:</strong> 7:00 AM - 12:00 PM</div>
            <div><strong>Sunday:</strong> Holiday</div>
        ";
    } elseif ($teacher['batch'] === 'Evening') {
        $timings_html = "
            <div><strong>Mon-Fri:</strong> 11:00 AM - 6:00 PM</div>
            <div><strong>Saturday:</strong> 11:00 AM - 4:00 PM</div>
            <div><strong>Sunday:</strong> Holiday</div>
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
    <title>View Teacher - <?php echo htmlspecialchars($teacher['teacher_name']); ?></title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .view-photo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid #e3e6f0;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .photo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .salary-display {
            font-size: 1.2em;
            font-weight: bold;
            color: #1cc88a;
        }

        .info-row {
            border-bottom: 1px solid #e3e6f0;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .card-body {
            padding: 1.25rem;
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
                        <h1 class="h3 mb-0 text-gray-800">Teacher Details</h1>
                        <div>
                            <a href="teacher_list.php" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                            <a href="edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit fa-sm"></i> Edit Teacher</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-camera"></i> Teacher Photo</h6>
                                </div>
                                <div class="card-body">
                                    <div class="photo-container">
                                        <?php if (!$show_default): ?>
                                            <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="<?php echo htmlspecialchars($teacher['teacher_name']); ?>" class="view-photo" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($default_photo); ?>';">
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($default_photo); ?>" alt="Default Teacher Avatar" class="view-photo" style="opacity: 0.8;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-center">
                                        <small class="text-muted"><?php echo !$show_default ? 'Teacher Photo' : 'Default Avatar'; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-tie"></i> Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Teacher ID:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['id']); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Name:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['teacher_name'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Email:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['email']); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Phone:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['phone']); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Address:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['address']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-user-md"><i class="fas fa-user-md"></i> Personal Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">DOB:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['dob']); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Gender:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['gender']); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Blood Group:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($teacher['blood_group']); ?></div>
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
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Assigned Batch:</div>
                                        <div class="col-sm-8"><span class="badge badge-<?php echo ($teacher['batch'] == 'Morning') ? 'primary' : 'warning'; ?>"><?php echo htmlspecialchars($teacher['batch'] ?? 'N/A'); ?></span></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Work Timings:</div>
                                        <div class="col-sm-8"><?php echo $timings_html; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-briefcase"></i> Professional & School Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="row">
                                                    <div class="col-sm-5 font-weight-bold">School Name:</div>
                                                    <div class="col-sm-7"><?php echo htmlspecialchars($teacher['school_name']); ?></div>
                                                </div>
                                            </div>
                                            <div class="info-row">
                                                <div class="row">
                                                    <div class="col-sm-5 font-weight-bold">School Email:</div>
                                                    <div class="col-sm-7"><?php echo htmlspecialchars($teacher['school_email']); ?></div>
                                                </div>
                                            </div>
                                            <div class="info-row">
                                                <div class="row">
                                                    <div class="col-sm-5 font-weight-bold">School Phone:</div>
                                                    <div class="col-sm-7"><?php echo htmlspecialchars($teacher['school_phone']); ?></div>
                                                </div>
                                            </div>
                                            <div class="info-row">
                                                <div class="row">
                                                    <div class="col-sm-5 font-weight-bold">School Address:</div>
                                                    <div class="col-sm-7"><?php echo htmlspecialchars($teacher['school_address']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="row">
                                                    <div class="col-sm-5 font-weight-bold">Qualification:</div>
                                                    <div class="col-sm-7"><?php echo htmlspecialchars($teacher['qualification']); ?></div>
                                                </div>
                                            </div>
                                            <div class="info-row">
                                                <div class="row">
                                                    <div class="col-sm-5 font-weight-bold">Subject:</div>
                                                    <div class="col-sm-7"><?php echo htmlspecialchars($teacher['subject']); ?></div>
                                                </div>
                                            </div>
                                            <div class="info-row">
                                                <div class="row">
                                                    <div class="col-sm-5 font-weight-bold">Teaching Standards:</div>
                                                    <div class="col-sm-7"><?php echo htmlspecialchars($teacher['std']); ?></div>
                                                </div>
                                            </div>
                                            <div class="info-row">
                                                <div class="row">
                                                    <div class="col-sm-5 font-weight-bold">Experience:</div>
                                                    <div class="col-sm-7"><?php echo htmlspecialchars($teacher['experience']); ?> Years</div>
                                                </div>
                                            </div>

                                            <div class="info-row">
                                                <div class="row">
                                                    <div class="col-sm-5 font-weight-bold">Is Class Teacher:</div>
                                                    <div class="col-sm-7">
                                                        <?php if ($teacher['class_teacher'] == 1): ?>
                                                            <span class="badge badge-success">Yes</span>
                                                            <?php if (!empty($teacher['class_teacher_std'])): ?>
                                                                <br><small class="text-muted">(Standard: <?php echo htmlspecialchars($teacher['class_teacher_std']); ?>)</small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">No</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="info-row">
                                                <div class="row">
                                                    <div class="col-sm-5 font-weight-bold">Salary:</div>
                                                    <div class="col-sm-7 salary-display">₹<?php echo number_format($teacher['salary'], 2); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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