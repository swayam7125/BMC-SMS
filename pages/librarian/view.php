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

$librarian_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($librarian_id <= 0) {
    header("Location: librarian_list.php?error=Invalid librarian ID");
    exit;
}

$librarian = null;
$timings = [];

try {
    // Fetch main librarian details
    $query_librarian = 'SELECT l.*, s.school_name
                        FROM "librarian" l
                        LEFT JOIN "school" s ON l.school_id = s.id
                        WHERE l.id = ?';
    $stmt_librarian = $conn->prepare($query_librarian);
    $stmt_librarian->execute([$librarian_id]);
    $librarian = $stmt_librarian->fetch(PDO::FETCH_ASSOC);

    if (!$librarian) {
        header("Location: librarian_list.php?error=Librarian not found");
        exit;
    }
    
    // Fetch librarian timings and key by day_of_week for easy access
    $stmt_timings = $conn->prepare('SELECT * FROM "librarian_timings" WHERE "librarian_id" = ?');
    $stmt_timings->execute([$librarian_id]);
    $timings_result = $stmt_timings->fetchAll(PDO::FETCH_ASSOC);
    foreach($timings_result as $row){
        $timings[$row['day_of_week']] = $row;
    }

    // Photo path logic
    $photo_path = $librarian['librarian_image'];
    $default_photo = '/BMC-SMS/assets/img/default-user.jpg';
    $server_photo_path = !empty($photo_path) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $photo_path : '';
    if (empty($photo_path) || !file_exists($server_photo_path)) {
        $photo_path = $default_photo;
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Librarian - <?php echo htmlspecialchars($librarian['librarian_name']); ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/librarian_view.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
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
                        <h1 class="h3 mb-0 text-gray-800">Librarian Details</h1>
                        <div>
                            <a href="librarian_list.php" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                            <a href="edit.php?id=<?php echo $librarian['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit fa-sm"></i> Edit Librarian</a>
                        </div>
                    </div>
                     <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Librarian details updated successfully!</div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-camera"></i> Librarian Photo</h6></div>
                                <div class="card-body text-center">
                                    <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="<?php echo htmlspecialchars($librarian['librarian_name']); ?>" class="view-photo">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-tie"></i> Basic Information</h6></div>
                                <div class="card-body">
                                    <div class="row"><div class="col-sm-4 info-label">Name:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['librarian_name'] ?? 'N/A'); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Email:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['email']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Phone:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['phone']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">DOB:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars(date("d M Y", strtotime($librarian['dob']))); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Gender:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['gender']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Blood Group:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['blood_group']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Address:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['address']); ?></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-info"><i class="fas fa-briefcase"></i> Professional Details</h6></div>
                                <div class="card-body">
                                    <div class="row"><div class="col-sm-4 info-label">School Name:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['school_name']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Qualification:</div><div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['qualification']); ?></div></div><hr>
                                    <div class="row"><div class="col-sm-4 info-label">Salary:</div><div class="col-sm-8 info-value font-weight-bold text-success">₹<?php echo number_format($librarian['salary'], 2); ?></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-warning"><i class="fas fa-clock"></i> Weekly Timings</h6></div>
                                <div class="card-body">
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day):
                                        $timing = $timings[$day] ?? null;
                                    ?>
                                        <div class="row align-items-center">
                                            <div class="col-sm-4 info-label"><?php echo $day; ?>:</div>
                                            <div class="col-sm-8 info-value">
                                                <?php if ($timing && $timing['is_closed']): ?>
                                                    <span class="badge badge-secondary">Closed</span>
                                                <?php elseif ($timing && !empty($timing['opens_at']) && !empty($timing['closes_at'])): ?>
                                                    <?php echo date('g:i A', strtotime($timing['opens_at'])) . ' - ' . date('g:i A', strtotime($timing['closes_at'])); ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($day !== 'Sunday') echo '<hr class="my-2">'; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
<?php $conn = null; ?>
