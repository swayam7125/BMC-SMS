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
    $user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

    if ($user_role === 'superadmin') {
        $userName = 'Super Admin';
    } else {
        // --- START: MODIFIED LOGIC - Fetch user name and image from the database ---
        $table_name = '';
        $name_field = '';
        $image_field = '';

        if ($user_id && isset($conn)) {
            // Determine the correct table and fields based on the user's role
            switch ($user_role) {
                case 'teacher':
                    $table_name = 'teacher';
                    $name_field = 'teacher_name';
                    $image_field = 'teacher_image';
                    break;
                case 'student':
                    $table_name = 'student';
                    $name_field = 'student_name';
                    $image_field = 'student_image';
                    break;
                case 'principal':
                    $table_name = 'principal';
                    $name_field = 'principal_name';
                    $image_field = 'principal_image';
                    break;
                case 'librarian':
                    $table_name = 'librarian';
                    $name_field = 'librarian_name';
                    $image_field = 'librarian_image';
                    break;
                case 'hr':
                    $table_name = 'hr';
                    $name_field = 'hr_name';
                    $image_field = 'hr_image'; // This field exists in your table
                    break;
            }

            if (!empty($table_name)) {
                try {
                    // Prepare and execute the query to get the name and image path
                    $stmt_details = $conn->prepare("SELECT {$name_field}, {$image_field} FROM {$table_name} WHERE id = ?");
                    $stmt_details->execute([$user_id]);
                    $user_details = $stmt_details->fetch(PDO::FETCH_ASSOC);

                    if ($user_details) {
                        // Set the user's name
                        $userName = $user_details[$name_field] ?? ucfirst($user_role);

                        // Set the profile image
                        $db_image_path = $user_details[$image_field];
                        if (!empty($db_image_path)) {
                            $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $db_image_path;
                            if (file_exists($filesystem_path) && is_file($filesystem_path)) {
                                $userProfileImage = $db_image_path;
                            }
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Failed to fetch profile details for user ID {$user_id}: " . $e->getMessage());
                    // Fallback to role name if query fails
                    $userName = ucfirst($user_role);
                }
            }
        }
        // --- END: MODIFIED LOGIC ---
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
            case 'teacher_salary': return 'fas fa-receipt text-white'; 
            case 'librarian_salary': return 'fas fa-receipt text-white'; 
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
        max-height: 50vh; 
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
    /* ⭐ START: Styles for Search History */
    .history-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
    }
    .history-item .history-text {
        display: flex;
        align-items: center;
    }
    .history-item .history-text i {
        margin-right: 12px;
        color: #858796;
    }
    .history-item .remove-history {
        color: #d1d3e2;
        font-size: 0.9rem;
        padding: 4px 8px; /* Easier to click */
    }
    .history-item .remove-history:hover {
        color: #e74a3b;
    }
    /* ⭐ END: Styles for Search History */
</style>
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <div class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search position-relative">
        <div class="input-group">
            <input type="text" id="pageSearchInput" class="form-control bg-light border-0 small" placeholder="Search for pages..."
                aria-label="Search" autocomplete="off"> <div class="input-group-append">
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
                <?php if ($user_role !== 'superadmin' && $user_role !== 'hr'): ?>
                <a class="dropdown-item" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                </a>
                <div class="dropdown-divider"></div>
                <?php endif; ?>

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

        // ⭐ FIX: Changed action to 'get_unread_total' to match the API endpoint
        // This was the primary reason the header counter was not updating.
        fetch(`${base_url}includes/messaging_api.php?action=get_unread_total`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const count = parseInt(data.total_unread, 10); // Use total_unread from response
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

    // Run it once on page load
    fetchUnreadMessageCount();

    // Set an interval to check for new messages every 15 seconds
    setInterval(fetchUnreadMessageCount, 15000); 

    if (messagesLink) {
        messagesLink.addEventListener('click', function() {
            if (messagesBadge) {
                messagesBadge.style.display = 'none';
            }
            // Note: Marking messages as read is handled on the message page itself
            // when a conversation is opened. No separate API call is needed here.
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

    // ⭐ START: ENHANCED DYNAMIC Search Bar Functionality ---
    const searchInput = document.getElementById('pageSearchInput');
    const searchResults = document.getElementById('pageSearchResults');
    const base_url_for_search = '<?php echo BASE_WEB_PATH; ?>';
    const SEARCH_HISTORY_COOKIE = 'page_search_history';
    const MAX_HISTORY_ITEMS = 5;

    <?php
    // Master list of all searchable pages with their allowed roles
    $all_pages = [        
        // Super Admin Pages
        ['title' => 'Dashboard', 'url' => 'dashboard.php', 'roles' => ['superadmin']],
        ['title' => 'Enroll School', 'url' => 'includes/forms/school_enrollment.php', 'roles' => ['superadmin']],
        ['title' => 'School List', 'url' => 'pages/school/school_list.php', 'roles' => ['superadmin']],
        ['title' => 'Enroll Principal', 'url' => 'includes/forms/principal_enrollment.php', 'roles' => ['superadmin']],
        ['title' => 'Principal List', 'url' => 'pages/principal/principal_list.php', 'roles' => ['superadmin']],
        ['title' => 'Principal Attendance', 'url' => 'pages/bmc/principal_attendance.php', 'roles' => ['superadmin']],
        ['title' => 'Send Notice to Principals', 'url' => 'pages/bmc/send_notice.php', 'roles' => ['superadmin']],
        ['title' => 'View Principal Notice', 'url' => 'pages/bmc/view_principal_notices.php', 'roles' => ['superadmin']],
        ['title' => 'Past School List', 'url' => 'pages/past_record/past_school.php', 'roles' => ['superadmin']],
        ['title' => 'Past Principal List', 'url' => 'pages/past_record/past_principal.php', 'roles' => ['superadmin']],
        ['title' => 'Enrollment Report', 'url' => 'pages/reports/report_enrollment.php', 'roles' => ['superadmin']],
        ['title' => 'Attendance Analysis', 'url' => 'pages/reports/report_attendance.php', 'roles' => ['superadmin']],
        ['title' => 'Academic Performance', 'url' => 'pages/reports/report_academic.php', 'roles' => ['superadmin']],
        ['title' => 'Payroll Summary', 'url' => 'pages/reports/report_payroll.php', 'roles' => ['superadmin']],
        ['title' => 'Library Usage', 'url' => 'pages/reports/report_library.php', 'roles' => ['superadmin']],

        // Principal Pages
        ['title' => 'Dashboard', 'url' => 'dashboard.php', 'roles' => ['principal']],
        ['title' => 'My Profile', 'url' => 'pages/user/profile.php', 'roles' => ['principal']],
        ['title' => 'Enroll Teacher', 'url' => 'includes/forms/teacher_enrollment.php', 'roles' => ['principal']],
        ['title' => 'Teacher List', 'url' => 'pages/teacher/teacher_list.php', 'roles' => ['principal']],
        ['title' => 'Teacher Attendance', 'url' => 'pages/principal/teacher_attendence.php', 'roles' => ['principal']],
        ['title' => 'View Teacher Attendance', 'url' => 'pages/principal/view_teacher_attendence.php', 'roles' => ['principal']],
        ['title' => 'Enroll Librarian', 'url' => 'includes/forms/librarian_enrollment.php', 'roles' => ['principal']],
        ['title' => 'Librarian List', 'url' => 'pages/librarian/librarian_list.php', 'roles' => ['principal']],
        ['title' => 'Librarian Attendance', 'url' => 'pages/principal/librarian_attendence.php', 'roles' => ['principal']],
        ['title' => 'View Librarian Attendance', 'url' => 'pages/principal/view_librarian_attendence.php', 'roles' => ['principal']],
        ['title' => 'Enroll Student', 'url' => 'includes/forms/student_enrollment.php', 'roles' => ['principal']],
        ['title' => 'Student List', 'url' => 'pages/student/student_list.php', 'roles' => ['principal']],
        ['title' => 'Generate LC', 'url' => 'pages/principal/generate_lc.php', 'roles' => ['principal']],
        ['title' => 'Enroll HR User', 'url' => 'includes/forms/payroll_enrollment.php', 'roles' => ['principal']],
        ['title' => 'HR User List', 'url' => 'pages/payroll/payroll_list.php', 'roles' => ['principal']],
        ['title' => 'HR Attendance', 'url' => 'pages/principal/payroll_attendance.php', 'roles' => ['principal']],
        ['title' => 'Manage Vehicles', 'url' => 'pages/transport/manage_vehicles.php', 'roles' => ['principal']],
        ['title' => 'Manage Drivers', 'url' => 'pages/transport/manage_drivers.php', 'roles' => ['principal']],
        ['title' => 'Manage Routes & Stops', 'url' => 'pages/transport/manage_routes.php', 'roles' => ['principal']],
        ['title' => 'Student Transport', 'url' => 'pages/transport/student_transport.php', 'roles' => ['principal']],
        ['title' => 'My Attendance', 'url' => 'pages/principal/view_my_attendance.php', 'roles' => ['principal']],
        ['title' => 'My Salary', 'url' => 'pages/principal/view_my_salary.php', 'roles' => ['principal']],
        ['title' => 'Send School Notice', 'url' => 'pages/principal/send_notice.php', 'roles' => ['principal']],
        ['title' => 'Send Notice to BMC', 'url' => 'pages/principal/send_notice_to_bmc.php', 'roles' => ['principal']],
        ['title' => 'Send Notice to Librarian', 'url' => 'pages/principal/send_notice_to_librarian.php', 'roles' => ['principal']],
        ['title' => 'View BMC Notice', 'url' => 'pages/principal/view_notice.php', 'roles' => ['principal']],
        ['title' => 'Manage Subjects', 'url' => 'pages/academics/manage_subjects.php', 'roles' => ['principal']],
        ['title' => 'Manage Timetable', 'url' => 'pages/academics/manage_timetable.php', 'roles' => ['principal']],
        ['title' => 'Send Exam Timetable', 'url' => 'pages/principal/send_exam_timetable.php', 'roles' => ['principal']],
        ['title' => 'Holiday Management', 'url' => 'pages/principal/manage_holidays.php', 'roles' => ['principal']],
        ['title' => 'School Settings', 'url' => 'pages/principal/school_settings.php', 'roles' => ['principal']],
        ['title' => 'Teacher Leave', 'url' => 'pages/principal/teacher_leave_management.php', 'roles' => ['principal']],
        ['title' => 'Librarian Leave', 'url' => 'pages/principal/librarian_leave_management.php', 'roles' => ['principal']],
        ['title' => 'Past Teacher List', 'url' => 'pages/past_record/past_teacher.php', 'roles' => ['principal']],
        ['title' => 'Past Librarian List', 'url' => 'pages/past_record/past_librarian.php', 'roles' => ['principal']],
        ['title' => 'Past Student List', 'url' => 'pages/past_record/past_student.php', 'roles' => ['principal']],
        ['title' => 'Enrollment Report', 'url' => 'pages/reports/report_enrollment.php', 'roles' => ['principal']],
        ['title' => 'Attendance Analysis', 'url' => 'pages/reports/report_attendance.php', 'roles' => ['principal']],
        ['title' => 'Academic Performance', 'url' => 'pages/reports/report_academic.php', 'roles' => ['principal']],
        ['title' => 'Payroll Summary', 'url' => 'pages/reports/report_payroll.php', 'roles' => ['principal']],
        ['title' => 'Library Usage', 'url' => 'pages/reports/report_library.php', 'roles' => ['principal']],
        
        // Teacher Pages
        ['title' => 'Dashboard', 'url' => 'dashboard.php', 'roles' => ['teacher']],
        ['title' => 'My Profile', 'url' => 'pages/user/profile.php', 'roles' => ['teacher']],
        ['title' => 'My Student', 'url' => 'pages/student/student_list.php', 'roles' => ['teacher']],
        ['title' => 'My Attendance', 'url' => 'pages/teacher/view_my_attendance.php', 'roles' => ['teacher']],
        ['title' => 'My Salary History', 'url' => 'pages/teacher/view_salary_history.php', 'roles' => ['teacher']],
        ['title' => 'Salary History', 'url' => 'pages/payroll/view_salary_history.php', 'roles' => ['teacher']],
        ['title' => 'Enter Marks', 'url' => 'pages/teacher/marks_entry/marks_entry.php', 'roles' => ['teacher']],
        ['title' => 'View Marks', 'url' => 'pages/teacher/marks_entry/view_marks.php', 'roles' => ['teacher']],
        ['title' => 'Send Assignment', 'url' => 'pages/assignments/send_assignment.php', 'roles' => ['teacher']],
        ['title' => 'Assignment History', 'url' => 'pages/assignments/assignment_history.php', 'roles' => ['teacher']],
        ['title' => 'Manage Leave', 'url' => 'pages/teacher/teacher_leave_management.php', 'roles' => ['teacher']],
        ['title' => 'Lecture Attendance', 'url' => 'pages/teacher/add_lecture_attendance.php', 'roles' => ['teacher']],
        ['title' => 'View Attendance', 'url' => 'pages/teacher/view_lecture_attendance.php', 'roles' => ['teacher']],
        ['title' => 'View Lecture Timetable', 'url' => 'pages/student/view_timetable.php', 'roles' => ['teacher']],
        ['title' => 'View Exam Timetable', 'url' => 'pages/teacher/view_exam_timetable.php', 'roles' => ['teacher']],
        ['title' => 'Send Notes', 'url' => 'pages/teacher/send_notes.php', 'roles' => ['teacher']],
        ['title' => 'View School Notices', 'url' => 'pages/teacher/view_notice.php', 'roles' => ['teacher']],
        ['title' => 'Browse & Request Books', 'url' => 'pages/teacher/browse_books.php', 'roles' => ['teacher']],
        ['title' => 'My Borrowing Record', 'url' => 'pages/teacher/my_library_record.php', 'roles' => ['teacher']],
        ['title' => 'Request New Book', 'url' => 'pages/user/request_new_book.php', 'roles' => ['teacher']],
        ['title' => 'My Request History', 'url' => 'pages/user/my_book_requests.php', 'roles' => ['teacher']],

        // Student Pages
        ['title' => 'Dashboard', 'url' => 'dashboard.php', 'roles' => ['student']],
        ['title' => 'My Profile', 'url' => 'pages/user/profile.php', 'roles' => ['student']],
        ['title' => 'View Assignments', 'url' => 'pages/assignments/view_assignments.php', 'roles' => ['student']],
        ['title' => 'View Attendance', 'url' => 'pages/student/view_lecture_attendance.php', 'roles' => ['student']],
        ['title' => 'View Results', 'url' => 'pages/student/view_my_marks.php', 'roles' => ['student']],
        ['title' => 'View School Notices', 'url' => 'pages/student/view_notice.php', 'roles' => ['student']],
        ['title' => 'View Notes', 'url' => 'pages/student/view_notes.php', 'roles' => ['student']],
        ['title' => 'View Lecture Timetable', 'url' => 'pages/student/view_timetable.php', 'roles' => ['student']],
        ['title' => 'View Exam Timetable', 'url' => 'pages/student/view_exam_timetable.php', 'roles' => ['student']],
        ['title' => 'Browse & Request Books', 'url' => 'pages/student/browse_books.php', 'roles' => ['student']],
        ['title' => 'My Borrowing Record', 'url' => 'pages/student/my_library_record.php', 'roles' => ['student']],
        ['title' => 'Request New Book', 'url' => 'pages/user/request_new_book.php', 'roles' => ['student']],
        ['title' => 'My Request History', 'url' => 'pages/user/my_book_requests.php', 'roles' => ['student']],

        // Librarian Pages
        ['title' => 'Dashboard', 'url' => 'dashboard.php', 'roles' => ['librarian']],
        ['title' => 'My Profile', 'url' => 'pages/user/profile.php', 'roles' => ['librarian']],
        ['title' => 'My Attendance', 'url' => 'pages/librarian/view_my_attendance.php', 'roles' => ['librarian']],
        ['title' => 'My Salary History', 'url' => 'pages/librarian/view_salary_history.php', 'roles' => ['librarian']],
        ['title' => 'Manage Leave', 'url' => 'pages/librarian/my_leave_management.php', 'roles' => ['librarian']],
        ['title' => 'Book List', 'url' => 'pages/librarian/book_list.php', 'roles' => ['librarian']],
        ['title' => 'Add New Book', 'url' => 'pages/librarian/add_new_book.php', 'roles' => ['librarian']],
        ['title' => 'Principal Notices', 'url' => 'pages/librarian/view_principal_notices.php', 'roles' => ['librarian']],
        ['title' => 'Issue & Return', 'url' => 'pages/librarian/issue_return.php', 'roles' => ['librarian']],
        ['title' => 'Borrow Requests', 'url' => 'pages/librarian/borrow_requests.php', 'roles' => ['librarian']],
        ['title' => 'Acquisition Requests', 'url' => 'pages/librarian/book_requests.php', 'roles' => ['librarian']],
        ['title' => 'Past Book Records', 'url' => 'pages/past_record/past_books.php', 'roles' => ['librarian']],

        // Payroll Pages
        ['title' => 'Dashboard', 'url' => 'dashboard.php', 'roles' => ['hr']],
        ['title' => 'My Attendance', 'url' => 'pages/payroll/view_my_attendance.php', 'roles' => ['hr']],
        ['title' => 'Manage Incentives', 'url' => 'pages/payroll/manage_incentives.php', 'roles' => ['hr']],
        ['title' => 'Teacher Payroll', 'url' => 'pages/payroll/process_teacher_salary.php', 'roles' => ['hr']],
        ['title' => 'Librarian Payroll', 'url' => 'pages/payroll/process_librarian_salary.php', 'roles' => ['hr']],
        ['title' => 'Principal Payroll', 'url' => 'pages/payroll/process_principal_salary.php', 'roles' => ['hr']],
        ['title' => 'Salary History', 'url' => 'pages/payroll/view_salary_history.php', 'roles' => ['hr']],
    ];

    // Filter the pages based on the current user's role
    $accessible_pages = [];
    if (isset($user_role)) {
        foreach ($all_pages as $page) {
            if (in_array($user_role, $page['roles'])) {
                $accessible_pages[] = ['title' => $page['title'], 'url' => $page['url']];
            }
        }
    }
    ?>
    
    const pages = <?php echo json_encode($accessible_pages); ?>;

    // --- ⭐ Helper functions for cookie management ---
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    // --- ⭐ Functions for search history ---
    function getSearchHistory() {
        const history = getCookie(SEARCH_HISTORY_COOKIE);
        return history ? JSON.parse(history) : [];
    }

    function addToSearchHistory(query) {
        if (!query) return;
        let history = getSearchHistory();
        // Remove existing entry to move it to the top
        history = history.filter(item => item !== query);
        // Add new query to the front
        history.unshift(query);
        // Trim history to the max length
        history = history.slice(0, MAX_HISTORY_ITEMS);
        setCookie(SEARCH_HISTORY_COOKIE, JSON.stringify(history), 365);
    }

    function removeFromSearchHistory(query) {
        let history = getSearchHistory();
        history = history.filter(item => item !== query);
        setCookie(SEARCH_HISTORY_COOKIE, JSON.stringify(history), 365);
        displaySearchHistory(); // Refresh the displayed list
    }

    function displaySearchHistory() {
        const history = getSearchHistory();
        searchResults.innerHTML = '';
        if (history.length > 0) {
            history.forEach(item => {
                const historyDiv = document.createElement('div');
                historyDiv.className = 'dropdown-item history-item';
                historyDiv.innerHTML = `
                    <div class="history-text">
                        <i class="fas fa-history"></i>
                        <span class="history-query">${item}</span>
                    </div>
                    <span class="remove-history" title="Remove">&times;</span>`;
                
                // ⭐ MODIFIED SECTION START: This logic now finds a page and navigates.
                historyDiv.querySelector('.history-text').addEventListener('click', () => {
                    const query = item.toLowerCase();
                    // Find the first page that matches the history query
                    const targetPage = pages.find(page => page.title.toLowerCase().includes(query));
                    
                    if (targetPage) {
                        // If a matching page is found, go to its URL
                        window.location.href = base_url_for_search + targetPage.url;
                    } else {
                        // Fallback: if no direct match, just fill the search bar
                        searchInput.value = item;
                        searchInput.dispatchEvent(new Event('input')); 
                    }
                });
                // ⭐ MODIFIED SECTION END

                // Event listener for removing an item
                historyDiv.querySelector('.remove-history').addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent the main click event
                    removeFromSearchHistory(item);
                });

                searchResults.appendChild(historyDiv);
            });
            searchResults.style.display = 'block';
        } else {
            searchResults.style.display = 'none';
        }
    }

    // --- ⭐ Main search event listeners ---
    searchInput.addEventListener('input', function() {
        const query = searchInput.value.toLowerCase().trim();
        searchResults.innerHTML = '';

        if (query.length === 0) {
            displaySearchHistory();
            return;
        }

        if (query.length > 1) {
            const filteredPages = pages.filter(page => 
                page.title.toLowerCase().includes(query)
            );

            if (filteredPages.length > 0) {
                filteredPages.forEach(page => {
                    const link = document.createElement('a');
                    link.href = base_url_for_search + page.url;
                    link.textContent = page.title;
                    link.addEventListener('click', () => {
                        addToSearchHistory(searchInput.value.trim());
                    });
                    searchResults.appendChild(link);
                });
                searchResults.style.display = 'block';
            } else {
                searchResults.innerHTML = '<div class="no-results">No pages found.</div>';
                searchResults.style.display = 'block';
            }
        } else {
            searchResults.style.display = 'none';
        }
    });

    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length === 0) {
            displaySearchHistory();
        }
    });

    searchInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const firstResult = searchResults.querySelector('a');
            if (firstResult) {
                addToSearchHistory(this.value.trim());
                window.location.href = firstResult.href;
            }
        }
    });

    // Hide results when clicking outside
    document.addEventListener('click', function(event) {
        if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
            searchResults.style.display = 'none';
        }
    });
    // ⭐ END: ENHANCED Search Bar Functionality ---

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

});
</script>

<div class="modal fade" id="clearNotificationsModal" tabindex="-1" role="dialog" aria-labelledby="clearNotificationsModalLabel" aria-hidden="true"></div>