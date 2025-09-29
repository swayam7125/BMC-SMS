<?php

// /BMC-SMS/index.php (UPDATED ROUTER & LAYOUT)

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. SETUP & SECURITY ---
require_once __DIR__ . "/includes/connect.php";
require_once __DIR__ . "/encryption.php";
require_once __DIR__ . "/includes/ajax_helpers.php"; // Contains is_ajax_request()

// Check if user is logged in. If not, redirect to login page.
if (!isset($_COOKIE['encrypted_user_role'])) {
    // If it's an AJAX request, return JSON error
    if (is_ajax_request()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Please login to continue.', 'redirect' => 'login.php']);
        exit;
    }
    // For regular requests, redirect to login
    header("Location: login.php");
    exit;
}

// --- 2. PAGE ROUTING ---

// Determine which page to load. Default to 'dashboard'.
$page_identifier = $_GET['page'] ?? 'dashboard';

// Sanitize the page identifier to prevent directory traversal
$page_identifier = preg_replace('/[^a-zA-Z0-9_-]/', '', $page_identifier);

// A mapping of simple URL identifiers to the actual file paths.
$page_map = [
    'dashboard' => __DIR__ . '/dashboard.php',

    // Superadmin
    'school_enrollment' => __DIR__ . '/includes/forms/school_enrollment.php',
    'school_list' => __DIR__ . '/pages/school/school_list.php',
    'principal_enrollment' => __DIR__ . '/includes/forms/principal_enrollment.php',
    'principal_list' => __DIR__ . '/pages/principal/principal_list.php',
    'principal_attendance' => __DIR__ . '/pages/bmc/principal_attendance.php',
    'send_notice' => __DIR__ . '/pages/bmc/send_notice.php',
    'view_principal_notices' => __DIR__ . '/pages/bmc/view_principal_notices.php',
    'past_school' => __DIR__ . '/pages/past_record/past_school.php',
    'past_principal' => __DIR__ . '/pages/past_record/past_principal.php',

    // Principal
    'teacher_enrollment' => __DIR__ . '/includes/forms/teacher_enrollment.php',
    'teacher_list' => __DIR__ . '/pages/teacher/teacher_list.php',
    'teacher_attendance' => __DIR__ . '/pages/principal/teacher_attendance.php',
    'view_teacher_attendance' => __DIR__ . '/pages/principal/view_teacher_attendance.php',
    'librarian_enrollment' => __DIR__ . '/includes/forms/librarian_enrollment.php',
    'librarian_list' => __DIR__ . '/pages/librarian/librarian_list.php',
    'librarian_attendance' => __DIR__ . '/pages/principal/librarian_attendance.php',
    'view_librarian_attendance' => __DIR__ . '/pages/principal/view_librarian_attendance.php',
    'student_enrollment' => __DIR__ . '/includes/forms/student_enrollment.php',
    'student_list' => __DIR__ . '/pages/student/student_list.php',
    'generate_lc' => __DIR__ . '/pages/principal/generate_lc.php',
    'hr_enrollment' => __DIR__ . '/includes/forms/hr_enrollment.php',
    'hr_list' => __DIR__ . '/pages/hr/hr_list.php',
    'hr_attendance' => __DIR__ . '/pages/principal/hr_attendance.php',
    'view_hr_attendance' => __DIR__ . '/pages/principal/view_hr_attendance.php',
    'manage_vehicles' => __DIR__ . '/pages/transport/manage_vehicles.php',
    'manage_drivers' => __DIR__ . '/pages/transport/manage_drivers.php',
    'manage_routes' => __DIR__ . '/pages/transport/manage_routes.php',
    'teacher_transport' => __DIR__ . '/pages/transport/teacher_transport.php',
    'librarian_transport' => __DIR__ . '/pages/transport/librarian_transport.php',
    'student_transport' => __DIR__ . '/pages/transport/student_transport.php',
    'hr_transport' => __DIR__ . '/pages/transport/hr_transport.php',
    'view_my_salary' => __DIR__ . '/pages/principal/view_my_salary.php',
    'send_school_notices' => __DIR__ . '/pages/principal/send_notice.php',
    'send_school_notices_to_bmc' => __DIR__ . '/pages/principal/send_notice_to_bmc.php',
    'send_school_notices_to_librarian' => __DIR__ . '/pages/principal/send_notice_to_librarian.php',
    'view_bmc_notice' => __DIR__ . '/pages/principal/view_notice.php',
    'manage_subjects' => __DIR__ . '/pages/academics/manage_subjects.php',
    'manage_timetable' => __DIR__ . '/pages/academics/manage_timetable.php',
    'send_exam_timetable' => __DIR__ . '/pages/principal/send_exam_timetable.php',
    'manage_holidays' => __DIR__ . '/pages/principal/manage_holidays.php',
    'school_settings' => __DIR__ . '/pages/principal/school_settings.php',
    'teacher_leave_management' => __DIR__ . '/pages/principal/teacher_leave_management.php',
    'librarian_leave_management' => __DIR__ . '/pages/principal/librarian_leave_management.php',
    'past_teacher' => __DIR__ . '/pages/past_record/past_teacher.php',
    'past_librarian' => __DIR__ . '/pages/past_record/past_librarian.php',
    'past_student' => __DIR__ . '/pages/past_record/past_student.php',
    'report_enrollment' => __DIR__ . '/pages/reports/report_enrollment.php',
    'report_attendance' => __DIR__ . '/pages/reports/report_attendance.php',
    'report_academic' => __DIR__ . '/pages/reports/report_academic.php',
    'report_payroll' => __DIR__ . '/pages/reports/report_payroll.php',
    'report_library' => __DIR__ . '/pages/reports/report_library.php',
    'user_profile' => __DIR__ . '/pages/user/profile.php',

    // Teacher
    'view_my_attendance_teacher' => __DIR__ . '/pages/teacher/view_my_attendance.php',
    'view_salary_history_teacher' => __DIR__ . '/pages/teacher/view_salary_history.php',
    'marks_entry_teacher' => __DIR__ . '/pages/teacher/marks_entry/marks_entry.php',
    'view_marks_teacher' => __DIR__ . '/pages/teacher/marks_entry/view_marks.php',
    'assignment_history' => __DIR__ . '/pages/assignments/assignment_history.php',
    'add_lecture_attendance' => __DIR__ . '/pages/teacher/add_lecture_attendance.php',
    'send_notes' => __DIR__ . '/pages/teacher/send_notes.php',
    'my_library_record' => __DIR__ . '/pages/teacher/my_library_record.php',
    'my_book_requests' => __DIR__ . '/pages/user/my_book_requests.php',

    // Student
    'view_assignments' => __DIR__ . '/pages/assignments/view_assignments.php',
    'view_lecture_attendance' => __DIR__ . '/pages/student/view_lecture_attendance.php',
    'view_my_marks' => __DIR__ . '/pages/student/view_my_marks.php',
    'view_notice' => __DIR__ . '/pages/student/view_notice.php',
    'view_notes' => __DIR__ . '/pages/student/view_notes.php',
    'view_timetable' => __DIR__ . '/pages/student/view_timetable.php',
    'view_exam_timetable' => __DIR__ . '/pages/student/view_exam_timetable.php',
    'browse_books' => __DIR__ . '/pages/student/browse_books.php',
    'request_new_book' => __DIR__ . '/pages/user/request_new_book.php',
    'view_fees' => __DIR__ . '/pages/student/view_fees.php',

    // Librarian
    'view_my_attendance' => __DIR__ . '/pages/librarian/view_my_attendance.php',
    'view_salary_history' => __DIR__ . '/pages/librarian/view_salary_history.php',
    'my_leave_management' => __DIR__ . '/pages/librarian/my_leave_management.php',
    'book_list' => __DIR__ . '/pages/librarian/book_list.php',
    'add_new_book' => __DIR__ . '/pages/librarian/add_new_book.php',
    'issue_return' => __DIR__ . '/pages/librarian/issue_return.php',
    'borrow_requests' => __DIR__ . '/pages/librarian/borrow_requests.php',
    'book_requests' => __DIR__ . '/pages/librarian/book_requests.php',
    'past_books' => __DIR__ . '/pages/past_record/past_books.php',

    // HR
    'manage_incentives' => __DIR__ . '/pages/hr/manage_incentives.php',
    'process_teacher_salary' => __DIR__ . '/pages/hr/process_teacher_salary.php',
    'process_librarian_salary' => __DIR__ . '/pages/hr/process_librarian_salary.php',
    'process_principal_salary' => __DIR__ . '/pages/hr/process_principal_salary.php',
    'manage_fees' => __DIR__ . '/pages/hr/manage_fees.php',
];

// Check if the requested page identifier exists in our map.
if (!array_key_exists($page_identifier, $page_map)) {
    // If not, show a 404 error
    if (is_ajax_request()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Page not found']);
        exit;
    }
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 Page Not Found</h1><p>The requested page was not found.</p>";
    exit;
}

$content_file = $page_map[$page_identifier];

// --- 3. CORE LOGIC: AJAX vs. FULL PAGE LOAD ---

// If this is an AJAX request, we only include the content file and stop.
if (is_ajax_request()) {
    if (file_exists($content_file)) {
        include $content_file;
    } else {
        // Handle case where file doesn't exist for an AJAX request
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Content file not found']);
    }
    exit; // IMPORTANT: Stop script execution for AJAX requests.
}

// If it's a normal browser request, proceed to load the full HTML layout below.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>BMC School Management</title>
    <link rel="shortcut icon" href="./assets/images/favicon.ico" type="image/x-icon">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/notification_window.css" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sidebar.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">

        <?php // include __DIR__ . '/includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php // include __DIR__ . '/includes/header.php'; ?>

                <div id="main-content">
                    <?php
                    // For regular page loads, include the content normally
                    if (file_exists($content_file)) {
                        echo "<div id='page-content'>";
                        include $content_file;
                        echo "</div>";
                    } else {
                        echo "<div class='container-fluid'>";
                        echo "<div class='alert alert-danger'>";
                        echo "<h4>Error: Content Not Found</h4>";
                        echo "<p>The requested content could not be loaded. Please try again or contact support.</p>";
                        echo "</div>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
            <?php // include __DIR__ . '/includes/footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <?php include_once __DIR__ . "/includes/logout_modal.php" ?>

    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>

    <script src="/BMC-SMS/assets/js/sidebar.js"></script>
    <script src="/BMC-SMS/assets/js/ajax-navigation.js"></script>
    <script src="/BMC-SMS/assets/js/notification.js"></script>
</body>

</html>