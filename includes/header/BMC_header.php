<?php
include_once __DIR__ . "/../connect.php"; // Ensure this path is correct for your setup
include_once __DIR__ . "/../../encryption.php"; // Ensure this path is correct for your setup

// For debugging - KEEP THIS DURING DEVELOPMENT
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define your project's base URL path. THIS IS CRUCIAL.
// Example 1: If your site is accessed via http://localhost/BMC-SMS/
// define('BASE_WEB_PATH', '/BMC-SMS/');
// Example 2: If your site is accessed via http://localhost/ (project in document root)
define('BASE_WEB_PATH', '/BMC-SMS/'); // Make sure this matches your actual setup.

// Set default values
$userProfileImage = BASE_WEB_PATH . 'assets/images/undraw_profile.svg'; // Default image relative to web root
$userName = 'Guest';

// Check if user is logged in via cookie
if (isset($_COOKIE['encrypted_user_id']) && isset($_COOKIE['encrypted_user_role'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']); //
    $user_role = decrypt_id($_COOKIE['encrypted_user_role']); //

    // Set values from encrypted cookies
    if (isset($_COOKIE['encrypted_user_name'])) {
        $userName = decrypt_id($_COOKIE['encrypted_user_name']); //
    }

    if (isset($_COOKIE['encrypted_profile_image'])) {
        $decrypted_image = decrypt_id($_COOKIE['encrypted_profile_image']); // Path like 'pages/student/uploads/687a228bc2425.jpg' or '../../uploads/principals/principal_687b7f94e3b2a2.34091769.png'

        // --- START DEBUGGING CODE FOR IMAGE PATH (Leave this for now) ---
        // echo "";
        // echo "<pre style='background-color:#f8d7da; color:#721c24; border:1px solid #f5c6cb; padding:10px; margin:10px; white-space:pre-wrap;'>";
        // echo "RAW Decrypted Image Path from Cookie: " . htmlspecialchars($decrypted_image) . "\n";
        // --- END DEBUGGING CODE FOR IMAGE PATH ---


        if (!empty($decrypted_image)) {
            $image_path_for_web = ''; // Initialize to empty

            // The goal is to get a path like 'pages/student/uploads/image.jpg' or 'uploads/principals/image.png'
            // and prepend BASE_WEB_PATH.
            // We need to handle two main patterns from your DB:
            // 1. 'pages/role/uploads/filename.jpg'
            // 2. '../../uploads/role/filename.jpg' (or '../uploads/role/filename.jpg')

            if (strpos($decrypted_image, 'pages/') === 0) {
                // Case 1: Path starts with 'pages/' (e.g., 'pages/student/uploads/...')
                $image_path_for_web = BASE_WEB_PATH . $decrypted_image;
            } elseif (strpos($decrypted_image, 'uploads/') !== false) {
                // Case 2: Path contains 'uploads/' but might have '../../' or '../' prefix
                // Find the first occurrence of 'uploads/'
                $uploads_pos = strpos($decrypted_image, 'uploads/');
                if ($uploads_pos !== false) {
                    $relative_uploads_path = substr($decrypted_image, $uploads_pos); // e.g., 'uploads/principals/image.png'
                    $image_path_for_web = BASE_WEB_PATH . $relative_uploads_path;
                } else {
                    // Fallback if 'uploads/' isn't found, should ideally not happen for DB paths
                    $image_path_for_web = BASE_WEB_PATH . ltrim($decrypted_image, '/');
                }
            } else {
                // Generic fallback if neither pattern matches, or if it's already an asset path
                $image_path_for_web = BASE_WEB_PATH . ltrim($decrypted_image, '/');
            }

            // --- Continue DEBUGGING CODE FOR IMAGE PATH ---
            // echo "Proposed Web-Accessible Path: " . htmlspecialchars($image_path_for_web) . "\n";
            // --- END DEBUGGING CODE FOR IMAGE PATH ---

            // Verify if the file actually exists on the server's filesystem
            $filesystem_path = $_SERVER['DOCUMENT_ROOT'] . $image_path_for_web;

            // --- Continue DEBUGGING CODE FOR IMAGE PATH ---
            // echo "Proposed Filesystem Path for file_exists: " . htmlspecialchars($filesystem_path) . "\n";
            // --- END DEBUGGING CODE FOR IMAGE PATH ---

            if (file_exists($filesystem_path) && is_file($filesystem_path)) {
                $userProfileImage = $image_path_for_web;
                error_log("Image found: " . $userProfileImage); //
                // --- Continue DEBUGGING CODE FOR IMAGE PATH ---
                // echo "RESULT: File EXISTS at Filesystem Path.\n";
                // --- END DEBUGGING CODE FOR IMAGE PATH ---
            } else {
                error_log("Image file not found at filesystem path: " . $filesystem_path . ". Using default profile image."); //
                $userProfileImage = BASE_WEB_PATH . 'assets/images/undraw_profile.svg'; // Fallback to default
                // --- Continue DEBUGGING CODE FOR IMAGE PATH ---
                echo "RESULT: File DOES NOT EXIST at Filesystem Path.\n";
                // --- END DEBUGGING CODE FOR IMAGE PATH ---
            }
        } else {
            // --- Continue DEBUGGING CODE FOR IMAGE PATH ---
            echo "Decrypted Image Path is EMPTY.\n";
            // --- END DEBUGGING CODE FOR IMAGE PATH ---
        }
        // --- Finalize DEBUGGING CODE FOR IMAGE PATH ---
        echo "</pre>";
        echo "";
        // --- End Finalize DEBUGGING CODE FOR IMAGE PATH ---
    }

    // Debug information (keep these for development, remove for production)
    error_log("User ID: " . $user_id); //
    error_log("User Role: " . $user_role); //
    error_log("Username: " . $userName); //
    error_log("Final Profile Image Path for Display: " . $userProfileImage); //

    // Set defaults if data is missing from cookies or image path is invalid
    if (empty($userName)) {
        $userName = 'Guest';
        error_log("Using default username: Guest"); //
    }
} else {
    // If no user cookies are set, ensure default image and name are used
    error_log("No user cookies found. Using default profile image and name."); //
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
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="#">
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
        </li>

    </ul>

</nav>