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

// Get teacher ID from URL
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($teacher_id <= 0) {
    header("Location: teacher_list.php?error=Invalid teacher ID");
    exit;
}

// Fetch teacher data with school information
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

// The image path from enrollment is a full relative path.
// This function checks if it exists, otherwise provides a default.
function getTeacherPhotoPath($teacher_image)
{
    // The path stored during enrollment is `../../pages/teacher/uploads/filename.ext`
    // We assume this path is correct relative to the file that saved it.
    // When viewing from `pages/teacher/view.php`, the path needs adjustment.
    $view_path = "../../pages/teacher/" . str_replace('../../pages/teacher/', '', $teacher_image);

    if (!empty($teacher_image) && file_exists($view_path)) {
        return $view_path;
    }
    
    // Fallback if the stored path is broken or image is missing
    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150' viewBox='0 0 150 150'%3E%3Crect width='150' height='150' fill='%23f8f9fc'/%3E%3Ctext x='75' y='75' text-anchor='middle' dy='0.35em' fill='%23858796' font-family='Arial' font-size='14'%3ENo Photo%3C/text%3E%3C/svg%3E";
}

$photo_path = getTeacherPhotoPath($teacher['teacher_image']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Teacher - <?php echo htmlspecialchars($teacher['teacher_name']); ?></title>

    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .teacher-photo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid #e3e6f0;
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
        <?php include_once '../../includes/sidebar/BMC_sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header/BMC_header.php'; ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Teacher Details</h1>
                        <div>
                            <a href="teacher_list.php" class="btn btn-secondary btn-sm mr-2">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <a href="edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit Teacher
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-camera"></i> Teacher Photo</h6>
                                </div>
                                <div class="card-body">
                                    <div class="photo-container">
                                        <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="<?php echo htmlspecialchars($teacher['teacher_name']); ?>" class="teacher-photo">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-tie"></i> Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Teacher ID:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['id']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Name:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['teacher_name'] ?? 'N/A'); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Email:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['email']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Phone:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['phone']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">DOB:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['dob']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Gender:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['gender']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Blood Group:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['blood_group']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Address:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['address']); ?></div></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-briefcase"></i> Professional Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Qualification:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['qualification']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Subject:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['subject']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Languages Known:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['language_known']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Teaching Standards:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['std']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Experience:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['experience']); ?> Years</div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">Salary:</div><div class="col-sm-8 salary-display">₹<?php echo number_format($teacher['salary'], 2); ?></div></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-school"></i> School Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row"><div class="col-sm-4 font-weight-bold">School Name:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['school_name']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">School Email:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['school_email']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">School Phone:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['school_phone']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 font-weight-bold">School Address:</div><div class="col-sm-8"><?php echo htmlspecialchars($teacher['school_address']); ?></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer/BMC_footer.php'; ?>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>