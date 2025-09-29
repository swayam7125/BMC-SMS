<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

define('BASE_PHYSICAL_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_WEB_PATH);

$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

$school_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($school_id <= 0) {
    header("Location: school_list.php?error=Invalid school ID");
    exit;
}

function getWebAccessibleImagePath($db_path)
{
    if (empty($db_path)) return null;

    $physical_path_full = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $db_path;
    if (strpos($db_path, BASE_WEB_PATH) === 0 && file_exists($physical_path_full)) {
        return htmlspecialchars($db_path);
    }

    $physical_path_relative = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . BASE_WEB_PATH . ltrim($db_path, '/');
    if (file_exists($physical_path_relative)) {
        return htmlspecialchars(BASE_WEB_PATH . ltrim($db_path, '/'));
    }

    return null;
}

function getDefaultImagePath($type = 'school')
{
    return BASE_WEB_PATH . 'assets/images/' . ($type === 'school' ? 'default-school.png' : 'unisex.png');
}

function cleanPgArray($pg_array_string)
{
    $trimmed_string = trim($pg_array_string, '{}');
    $items = preg_split('/,(?=(?:(?:[^"]*"){2})*[^"]*$)/', $trimmed_string);
    $cleaned_items = array_map(function ($item) {
        return trim($item, ' "');
    }, $items);
    return implode(', ', $cleaned_items);
}

$school = null;
$principals = [];
try {
    // Query 1: Fetch school details
    $query_school = "SELECT * FROM school WHERE id = :id";
    $stmt_school = $conn->prepare($query_school);
    $stmt_school->execute(['id' => $school_id]);
    $school = $stmt_school->fetch(PDO::FETCH_ASSOC);

    if (!$school) {
        header("Location: school_list.php?error=School not found");
        exit;
    }

    // Query 2: Fetch all principals for the school
    $query_principals = "SELECT p.* FROM principal p WHERE p.school_id = :school_id";
    $stmt_principals = $conn->prepare($query_principals);
    $stmt_principals->execute(['school_id' => $school_id]);
    $principals = $stmt_principals->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

$school_logo_web_path = getWebAccessibleImagePath($school['school_logo']);
$default_school_logo = getDefaultImagePath('school');

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
    <link rel="stylesheet" href="../../assets/css/responsive.css" />

</head>

<body id="page-top">
    <div id="wrapper">
        <?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?> <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
if (!$is_ajax_request) {
    include '../../includes/header.php';
}
?> <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">School Details</h1>
                        <div>
                            <a href="school_list.php" class="btn btn-secondary btn-sm mr-2"><i
                                    class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                            <a href="edit.php?id=<?php echo $school['id']; ?>" class="btn btn-primary btn-sm"><i
                                    class="fas fa-edit fa-sm"></i> Edit School</a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-image"></i> School
                                        Logo</h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="photo-container">
                                        <img src="<?php echo $school_logo_web_path ?? $default_school_logo; ?>"
                                            alt="School Logo" class="view-image view-logo"
                                            onerror="this.onerror=null; this.src='<?php echo $default_school_logo; ?>';">
                                    </div>
                                    <div class="text-center">
                                        <small
                                            class="text-muted"><?php echo $school_logo_web_path ? 'School Logo' : 'Default Logo'; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i>
                                        Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">School ID:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($school['id']); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Name:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($school['school_name']); ?>
                                        </div>
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
                                        <div class="col-sm-8">
                                            <?php echo date("d M, Y", strtotime($school['school_opening'])); ?></div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-4 font-weight-bold">Address:</div>
                                        <div class="col-sm-8"><?php echo nl2br(htmlspecialchars($school['address'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-university"></i>
                                        Academic Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row info-row">
                                        <div class="col-sm-5 font-weight-bold">School Type:</div>
                                        <div class="col-sm-7"><?php echo htmlspecialchars($school['school_type']); ?>
                                        </div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-5 font-weight-bold">Education Board(s):</div>
                                        <div class="col-sm-7">
                                            <?php echo htmlspecialchars(cleanPgArray($school['education_board'])); ?>
                                        </div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-5 font-weight-bold">Medium(s):</div>
                                        <div class="col-sm-7">
                                            <?php echo htmlspecialchars(cleanPgArray($school['school_medium'])); ?>
                                        </div>
                                    </div>
                                    <div class="row info-row">
                                        <div class="col-sm-5 font-weight-bold">Categories:</div>
                                        <div class="col-sm-7">
                                            <?php echo htmlspecialchars(cleanPgArray($school['school_category'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-tie"></i>
                                        Principal(s) Information</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($principals)): ?>
                                    <div class="row">
                                        <?php foreach ($principals as $principal):
                                                $principal_photo_web_path = getWebAccessibleImagePath($principal['principal_image']);
                                                $default_principal_photo = getDefaultImagePath('principal');

                                                // Dynamic column classes based on the number of principals
                                                $col_classes = (count($principals) === 1) ? 'col-lg-6 offset-lg-3' : 'col-md-6';
                                            ?>
                                        <div class="<?php echo $col_classes; ?> mb-4">
                                            <div class="card shadow h-100">
                                                <div class="card-body text-center">
                                                    <img src="<?php echo $principal_photo_web_path ?? $default_principal_photo; ?>"
                                                        alt="Principal Photo" class="profile-photo mb-3 mt-3 h-50 w-50"
                                                        onerror="this.onerror=null; this.src='<?php echo $default_principal_photo; ?>';" />
                                                    <h4 class="mt-2 font-weight-bold text-gray-800">
                                                        <a
                                                            href="../principal/view.php?id=<?php echo $principal['id']; ?>">
                                                            <?php echo htmlspecialchars($principal['principal_name']); ?>
                                                        </a>
                                                    </h4>
                                                    <p class="text-muted">Assigned Principal
                                                        (<?php echo htmlspecialchars($principal['batch']); ?> Batch)</p>
                                                    <hr>
                                                    <div class="row info-row">
                                                        <div class="col-sm-5 font-weight-bold">Email:</div>
                                                        <div class="col-sm-7">
                                                            <?php echo htmlspecialchars($principal['email'] ?? 'N/A'); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row info-row">
                                                        <div class="col-sm-5 font-weight-bold">Phone:</div>
                                                        <div class="col-sm-7">
                                                            <?php echo htmlspecialchars($principal['phone'] ?? 'N/A'); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row info-row">
                                                        <div class="col-sm-5 font-weight-bold">Qualification:</div>
                                                        <div class="col-sm-7">
                                                            <?php echo htmlspecialchars($principal['qualification'] ?? 'N/A'); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row info-row">
                                                        <div class="col-sm-5 font-weight-bold">Batch:</div>
                                                        <div class="col-sm-7">
                                                            <span
                                                                class="col-sm-7<?php echo ($principal['batch'] ?? '')  ?>">
                                                                <?php echo htmlspecialchars($principal['batch'] ?? 'N/A'); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="row info-row">
                                                        <div class="col-sm-5 font-weight-bold">Salary:</div>
                                                        <div class="col-sm-7 salary-display">
                                                            ₹<?php echo number_format($principal['salary'] ?? 0, 2); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row info-row">
                                                        <div class="col-sm-5 font-weight-bold">Address:</div>
                                                        <div class="col-sm-7 info-value">
                                                            <?php echo nl2br(htmlspecialchars($principal['address'] ?? 'N/A')); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
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
if (!$is_ajax_request) {
    include '../../includes/footer.php';
}
?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>

</body>

</html>