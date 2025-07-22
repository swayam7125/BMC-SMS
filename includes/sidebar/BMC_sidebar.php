<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="/BMC-SMS/dashboard.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3"> </div>
        <!-- add Dashboard name dynamically -->
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item active">
        <a class="nav-link" href="/BMC-SMS/dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        <!-- Anrollment -->
    </div>

    <!-- Nav Item - School Management -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSchool" aria-expanded="true"
            aria-controls="collapseSchool">
            <i class="fas fa-fw fa-school"></i>
            <span>School</span>
        </a>
        <div id="collapseSchool" class="collapse" aria-labelledby="headingSchool" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">School Management:</h6>
                <a class="collapse-item" href="/BMC-SMS/includes/forms/school_enrollment.php">School Enrollment</a>
                <a class="collapse-item" href="/BMC-SMS/pages/school/school_list.php">School List</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Principal Management -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePrincipal"
            aria-expanded="true" aria-controls="collapsePrincipal">
            <i class="fas fa-fw fa-user-tie"></i>
            <span>Principal</span>
        </a>
        <div id="collapsePrincipal" class="collapse" aria-labelledby="headingPrincipal" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Principal Management:</h6>
                <a class="collapse-item" href="/BMC-SMS/includes/forms/principal_enrollment.php">Principal Enrollment</a>
                <a class="collapse-item" href="/BMC-SMS/pages/principal/principal_list.php">Principal List</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - List of teacher -->
    <li class="nav-item">
        <a class="nav-link" href="/BMC-SMS/pages/teacher/teacher_list.php">
            <i class=" fas fa-fw fa-person-chalkboard"></i>
            <span>List of Teachers</span></a>
    </li>

    <!-- Nav Item - List of Student -->
    <li class="nav-item">
        <a class="nav-link" href="/BMC-SMS/pages/student/student_list.php">
            <i class="fas fa-fw fa-children"></i>
            <span>List of Student</span></a>
    </li>

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePrincipal"
            aria-expanded="true" aria-controls="collapsePrincipal">
            <i class="fas fa-fw fa-history"></i>
            <span>View Past Data</span>
        </a>
        <div id="collapsePrincipal" class="collapse" aria-labelledby="headingPrincipal" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Principal :</h6>
                <a class="collapse-item" href="/BMC-SMS/includes/forms/principal_enrollment.php">Principal Enrollment</a>
                <a class="collapse-item" href="/BMC-SMS/pages/principal/principal_list.php">Principal List</a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>