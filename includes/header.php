<?php
// Corrected absolute paths for both files for reliability
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// Set default values for a logged-out user
$userName = 'Guest';
$user_role = 'User';
$userProfileImage = BASE_WEB_PATH . 'assets/images/undraw_profile.svg';
$isLoggedIn = false;
$current_user_id = null;

// If the user is logged in, determine their details
if (isset($_COOKIE['encrypted_user_id'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (isset($_COOKIE['encrypted_user_role'])) {
    $isLoggedIn = true;
    $user_role = decrypt_id($_COOKIE['encrypted_user_role']);

    if ($user_role === 'superadmin') {
        $userName = 'Super Admin';
    } else {
        if (isset($_COOKIE['encrypted_user_name'])) {
            $userName = decrypt_id($_COOKIE['encrypted_user_name']);
        }
        if (isset($_COOKIE['encrypted_profile_image'])) {
            $decrypted_image_relative_path = decrypt_id($_COOKIE['encrypted_profile_image']);
            $image_path_for_web = BASE_WEB_PATH . ltrim($decrypted_image_relative_path, '/');
            $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $image_path_for_web;
            if (!empty($decrypted_image_relative_path) && file_exists($filesystem_path) && is_file($filesystem_path)) {
                $userProfileImage = $image_path_for_web;
            }
        }
    }
}

// --- CORRECTED: Dynamic Notification Logic with PDO ---
$notifications = [];
$unread_count = 0;

if ($isLoggedIn && $current_user_id && isset($conn)) {
    try {
        $stmt_notifications = $conn->prepare('SELECT "id", "message", "link", "type", "created_at" FROM "notifications" WHERE "user_id" = ? AND "is_read" = false ORDER BY "created_at" DESC LIMIT 5');
        $stmt_notifications->execute([$current_user_id]);
        $notifications = $stmt_notifications->fetchAll(PDO::FETCH_ASSOC);

        $stmt_count = $conn->prepare('SELECT COUNT(*) FROM "notifications" WHERE "user_id" = ? AND "is_read" = false');
        $stmt_count->execute([$current_user_id]);
        $unread_count = $stmt_count->fetchColumn();
    } catch (PDOException $e) {
        // Handle potential database errors gracefully
        error_log("Error fetching notifications: " . $e->getMessage());
    }
}

function getNotificationIcon($type) {
    switch ($type) {
        case 'leave_request': return 'fas fa-calendar-plus text-white';
        case 'new_notice': return 'fas fa-file-alt text-white';
        case 'principal_notice': return 'fas fa-user-tie text-white';
        case 'leave_status': return 'fas fa-check-circle text-white';
        case 'school_notice': return 'fas fa-chalkboard-teacher text-white';
        case 'new_assignment': return 'fas fa-file-signature text-white';
        case 'marks_uploaded': return 'fas fa-award text-white';
        case 'exam_timetable': return 'fas fa-calendar-alt text-white';
        case 'new_notes': return 'fas fa-sticky-note text-white';
        case 'result_published': return 'fas fa-poll-h text-white';
        default: return 'fas fa-bell text-white';
    }
}
?>
<!-- The rest of the HTML for the header remains the same -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3"><i class="fa fa-bars"></i></button>
    <div class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search position-relative">
        <div class="input-group">
            <input type="text" id="pageSearchInput" class="form-control bg-light border-0 small" placeholder="Search for pages..." aria-label="Search">
            <div class="input-group-append"><button class="btn btn-primary" type="button"><i class="fas fa-search fa-sm"></i></button></div>
        </div>
        <div id="pageSearchResults" class="search-results-container"></div>
    </div>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell fa-fw"></i>
                <?php if ($unread_count > 0): ?>
                <span class="badge badge-danger badge-counter"><?php echo ($unread_count > 5) ? '5+' : $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
                <div class="dropdown-header d-flex justify-content-between align-items-center">
                    <h6 class="font-weight-semibold mb-0">Alerts Center</h6>
                    <?php if ($unread_count > 0): ?>
                    <a href="#" class="text-decoration-none" id="clear-all-notifications-btn" style="color: #ffffff; font-size: 0.85rem;">Clear All</a>
                    <?php endif; ?>
                </div>
                <div id="notification-items-container">
                    <?php if (empty($notifications)): ?>
                    <a class="dropdown-item d-flex align-items-center" href="#">
                        <div class="mr-3"><div class="icon-circle bg-secondary"><i class="fas fa-info-circle text-white"></i></div></div>
                        <div>
                            <div class="small text-gray-500"><?php echo date('F j, Y'); ?></div>
                            No new notifications.
                        </div>
                    </a>
                    <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                    <?php
                        $base_link = htmlspecialchars(BASE_WEB_PATH . ltrim($notification['link'], '/'));
                        $separator = (strpos($base_link, '?') === false) ? '?' : '&';
                        $final_link = $base_link . $separator . 'notif_id=' . $notification['id'];
                    ?>
                    <a class="dropdown-item d-flex align-items-center" href="<?php echo $final_link; ?>">
                        <div class="mr-3"><div class="icon-circle bg-primary"><i class="<?php echo getNotificationIcon($notification['type']); ?>"></i></div></div>
                        <div>
                            <div class="small text-gray-500"><?php echo date('F j, Y', strtotime($notification['created_at'])); ?></div>
                            <span class="font-weight-bold"><?php echo htmlspecialchars($notification['message']); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a class="dropdown-item text-center small text-gray-500" href="/BMC-SMS/notification_history.php">Show All Notifications</a>
            </div>
        </li>
        <?php if ($user_role === 'teacher' || $user_role === 'student'): ?>
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link" id="messages-link" href="<?php echo BASE_WEB_PATH . 'pages/' . $user_role . '/message.php'; ?>" role="button">
                <i class="fas fa-comments fa-fw"></i>
                <span class="badge badge-danger badge-counter" id="messages-badge" style="display: none;"></span>
            </a>
        </li>
        <?php endif; ?>
        <div class="topbar-divider d-none d-sm-block"></div>
        <?php if ($isLoggedIn): ?>
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($userName); ?></span>
                <img class="img-profile rounded-circle" src="<?php echo htmlspecialchars($userProfileImage); ?>" onerror="this.src='<?php echo BASE_WEB_PATH; ?>assets/images/undraw_profile.svg';" alt="Profile" style="width: 32px; height: 32px; object-fit: cover;">
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <?php if ($user_role !== 'superadmin'): ?>
                <a class="dropdown-item" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php"><i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile</a>
                <?php endif; ?>
                <a class="dropdown-item" href="#"><i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i> Settings</a>
                <a class="dropdown-item" href="#"><i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i> Activity Log</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout</a>
            </div>
        </li>
        <?php endif; ?>
    </ul>
</nav>
