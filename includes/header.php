<?php
include_once __DIR__ . "/../includes/connect.php"; // Corrected path to connect.php
include_once __DIR__ . "/../encryption.php"; // Corrected path to encryption.php

// For debugging - KEEP THIS DURING DEVELOPMENT
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FIX: Only define the constant if it hasn't been defined already.
// This prevents a "redeclaration" error when this file is included by other pages.
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/'); // Make sure this matches your actual setup.
}

// Set default values
$userProfileImage = BASE_WEB_PATH . 'assets/images/undraw_profile.svg'; // Default image relative to web root
$userName = 'Guest';
$user_role = 'User'; // Default role

// Check if user is logged in via cookie
if (isset($_COOKIE['encrypted_user_id']) && isset($_COOKIE['encrypted_user_role'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
    $user_role = decrypt_id($_COOKIE['encrypted_user_role']);

    // Set values from encrypted cookies
    if (isset($_COOKIE['encrypted_user_name'])) {
        $userName = decrypt_id($_COOKIE['encrypted_user_name']);
    }

    if (isset($_COOKIE['encrypted_profile_image'])) {
        $decrypted_image_relative_path = decrypt_id($_COOKIE['encrypted_profile_image']); // This path should already be relative from web root (e.g., 'pages/student/uploads/filename.jpg')

        // Construct the full web-accessible path
        $image_path_for_web = BASE_WEB_PATH . ltrim($decrypted_image_relative_path, '/');

        // Verify if the file actually exists on the server's filesystem
        $filesystem_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $image_path_for_web;


        if (!empty($decrypted_image_relative_path) && file_exists($filesystem_path) && is_file($filesystem_path)) {
            $userProfileImage = $image_path_for_web;
            error_log("Image found: " . $userProfileImage);
        } else {
            error_log("Image file not found at filesystem path: " . $filesystem_path . ". Using default profile image.");
            $userProfileImage = BASE_WEB_PATH . 'assets/images/undraw_profile.svg'; // Fallback to default
        }
    }

    // Debug information (keep these for development, remove for production)
    error_log("User ID: " . $user_id);
    error_log("User Role: " . $user_role);
    error_log("Username: " . $userName);
    error_log("Final Profile Image Path for Display: " . $userProfileImage);

    // Set defaults if data is missing from cookies or image path is invalid
    if (empty($userName)) {
        $userName = 'Guest';
        error_log("Using default username: Guest");
    }
} else {
    // If no user cookies are set, ensure default image and name are used
    error_log("No user cookies found. Using default profile image and name.");
}
?>
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
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
                <span class="badge badge-danger badge-counter">3+</span>
            </a>
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="alertsDropdown">
                <h6 class="dropdown-header">
                    Alerts Center
                </h6>
                <a class="dropdown-item d-flex align-items-center" href="#">
                    <div class="mr-3">
                        <div class="icon-circle bg-primary">
                            <i class="fas fa-file-alt text-white"></i>
                        </div>
                    </div>
                    <div>
                        <div class="small text-gray-500">December 12, 2019</div>
                        <span class="font-weight-bold">A new monthly report is ready to download!</span>
                    </div>
                </a>
                <a class="dropdown-item d-flex align-items-center" href="#">
                    <div class="mr-3">
                        <div class="icon-circle bg-success">
                            <i class="fas fa-donate text-white"></i>
                        </div>
                    </div>
                    <div>
                        <div class="small text-gray-500">December 7, 2019</div>
                        $290.29 has been deposited into your account!
                    </div>
                </a>
                <a class="dropdown-item d-flex align-items-center" href="#">
                    <div class="mr-3">
                        <div class="icon-circle bg-warning">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                        </div>
                    </div>
                    <div>
                        <div class="small text-gray-500">December 2, 2019</div>
                        Spending Alert: We've noticed unusually high spending for your account.
                    </div>
                </a>
                <a class="dropdown-item text-center small text-gray-500" href="#">Show All Alerts</a>
            </div>
        </li>

        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-envelope fa-fw"></i>
                <span class="badge badge-danger badge-counter">7</span>
            </a>
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="messagesDropdown">
                <h6 class="dropdown-header">
                    Message Center
                </h6>
                <a class="dropdown-item d-flex align-items-center" href="#">
                    <div class="dropdown-list-image mr-3">
                        <img class="rounded-circle" src="img/undraw_profile_1.svg" alt="...">
                        <div class="status-indicator bg-success"></div>
                    </div>
                    <div class="font-weight-bold">
                        <div class="text-truncate">Hi there! I am wondering if you can help me with a
                            problem I've been having.</div>
                        <div class="small text-gray-500">Emily Fowler · 58m</div>
                    </div>
                </a>
                <a class="dropdown-item d-flex align-items-center" href="#">
                    <div class="dropdown-list-image mr-3">
                        <img class="rounded-circle" src="img/undraw_profile_2.svg" alt="...">
                        <div class="status-indicator"></div>
                    </div>
                </a>
                <a class="dropdown-item d-flex align-items-center" href="#">
                    <div class="dropdown-list-image mr-3">
                        <img class="rounded-circle" src="img/undraw_profile_3.svg" alt="...">
                        <div class="status-indicator bg-warning"></div>
                    </div>
                    <div>
                        <div class="text-truncate">Last month's report looks great, I am very happy with
                            the progress so far, keep up the good work!</div>
                        <div class="small text-gray-500">Morgan Alvarez · 2d</div>
                    </div>
                </a>
                <a class="dropdown-item d-flex align-items-center" href="#">
                    <div class="dropdown-list-image mr-3">
                        <img class="rounded-circle" src="https://source.unsplash.com/Mv9hjnEUHR4/60x60" alt="...">
                        <div class="status-indicator bg-success"></div>
                    </div>
                    <div>
                        <div class="text-truncate">Am I a good boy? The reason I ask is because someone
                            told me that people say this to all dogs, even if they aren't good...</div>
                        <div class="small text-gray-500">Chicken the Dog · 2w</div>
                    </div>
                </a>
                <a class="dropdown-item text-center small text-gray-500" href="#">Read More Messages</a>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($userName); ?></span>
                <img class="img-profile rounded-circle"
                     src="<?php echo htmlspecialchars($userProfileImage); ?>"
                     onerror="this.src='<?php echo BASE_WEB_PATH; ?>assets/images/undraw_profile.svg';"
                     alt="Profile"
                     style="width: 32px; height: 32px; object-fit: cover;">
            </a>
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown" style="width: 280px;">
                <div class="p-3 text-center" style="background-color: #4e73df;">
                    <img class="img-profile rounded-circle mb-2"
                         src="<?php echo htmlspecialchars($userProfileImage); ?>"
                         onerror="this.src='<?php echo BASE_WEB_PATH; ?>assets/images/undraw_profile.svg';"
                         alt="Profile"
                         style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #fff;">
                    <h6 class="font-weight-bold text-white"><?php echo htmlspecialchars($userName); ?></h6>
                    <p class="mb-0 small text-white-50 text-capitalize"><?php echo htmlspecialchars($user_role); ?></p>
                </div>
                <div class="p-2">
                    <a class="dropdown-item" href="<?php echo BASE_WEB_PATH; ?>pages/user/profile.php">
                        <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                        Profile
                    </a>
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
            </div>
        </li>

    </ul>

</nav>
