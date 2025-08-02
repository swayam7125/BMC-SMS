<?php
// Corrected absolute paths for both files for reliability
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';

// For debugging - KEEP THIS DURING DEVELOPMENT
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only define the constant if it hasn't been defined already.
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// Set default values for a logged-out user
$userName = 'Guest';
$user_role = 'User';
$userProfileImage = BASE_WEB_PATH . 'assets/images/undraw_profile.svg';
$isLoggedIn = false;

// If the user is logged in, determine their details
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


// --- START: Dynamic Notification Logic ---
$notifications = [];
$unread_count = 0;
$current_user_id = null;

if ($isLoggedIn && isset($_COOKIE['encrypted_user_id'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
    
    // FIX: Check if the connection exists AND is still active using ping()
    if ($current_user_id && isset($conn) && $conn->ping()) {
        $stmt_notifications = $conn->prepare("SELECT id, message, link, type, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
        $stmt_notifications->bind_param("i", $current_user_id);
        $stmt_notifications->execute();
        $result_notifications = $stmt_notifications->get_result();
        while ($row = $result_notifications->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt_notifications->close();

        $stmt_count = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt_count->bind_param("i", $current_user_id);
        $stmt_count->execute();
        $unread_count = $stmt_count->get_result()->fetch_assoc()['unread_count'];
        $stmt_count->close();
    }
}

function getNotificationIcon($type) {
    switch ($type) {
        case 'leave_request':
            return 'fas fa-calendar-plus text-white';
        case 'new_notice': // BMC to Principal notice
            return 'fas fa-file-alt text-white';
        case 'principal_notice': // Principal to BMC notice
            return 'fas fa-user-tie text-white';
        case 'leave_status':
            return 'fas fa-check-circle text-white';
        case 'school_notice': // Principal to School notice
            return 'fas fa-chalkboard-teacher text-white';
        case 'new_assignment':
            return 'fas fa-file-signature text-white';
        case 'marks_uploaded':
            return 'fas fa-award text-white';
        case 'exam_timetable':
            return 'fas fa-calendar-alt text-white';
        case 'new_notes':
            return 'fas fa-sticky-note text-white';
        // --- NEW: Added case for result notifications ---
        case 'result_published':
            return 'fas fa-poll-h text-white';
        default:
            return 'fas fa-bell text-white';
    }
}
// --- END: Dynamic Notification Logic ---
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
        display: none; /* Hidden by default */
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
                        // --- FIX: Correctly build the notification link ---
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
document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Navigation Data
    // This object replicates the sidebar structure for searching.
    const BASE_URL = '<?php echo BASE_WEB_PATH; ?>';
    const sidebarPages = {
        superadmin: [
            { title: 'Dashboard', url: `${BASE_URL}dashboard.php`, keywords: 'dashboard home main' },
            { title: 'Enroll School', url: `${BASE_URL}includes/forms/school_enrollment.php`, keywords: 'enroll school new add' },
            { title: 'School List', url: `${BASE_URL}pages/school/school_list.php`, keywords: 'school list view all management' },
            { title: 'Enroll Principal', url: `${BASE_URL}includes/forms/principal_enrollment.php`, keywords: 'enroll principal new add management' },
            { title: 'Principal List', url: `${BASE_URL}pages/principal/principal_list.php`, keywords: 'principal list view all management' },
            { title: 'Principal Attendance', url: `${BASE_URL}pages/bmc/principal_attendance.php`, keywords: 'principal attendance clock in out' },
            { title: 'Send Notice to Principals', url: `${BASE_URL}pages/bmc/send_notice.php`, keywords: 'send notice message communication' },
            { title: 'View Principal Notices', url: `${BASE_URL}pages/bmc/view_principal_notices.php`, keywords: 'view notices inbox' },
            { title: 'Past School List', url: `${BASE_URL}pages/past_record/past_school.php`, keywords: 'past data history archive school' },
            { title: 'Past Principal List', url: `${BASE_URL}pages/past_record/past_principal.php`, keywords: 'past data history archive principal' },
        ],
        principal: [
            { title: 'Dashboard', url: `${BASE_URL}dashboard.php`, keywords: 'dashboard home main' },
            { title: 'Enroll Teacher', url: `${BASE_URL}includes/forms/teacher_enrollment.php`, keywords: 'enroll teacher new add' },
            { title: 'Teacher List', url: `${BASE_URL}pages/teacher/teacher_list.php`, keywords: 'teacher list view all' },
            { title: 'Teacher Attendance', url: `${BASE_URL}pages/principal/teacher_attendence.php`, keywords: 'teacher attendance clock in out' },
            { title: 'View Teacher Attendance', url: `${BASE_URL}pages/principal/view_teacher_attendence.php`, keywords: 'view teacher attendance report' },
            { title: 'Enroll Student', url: `${BASE_URL}includes/forms/student_enrollment.php`, keywords: 'enroll student new add' },
            { title: 'Student List', url: `${BASE_URL}pages/student/student_list.php`, keywords: 'student list view all' },
            { title: 'Generate LC', url: `${BASE_URL}pages/principal/generate_lc.php`, keywords: 'generate lc leaving certificate' },
            { title: 'My Attendance', url: `${BASE_URL}pages/principal/view_my_attendance.php`, keywords: 'my attendance view report' },
            { title: 'Send School Notice', url: `${BASE_URL}pages/principal/send_notice.php`, keywords: 'send school notice message communication' },
            { title: 'Send Notice to BMC', url: `${BASE_URL}pages/principal/send_notice_to_bmc.php`, keywords: 'send notice bmc communication' },
            { title: 'View BMC Notices', url: `${BASE_URL}pages/principal/view_notice.php`, keywords: 'view bmc notice inbox' },
            { title: 'Manage Subjects', url: `${BASE_URL}pages/academics/manage_subjects.php`, keywords: 'manage subjects academics' },
            { title: 'Manage Timetable', url: `${BASE_URL}pages/academics/manage_timetable.php`, keywords: 'manage timetable schedule' },
            { title: 'Send Exam Timetable', url: `${BASE_URL}pages/principal/send_exam_timetable.php`, keywords: 'send exam timetable schedule test' },
            { title: 'Passing Criteria', url: `${BASE_URL}pages/principal/school_settings.php`, keywords: 'passing criteria settings marks' },
            { title: 'Pending Leave Requests', url: `${BASE_URL}pages/principal/principal_leave_requests.php`, keywords: 'pending leave requests teacher approval' },
            { title: 'Leave Application History', url: `${BASE_URL}pages/principal/principal_leave_history.php`, keywords: 'leave application history teacher report' },
            { title: 'Past Teacher List', url: `${BASE_URL}pages/past_record/past_teacher.php`, keywords: 'past data history archive teacher' },
            { title: 'Past Student List', url: `${BASE_URL}pages/past_record/past_student.php`, keywords: 'past data history archive student' },
        ],
        teacher: [
            { title: 'Dashboard', url: `${BASE_URL}dashboard.php`, keywords: 'dashboard home main' },
            { title: 'My Students', url: `${BASE_URL}pages/student/student_list.php`, keywords: 'my students list view' },
            { title: 'Enter Marks', url: `${BASE_URL}pages/teacher/marks_entry/marks_entry.php`, keywords: 'enter marks results grade' },
            { title: 'View Marks', url: `${BASE_URL}pages/teacher/marks_entry/view_marks.php`, keywords: 'view marks results report' },
            { title: 'Send Assignment', url: `${BASE_URL}pages/assignments/send_assignment.php`, keywords: 'send assignment homework' },
            { title: 'Assignment History', url: `${BASE_URL}pages/assignments/assignment_history.php`, keywords: 'assignment history submissions' },
            { title: 'Manage Leave', url: `${BASE_URL}pages/teacher/teacher_leave_management.php`, keywords: 'manage leave apply request' },
            { title: 'Lecture Attendance', url: `${BASE_URL}pages/teacher/add_lecture_attendance.php`, keywords: 'lecture attendance daily entry' },
            { title: 'View Attendance', url: `${BASE_URL}pages/teacher/view_lecture_attendance.php`, keywords: 'view attendance report' },
            { title: 'View Lecture Timetable', url: `${BASE_URL}pages/student/view_timetable.php`, keywords: 'view lecture timetable schedule' },
            { title: 'View Exam Timetable', url: `${BASE_URL}pages/teacher/view_exam_timetable.php`, keywords: 'view exam timetable schedule test' },
            { title: 'Send Notes', url: `${BASE_URL}pages/teacher/send_notes.php`, keywords: 'send notes material study' },
            { title: 'View School Notices', url: `${BASE_URL}pages/teacher/view_notice.php`, keywords: 'view school notices inbox' },
        ],
        student: [
            { title: 'Dashboard', url: `${BASE_URL}dashboard.php`, keywords: 'dashboard home main' },
            { title: 'My Profile', url: `${BASE_URL}pages/user/profile.php`, keywords: 'my profile details information' },
            { title: 'View Assignments', url: `${BASE_URL}pages/assignments/view_assignments.php`, keywords: 'view assignments homework submissions' },
            { title: 'View Attendance', url: `${BASE_URL}pages/student/view_lecture_attendance.php`, keywords: 'view attendance report' },
            { title: 'View Results', url: `${BASE_URL}pages/student/view_my_marks.php`, keywords: 'view results marks report card' },
            { title: 'View School Notices', url: `${BASE_URL}pages/student/view_notice.php`, keywords: 'view school notices inbox' },
            { title: 'View Notes', url: `${BASE_URL}pages/student/view_notes.php`, keywords: 'view notes study material' },
            { title: 'View Lecture Timetable', url: `${BASE_URL}pages/student/view_timetable.php`, keywords: 'view lecture timetable schedule' },
            { title: 'View Exam Timetable', url: `${BASE_URL}pages/student/view_exam_timetable.php`, keywords: 'view exam timetable schedule test' },
        ]
    };

    // 2. Search Logic
    const searchInput = document.getElementById('pageSearchInput');
    const resultsContainer = document.getElementById('pageSearchResults');
    const currentUserRole = '<?php echo $user_role; ?>';
    const availablePages = sidebarPages[currentUserRole] || [];

    searchInput.addEventListener('input', function (e) {
        const query = e.target.value.toLowerCase().trim();
        resultsContainer.innerHTML = ''; // Clear previous results

        if (query.length === 0) {
            resultsContainer.style.display = 'none';
            return;
        }

        const filteredPages = availablePages.filter(page =>
            page.keywords.toLowerCase().includes(query)
        );

        if (filteredPages.length > 0) {
            filteredPages.forEach(page => {
                const link = document.createElement('a');
                link.href = page.url;
                link.textContent = page.title;
                resultsContainer.appendChild(link);
            });
        } else {
            const noResults = document.createElement('div');
            noResults.className = 'no-results';
            noResults.textContent = 'No pages found';
            resultsContainer.appendChild(noResults);
        }

        resultsContainer.style.display = 'block';
    });

    // Hide results when clicking outside
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const clearAllBtn = document.getElementById('clear-all-notifications-btn');
    const confirmClearBtn = document.getElementById('confirmClearBtn');

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            $('#clearNotificationsModal').modal('show');
        });
    }

    if (confirmClearBtn) {
        confirmClearBtn.addEventListener('click', function(e) {
            e.preventDefault();

            fetch('<?php echo BASE_WEB_PATH; ?>includes/actions/clear_all_notifications.php', {
                method: 'POST',
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#clearNotificationsModal').modal('hide');

                    const counter = document.querySelector('#alertsDropdown .badge-counter');
                    if (counter) counter.style.display = 'none';
                    
                    const container = document.getElementById('notification-items-container');
                    if (container) {
                        container.innerHTML = `
                        <a class="dropdown-item d-flex align-items-center" href="#">
                            <div class="mr-3"><div class="icon-circle bg-success"><i class="fas fa-check-circle text-white"></i></div></div>
                            <div><div class="small text-gray-500"><?php echo date('F j, Y'); ?></div>All caught up! No new notifications.</div>
                        </a>`;
                    }
                    if (clearAllBtn) clearAllBtn.style.display = 'none';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error clearing notifications:', error);
                alert('An error occurred while clearing notifications.');
            });
        });
    }
});
</script>

<div class="modal fade" id="clearNotificationsModal" tabindex="-1" role="dialog" aria-labelledby="clearNotificationsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearNotificationsModalLabel">Clear All Notifications?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Clear" below if you are ready to mark all notifications as read.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="#" id="confirmClearBtn">Clear</a>
            </div>
        </div>
    </div>
</div>