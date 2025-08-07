<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Define the base web path for your project
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

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

try {
    $query = "SELECT s.*, p.id as principal_user_id, p.principal_name, p.principal_image 
              FROM school s 
              LEFT JOIN principal p ON s.id = p.school_id 
              WHERE s.id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $school_id]);
    $school = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$school) {
        header("Location: school_list.php?error=School not found");
        exit;
    }
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

// --- START: ROBUST IMAGE PATH HANDLING ---
function getWebAccessibleImagePath($db_path) {
    if (empty($db_path)) {
        return null;
    }
    // Construct the full physical path on the server from the web path
    $physical_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $db_path;
    
    // Check if the file actually exists at that physical path
    if (file_exists($physical_path) && is_file($physical_path)) {
        // Return the original web path if the file exists
        return htmlspecialchars($db_path);
    }
    
    return null; // Return null if file not found
}

function getDefaultImagePath($type = 'school') {
    return BASE_WEB_PATH . 'assets/img/' . ($type === 'school' ? 'default-school.png' : 'default-user.jpg');
}

$school_logo_web_path = getWebAccessibleImagePath($school['school_logo']);
$default_school_logo = getDefaultImagePath('school');

$principal_photo_web_path = getWebAccessibleImagePath($school['principal_image']);
$default_principal_photo = getDefaultImagePath('principal');
// --- END: ROBUST IMAGE PATH HANDLING ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View School - <?php echo htmlspecialchars($school['school_name']); ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/school_view.css">
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
                                        <img src="<?php echo $school_logo_web_path ?? $default_school_logo; ?>" 
                                             alt="School Logo" 
                                             class="view-image view-logo" 
                                             onerror="this.onerror=null; this.src='<?php echo $default_school_logo; ?>';">
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
                                        <div class="col-sm-7"><?php echo htmlspecialchars(str_replace(',', ', ', trim($school['education_board'], '{}'))); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-5 font-weight-bold">Medium(s):</div>
                                        <div class="col-sm-7"><?php echo htmlspecialchars(str_replace(',', ', ', trim($school['school_medium'], '{}'))); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-5 font-weight-bold">Categories:</div>
                                        <div class="col-sm-7"><?php echo htmlspecialchars(str_replace(',', ', ', trim($school['school_category'], '{}'))); ?></div>
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
                                    <?php if (!empty($school['principal_user_id'])): ?>
                                        <div class="photo-container">
                                            <img src="<?php echo $principal_photo_web_path ?? $default_principal_photo; ?>" 
                                                 alt="Principal Photo" 
                                                 class="view-image view-photo" 
                                                 onerror="this.onerror=null; this.src='<?php echo $default_principal_photo; ?>';">
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
    
    <?php include_once "../../includes/logout_modal.php"?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>