<?php
// We don't need to include 'encryption.php' here because the main
// page (like dashboard.php) already includes it before this sidebar is loaded.

// Initialize role to null
$role = null;

// Read the encrypted role from the cookie and decrypt it
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
?>

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="/BMC-SMS/dashboard.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Your Portal</div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item active">
        <a class="nav-link" href="/BMC-SMS/dashboard.php">
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
                        <a class="collapse-item" href="/BMC-SMS/includes/forms/school_enrollment.php">Enroll School</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/school/school_list.php">School List</a>
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
                        <a class="collapse-item" href="/BMC-SMS/includes/forms/principal_enrollment.php">Enroll Principal</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/principal/principal_list.php">Principal List</a>
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
                        <a class="collapse-item" href="#">Past School List</a>
                        <a class="collapse-item" href="#">Past Principal List</a>
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
                        <a class="collapse-item" href="/BMC-SMS/includes/forms/teacher_enrollment.php">Enroll Teacher</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/teacher/teacher_list.php">Teacher List</a>
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
                        <a class="collapse-item" href="/BMC-SMS/includes/forms/student_enrollment.php">Enroll Student</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/student/student_list.php">Student List</a>
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
                        <a class="collapse-item" href="/BMC-SMS/pages/principal/principal_leave_requests.php">Pending Requests</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/principal/principal_leave_history.php">Application History</a>
                    </div>
                </div>
            </li>
    <?php
            break;


        // ====== Teacher Panel ======
        case 'teacher':
    ?>
            <div class="sidebar-heading">Classroom & Actions</div>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/student/student_list.php">
                    <i class="fas fa-fw fa-children"></i>
                    <span>My Students</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLeave">
                    <i class="fas fa-fw fa-calendar-alt"></i>
                    <span>Leave Management</span>
                </a>
                <div id="collapseLeave" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="/BMC-SMS/pages/teacher/teacher_leave_form.php">Apply for Leave</a>
                        <a class="collapse-item" href="/BMC-SMS/pages/teacher/teacher_leave_history.php">Leave History</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-fw fa-clipboard-user"></i>
                    <span>Manage Attendance</span>
                </a>
            </li>
    <?php
            break;


        // ====== Student Panel ======
        case 'student':
    ?>
            <div class="sidebar-heading">My Academics</div>
            <li class="nav-item">
                <a class="nav-link" href="/BMC-SMS/pages/user/profile.php">
                    <i class="fas fa-fw fa-id-card"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
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
    <?php
            break;
    }
    ?>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>