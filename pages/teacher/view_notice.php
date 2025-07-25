<?php
include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;
$schoolId = null;
$studentStd = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Redirect if user is not properly logged in
if (!$role || !$userId) {
    header("Location: ../login.php");
    exit;
}

// Fetch user-specific data (school_id, std for students, or just school_id for teachers)
if ($role == 'student') {
    $stmt = $conn->prepare("SELECT school_id, std FROM student WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $schoolId = $row['school_id'];
        $studentStd = $row['std'];
    }
    $stmt->close();
} elseif ($role == 'teacher') {
    $stmt = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $schoolId = $row['school_id'];
    }
    $stmt->close();
}

$notices = [];

// New query to join content and recipient tables
$sql = "SELECT DISTINCT c.title, c.content, c.file_path, c.original_filename, c.created_at
        FROM school_notices_content c
        JOIN school_notice_recipients r ON c.id = r.notice_id
        WHERE c.school_id = ?";

$params = [$schoolId];
$types = "i";

// Add condition based on user role
if ($role == 'teacher') {
    $sql .= " AND r.recipient_type = 'teacher' AND r.recipient_identifier = ?";
    $params[] = $userId;
    $types .= "s"; // Identifier is stored as varchar
} elseif ($role == 'student' && $studentStd) {
    $sql .= " AND r.recipient_type = 'standard' AND r.recipient_identifier = ?";
    $params[] = $studentStd;
    $types .= "s"; // Identifier is stored as varchar
} else {
    // If role is not matched, prevent fetching any notices
    $sql .= " AND 1=0"; 
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if($stmt){
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notices[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'View School Notices';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400i,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">School Notices</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Notice Feed</h6>
                        </div>
                        <div class="card-body">
                           <?php if (empty($notices)): ?>
                                <div class="text-center">No notices have been sent to you yet.</div>
                            <?php else: ?>
                                <?php foreach ($notices as $notice): ?>
                                    <div class="card mb-3">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($notice['title']); ?></h5>
                                            <small class="text-muted">Posted on: <?php echo date('d-m-Y H:i', strtotime($notice['created_at'])); ?></small>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars($notice['content'])); ?></p>
                                            <?php if ($notice['file_path']): ?>
                                                <hr>
                                                <a href="<?php echo htmlspecialchars($notice['file_path']); ?>" class="btn btn-success btn-sm" download="<?php echo htmlspecialchars($notice['original_filename']); ?>">
                                                    <i class="fas fa-download"></i> Download Attachment
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                           <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <?php
$conn->close();
?>
</body>
</html>