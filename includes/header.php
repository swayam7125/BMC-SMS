<?php
// Corrected absolute paths for both files for reliability
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';

// For debugging - KEEP THIS DURING DEVELOPMENT
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- CORRECTED: Using PDO to mark notification as read ---
if (isset($_GET['notif_id'])) {
    $notification_id_to_mark = filter_var($_GET['notif_id'], FILTER_VALIDATE_INT);
    $current_user_id_for_notif = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

    if ($notification_id_to_mark && $current_user_id_for_notif && isset($conn)) {
        try {
            $stmt_mark_read = $conn->prepare('UPDATE "notifications" SET "is_read" = true WHERE "id" = ? AND "user_id" = ?');
            $stmt_mark_read->execute([$notification_id_to_mark, $current_user_id_for_notif]);
        } catch (PDOException $e) {
            error_log("Failed to mark notification as read: " . $e->getMessage());
        }
    }
}

if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// Set default values
$userName = 'Guest';
$user_role = 'User';
$userProfileImage = BASE_WEB_PATH . 'assets/images/undraw_profile.svg';
$isLoggedIn = false;
$notifications = [];
$unread_count = 0;
$current_user_id = null;

if (isset($_COOKIE['encrypted_user_id'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
    $isLoggedIn = true;
}
if (isset($_COOKIE['encrypted_user_role'])) {
    $user_role = decrypt_id($_COOKIE['encrypted_user_role']);
    if ($user_role === 'superadmin') {
        $userName = 'Super Admin';
    } else {
        if (isset($_COOKIE['encrypted_user_name'])) $userName = decrypt_id($_COOKIE['encrypted_user_name']);
        if (isset($_COOKIE['encrypted_profile_image'])) {
            $decrypted_image_relative_path = decrypt_id($_COOKIE['encrypted_profile_image']);
            $userProfileImage = BASE_WEB_PATH . ltrim($decrypted_image_relative_path, '/');
        }
    }
}

if ($isLoggedIn && $current_user_id && isset($conn)) {
    try {
        // --- CORRECTED: Using PDO to fetch notifications ---
        $stmt_notifications = $conn->prepare('SELECT "id", "message", "link", "type", "created_at" FROM "notifications" WHERE "user_id" = ? AND "is_read" = false ORDER BY "created_at" DESC LIMIT 5');
        $stmt_notifications->execute([$current_user_id]);
        $notifications = $stmt_notifications->fetchAll(PDO::FETCH_ASSOC);

        $stmt_count = $conn->prepare('SELECT COUNT(*) FROM "notifications" WHERE "user_id" = ? AND "is_read" = false');
        $stmt_count->execute([$current_user_id]);
        $unread_count = $stmt_count->fetchColumn();
    } catch (PDOException $e) {
        error_log("Notification fetch error: " . $e->getMessage());
    }
}

function getNotificationIcon($type) {
    $icons = [
        'borrow_status' => 'fas fa-book-reader text-white', 'borrow_request' => 'fas fa-hand-holding-hand text-white',
        'leave_request' => 'fas fa-calendar-plus text-white', 'new_notice' => 'fas fa-file-alt text-white',
        'principal_notice' => 'fas fa-user-tie text-white', 'leave_status' => 'fas fa-check-circle text-white',
        'school_notice' => 'fas fa-chalkboard-teacher text-white', 'new_assignment' => 'fas fa-file-signature text-white',
        'marks_uploaded' => 'fas fa-award text-white', 'exam_timetable' => 'fas fa-calendar-alt text-white',
        'new_notes' => 'fas fa-sticky-note text-white', 'result_published' => 'fas fa-poll-h text-white'
    ];
    return $icons[$type] ?? 'fas fa-bell text-white';
}
?>
<style>
    .search-results-container {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1000;
        background-color: white;
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        max-height: 300px;
        overflow-y: auto;
        display: none;
    }
    .search-results-container a {
        display: block;
        padding: 0.75rem 1.5rem;
        color: #3a3b45;
        text-decoration: none;
        border-bottom: 1px solid #e3e6f0;
    }
    .search-results-container a:last-child {
        border-bottom: none;
    }
    .search-results-container a:hover {
        background-color: #f8f9fc;
    }
    .search-results-container .no-results {
        padding: 0.75rem 1.5rem;
        color: #858796;
        text-align: center;
    }
</style>
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <div class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search position-relative">
        <div class="input-group">
            <input type="text" id="pageSearchInput" class="form-control bg-light border-0 small" placeholder="Search for pages..."
                aria-label="Search">
            <div class="input-group-append">
                <button class="btn btn-primary" type="button">
                    <i class="fas fa-search fa-sm"></i>
                </button>
            </div>
        </div>
        <div id="pageSearchResults" class="search-results-container"></div>
    </div>
    <ul class="navbar-nav ml-auto">

        <li class="nav-item dropdown no-arrow d-sm-none">
            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-search fa-fw"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                aria-labelledby="searchDropdown">
                <form class="form-inline mr-auto w-100 navbar-search">
                    <div class="input-group">
                        <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
                            aria-label="Search" aria-describedby="basic-addon2">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </li>

        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell fa-fw"></i>
                <?php if ($unread_count > 0): ?>
                <span class="badge badge-danger badge-counter">
                    <?php echo ($unread_count > 5) ? '5+' : $unread_count; ?>
                </span>
                <?php endif; ?>
            </a>
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="alertsDropdown">
                <div class="dropdown-header d-flex justify-content-between align-items-center">
                    <h6 class="font-weight-semibold mb-0">Alerts Center</h6>
                    <?php if ($unread_count > 0): ?>
                    <a href="#" class="text-decoration-none" id="clear-all-notifications-btn" style="color: #ffffff; font-size: 0.85rem;">Clear All</a>
                    <?php endif; ?>
                </div>

                <div id="notification-items-container">
                    <?php if (empty($notifications)): ?>
                    <a class="dropdown-item d-flex align-items-center" href="#">
                        <div class="mr-3">
                            <div class="icon-circle bg-secondary">
                                <i class="fas fa-info-circle text-white"></i>
                            </div>
                        </div>
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
                        <div class="mr-3">
                            <div class="icon-circle bg-primary">
                                <i class="<?php echo getNotificationIcon($notification['type']); ?>"></i>
                            </div>
                        </div>
                        <div>
                            <div class="small text-gray-500">
                                <?php echo date('F j, Y', strtotime($notification['created_at'])); ?></div>
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
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <span
                    class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($userName); ?></span>
                <img class="img-profile rounded-circle" src="<?php echo htmlspecialchars($userProfileImage); ?>"
                    onerror="this.src='<?php echo BASE_WEB_PATH; ?>assets/images/undraw_profile.svg';" alt="Profile"
                    style="width: 32px; height: 32px; object-fit: cover;">
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <?php if ($user_role !== 'superadmin'): ?>
                <a class="dropdown-item" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                </a>
                <?php endif; ?>

                <a class="dropdown-item" href="#">
                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                    Settings
                </a>
                <a class="dropdown-item" href="#">
                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                    Activity Log
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>
        <?php endif; ?>
    </ul>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Messaging Script
    <?php if ($isLoggedIn && ($user_role === 'teacher' || $user_role === 'student')): ?>
    
    const messagesBadge = document.getElementById('messages-badge');
    const messagesLink = document.getElementById('messages-link');
    const base_url = '<?php echo BASE_WEB_PATH; ?>';

    function fetchUnreadMessageCount() {
        if (!messagesBadge) return; 

        let formData = new FormData();
        formData.append('action', 'get_unread_count');

        fetch(`${base_url}includes/messaging_api.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const count = parseInt(data.unread_count, 10);
                if (count > 0) {
                    messagesBadge.textContent = count > 9 ? '9+' : count;
                    messagesBadge.style.display = 'block';
                } else {
                    messagesBadge.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error fetching unread message count:', error));
    }

    fetchUnreadMessageCount();

    if (messagesLink) {
        messagesLink.addEventListener('click', function() {
            if (messagesBadge) {
                messagesBadge.style.display = 'none';
            }
            let formData = new FormData();
            formData.append('action', 'mark_all_messages_as_read');
            fetch(`${base_url}includes/messaging_api.php`, {
                method: 'POST',
                body: formData
            });
        });
    }
    <?php endif; ?>

    // "Clear All" Notifications Script
    const clearAllBtn = document.getElementById('clear-all-notifications-btn');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function(e) {
            e.preventDefault(); 

            fetch('<?php echo BASE_WEB_PATH; ?>includes/actions/clear_all_notifications.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const bellBadge = document.querySelector('#alertsDropdown .badge-counter');
                    if (bellBadge) {
                        bellBadge.style.display = 'none';
                    }

                    const container = document.getElementById('notification-items-container');
                    container.innerHTML = `
                        <a class="dropdown-item d-flex align-items-center" href="#">
                            <div class="mr-3">
                                <div class="icon-circle bg-secondary">
                                    <i class="fas fa-info-circle text-white"></i>
                                </div>
                            </div>
                            <div>
                                <div class="small text-gray-500"><?php echo date('F j, Y'); ?></div>
                                All caught up! No new notifications.
                            </div>
                        </a>`;

                    clearAllBtn.style.display = 'none';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error clearing notifications:', error));
        });
    }
});
</script>

<div class="modal fade" id="clearNotificationsModal" tabindex="-1" role="dialog" aria-labelledby="clearNotificationsModalLabel" aria-hidden="true"></div>