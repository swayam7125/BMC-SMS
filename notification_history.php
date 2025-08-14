<?php
// Corrected absolute paths for reliability
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to login if not logged in
if (!isset($_COOKIE['encrypted_user_id'])) {
    header("Location: /BMC-SMS/login.php");
    exit;
}

$userId = decrypt_id($_COOKIE['encrypted_user_id']);

// --- START: Notification Fetching and Filtering Logic ---
$all_notifications = [];
$notification_types = [];
$params = [$userId];

// This query correctly fetches ALL notifications for the user, both read and unread.
$sql = "SELECT id, message, link, type, created_at, is_read FROM notifications WHERE user_id = ?";

// Get filter values from GET request
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Append filters to the SQL query
if (!empty($filter_type)) {
    $sql .= " AND type = ?";
    $params[] = $filter_type;
}
if (!empty($start_date)) {
    $sql .= " AND created_at::DATE >= ?";
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $sql .= " AND created_at::DATE <= ?";
    $params[] = $end_date;
}

$sql .= " ORDER BY created_at DESC";

try {
    // Fetch filtered notifications using PDO
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $all_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch distinct notification types for the filter dropdown
    $stmt_types = $conn->prepare("SELECT DISTINCT type FROM notifications WHERE user_id = ? ORDER BY type ASC");
    $stmt_types->execute([$userId]);
    $notification_types = $stmt_types->fetchAll(PDO::FETCH_COLUMN, 0);

} catch (PDOException $e) {
    error_log("Notification History Error: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

// Re-use the function from header.php if it's not already defined
if (!function_exists('getNotificationIcon')) {
    function getNotificationIcon($type) {
        switch ($type) {
            case 'borrow_status': return 'fas fa-book-reader text-white';
            case 'borrow_request': return 'fas fa-hand-holding-hand text-white';
            case 'leave_request': return 'fas fa-calendar-plus text-white';
            case 'new_notice': return 'fas fa-file-alt text-white';
            case 'principal_notice': return 'fas fa-user-tie text-white';
            case 'principal_to_librarian_notice': return 'fas fa-user-graduate text-white';
            case 'leave_status': return 'fas fa-check-circle text-white';
            case 'school_notice': return 'fas fa-chalkboard-teacher text-white';
            case 'new_assignment': return 'fas fa-file-signature text-white';
            case 'marks_uploaded': return 'fas fa-award text-white';
            case 'exam_timetable': return 'fas fa-calendar-alt text-white';
            case 'new_notes': return 'fas fa-sticky-note text-white';
            case 'result_published': return 'fas fa-poll-h text-white';
            case 'acquisition_request': return 'fas fa-inbox text-white';
            case 'acquisition_status': return 'fas fa-check-circle text-white';
            default: return 'fas fa-bell text-white';
        }
    }
}
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Notification History</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sidebar.css" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/scrollbar_hidden.css" rel="stylesheet">
    <style>
        .icon-circle { height: 2.5rem; width: 2.5rem; border-radius: 100%; display: flex; align-items: center; justify-content: center; }
        .filter-toolbar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; }
        .filter-inputs { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Notification History</h1>
                        <a href="/BMC-SMS/dashboard.php" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Dashboard</a>
                    </div>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="notification_history.php" method="GET">
                                <div class="filter-toolbar">
                                    <div class="filter-inputs">
                                        <div class="input-group">
                                            <div class="input-group-prepend"><label class="input-group-text" for="filter_type">Type</label></div>
                                            <select id="filter_type" name="filter_type" class="custom-select">
                                                <option value="">All Types</option>
                                                <?php foreach ($notification_types as $type): ?>
                                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($filter_type == $type) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $type))); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="input-group">
                                             <div class="input-group-prepend"><span class="input-group-text">Date Range</span></div>
                                            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>" title="Start Date">
                                            <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>" title="End Date">
                                        </div>
                                    </div>
                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Apply Filters</button>
                                        <a href="notification_history.php" class="btn btn-outline-secondary"><i class="fas fa-undo"></i> Reset</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">All Notifications</h6></div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php if (empty($all_notifications)): ?>
                                    <div class="list-group-item text-center text-gray-500 py-4">No notifications found matching your criteria.</div>
                                <?php else: ?>
                                    <?php foreach ($all_notifications as $notification): ?>
                                        <?php
                                            $base_link = htmlspecialchars(BASE_WEB_PATH . ltrim($notification['link'], '/'));
                                            $is_unread = !$notification['is_read'];
                                            
                                            // Add a special class and data-attribute for unread notifications to be handled by JS
                                            $link_class = $is_unread ? 'notification-history-link unread' : '';
                                            $data_attr = $is_unread ? 'data-notif-id="' . htmlspecialchars($notification['id']) . '"' : '';

                                            $icon_class = getNotificationIcon($notification['type']);
                                            $bgClass = $is_unread ? 'bg-light' : '';
                                            $iconBgClass = $is_unread ? 'bg-primary' : 'bg-success';
                                            $fontWeightClass = $is_unread ? 'font-weight-bold' : '';
                                        ?>
                                        <a href="<?php echo $base_link; ?>" class="list-group-item list-group-item-action d-flex align-items-center <?php echo $bgClass; ?> <?php echo $link_class; ?>" <?php echo $data_attr; ?>>
                                            <div class="mr-3">
                                                <div class="icon-circle <?php echo $iconBgClass; ?>">
                                                    <i class="<?php echo $icon_class; ?>"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="small text-gray-500"><?php echo date('F j, Y, g:i a', strtotime($notification['created_at'])); ?></div>
                                                <span class="<?php echo $fontWeightClass; ?>"><?php echo htmlspecialchars($notification['message']); ?></span>
                                            </div>
                                            <div class="ml-3">
                                                <span class="badge badge-light p-2"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $notification['type']))); ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/BMC-SMS/includes/logout_modal.php" ?>
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const base_api_path = '<?php echo BASE_WEB_PATH; ?>includes/actions/mark_notifications_as_read.php';

        // Add click handler for unread notifications on this page
        document.querySelectorAll('.notification-history-link.unread').forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault(); // Stop navigation
                
                const notifId = this.getAttribute('data-notif-id');
                const targetUrl = this.getAttribute('href');

                if (notifId) {
                    let formData = new FormData();
                    formData.append('notif_id', notifId);

                    // Call the API and then navigate, ensuring the read status is updated
                    fetch(base_api_path, {
                        method: 'POST',
                        body: formData,
                        keepalive: true
                    })
                    .catch(error => console.error('Error marking notification as read:', error))
                    .finally(() => {
                        window.location.href = targetUrl; // Navigate after the attempt
                    });
                } else {
                    window.location.href = targetUrl; // Fallback navigation
                }
            });
        });
    });
    </script>
</body>
</html>
<?php $conn = null; ?>