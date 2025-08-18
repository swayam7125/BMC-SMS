<?php
// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>BMC-SMS</title>

    <!-- Custom fonts and styles -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="assets/css/ajax-loader.css" rel="stylesheet"
    <?php
    // Include all your custom CSS files
    $cssFiles = glob("assets/css/*.css");
    foreach($cssFiles as $css) {
        echo '<link href="' . $css . '" rel="stylesheet">' . "\n";
    }
    ?>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'includes/header.php'; ?>
                <div id="main-content" class="container-fluid">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <?php include 'includes/logout_modal.php'; ?>

    <!-- Core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/js/sb-admin-2.min.js"></script>

    <!-- AJAX Navigation and Utilities -->
    <script src="assets/js/ajax-navigation.js"></script>
    <script src="assets/js/ajax-upload.js"></script>
    
    <!-- Custom Scripts -->
    <?php
    // Include all your custom JS files
    $jsFiles = glob("assets/js/*.js");
    foreach($jsFiles as $js) {
        if(!in_array(basename($js), ['ajax-navigation.js', 'ajax-upload.js', 'sb-admin-2.min.js'])) {
            echo '<script src="' . $js . '"></script>' . "\n";
        }
    }
    ?>
</body>
</html>
