<?php
// Corrected absolute paths for reliability
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- START: CODE TO MARK INDIVIDUAL NOTIFICATION AS READ (PDO VERSION) ---
if (isset($_GET['notif_id'])) {
    $notification_id_to_mark = filter_var($_GET['notif_id'], FILTER_VALIDATE_INT);
    $current_user_id_for_notif = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

    if ($notification_id_to_mark && $current_user_id_for_notif && isset($conn)) {
        try {
            // PostgreSQL uses 'true' for boolean values
            $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = true WHERE id = ? AND user_id = ?");
            $stmt_mark_read->execute([$notification_id_to_mark, $current_user_id_for_notif]);
        } catch (PDOException $e) {
            error_log("Failed to mark notification as read: " . $e->getMessage());
        }
    }
}
// --- END: CODE ---


// Define BASE_WEB_PATH if it hasn't been defined already.
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// Set default values
$userName = 'Guest';
$user_role = 'User';
$userProfileImage = BASE_WEB_PATH . 'assets/images/unisex.png';
$isLoggedIn = false;

// Determine user details if logged in
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


// --- START: Dynamic Notification Logic (PDO VERSION) ---
$notifications = [];
$unread_count = 0;
$current_user_id = null;

if ($isLoggedIn && isset($_COOKIE['encrypted_user_id'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
    
    if ($current_user_id && isset($conn)) {
        try {
            // Fetch latest 5 unread notifications
            $stmt_notifications = $conn->prepare("SELECT id, message, link, type, created_at FROM notifications WHERE user_id = ? AND is_read = false ORDER BY created_at DESC LIMIT 5");
            $stmt_notifications->execute([$current_user_id]);
            $notifications = $stmt_notifications->fetchAll(PDO::FETCH_ASSOC);

            // Fetch the total count of unread notifications
            $stmt_count = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = false");
            $stmt_count->execute([$current_user_id]);
            $unread_count = (int) $stmt_count->fetchColumn();

        } catch (PDOException $e) {
            error_log("Header notification fetch error: " . $e->getMessage());
            // Fail gracefully, don't break the entire header
            $notifications = [];
            $unread_count = 0;
        }
    }
}

// --- FIX: Wrap the function in a check to prevent redeclaration and add new notification types ---
if (!function_exists('getNotificationIcon')) {
    // Function to determine notification icon based on type
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
                    onerror="this.src='<?php echo BASE_WEB_PATH; ?>assets/images/unisex.png';" alt="Profile"
                    style="width: 32px; height: 32px; object-fit: cover;">
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <?php if ($user_role !== 'superadmin'): ?>
                <a class="dropdown-item" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                </a>
                <?php endif; ?>

                <!-- <a class="dropdown-item" href="#">
                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                    Settings
                </a>
                <a class="dropdown-item" href="#">
                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                    Activity Log
                </a> -->
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

    // --- START: Search Bar Functionality ---
    const searchInput = document.getElementById('pageSearchInput');
    const searchResults = document.getElementById('pageSearchResults');
    const base_url_for_search = '<?php echo BASE_WEB_PATH; ?>';

    // A list of pages for the 'principal' role. This list can be expanded.
    const pages = [
        { title: 'Dashboard', url: 'dashboard.php' },
        { title: 'Enroll Teacher', url: 'includes/forms/teacher_enrollment.php' },
        { title: 'Teacher List', url: 'pages/teacher/teacher_list.php' },
        { title: 'Teacher Attendance', url: 'pages/principal/teacher_attendence.php' },
        { title: 'View Teacher Attendance', url: 'pages/principal/view_teacher_attendence.php' },
        { title: 'Enroll Librarian', url: 'includes/forms/librarian_enrollment.php' },
        { title: 'Librarian List', url: 'pages/librarian/librarian_list.php' },
        { title: 'Librarian Attendance', url: 'pages/principal/librarian_attendance.php' },
        { title: 'View Librarian Attendance', url: 'pages/principal/view_librarian_attendance.php' },
        { title: 'Enroll Student', url: 'includes/forms/student_enrollment.php' },
        { title: 'Student List', url: 'pages/student/student_list.php' },
        { title: 'Generate LC', url: 'pages/principal/generate_lc.php' },
        { title: 'My Attendance', url: 'pages/principal/view_my_attendance.php' },
        { title: 'Send School Notice', url: 'pages/principal/send_notice.php' },
        { title: 'Send Notice to BMC', url: 'pages/principal/send_notice_to_bmc.php' },
        { title: 'Send Notice to Librarian', url: 'pages/principal/send_notice_to_librarian.php' },
        { title: 'View BMC Notices', url: 'pages/principal/view_notice.php' },
        { title: 'Manage Subjects', url: 'pages/academics/manage_subjects.php' },
        { title: 'Manage Timetable', url: 'pages/academics/manage_timetable.php' },
        { title: 'Send Exam Timetable', url: 'pages/principal/send_exam_timetable.php' },
        { title: 'Passing Criteria', url: 'pages/principal/school_settings.php' },
        { title: 'Teacher Leave', url: 'pages/principal/principal_leave_requests.php' },
        { title: 'Past Teacher List', url: 'pages/past_record/past_teacher.php' },
        { title: 'Past Librarian List', url: 'pages/past_record/past_librarian.php' },
        { title: 'Past Student List', url: 'pages/past_record/past_student.php' },
    ];

    searchInput.addEventListener('input', function() {
        const query = searchInput.value.toLowerCase();
        searchResults.innerHTML = '';
        searchResults.style.display = 'none';

        if (query.length > 1) {
            const filteredPages = pages.filter(page => 
                page.title.toLowerCase().includes(query)
            );

            if (filteredPages.length > 0) {
                filteredPages.forEach(page => {
                    const link = document.createElement('a');
                    link.href = base_url_for_search + page.url;
                    link.textContent = page.title;
                    searchResults.appendChild(link);
                });
                searchResults.style.display = 'block';
            } else {
                searchResults.innerHTML = '<div class="no-results">No pages found.</div>';
                searchResults.style.display = 'block';
            }
        }
    });

    // Hide results when clicking outside
    document.addEventListener('click', function(event) {
        if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
            searchResults.style.display = 'none';
        }
    });
    // --- END: Search Bar Functionality ---

    // --- START: FIX - SIDEBAR NOTIFICATION CLEARING SCRIPT ---
    const sidebarNotificationLinks = document.querySelectorAll('#accordionSidebar .nav-link[data-notification-type]');
    
    sidebarNotificationLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            const notificationType = this.getAttribute('data-notification-type');
            const badge = this.querySelector('.badge-counter');

            // If a notification type is defined and a badge is visible, send a background request to mark them as read.
            if (notificationType && badge && badge.style.display !== 'none') {
                let formData = new FormData();
                formData.append('type', notificationType);
                
                // This is the new endpoint you just created
                const endpoint = '<?php echo BASE_WEB_PATH; ?>includes/actions/mark_notifications_as_read.php';

                // 'keepalive' ensures the request is sent even if the user navigates away immediately.
                fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    keepalive: true 
                }).catch(error => console.error('Sidebar notification clear error:', error));
            }
        });
    });
    // --- END: FIX ---

});
</script>

<div class="modal fade" id="clearNotificationsModal" tabindex="-1" role="dialog" aria-labelledby="clearNotificationsModalLabel" aria-hidden="true"></div>
