<?php
// This file can be expanded to include your Supabase connection logic
// For now, we'll fetch school info using your existing connect.php
require_once '../includes/connect.php';

try {
    // Fetch school name and logo from the database
    $stmt = $conn->query("SELECT school_name, logo_path FROM school_info WHERE id = 1");
    $school_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback values if the database is unavailable
    $school_info = ['school_name' => 'BMC School', 'logo_path' => 'images/Group2.svg'];
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
                        <li class="nav-item btn-contact-us pl-4 pl-lg-0"><a class="btn btn-info" href="/login.php">Login</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>