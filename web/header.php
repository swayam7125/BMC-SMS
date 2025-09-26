<?php
// --- 1. Safely Include Database Connection ---
@include_once '../includes/connect.php';

// Define the ID of the school to feature
$school_id_to_feature = 4;

// --- 2. Initialize $school_info with a complete set of robust fallback values ---
$school_info = [
    'school_name' => 'Sanskar Bharti Vidyalay', // Default name
    'logo_path' => 'images/Group2.svg', // Ensure this path is correct for your default logo
    'email' => 'sbv@example.com',
    'phone' => '+91 8526548525',
    'address' => 'Crossroad, Beside D-Mart, Katargam, Surat-395001'
];

// Check if the connection object ($conn) exists after including connect.php
if (isset($conn)) {
    try {
        // --- 3. Attempt to fetch specific school details from the 'school' table (ID 4) ---
        $stmt = $conn->prepare("SELECT school_name, school_logo AS logo_path, email, phone, address FROM school WHERE id = :school_id");
        $stmt->bindParam(':school_id', $school_id_to_feature, PDO::PARAM_INT);
        $stmt->execute();
        $fetched_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fetched_info) {
            // Overwrite defaults with fetched data
            $school_info = array_merge($school_info, $fetched_info);

            // Correct the logo_path mapping if necessary (from school_logo)
            // Also add a base path if school_logo from DB is relative and needs it
            if (isset($fetched_info['logo_path']) && !empty($fetched_info['logo_path'])) {
                // Assuming logo paths like '/BMC-SMS/uploads/school_logos/school_4_...'
                // You might need to adjust this base path based on your server configuration
                $base_upload_path = '/BMC-SMS/'; // Adjust if your root path is different
                if (strpos($fetched_info['logo_path'], $base_upload_path) === 0) {
                     $school_info['logo_path'] = $fetched_info['logo_path'];
                } else {
                    // Prepend a default path if the DB path is just a filename or relative
                    $school_info['logo_path'] = $base_upload_path . $fetched_info['logo_path'];
                }
            }
        }
        
    } catch (PDOException $e) {
        // Log the error but continue using fallback data
        error_log("Database Query Error in header.php: " . $e->getMessage());
    }
} else {
    // Log if the connection object was never initialized
    error_log("Database connection file could not be included or \$conn was not initialized.");
}

// Final check for logo_path to ensure it's not empty and has a sensible default
if (empty($school_info['logo_path'])) {
    $school_info['logo_path'] = 'images/Group2.svg'; // Fallback image if DB path is empty
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . htmlspecialchars($school_info['school_name']) : htmlspecialchars($school_info['school_name']); ?></title>

    <link rel="stylesheet" href="vendors/owl-carousel/css/owl.carousel.min.css">
    <link rel="stylesheet" href="vendors/owl-carousel/css/owl.theme.default.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/aos/css/aos.css">
    <link rel="stylesheet" href="css/style.min.css">

    <style>
        /* --- Smooth Transitions --- */
        .btn,
        .card,
        .nav-link {
            transition: all 0.3s ease-in-out;
        }

        /* --- Interactive Card Hover Effect --- */
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* --- Button Hover Effect --- */
        .btn-info:hover,
        .btn-opacity-light:hover,
        .btn-opacity-success:hover {
            transform: scale(1.05);
        }

        /* --- Form Input Focus Effect --- */
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        /* --- Nav Link Hover Effect --- */
        .navbar .nav-link:hover {
            color: #4e73df !important;
        }

        /* --- Consistent Header Shadow --- */
        #header-section {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .navbar-brand span {
            vertical-align: middle;
        }

        /* --- NEW CSS FOR LOGO SIZING --- */
        .navbar-brand img {
            max-height: 40px; /* Adjust this value as needed */
            width: auto; /* Maintain aspect ratio */
            vertical-align: middle;
        }
        /* Optional: If the parent wrapper itself is too big, constrain it */
        .navbar-brand-wrapper {
            max-height: 60px; /* Example, adjust to give space around logo */
            overflow: hidden; /* Hide anything that overflows */
            display: flex;
            align-items: center; /* Vertically align items */
        }
        /* Ensure the entire navbar doesn't get too tall */
        .navbar {
            min-height: 60px; /* Adjust to ensure navbar has consistent height */
        }
    </style>
</head>

<body id="body" data-spy="scroll" data-target=".navbar" data-offset="100">

    <header id="header-section">
        <nav class="navbar navbar-expand-lg pl-3 pl-sm-0" id="navbar">
            <div class="container">
                <div class="navbar-brand-wrapper d-flex w-100">
                    <a href="index.php" class="navbar-brand d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($school_info['logo_path']); ?>" alt="<?php echo htmlspecialchars($school_info['school_name']); ?> Logo">
                        <span class="font-weight-bold ml-2 text-dark d-none d-md-block"><?php echo htmlspecialchars($school_info['school_name']); ?></span>
                    </a>
                    <button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#navbarSupportedContent">
                        <span class="mdi mdi-menu navbar-toggler-icon"></span>
                    </button>
                </div>
                <div class="collapse navbar-collapse navbar-menu-wrapper" id="navbarSupportedContent">
                    <ul class="navbar-nav align-items-lg-center align-items-start ml-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="admission.php">Admissions</a></li>
                        <li class="nav-item"><a class="nav-link" href="blog.php">Blog</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                        <li class="nav-item btn-contact-us pl-4 pl-lg-0"><a class="btn btn-info" href="../login.php">Login</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>