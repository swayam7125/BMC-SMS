<?php
$role = null;
$user_id = null;

// Read the encrypted role from the cookie and decrypt it
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Define BASE_WEB_PATH if it's not already defined
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}
?>

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo BASE_WEB_PATH; ?>dashboard.php">
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

        // ====== BMC Admin Panel ======
        case 'bmc':
    ?>
            <div class="sidebar-heading">Admin Controls</div>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSchool">
                    <i class="fas fa-fw fa-school"></i>
                    <span>School Management</span>
                </a>
                <div id="collapseSchool" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>includes/forms/school_enrollment.php">Enroll School</a>
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
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>includes/forms/principal_enrollment.php">Enroll Principal</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/principal/principal_list.php">Principal List</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/bmc/send_notice.php">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Send Notice</span>
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


        // ====== School Admin (Principal) Panel ======
        case 'schooladmin':
        ?>
            <div class="sidebar-heading">School Management</div>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTeacher">
                    <i class="fas fa-fw fa-person-chalkboard"></i>
                    <span>Manage Teachers</span>
                </a>
                <div id="collapseTeacher" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>includes/forms/teacher_enrollment.php">Enroll Teacher</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/teacher_list.php">Teacher List</a>
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
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>includes/forms/student_enrollment.php">Enroll Student</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/student/student_list.php">Student List</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseNotices">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Notices</span>
                </a>
                <div id="collapseNotices" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="/BMC-SMS/pages/principal/send_notice.php">Send School Notice</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/principal/view_notice.php">View BMC Notices</a>
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
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/academics/manage_subjects.php">Manage Subjects</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLeaveManagement">
                    <i class="fas fa-fw fa-calendar-alt"></i>
                    <span>Leave Management</span>
                </a>
                <div id="collapseLeaveManagement" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/principal/principal_leave_requests.php">Pending Requests</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/principal/principal_leave_history.php">Application History</a>
                    </div>
                </div>
            </li>
             <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePastData">
                    <i class="fas fa-fw fa-history"></i>
                    <span>View Past Data</span>
                </a>
                <div id="collapsePastData" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="/BMC-SMS/pages/past_record/past_teacher.php">Past Teacher List</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/past_record/past_student.php">Past Student List</a>
                    </div>
                </div>
            </li>
        <?php
            break;


        // ====== Teacher Panel ======
        case 'teacher':
            // Check if the logged-in teacher is a class teacher
            $is_class_teacher = false;
            // FIX: Check if $conn object exists and the connection is still alive before using it
            if ($user_id && isset($conn) && $conn->ping()) {
                // Use object-oriented style for consistency
                $stmt_check = $conn->prepare("SELECT class_teacher FROM teacher WHERE id = ?");
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
    ?>
            <div class="sidebar-heading">Classroom & Actions</div>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/student/student_list.php">
                    <i class="fas fa-fw fa-children"></i>
                    <span>My Students</span>
                </a>
            </li>
            
            <?php if ($is_class_teacher): ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMarks">
                    <i class="fas fa-fw fa-marker"></i>
                    <span>Marks Management</span>
                </a>
                <div id="collapseMarks" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/marks_entry/marks_entry.php">Enter Marks</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/marks_entry/view_marks.php">View Marks</a>
                    </div>
                </div>
            </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAssignments">
                    <i class="fas fa-fw fa-book-open"></i>
                    <span>Assignment Management</span>
                </a>
                <div id="collapseAssignments" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="/BMC-SMS/pages/assignments/send_assignment.php">Send Assignment</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/assignments/assignment_history.php">Assignment History</a>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLeave">
                    <i class="fas fa-fw fa-calendar-alt"></i>
                    <span>Leave Management</span>
                </a>
                <div id="collapseLeave" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/teacher_leave_form.php">Apply for Leave</a>
                        <a class="collapse-item" href="<?php echo BASE_WEB_PATH; ?>pages/teacher/teacher_leave_history.php">Leave History</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAttendance">
                    <i class="fas fa-fw fa-clipboard-user"></i>
                    <span>Manage Attendance</span>
                </a>
                <div id="collapseAttendance" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="/BMC-SMS/pages/teacher/add_attendance.php">Add Attendance</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/teacher/view_attendance.php">View Attendance</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/teacher/send_notes.php">
                    <i class="fas fa-fw fa-paper-plane"></i>
                    <span>Send Notes</span></a>
            </li>
            <li class="nav-item">
                 <a class="nav-link" href="/BMC-SMS/pages/teacher/view_notice.php">
                    <i class="fas fa-fw fa-bell"></i>
                    <span>View School Notices</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/teacher/send_timetable.php">
                    <i class="fas fa-fw fa-calendar-days"></i>
                    <span>Send Timetable</span>
                </a>
            </li>

        <?php
            break;


        // ====== Student Panel ======
        case 'student':
        ?>
            <div class="sidebar-heading">My Academics</div>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
                    <i class="fas fa-fw fa-id-card"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/assignments/view_assignments.php">
                    <i class="fas fa-fw fa-clipboard-list"></i>
                    <span>View Assignments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_attendance.php">
                    <i class="fas fa-fw fa-book-open-reader"></i>
                    <span>View Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-fw fa-file-lines"></i>
                    <span>View Results</span>
                </a>
            </li>
             <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_notice.php">
                    <i class="fas fa-fw fa-bell"></i>
                    <span>View School Notices</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_notes.php">
                    <i class="fas fa-fw fa-eye"></i>
                    <span>View Notes</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/student/view_timetable.php">
                    <i class="fas fa-fw fa-table-list"></i>
                    <span>View Timetable</span>
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