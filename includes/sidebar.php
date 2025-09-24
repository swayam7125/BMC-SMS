<?php
include_once __DIR__ . '/functions.php';

$role = null;
$user_id = null;

// Get the current page's file name for individual link checks
$current_page = basename($_SERVER['SCRIPT_NAME']);

// Read and decrypt user role and ID from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Define BASE_WEB_PATH if not already defined
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// ⭐ NEW: Include log system file here. (Does NOT perform automatic logging anymore)
include_once __DIR__ . '/log_system.php';

// --- START: FETCH UNREAD NOTIFICATION COUNTS (PDO VERSION) ---
// Initialize all counter variables
$unread_assignments = 0;
$unread_results = 0;
$unread_student_notices = 0;
$unread_notes = 0;
$unread_bmc_notices = 0;
$unread_leave_requests = 0;
$unread_principal_notices = 0;
$unread_teacher_notices = 0;
$unread_submissions = 0;
$unread_leave_status = 0;
$unread_exam_timetables = 0;
$unread_borrow_requests = 0;
$unread_acquisition_requests = 0;
$unread_library_status = 0;
$unread_principal_to_librarian_notices = 0;
$unread_librarian_requests = 0;
$unread_hr_requests = 0;
$unread_salary_notifications = 0; // For librarian salary history
$unread_teacher_salary = 0; // For teacher salary history
$unread_principal_salary = 0; // For principal salary history
$unread_hr_salary = 0; // For hr salary history
$unread_hr_leave_status = 0; // For HR leave status
$is_class_teacher = false; // Initialize teacher-specific flag

// Fetch counts based on the user's role if a valid user ID and connection exist
if (isset($conn) && $user_id) {
    try {
        switch ($role) {
            case 'student':
                // Using COUNT with FILTER is more efficient in PostgreSQL
                $sql_counts = "SELECT
                                COUNT(*) FILTER (WHERE type = 'new_assignment' AND is_read = false) AS assignments,
                                COUNT(*) FILTER (WHERE type = 'marks_uploaded' AND is_read = false) AS results,
                                COUNT(*) FILTER (WHERE type = 'school_notice' AND is_read = false) AS notices,
                                COUNT(*) FILTER (WHERE type = 'new_notes' AND is_read = false) AS notes,
                                COUNT(*) FILTER (WHERE type = 'exam_timetable' AND is_read = false) AS exam_timetables,
                                COUNT(*) FILTER (WHERE type = 'borrow_status' AND is_read = false) AS library_status
                           FROM notifications WHERE user_id = ?";
                $stmt_counts = $conn->prepare($sql_counts);
                $stmt_counts->execute([$user_id]);
                $result = $stmt_counts->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $unread_assignments = (int) ($result['assignments'] ?? 0);
                    $unread_results = (int) ($result['results'] ?? 0);
                    $unread_student_notices = (int) ($result['notices'] ?? 0);
                    $unread_notes = (int) ($result['notes'] ?? 0);
                    $unread_exam_timetables = (int) ($result['exam_timetables'] ?? 0);
                    $unread_library_status = (int) ($result['library_status'] ?? 0);
                }
                break;

            case 'principal':
                $sql_bmc_notices = "SELECT COUNT(*)
                    FROM notifications
                    WHERE user_id = ?
                      AND is_read = false
                      AND type = 'new_notice'";
                $stmt_bmc = $conn->prepare($sql_bmc_notices);
                $stmt_bmc->execute([$user_id]);
                $unread_bmc_notices = (int) $stmt_bmc->fetchColumn();

                $sql_leave_requests = "SELECT COUNT(*)
                    FROM notifications
                    WHERE user_id = ?
                      AND is_read = false
                      AND type = 'leave_request'";
                $stmt_leave_reqs = $conn->prepare($sql_leave_requests);
                $stmt_leave_reqs->execute([$user_id]);
                $unread_leave_requests = (int) $stmt_leave_reqs->fetchColumn();

                $sql_librarian_requests = "SELECT COUNT(*)
                    FROM notifications
                    WHERE user_id = ?
                      AND is_read = false
                      AND type = 'librarian_leave_request'";
                $stmt_librarian_reqs = $conn->prepare($sql_librarian_requests);
                $stmt_librarian_reqs->execute([$user_id]);
                $unread_librarian_requests = (int) $stmt_librarian_reqs->fetchColumn();
                
                $sql_hr_requests = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = false AND type = 'hr_leave_request'";
                $stmt_hr_reqs = $conn->prepare($sql_hr_requests);
                $stmt_hr_reqs->execute([$user_id]);
                $unread_hr_requests = (int) $stmt_hr_reqs->fetchColumn();
                break;

            case 'teacher':
                $stmt_check = $conn->prepare("SELECT class_teacher FROM teacher WHERE id = ?");
                $stmt_check->execute([$user_id]);
                $teacher_details = $stmt_check->fetch(PDO::FETCH_ASSOC);
                if ($teacher_details && $teacher_details['class_teacher'] === true) {
                    $is_class_teacher = true;
                }

                $sql_counts = "SELECT
                                  COUNT(*) FILTER (WHERE type = 'school_notice' AND is_read = false) AS teacher_notices,
                                  COUNT(*) FILTER (WHERE type = 'assignment_submission' AND is_read = false) AS submissions,
                                  COUNT(*) FILTER (WHERE type = 'leave_status' AND is_read = false) AS leave_status,
                                  COUNT(*) FILTER (WHERE type = 'exam_timetable' AND is_read = false) AS exam_timetables,
                                  COUNT(*) FILTER (WHERE type = 'borrow_status' AND is_read = false) AS library_status,
                                  COUNT(*) FILTER (WHERE type = 'salary' AND is_read = false) AS salary_notifs
                               FROM notifications WHERE user_id = ?";
                $stmt_counts = $conn->prepare($sql_counts);
                $stmt_counts->execute([$user_id]);
                $result = $stmt_counts->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $unread_teacher_notices = (int) ($result['teacher_notices'] ?? 0);
                    $unread_submissions = (int) ($result['submissions'] ?? 0);
                    $unread_leave_status = (int) ($result['leave_status'] ?? 0);
                    $unread_exam_timetables = (int) ($result['exam_timetables'] ?? 0);
                    $unread_library_status = (int) ($result['library_status'] ?? 0);
                    $unread_teacher_salary = (int) ($result['salary_notifs'] ?? 0);
                }
                break;

            case 'librarian':
                $sql_counts = "SELECT
                                    COUNT(*) FILTER (WHERE type = 'borrow_request' AND is_read = false) AS borrow_reqs,
                                    COUNT(*) FILTER (WHERE type = 'acquisition_request' AND is_read = false) AS acq_reqs,
                                    COUNT(*) FILTER (WHERE type = 'principal_to_librarian_notice' AND is_read = false) AS p_to_l_notices,
                                    COUNT(*) FILTER (WHERE type = 'librarian_salary' AND is_read = false) AS salary_notifs
                                FROM notifications WHERE user_id = ?";
                $stmt_counts = $conn->prepare($sql_counts);
                $stmt_counts->execute([$user_id]);
                $result = $stmt_counts->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $unread_borrow_requests = (int) ($result['borrow_reqs'] ?? 0);
                    $unread_acquisition_requests = (int) ($result['acq_reqs'] ?? 0);
                    $unread_principal_to_librarian_notices = (int) ($result['p_to_l_notices'] ?? 0);
                    $unread_salary_notifications = (int) ($result['salary_notifs'] ?? 0);
                }
                break;

            case 'superadmin':
                $sql_counts = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'principal_notice' AND is_read = false";
                $stmt_counts = $conn->prepare($sql_counts);
                $stmt_counts->execute([$user_id]);
                $unread_principal_notices = (int) $stmt_counts->fetchColumn();
                break;
                
            case 'hr':
                $sql_hr_leave_status = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'hr_leave_status' AND is_read = false";
                $stmt_hr_leave_status = $conn->prepare($sql_hr_leave_status);
                $stmt_hr_leave_status->execute([$user_id]);
                $unread_hr_leave_status = (int) $stmt_hr_leave_status->fetchColumn();
                break;
        }
    } catch (PDOException $e) {
        error_log("Sidebar notification count error: " . $e->getMessage());
    }
}
?>
<style>
    .sidebar .nav-item .nav-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .sidebar .nav-item .nav-link>div,
    .collapse-inner>.collapse-item {
        display: flex;
        align-items: center;
    }

    .sidebar .nav-item .nav-link .badge-counter,
    .collapse-inner>.collapse-item .badge-counter {
        margin-left: 0.5rem;
        position: static;
        transform: none;
        font-size: 0.65rem;
        padding: 0.25em 0.4em;
        line-height: 1;
    }

    .sidebar.toggled .nav-item .nav-link {
        justify-content: center;
        position: relative;
    }

    .sidebar.toggled .nav-item .nav-link .badge-counter {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        transform: scale(0.7) translate(50%, -50%);
    }
</style>
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center"
        href="<?php echo BASE_WEB_PATH; ?>dashboard.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>dashboard.php">
            <div>
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </div>
        </a>
    </li>

    <hr class="sidebar-divider">

    <?php
    switch ($role) {
        case 'superadmin':
            $school_pages = ['school_enrollment.php', 'school_list.php'];
            $principal_pages = ['principal_enrollment.php', 'principal_list.php'];
            $past_data_pages = ['past_school.php', 'past_principal.php'];
            $reports_pages = ['report_enrollment.php', 'report_attendance.php', 'report_academic.php', 'report_payroll.php', 'report_library.php'];
    ?>
            <div class="sidebar-heading font-weight-semibold">Admin Controls</div>
            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($school_pages)) ? '' : 'collapsed'; ?>" href="#"
                    data-toggle="collapse" data-target="#collapseSchool">
                    <div><i class="fas fa-fw fa-school"></i>
                        <span>School Management</span>
                    </div>
                </a>
                <div id="collapseSchool" class="collapse <?php echo (is_active_page($school_pages)) ? 'show' : ''; ?>"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo ($current_page == 'school_enrollment.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>includes/forms/school_enrollment.php">Enroll School</a>
                        <a class="collapse-item <?php echo ($current_page == 'school_list.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/school/school_list.php">School List</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($principal_pages)) ? '' : 'collapsed'; ?>" href="#"
                    data-toggle="collapse" data-target="#collapsePrincipal">
                    <div><i class="fas fa-fw fa-user-tie"></i>
                        <span>Principal Management</span>
                    </div>
                </a>
                <div id="collapsePrincipal" class="collapse <?php echo (is_active_page($principal_pages)) ? 'show' : ''; ?>"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo ($current_page == 'principal_enrollment.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>includes/forms/principal_enrollment.php">Enroll Principal</a>
                        <a class="collapse-item <?php echo ($current_page == 'principal_list.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/principal/principal_list.php">Principal List</a>
                    </div>
                </div>
            </li>
            <li class="nav-item <?php echo ($current_page == 'principal_attendance.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/bmc/principal_attendance.php">
                    <div><i class="fas fa-fw fa-user-clock"></i>
                        <span>Principal Attendance</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'send_notice.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/bmc/send_notice.php">
                    <div><i class="fas fa-fw fa-paper-plane"></i>
                        <span>Send Notice to Principals</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_principal_notices.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/bmc/view_principal_notices.php"
                    data-notification-type="principal_notice">
                    <div>
                        <i class="fas fa-fw fa-envelope-open-text"></i>
                        <span>View Principal Notices</span>
                        <?php if ($unread_principal_notices > 0): ?>
                            <span
                                class="badge badge-danger badge-counter"><?php echo ($unread_principal_notices > 9) ? '9+' : $unread_principal_notices; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($past_data_pages)) ? '' : 'collapsed'; ?>" href="#"
                    data-toggle="collapse" data-target="#collapsePastData">
                    <div><i class="fas fa-fw fa-history"></i>
                        <span>View Past Data</span>
                    </div>
                </a>
                <div id="collapsePastData" class="collapse <?php echo (is_active_page($past_data_pages)) ? 'show' : ''; ?>"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo ($current_page == 'past_school.php') ? 'active' : ''; ?>"
                            href="/BMC-SMS/pages/past_record/past_school.php">Past School List</a>
                        <a class="collapse-item <?php echo ($current_page == 'past_principal.php') ? 'active' : ''; ?>"
                            href="/BMC-SMS/pages/past_record/past_principal.php">Past Principal List</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($reports_pages)) ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseReports"
                    aria-expanded="true" aria-controls="collapseReports">
                    <div><i class="fas fa-fw fa-chart-area"></i>
                        <span>Reports</span>
                    </div>
                </a>
                <div id="collapseReports" class="collapse <?php echo (is_active_page($reports_pages)) ? 'show' : ''; ?>" aria-labelledby="headingReports" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">School Reports:</h6>
                        <a class="collapse-item <?php echo ($current_page == 'report_enrollment.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/reports/report_enrollment.php">Enrollment Report</a>
                        <a class="collapse-item <?php echo ($current_page == 'report_attendance.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/reports/report_attendance.php">Attendance Analysis</a>
                        <a class="collapse-item <?php echo ($current_page == 'report_academic.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/reports/report_academic.php">Academic Performance</a>
                        <a class="collapse-item <?php echo ($current_page == 'report_payroll.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/reports/report_payroll.php">Payroll Summary</a>
                        <a class="collapse-item <?php echo ($current_page == 'report_library.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/reports/report_library.php">Library Usage</a>
                    </div>
                </div>
            </li>
        <?php
            break;

        case 'principal':
            $teacher_pages = ['teacher_enrollment.php', 'teacher_list.php', 'teacher_attendance.php', 'view_teacher_attendance.php'];
            $librarian_pages = ['librarian_enrollment.php', 'librarian_list.php', 'librarian_attendance.php', 'view_librarian_attendance.php'];
            $student_pages = ['student_enrollment.php', 'student_list.php', 'generate_lc.php'];
            $hr_pages = ['hr_enrollment.php', 'hr_list.php', 'hr_attendance.php', 'view_hr_attendance.php'];
            $payroll_pages = ['hr_enrollment.php', 'hr_list.php', 'hr_attendance.php', 'view_hr_attendance.php'];
            $notice_pages = ['send_notice.php', 'send_notice_to_bmc.php', 'send_notice_to_librarian.php', 'view_notice.php'];
            $academics_pages = ['manage_subjects.php', 'manage_timetable.php', 'send_exam_timetable.php', 'manage_holidays.php'];
            $past_data_pages_principal = ['past_teacher.php', 'past_librarian.php', 'past_hr.php', 'past_student.php'];
            $leave_management_pages = ['teacher_leave_management.php', 'librarian_leave_management.php', 'hr_leave_management.php'];
            $is_leave_management_active = in_array($current_page, $leave_management_pages);
            $reports_pages = ['report_enrollment.php', 'report_attendance.php', 'report_academic.php', 'report_payroll.php', 'report_library.php'];

        ?>
    <div class="sidebar-heading font-weight-semibold">School Management</div>
    <li class="nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
            <div><i class="fas fa-fw fa-id-card"></i>
                <span>My Profile</span>
            </div>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo (is_active_page($teacher_pages)) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseTeacher">
            <div><i class="fas fa-fw fa-person-chalkboard"></i>
                <span>Manage Teachers</span>
            </div>
        </a>
        <div id="collapseTeacher" class="collapse <?php echo (is_active_page($teacher_pages)) ? 'show' : ''; ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo ($current_page == 'teacher_enrollment.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>includes/forms/teacher_enrollment.php">Enroll Teacher</a>
                <a class="collapse-item <?php echo ($current_page == 'teacher_list.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/teacher/teacher_list.php">Teacher List</a>
                <a class="collapse-item <?php echo ($current_page == 'teacher_attendance.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/teacher_attendance.php">Teacher Attendance</a>
                <a class="collapse-item <?php echo ($current_page == 'view_teacher_attendance.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/view_teacher_attendance.php">View Teacher
                    Attendance</a>
            </div>
        </div>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo (is_active_page($librarian_pages)) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseLibrarian">
            <div><i class="fas fa-fw fa-book-reader"></i>
                <span>Manage Librarians</span>
            </div>
        </a>
        <div id="collapseLibrarian" class="collapse <?php echo (is_active_page($librarian_pages)) ? 'show' : ''; ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo ($current_page == 'librarian_enrollment.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>includes/forms/librarian_enrollment.php">Enroll Librarian</a>
                <a class="collapse-item <?php echo ($current_page == 'librarian_list.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/librarian/librarian_list.php">Librarian List</a>
                <a class="collapse-item <?php echo ($current_page == 'librarian_attendance.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/librarian_attendance.php">Librarian Attendance</a>
                <a class="collapse-item <?php echo ($current_page == 'view_librarian_attendance.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/view_librarian_attendance.php">View Librarian
                    Attendance</a>
            </div>
        </div>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo (is_active_page($student_pages)) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseStudent">
            <div><i class="fas fa-fw fa-children"></i>
                <span>Manage Students</span>
            </div>
        </a>
        <div id="collapseStudent" class="collapse <?php echo (is_active_page($student_pages)) ? 'show' : ''; ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo ($current_page == 'student_enrollment.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>includes/forms/student_enrollment.php">Enroll Student</a>
                <a class="collapse-item <?php echo ($current_page == 'student_list.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/student/student_list.php">Student List</a>
                <a class="collapse-item <?php echo ($current_page == 'generate_lc.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/generate_lc.php">Generate LC</a>
            </div>
        </div>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo (is_active_page($hr_pages)) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapsePayrollUsers">
            <div><i class="fas fa-fw fa-users-cog"></i>
                <span>Manage HR</span>
            </div>
        </a>
        <div id="collapsePayrollUsers" class="collapse <?php echo (is_active_page($hr_pages)) ? 'show' : ''; ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo ($current_page == 'hr_enrollment.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>includes/forms/hr_enrollment.php">Enroll HR</a>
                <a class="collapse-item <?php echo ($current_page == 'hr_list.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/hr/hr_list.php">HR List</a>
                <a class="collapse-item <?php echo ($current_page == 'hr_attendance.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/hr_attendance.php">HR Attendance</a>
                <a class="collapse-item <?php echo ($current_page == 'view_hr_attendance.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/view_hr_attendance.php">View HR Attendance</a>
            </div>
        </div>
    </li>
    <?php
                $transport_pages = ['manage_vehicles.php','manage_drivers.php','manage_routes.php','teacher_transport.php','librarian_transport.php','student_transport.php'];
            ?>
            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($transport_pages)) ? '' : 'collapsed'; ?>" href="#"
                    data-toggle="collapse" data-target="#collapseTransport">
                    <div><i class="fas fa-fw fa-bus"></i>
                        <span>Transport Management</span>
                    </div>
                </a>
                <div id="collapseTransport" class="collapse <?php echo (is_active_page($transport_pages)) ? 'show' : ''; ?>"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Core Management:</h6>
                        <a class="collapse-item <?php echo ($current_page == 'manage_vehicles.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/transport/manage_vehicles.php">Manage Vehicles</a>
                        <a class="collapse-item <?php echo ($current_page == 'manage_drivers.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/transport/manage_drivers.php">Manage Drivers</a>
                        <a class="collapse-item <?php echo ($current_page == 'manage_routes.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/transport/manage_routes.php">Manage Routes & Stops</a>
                        <hr class="collapse-divider">
                        <h6 class="collapse-header">Manage Allocation:</h6>
                        <a class="collapse-item <?php echo ($current_page == 'teacher_transport.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/transport/teacher_transport.php">Teacher Transport</a>
                        <a class="collapse-item <?php echo ($current_page == 'librarian_transport.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/transport/librarian_transport.php">Librarian Transport</a>
                        <a class="collapse-item <?php echo ($current_page == 'student_transport.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/transport/student_transport.php">Student Transport</a>
                    </div>
                </div>
            </li>

            <li class="nav-item <?php echo ($current_page == 'view_my_attendance.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/principal/view_my_attendance.php">
                    <div><i class="fas fa-fw fa-user-check"></i>
                        <span>My Attendance</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_my_salary.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/principal/view_my_salary.php"
                    data-notification-type="principal_salary">
                    <div>
                        <i class="fas fa-fw fa-receipt"></i>
                        <span>My Salary History</span>
                        <?php if ($unread_principal_salary > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_principal_salary > 9) ? '9+' : $unread_principal_salary; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($notice_pages)) ? '' : 'collapsed'; ?>" href="#"
                    data-toggle="collapse" data-target="#collapseNotices">
                    <div>
                        <i class="fas fa-fw fa-bullhorn"></i>
                        <span>Notices</span>
                        <?php if ($unread_bmc_notices > 0): ?>
                            <span
                                class="badge badge-danger badge-counter"><?php echo ($unread_bmc_notices > 9) ? '9+' : $unread_bmc_notices; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <div id="collapseNotices" class="collapse <?php echo (is_active_page($notice_pages)) ? 'show' : ''; ?>"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo ($current_page == 'send_notice.php') ? 'active' : ''; ?>"
                            href="/BMC-SMS/pages/principal/send_notice.php">Send School Notice</a>
                        <a class="collapse-item <?php echo ($current_page == 'send_notice_to_bmc.php') ? 'active' : ''; ?>"
                            href="/BMC-SMS/pages/principal/send_notice_to_bmc.php">Send Notice to BMC</a>
                        <a class="collapse-item <?php echo ($current_page == 'send_notice_to_librarian.php') ? 'active' : ''; ?>"
                            href="/BMC-SMS/pages/principal/send_notice_to_librarian.php">Send Notice to Librarian</a>
                        <a class="collapse-item <?php echo ($current_page == 'view_notice.php') ? 'active' : ''; ?>"
                            href="/BMC-SMS/pages/principal/view_notice.php" data-notification-type="new_notice">View BMC Notices
                            <?php if ($unread_bmc_notices > 0): ?>
                                <span
                                    class="badge badge-danger badge-counter"><?php echo ($unread_bmc_notices > 9) ? '9+' : $unread_bmc_notices; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </li>

    <li class="nav-item">
        <a class="nav-link <?php echo (is_active_page($academics_pages)) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseAcademics">
            <div><i class="fas fa-fw fa-book"></i>
                <span>Academics</span>
            </div>
        </a>
        <div id="collapseAcademics" class="collapse <?php echo (is_active_page($academics_pages)) ? 'show' : ''; ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo ($current_page == 'manage_subjects.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/academics/manage_subjects.php">Manage Subjects</a>
                <a class="collapse-item <?php echo ($current_page == 'manage_timetable.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/academics/manage_timetable.php">Manage Timetable</a>
                <a class="collapse-item <?php echo ($current_page == 'send_exam_timetable.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/send_exam_timetable.php">Send Exam Timetable</a>
                <a class="collapse-item <?php echo ($current_page == 'manage_holidays.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/manage_holidays.php">Holiday Management</a>
            </div>
        </div>
    </li>
    <li class="nav-item <?php echo ($current_page == 'school_settings.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/principal/school_settings.php">
            <div><i class="fas fa-fw fa-cogs"></i>
                <span>School Settings</span>
            </div>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $is_leave_management_active ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseLeave">
            <div>
                <i class="fas fa-fw fa-calendar-alt"></i>
                <span>Leave Management</span>
                <?php
                            $total_leave_notifs = $unread_leave_requests + $unread_librarian_requests + $unread_hr_requests;
                            if ($total_leave_notifs > 0):
                        ?>
                <span class="badge badge-danger badge-counter">
                    <?php echo ($total_leave_notifs > 9) ? '9+' : $total_leave_notifs; ?>
                </span>
                <?php endif; ?>
            </div>
        </a>
        <div id="collapseLeave" class="collapse <?php echo $is_leave_management_active ? 'show' : ''; ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo ($current_page == 'teacher_leave_management.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/teacher_leave_management.php"
                    data-notification-type="leave_request">
                    Teacher Leave
                    <?php if ($unread_leave_requests > 0): ?>
                    <span
                        class="badge badge-danger badge-counter"><?php echo ($unread_leave_requests > 9) ? '9+' : $unread_leave_requests; ?></span>
                    <?php endif; ?>
                </a>
                <a class="collapse-item <?php echo ($current_page == 'librarian_leave_management.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/librarian_leave_management.php"
                    data-notification-type="librarian_leave_request">
                    Librarian Leave
                    <?php if ($unread_librarian_requests > 0): ?>
                    <span
                        class="badge badge-danger badge-counter"><?php echo ($unread_librarian_requests > 9) ? '9+' : $unread_librarian_requests; ?></span>
                    <?php endif; ?>
                </a>
                <a class="collapse-item <?php echo ($current_page == 'hr_leave_management.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_WEB_PATH; ?>pages/principal/hr_leave_management.php"
                    data-notification-type="hr_leave_request">
                    HR Leave
                    <?php if ($unread_hr_requests > 0): ?>
                    <span
                        class="badge badge-danger badge-counter"><?php echo ($unread_hr_requests > 9) ? '9+' : $unread_hr_requests; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo (is_active_page($past_data_pages_principal)) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapsePastDataPrincipal">
            <div><i class="fas fa-fw fa-history"></i>
                <span>View Past Data</span>
            </div>
        </a>
        <div id="collapsePastDataPrincipal"
            class="collapse <?php echo (is_active_page($past_data_pages_principal)) ? 'show' : ''; ?>"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo ($current_page == 'past_teacher.php') ? 'active' : ''; ?>"
                    href="/BMC-SMS/pages/past_record/past_teacher.php">Past Teacher List</a>
                <a class="collapse-item <?php echo ($current_page == 'past_librarian.php') ? 'active' : ''; ?>"
                    href="/BMC-SMS/pages/past_record/past_librarian.php">Past Librarian List</a>
                <a class="collapse-item <?php echo ($current_page == 'past_hr.php') ? 'active' : ''; ?>"
                    href="/BMC-SMS/pages/past_record/past_hr.php">Past HR List</a>
                <a class="collapse-item <?php echo ($current_page == 'past_student.php') ? 'active' : ''; ?>"
                    href="/BMC-SMS/pages/past_record/past_student.php">Past Student List</a>
            </div>
        </div>
    </li>
     <li class="nav-item">
        <a class="nav-link <?php echo (is_active_page($reports_pages)) ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseReports"
            aria-expanded="true" aria-controls="collapseReports">
            <div><i class="fas fa-fw fa-chart-area"></i>
                <span>Reports</span>
            </div>
        </a>
        <div id="collapseReports" class="collapse <?php echo (is_active_page($reports_pages)) ? 'show' : ''; ?>" aria-labelledby="headingReports" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">School Reports:</h6>
                <a class="collapse-item <?php echo ($current_page == 'report_enrollment.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/reports/report_enrollment.php">Enrollment Report</a>
                <a class="collapse-item <?php echo ($current_page == 'report_attendance.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/reports/report_attendance.php">Attendance Analysis</a>
                <a class="collapse-item <?php echo ($current_page == 'report_academic.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/reports/report_academic.php">Academic Performance</a>
                <a class="collapse-item <?php echo ($current_page == 'report_payroll.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/reports/report_payroll.php">Payroll Summary</a>
                <a class="collapse-item <?php echo ($current_page == 'report_library.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/reports/report_library.php">Library Usage</a>
            </div>
        </div>
    </li>
    <?php
            break;

        case 'teacher':
            $marks_pages = ['marks_entry.php', 'view_marks.php'];
            $assignment_pages = ['send_assignment.php', 'assignment_history.php'];
            $attendance_pages = ['add_lecture_attendance.php', 'view_lecture_attendance.php'];
            $library_pages_teacher = ['browse_books.php', 'my_library_record.php', 'request_new_book.php'];
        ?>
            <div class="sidebar-heading font-weight-semibold">Classroom & Actions</div>
            <li class="nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
                    <div><i class="fas fa-fw fa-id-card"></i>
                        <span>My Profile</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'student_list.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/student/student_list.php">
                    <div><i class="fas fa-fw fa-children"></i>
                        <span>My Students</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_my_attendance.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/view_my_attendance.php">
                    <div><i class="fas fa-fw fa-user-check"></i>
                        <span>My Attendance</span>
                    </div>
                </a>
            </li>

            <li class="nav-item <?php echo ($current_page == 'view_salary_history.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/view_salary_history.php"
                    data-notification-type="salary">
                    <div>
                        <i class="fas fa-fw fa-receipt"></i>
                        <span>My Salary History</span>
                        <?php if ($unread_teacher_salary > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_teacher_salary > 9) ? '9+' : $unread_teacher_salary; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>

            <?php if ($is_class_teacher): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (is_active_page($marks_pages)) ? '' : 'collapsed'; ?>" href="#"
                        data-toggle="collapse" data-target="#collapseMarks">
                        <div><i class="fas fa-fw fa-marker"></i>
                            <span>Manage Marks</span>
                        </div>
                    </a>
                    <div id="collapseMarks" class="collapse <?php echo (is_active_page($marks_pages)) ? 'show' : ''; ?>"
                        data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item <?php echo ($current_page == 'marks_entry.php') ? 'active' : ''; ?>"
                                href="<?php echo BASE_WEB_PATH; ?>pages/teacher/marks_entry/marks_entry.php">Enter Marks</a>
                            <a class="collapse-item <?php echo ($current_page == 'view_marks.php') ? 'active' : ''; ?>"
                                href="<?php echo BASE_WEB_PATH; ?>pages/teacher/marks_entry/view_marks.php">View Marks</a>
                        </div>
                    </div>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($assignment_pages)) ? '' : 'collapsed'; ?>" href="#"
                    data-toggle="collapse" data-target="#collapseAssignments">
                    <div>
                        <i class="fas fa-fw fa-book-open"></i>
                        <span>Manage Assignment</span>
                        <?php if ($unread_submissions > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_submissions > 9) ? '9+' : $unread_submissions; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
                <div id="collapseAssignments" class="collapse <?php echo (is_active_page($assignment_pages)) ? 'show' : ''; ?>"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo ($current_page == 'send_assignment.php') ? 'active' : ''; ?>"
                            href="/BMC-SMS/pages/assignments/send_assignment.php">Send Assignment</a>
                        <a class="collapse-item <?php echo ($current_page == 'assignment_history.php') ? 'active' : ''; ?>"
                            href="/BMC-SMS/pages/assignments/assignment_history.php"
                            data-notification-type="assignment_submission">Assignment History
                            <?php if ($unread_submissions > 0): ?>
                                <span class="badge badge-danger badge-counter">
                                    <?php echo ($unread_submissions > 9) ? '9+' : $unread_submissions; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </li>

            <li class="nav-item <?php echo ($current_page == 'teacher_leave_management.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/teacher_leave_management.php"
                    data-notification-type="leave_status">
                    <div>
                        <i class="fas fa-fw fa-calendar-alt"></i>
                        <span>Manage Leave</span>
                        <?php if ($unread_leave_status > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_leave_status > 9) ? '9+' : $unread_leave_status; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($attendance_pages)) ? '' : 'collapsed'; ?>" href="#"
                    data-toggle="collapse" data-target="#collapseAttendance">
                    <div><i class="fas fa-fw fa-clipboard-user"></i>
                        <span>Manage Attendance</span>
                    </div>
                </a>
                <div id="collapseAttendance" class="collapse <?php echo (is_active_page($attendance_pages)) ? 'show' : ''; ?>"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo ($current_page == 'add_lecture_attendance.php') ? 'active' : ''; ?>"
                            href="/BMC-SMS/pages/teacher/add_lecture_attendance.php">Lecture Attendance</a>
                        <a class="collapse-item <?php echo ($current_page == 'view_lecture_attendance.php') ? 'active' : ''; ?>"
                            href="/BMC-SMS/pages/teacher/view_lecture_attendance.php">View Attendance</a>
                    </div>
                </div>
            </li>

            <li class="nav-item <?php echo ($current_page == 'view_timetable.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_timetable.php">
                    <div><i class="fas fa-fw fa-calendar-week"></i>
                        <span>View Lecture Timetable</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_exam_timetable.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/teacher/view_exam_timetable.php"
                    data-notification-type="exam_timetable">
                    <div>
                        <i class="fas fa-fw fa-calendar-alt"></i>
                        <span>View Exam Timetable</span>
                        <?php if ($unread_exam_timetables > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_exam_timetables > 9) ? '9+' : $unread_exam_timetables; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'send_notes.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/teacher/send_notes.php">
                    <div><i class="fas fa-fw fa-paper-plane"></i>
                        <span>Send Notes</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_notice.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/teacher/view_notice.php" data-notification-type="school_notice">
                    <div>
                        <i class="fas fa-fw fa-bell"></i>
                        <span>View School Notices</span>
                        <?php if ($unread_teacher_notices > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_teacher_notices > 5) ? '5+' : $unread_teacher_notices; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>

            <hr class="sidebar-divider">
            <div class="sidebar-heading font-weight-semibold">Library</div>
            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($library_pages_teacher)) ? '' : 'collapsed'; ?>" href="#"
                    data-toggle="collapse" data-target="#collapseLibraryTeacher">
                    <div>
                        <i class="fas fa-fw fa-book-reader"></i>
                        <span>Library Services</span>
                        <?php if ($unread_library_status > 0): ?>
                            <span class="badge badge-danger badge-counter"><?php echo $unread_library_status; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <div id="collapseLibraryTeacher"
                    class="collapse <?php echo (is_active_page($library_pages_teacher)) ? 'show' : ''; ?>"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo ($current_page == 'browse_books.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/teacher/browse_books.php">Browse & Request Books</a>
                        <a class="collapse-item <?php echo ($current_page == 'my_library_record.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/teacher/my_library_record.php"
                            data-notification-type="borrow_status">
                            My Borrowing Record
                            <?php if ($unread_library_status > 0): ?>
                                <span class="badge badge-danger badge-counter"><?php echo $unread_library_status; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="collapse-item <?php echo ($current_page == 'request_new_book.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/user/request_new_book.php">Request New Book</a>
                        <a class="collapse-item <?php echo ($current_page == 'my_book_requests.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/user/my_book_requests.php">My Request History</a>
                    </div>
                </div>
            </li>

        <?php
            break;

        case 'student':
            $library_pages_student = ['browse_books.php', 'my_library_record.php', 'request_new_book.php'];
        ?>
            <div class="sidebar-heading font-weight-semibold">My Academics</div>
            <li class="nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
                    <div><i class="fas fa-fw fa-id-card"></i>
                        <span>My Profile</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_assignments.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/assignments/view_assignments.php"
                    data-notification-type="new_assignment">
                    <div>
                        <i class="fas fa-fw fa-clipboard-list"></i>
                        <span>View Assignments</span>
                        <?php if ($unread_assignments > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_assignments > 9) ? '9+' : $unread_assignments; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_lecture_attendance.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_lecture_attendance.php">
                    <div><i class="fas fa-fw fa-book-open-reader"></i>
                        <span>View Attendance</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_my_marks.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/student/view_my_marks.php"
                    data-notification-type="marks_uploaded">
                    <div>
                        <i class="fas fa-fw fa-file-lines"></i>
                        <span>View Results</span>
                        <?php if ($unread_results > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_results > 9) ? '9+' : $unread_results; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_notice.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_notice.php" data-notification-type="school_notice">
                    <div>
                        <i class="fas fa-fw fa-bell"></i>
                        <span>View School Notices</span>
                        <?php if ($unread_student_notices > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_student_notices > 9) ? '9+' : $unread_student_notices; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_notes.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_notes.php" data-notification-type="new_notes">
                    <div>
                        <i class="fas fa-fw fa-eye"></i>
                        <span>View Notes</span>
                        <?php if ($unread_notes > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_notes > 9) ? '9+' : $unread_notes; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_timetable.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_timetable.php">
                    <div><i class="fas fa-fw fa-table-list"></i>
                        <span>View Lecture Timetable</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_exam_timetable.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_exam_timetable.php"
                    data-notification-type="exam_timetable">
                    <div>
                        <i class="fas fa-fw fa-calendar-alt"></i>
                        <span>View Exam Timetable</span>
                        <?php if ($unread_exam_timetables > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_exam_timetables > 9) ? '9+' : $unread_exam_timetables; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>

            <li class="nav-item <?php echo ($current_page == 'view_fees.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_fees.php">
                    <div><i class="fas fa-dollar-sign"></i>
                        <span>View Fees</span>
                    </div>
                </a>
            </li>

            <hr class="sidebar-divider">
            <div class="sidebar-heading font-weight-semibold">Library</div>
            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($library_pages_student)) ? '' : 'collapsed'; ?>" href="#"
                    data-toggle="collapse" data-target="#collapseLibraryStudent">
                    <div>
                        <i class="fas fa-fw fa-book-reader"></i>
                        <span>Library Services</span>
                        <?php if ($unread_library_status > 0): ?>
                            <span class="badge badge-danger badge-counter"><?php echo $unread_library_status; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <div id="collapseLibraryStudent"
                    class="collapse <?php echo (is_active_page($library_pages_student)) ? 'show' : ''; ?>"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo ($current_page == 'browse_books.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/student/browse_books.php">Browse & Request Books</a>
                        <a class="collapse-item <?php echo ($current_page == 'my_library_record.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/student/my_library_record.php"
                            data-notification-type="borrow_status">
                            My Borrowing Record
                            <?php if ($unread_library_status > 0): ?>
                                <span class="badge badge-danger badge-counter"><?php echo $unread_library_status; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="collapse-item <?php echo ($current_page == 'request_new_book.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/user/request_new_book.php">Request New Book</a>
                        <a class="collapse-item <?php echo ($current_page == 'my_book_requests.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/user/my_book_requests.php">My Request History</a>
                    </div>
                </div>
            </li>
        <?php
            break;

        case 'librarian':
            $books_pages = ['book_list.php', 'add_new_book.php'];
            $past_data_librarian = ['past_books.php'];
            $leave_pages = ['my_leave_management.php'];
        ?>
            <div class="sidebar-heading font-weight-semibold">Library Management</div>
            <li class="nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
                    <div><i class="fas fa-fw fa-id-card"></i>
                        <span>My Profile</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_my_attendance.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/view_my_attendance.php">
                    <div><i class="fas fa-fw fa-user-check"></i>
                        <span>My Attendance</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_salary_history.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/view_salary_history.php"
                    data-notification-type="librarian_salary">
                    <div>
                        <i class="fas fa-fw fa-receipt"></i>
                        <span>My Salary History</span>
                        <?php if ($unread_salary_notifications > 0): ?>
                            <span
                                class="badge badge-danger badge-counter"><?php echo ($unread_salary_notifications > 9) ? '9+' : $unread_salary_notifications; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo (is_active_page($leave_pages)) ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/my_leave_management.php"
                    data-notification-type="librarian_leave_status">
                    <div>
                        <i class="fas fa-fw fa-calendar-alt"></i>
                        <span>Manage Leave</span>
                        <?php
                        $unread_librarian_leave_status = 0;
                        if (isset($conn) && $user_id) {
                            try {
                                $stmt_leave_status = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'librarian_leave_status' AND is_read = false");
                                $stmt_leave_status->execute([$user_id]);
                                $unread_librarian_leave_status = (int) $stmt_leave_status->fetchColumn();
                            } catch (PDOException $e) {
                                error_log("Librarian Leave Status count error: " . $e->getMessage());
                            }
                        }
                        if ($unread_librarian_leave_status > 0):
                        ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_librarian_leave_status > 9) ? '9+' : $unread_librarian_leave_status; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($books_pages)) ? '' : 'collapsed'; ?>" href="#"
                    data-toggle="collapse" data-target="#collapseBooks">
                    <div><i class="fas fa-fw fa-book-journal-whills"></i>
                        <span>Manage Books</span>
                    </div>
                </a>
                <div id="collapseBooks" class="collapse <?php echo (is_active_page($books_pages)) ? 'show' : ''; ?>"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo ($current_page == 'book_list.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/librarian/book_list.php">Book List</a>
                        <a class="collapse-item <?php echo ($current_page == 'add_new_book.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_WEB_PATH; ?>pages/librarian/add_new_book.php">Add New Book</a>
                    </div>
                </div>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_principal_notices.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/view_principal_notices.php"
                    data-notification-type="principal_to_librarian_notice">
                    <div>
                        <i class="fas fa-fw fa-envelope-open-text"></i>
                        <span>Principal Notices</span>
                        <?php if ($unread_principal_to_librarian_notices > 0): ?>
                            <span
                                class="badge badge-danger badge-counter"><?php echo ($unread_principal_to_librarian_notices > 9) ? '9+' : $unread_principal_to_librarian_notices; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'issue_return.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/issue_return.php">
                    <div><i class="fas fa-fw fa-right-left"></i>
                        <span>Issue & Return</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'borrow_requests.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/borrow_requests.php"
                    data-notification-type="borrow_request">
                    <div>
                        <i class="fas fa-fw fa-hand-holding-hand"></i>
                        <span>Borrow Requests</span>
                        <?php if ($unread_borrow_requests > 0): ?>
                            <span
                                class="badge badge-danger badge-counter"><?php echo ($unread_borrow_requests > 9) ? '9+' : $unread_borrow_requests; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'book_requests.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/book_requests.php"
                    data-notification-type="acquisition_request">
                    <div>
                        <i class="fas fa-fw fa-inbox"></i>
                        <span>Acquisition Requests</span>
                        <?php if ($unread_acquisition_requests > 0): ?>
                            <span
                                class="badge badge-danger badge-counter"><?php echo ($unread_acquisition_requests > 9) ? '9+' : $unread_acquisition_requests; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'past_book.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/past_record/past_books.php"
                    data-notification-type="acquisition_request">
                    <div>
                        <i class="fas fa-fw fa-history"></i>
                         <span>View Past Data</span>
                        <?php if ($unread_acquisition_requests > 0): ?>
                            <span
                                class="badge badge-danger badge-counter"><?php echo ($unread_acquisition_requests > 9) ? '9+' : $unread_acquisition_requests; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
        <?php
            break;

        case 'hr':
            $payroll_pages = ['process_teacher_salary.php', 'process_librarian_salary.php', 'process_hr_salary.php', 'process_principal_salary.php', 'view_salary_history.php'];
            $hr_leave_pages = ['my_leave_management.php'];
            $hr_manage_profiles_pages = ['principal_list.php', 'teacher_list.php', 'student_list.php', 'librarian_list.php'];
        ?>
            <div class="sidebar-heading font-weight-semibold">Management</div>
            <li class="nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
                    <div><i class="fas fa-fw fa-id-card"></i>
                        <span>My Profile</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_my_salary.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/hr/view_my_salary.php"
                    data-notification-type="hr_salary">
                    <div>
                        <i class="fas fa-fw fa-receipt"></i>
                        <span>My Salary History</span>
                        <?php if ($unread_hr_salary > 0): ?>
                            <span class="badge badge-danger badge-counter">
                                <?php echo ($unread_hr_salary > 9) ? '9+' : $unread_hr_salary; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo (is_active_page($hr_leave_pages)) ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/hr/my_leave_management.php"
                   data-notification-type="hr_leave_status">
                    <div>
                        <i class="fas fa-fw fa-calendar-alt"></i>
                        <span>Manage Leave</span>
                        <?php if ($unread_hr_leave_status > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_hr_leave_status > 9) ? '9+' : $unread_hr_leave_status; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (is_active_page($hr_manage_profiles_pages)) ? '' : 'collapsed'; ?>" href="#"
                   data-toggle="collapse" data-target="#collapseManageProfiles">
                    <div><i class="fas fa-fw fa-users"></i>
                    <span>Manage Profiles</span></div>
                </a>
                <div id="collapseManageProfiles" class="collapse <?php echo (is_active_page($hr_manage_profiles_pages)) ? 'show' : ''; ?>"
                     data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo ($current_page == 'principal_list.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/principal/principal_list.php">Principal Profile</a>
                        <a class="collapse-item <?php echo ($current_page == 'teacher_list.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/teacher_list.php">Teacher Profile</a>
                        <a class="collapse-item <?php echo ($current_page == 'student_list.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/student/student_list.php">Student Profile</a>
                        <a class="collapse-item <?php echo ($current_page == 'librarian_list.php') ? 'active' : ''; ?>" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/librarian_list.php">Librarian Profile</a>
                    </div>
                </div>
            </li>
            <li class="nav-item <?php echo ($current_page == 'manage_incentives.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/hr/manage_incentives.php">
                    <div><i class="fas fa-gift"></i>
                        <span>Manage Incentives</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_my_attendance.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/hr/view_my_attendance.php">
                    <div><i class="fas fa-fw fa-user-check"></i>
                        <span>My Attendance</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'manage_fees.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/hr/manage_fees.php">
                    <div><i class="fas fa-fw fa-user-check"></i>
                        <span>Add Fees</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'process_teacher_salary.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/hr/process_teacher_salary.php">
                    <div><i class="fas fa-file-invoice-dollar"></i>
                        <span>Teacher Payroll</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'process_librarian_salary.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/hr/process_librarian_salary.php">
                    <div><i class="fas fa-file-invoice-dollar"></i>
                        <span>Librarian Payroll</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'process_hr_salary.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/hr/process_hr_salary.php">
                    <div><i class="fas fa-file-invoice-dollar"></i>
                        <span>HR Payroll</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'process_principal_salary.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/hr/process_principal_salary.php">
                    <div><i class="fas fa-file-invoice-dollar"></i>
                        <span>Principal Payroll</span>
                    </div>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'view_salary_history.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="/BMC-SMS/pages/hr/view_salary_history.php">
                    <div><i class="fas fa-history"></i>
                        <span>Salary History</span>
                    </div>
                </a>
            </li>
    <?php
            break;
    }
    ?>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/BMC-SMS/assets/js/sidebar.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Find the active menu item within the sidebar.
        const activeMenuItem = document.querySelector('#accordionSidebar .active');

        // Check if an active menu item exists on the page
        if (activeMenuItem) {
            // Scroll the sidebar so the active item is vertically centered in the visible area.
            activeMenuItem.scrollIntoView({
                behavior: 'auto', // Use 'smooth' for a scrolling animation, or 'auto' for instant.
                block: 'center' // This vertically aligns the item to the center.
            });
        }
    });
</script>

<script>
// Enhanced sidebar functionality for AJAX
$(document).ready(function() {
    // Add AJAX data attributes to navigation links
    $('.nav-link, .collapse-item').each(function() {
        const href = $(this).attr('href');
        if (href && !href.startsWith('#') && !href.startsWith('javascript:') && !href.startsWith('http')) {
            $(this).attr('data-ajax', 'true');
        }
    });
    
    // Update active states based on current URL
    function updateActiveStates() {
        const currentPath = window.location.pathname;
        const currentPage = currentPath.substring(currentPath.lastIndexOf('/') + 1);
        
        $('.nav-link, .collapse-item').removeClass('active');
        $('.nav-item').removeClass('active');
        
        // Find and activate current page
        $(`.nav-link[href*="${currentPage}"], .collapse-item[href*="${currentPage}"]`).each(function() {
            $(this).addClass('active');
            $(this).closest('.nav-item').addClass('active');
            $(this).closest('.collapse').addClass('show');
        });
    }
    
    // Update active states on page load
    updateActiveStates();
    
    // Listen for AJAX page loads to update active states
    $(document).on('ajax:page:loaded', updateActiveStates);
});
</script>