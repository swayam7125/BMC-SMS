<?php
$role = null;
$user_id = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

// --- CORRECTED: FETCH UNREAD NOTIFICATION COUNTS WITH PDO ---
$unread_assignments = 0; $unread_results = 0; $unread_student_notices = 0; $unread_notes = 0;
$unread_bmc_notices = 0; $unread_leave_requests = 0; $unread_principal_notices = 0;
$unread_teacher_notices = 0; $unread_submissions = 0; $unread_leave_status = 0;
$unread_exam_timetables = 0; $unread_borrow_requests = 0; $unread_acquisition_requests = 0; $unread_library_status = 0;
$is_class_teacher = false;

// The check `$conn->ping()` has been removed as it is not a PDO method.
if (isset($conn) && $user_id) {
    try {
        switch ($role) {
            case 'student':
                $sql_counts = "SELECT
                    SUM(CASE WHEN type = 'new_assignment' THEN 1 ELSE 0 END) AS assignments,
                    SUM(CASE WHEN type = 'marks_uploaded' THEN 1 ELSE 0 END) AS results,
                    SUM(CASE WHEN type = 'school_notice' THEN 1 ELSE 0 END) AS notices,
                    SUM(CASE WHEN type = 'new_notes' THEN 1 ELSE 0 END) AS notes,
                    SUM(CASE WHEN type = 'exam_timetable' THEN 1 ELSE 0 END) AS exam_timetables,
                    SUM(CASE WHEN type = 'borrow_status' THEN 1 ELSE 0 END) AS library_status
                    FROM notifications WHERE user_id = ? AND is_read = false";
                $stmt_counts = $conn->prepare($sql_counts);
                $stmt_counts->execute([$user_id]);
                $result = $stmt_counts->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $unread_assignments = (int)($result['assignments'] ?? 0);
                    $unread_results = (int)($result['results'] ?? 0);
                    $unread_student_notices = (int)($result['notices'] ?? 0);
                    $unread_notes = (int)($result['notes'] ?? 0);
                    $unread_exam_timetables = (int)($result['exam_timetables'] ?? 0);
                    $unread_library_status = (int)($result['library_status'] ?? 0);
                }
                break;

            case 'principal':
                $sql_principal_counts = "SELECT
                                        SUM(CASE WHEN type = 'new_notice' THEN 1 ELSE 0 END) AS bmc_notices,
                                        SUM(CASE WHEN type = 'leave_request' THEN 1 ELSE 0 END) AS leave_requests
                                     FROM notifications WHERE user_id = ? AND is_read = false";
                $stmt_principal_counts = $conn->prepare($sql_principal_counts);
                $stmt_principal_counts->execute([$user_id]);
                $result_principal = $stmt_principal_counts->fetch(PDO::FETCH_ASSOC);
                if ($result_principal) {
                    $unread_bmc_notices = (int) ($result_principal['bmc_notices'] ?? 0);
                    $unread_leave_requests = (int) ($result_principal['leave_requests'] ?? 0);
                }
                break;

            case 'teacher':
                $sql_teacher_counts = "SELECT
                                      SUM(CASE WHEN type = 'school_notice' THEN 1 ELSE 0 END) AS teacher_notices,
                                      SUM(CASE WHEN type = 'assignment_submission' THEN 1 ELSE 0 END) AS submissions,
                                      SUM(CASE WHEN type = 'leave_status' THEN 1 ELSE 0 END) AS leave_status,
                                      SUM(CASE WHEN type = 'exam_timetable' THEN 1 ELSE 0 END) AS exam_timetables,
                                      SUM(CASE WHEN type = 'borrow_status' THEN 1 ELSE 0 END) AS library_status
                                   FROM notifications WHERE user_id = ? AND is_read = false";
                $stmt_teacher_counts = $conn->prepare($sql_teacher_counts);
                $stmt_teacher_counts->execute([$user_id]);
                $result_teacher = $stmt_teacher_counts->fetch(PDO::FETCH_ASSOC);
                if ($result_teacher) {
                    $unread_teacher_notices = (int) ($result_teacher['teacher_notices'] ?? 0);
                    $unread_submissions = (int) ($result_teacher['submissions'] ?? 0);
                    $unread_leave_status = (int) ($result_teacher['leave_status'] ?? 0);
                    $unread_exam_timetables = (int) ($result_teacher['exam_timetables'] ?? 0);
                    $unread_library_status = (int) ($result_teacher['library_status'] ?? 0);
                }
                
                // --- CORRECTED: Using PDO to check if class teacher ---
                $stmt_check = $conn->prepare('SELECT "class_teacher" FROM "teacher" WHERE "id" = ?');
                $stmt_check->execute([$user_id]);
                $is_class_teacher = $stmt_check->fetchColumn();
                break;

            case 'librarian':
                $sql_librarian_counts = "SELECT
                                        SUM(CASE WHEN type = 'borrow_request' THEN 1 ELSE 0 END) AS borrow_reqs,
                                        SUM(CASE WHEN type = 'acquisition_request' THEN 1 ELSE 0 END) AS acq_reqs
                                     FROM notifications WHERE user_id = ? AND is_read = false";
                $stmt_librarian_counts = $conn->prepare($sql_librarian_counts);
                $stmt_librarian_counts->execute([$user_id]);
                $result_librarian = $stmt_librarian_counts->fetch(PDO::FETCH_ASSOC);
                if ($result_librarian) {
                    $unread_borrow_requests = (int) ($result_librarian['borrow_reqs'] ?? 0);
                    $unread_acquisition_requests = (int) ($result_librarian['acq_reqs'] ?? 0);
                }
                break;

            case 'superadmin':
                $sql_bmc_counts = 'SELECT COUNT(*) FROM "notifications" WHERE "user_id" = ? AND "type" = \'principal_notice\' AND "is_read" = false';
                $stmt_bmc_counts = $conn->prepare($sql_bmc_counts);
                $stmt_bmc_counts->execute([$user_id]);
                $unread_principal_notices = (int) $stmt_bmc_counts->fetchColumn();
                break;
        }
    } catch (PDOException $e) {
        error_log("Sidebar notification count failed: " . $e->getMessage());
    }
}
?>

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center"
        href="<?php echo BASE_WEB_PATH; ?>dashboard.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item active">
        <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <hr class="sidebar-divider">

    <?php
    // Use a switch statement to show menu items based on the user's role
    switch ($role) {

        // ====== Super Admin Admin Panel ======
        case 'superadmin':
    ?>
            <div class="sidebar-heading font-weight-semibold">Admin Controls</div>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSchool">
                    <i class="fas fa-fw fa-school"></i>
                    <span>School Management</span>
                </a>
                <div id="collapseSchool" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>includes/forms/school_enrollment.php">Enroll
                            School</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/school/school_list.php">School List</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePrincipal">
                    <i class="fas fa-fw fa-user-tie"></i>
                    <span>Principal Management</span>
                </a>
                <div id="collapsePrincipal" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item"
                            href="<?php echo BASE_WEB_PATH; ?>includes/forms/principal_enrollment.php">Enroll Principal</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/principal/principal_list.php">Principal
                            List</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/bmc/principal_attendance.php">
                    <i class="fas fa-fw fa-user-clock"></i>
                    <span>Principal Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/bmc/send_notice.php">
                    <i class="fas fa-fw fa-paper-plane"></i>
                    <span>Send Notice to Principals</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/bmc/view_principal_notices.php" data-notification-type="principal_notice">
                    <i class="fas fa-fw fa-envelope-open-text"></i>
                    <span>View Principal Notices</span>
                    <?php if ($unread_principal_notices > 0): ?>
                        <span class="badge badge-danger badge-counter"><?php echo ($unread_principal_notices > 9) ? '9+' : $unread_principal_notices; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePastData">
                    <i class="fas fa-fw fa-history"></i>
                    <span>View Past Data</span>
                </a>
                <div id="collapsePastData" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="/BMC-SMS/pages/past_record/past_school.php">Past school List</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/past_record/past_principal.php">Past principal List</a>
                    </div>
                </div>
            </li>
        <?php
            break;


        // ====== Principal Panel ======
        case 'principal':
        ?>
            <div class="sidebar-heading font-weight-semibold">School Management</div>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTeacher">
                    <i class="fas fa-fw fa-person-chalkboard"></i>
                    <span>Manage Teachers</span>
                </a>
                <div id="collapseTeacher" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>includes/forms/teacher_enrollment.php">Enroll
                            Teacher</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/teacher_list.php">Teacher
                            List</a>
                            <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/principal/teacher_attendence.php">Teacher
                            Attendance</a>
                            <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/principal/view_teacher_attendence.php">View Teacher
                            Attendance</a>
                    </div>
                </div>
            </li>
             <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLibrarian">
                    <i class="fas fa-fw fa-book-reader"></i>
                    <span>Manage Librarians</span>
                </a>
                <div id="collapseLibrarian" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>includes/forms/librarian_enrollment.php">Enroll Librarian</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/librarian_list.php">Librarian List</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/principal/librarian_attendance.php">Librarian Attendance</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/principal/view_librarian_attendance.php">View Librarian Attendance</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseStudent">
                    <i class="fas fa-fw fa-children"></i>
                    <span>Manage Students</span>
                </a>
                <div id="collapseStudent" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>includes/forms/student_enrollment.php">Enroll
                            Student</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/student/student_list.php">Student
                            List</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/principal/generate_lc.php">Generate LC</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/principal/view_my_attendance.php">
                    <i class="fas fa-fw fa-user-check"></i>
                    <span>My Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseNotices">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Notices</span>
                    <?php if ($unread_bmc_notices > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_bmc_notices > 9) ? '9+' : $unread_bmc_notices; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <div id="collapseNotices" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="/BMC-SMS/pages/principal/send_notice.php">Send School Notice</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/principal/send_notice_to_bmc.php">Send Notice to BMC</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/principal/view_notice.php" data-notification-type="new_notice">View BMC Notices
                            <?php if ($unread_bmc_notices > 0): ?>
                                <span class="badge badge-danger badge-counter">
                                    <?php echo ($unread_bmc_notices > 9) ? '9+' : $unread_bmc_notices; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAcademics">
                    <i class="fas fa-fw fa-book"></i>
                    <span>Academics</span>
                </a>
                <div id="collapseAcademics" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/academics/manage_subjects.php">Manage
                            Subjects</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/academics/manage_timetable.php">Manage
                            Timetable</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/principal/send_exam_timetable.php">Send Exam Timetable</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/principal/school_settings.php">
                    <i class="fas fa-fw fa-children"></i>
                    <span>Passing Criteria</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLeaveManagement">
                    <i class="fas fa-fw fa-calendar-alt"></i>
                    <span>Teacher Leave</span>
                    <?php if ($unread_leave_requests > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_leave_requests > 9) ? '9+' : $unread_leave_requests; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <div id="collapseLeaveManagement" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/principal/principal_leave_requests.php" data-notification-type="leave_request">
                            Pending Requests
                            <?php if ($unread_leave_requests > 0): ?>
                                <span class="badge badge-danger badge-counter">
                                    <?php echo ($unread_leave_requests > 9) ? '9+' : $unread_leave_requests; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <a class="collapse-item"
                            href="<?php echo BASE_WEB_PATH; ?>pages/principal/principal_leave_history.php">Application
                            History</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePastDataPrincipal">
                    <i class="fas fa-fw fa-history"></i>
                    <span>View Past Data</span>
                </a>
                <div id="collapsePastDataPrincipal" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="/BMC-SMS/pages/past_record/past_teacher.php">Past Teacher List</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/past_record/past_librarian.php">Past Librarian List</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/past_record/past_student.php">Past Student List</a>
                    </div>
                </div>
            </li>
        <?php
            break;


        // ====== Teacher Panel ======
        case 'teacher':
            $is_class_teacher = false;
            if ($user_id && isset($conn)) {
                $stmt_check = $conn->prepare("SELECT class_teacher FROM teacher WHERE id = ?");
                if ($stmt_check) {
                    $stmt_check->bind_param("i", $user_id);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    if ($teacher_details = $result_check->fetch_assoc()) {
                        if ($teacher_details['class_teacher'] == 1) {
                            $is_class_teacher = true;
                        }
                    }
                    $stmt_check->close();
                }
            }
        ?>
            <div class="sidebar-heading font-weight-semibold">Classroom & Actions</div>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/student/student_list.php">
                    <i class="fas fa-fw fa-children"></i>
                    <span>My Students</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/view_my_attendance.php">
                    <i class="fas fa-fw fa-user-check"></i>
                    <span>My Attendance</span>
                </a>
            </li>

            <?php if ($is_class_teacher): ?>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMarks">
                        <i class="fas fa-fw fa-marker"></i>
                        <span>Manage Marks</span>
                    </a>
                    <div id="collapseMarks" class="collapse" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item"
                                href="<?php echo BASE_WEB_PATH; ?>pages/teacher/marks_entry/marks_entry.php">Enter Marks</a>
                            <a class="collapse-item"
                                href="<?php echo BASE_WEB_PATH; ?>pages/teacher/marks_entry/view_marks.php">View Marks</a>
                        </div>
                    </div>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAssignments">
                    <i class="fas fa-fw fa-book-open"></i>
                    <span>Manage Assignment</span>
                    <?php if ($unread_submissions > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_submissions > 9) ? '9+' : $unread_submissions; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <div id="collapseAssignments" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="/BMC-SMS/pages/assignments/send_assignment.php">Send Assignment</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/assignments/assignment_history.php" data-notification-type="assignment_submission">Assignment History</a>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/teacher_leave_management.php" data-notification-type="leave_status">
                    <i class="fas fa-fw fa-calendar-alt"></i>
                    <span>Manage Leave</span>
                    <?php if ($unread_leave_status > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_leave_status > 9) ? '9+' : $unread_leave_status; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAttendance">
                    <i class="fas fa-fw fa-clipboard-user"></i>
                    <span>Manage Attendance</span>
                </a>
                <div id="collapseAttendance" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="/BMC-SMS/pages/teacher/add_lecture_attendance.php">Lecture Attendance</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/teacher/view_lecture_attendance.php">View Attendance</a>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_timetable.php">
                    <i class="fas fa-fw fa-calendar-week"></i>
                    <span>View Lecture Timetable</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/teacher/view_exam_timetable.php" data-notification-type="exam_timetable">
                    <i class="fas fa-fw fa-calendar-alt"></i>
                    <span>View Exam Timetable</span>
                    <?php if ($unread_exam_timetables > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_exam_timetables > 9) ? '9+' : $unread_exam_timetables; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/teacher/send_notes.php">
                    <i class="fas fa-fw fa-paper-plane"></i>
                    <span>Send Notes</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/teacher/view_notice.php" data-notification-type="school_notice">
                    <i class="fas fa-fw fa-bell"></i>
                    <span>View School Notices</span>
                     <?php if ($unread_teacher_notices > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_teacher_notices > 9) ? '9+' : $unread_teacher_notices; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            
            <hr class="sidebar-divider">
            <div class="sidebar-heading font-weight-semibold">Library</div>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLibraryTeacher">
                    <i class="fas fa-fw fa-book-reader"></i>
                    <span>Library Services</span>
                    <?php if ($unread_library_status > 0): ?>
                        <span class="badge badge-danger badge-counter"><?php echo $unread_library_status; ?></span>
                    <?php endif; ?>
                </a>
                <div id="collapseLibraryTeacher" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/browse_books.php">Browse & Request Books</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/my_library_record.php" data-notification-type="borrow_status">
                            My Borrowing Record
                            <?php if ($unread_library_status > 0): ?>
                                <span class="badge badge-danger badge-counter"><?php echo $unread_library_status; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/user/request_new_book.php">Request New Book</a>
                    </div>
                </div>
            </li>

        <?php
            break;


        // ====== Student Panel ======
        case 'student':
        ?>
            <div class="sidebar-heading font-weight-semibold">My Academics</div>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
                    <i class="fas fa-fw fa-id-card"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/assignments/view_assignments.php" data-notification-type="new_assignment">
                    <i class="fas fa-fw fa-clipboard-list"></i>
                    <span>View Assignments</span>
                    <?php if ($unread_assignments > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_assignments > 9) ? '9+' : $unread_assignments; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_lecture_attendance.php">
                    <i class="fas fa-fw fa-book-open-reader"></i>
                    <span>View Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/student/view_my_marks.php" data-notification-type="marks_uploaded">
                    <i class="fas fa-fw fa-file-lines"></i>
                    <span>View Results</span>
                    <?php if ($unread_results > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_results > 9) ? '9+' : $unread_results; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_notice.php" data-notification-type="school_notice">
                    <i class="fas fa-fw fa-bell"></i>
                    <span>View School Notices</span>
                    <?php if ($unread_student_notices > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_student_notices > 9) ? '9+' : $unread_student_notices; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_notes.php" data-notification-type="new_notes">
                    <i class="fas fa-fw fa-eye"></i>
                    <span>View Notes</span>
                    <?php if ($unread_notes > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_notes > 9) ? '9+' : $unread_notes; ?>
                        </span>
                    <?php endif; ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_timetable.php">
                    <i class="fas fa-fw fa-table-list"></i>
                    <span>View Lecture Timetable</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_exam_timetable.php" data-notification-type="exam_timetable">
                    <i class="fas fa-fw fa-calendar-alt"></i>
                    <span>View Exam Timetable</span>
                    <?php if ($unread_exam_timetables > 0): ?>
                        <span class="badge badge-danger badge-counter">
                            <?php echo ($unread_exam_timetables > 9) ? '9+' : $unread_exam_timetables; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>

            <hr class="sidebar-divider">
            <div class="sidebar-heading font-weight-semibold">Library</div>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLibraryStudent">
                    <i class="fas fa-fw fa-book-reader"></i>
                    <span>Library Services</span>
                     <?php if ($unread_library_status > 0): ?>
                        <span class="badge badge-danger badge-counter"><?php echo $unread_library_status; ?></span>
                    <?php endif; ?>
                </a>
                <div id="collapseLibraryStudent" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/student/browse_books.php">Browse & Request Books</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/student/my_library_record.php" data-notification-type="borrow_status">
                            My Borrowing Record
                            <?php if ($unread_library_status > 0): ?>
                                <span class="badge badge-danger badge-counter"><?php echo $unread_library_status; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/user/request_new_book.php">Request New Book</a>
                    </div>
                </div>
            </li>
        <?php
            break;

        // ====== Librarian Panel ======
        case 'librarian':
        ?>
            <div class="sidebar-heading font-weight-semibold">Library Management</div>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
                    <i class="fas fa-fw fa-id-card"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/view_my_attendance.php">
                    <i class="fas fa-fw fa-user-check"></i>
                    <span>My Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBooks">
                    <i class="fas fa-fw fa-book-journal-whills"></i>
                    <span>Manage Books</span>
                </a>
                <div id="collapseBooks" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/book_list.php">Book List</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/add_new_book.php">Add New Book</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/issue_return.php">
                    <i class="fas fa-fw fa-right-left"></i>
                    <span>Issue & Return</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/borrow_requests.php" data-notification-type="borrow_request">
                    <i class="fas fa-fw fa-hand-holding-hand"></i>
                    <span>Borrow Requests</span>
                    <?php if ($unread_borrow_requests > 0): ?>
                        <span class="badge badge-danger badge-counter"><?php echo ($unread_borrow_requests > 9) ? '9+' : $unread_borrow_requests; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/book_requests.php" data-notification-type="acquisition_request">
                    <i class="fas fa-fw fa-inbox"></i>
                    <span>Acquisition Requests</span>
                     <?php if ($unread_acquisition_requests > 0): ?>
                        <span class="badge badge-danger badge-counter"><?php echo ($unread_acquisition_requests > 9) ? '9+' : $unread_acquisition_requests; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePastDataLibrarian">
                    <i class="fas fa-fw fa-history"></i>
                    <span>View Past Data</span>
                </a>
                <div id="collapsePastDataLibrarian" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/librarian/past_books.php">Past Book Records</a>
                    </div>
                </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery) {
        // Function to handle badge removal on click
        $('#accordionSidebar .nav-link').on('click', function() {
            var link = $(this);
            var badge = link.find('.badge-counter');
            var notificationType = link.data('notification-type'); // Get the type from data attribute

            // If the link has a badge and a defined notification type
            if (badge.length > 0 && notificationType) {
                // 1. Visually remove the badge immediately for good user experience
                badge.fadeOut('fast', function() {
                    $(this).remove();
                });

                // 2. Send a request to the backend to mark these notifications as read
                $.post('<?php echo BASE_WEB_PATH; ?>includes/actions/mark_notifications_read.php', 
                    { type: notificationType }, 
                    function(response) {
                        if (response.status !== 'success') {
                            console.error('Failed to mark notifications as read:', response.message);
                        }
                    },
                    'json' // Expect a JSON response
                ).fail(function() {
                    console.error('AJAX request failed.');
                });
            }
        });
    }
});
</script>