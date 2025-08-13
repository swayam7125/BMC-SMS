<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="description" content="BMC School Management System">
    <meta name="author" content="">
    <title>BMC-SMS</title>

    <!-- Custom fonts -->
    <link href="<?php echo BASE_WEB_PATH; ?>assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Bootstrap core CSS -->
    <link href="<?php echo BASE_WEB_PATH; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Core theme CSS -->
    <link href="<?php echo BASE_WEB_PATH; ?>assets/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Component styles -->
    <link href="<?php echo BASE_WEB_PATH; ?>assets/css/components/utilities.css" rel="stylesheet">
    <link href="<?php echo BASE_WEB_PATH; ?>assets/css/components/forms-tables.css" rel="stylesheet">
    <link href="<?php echo BASE_WEB_PATH; ?>assets/css/components/sidebar.css" rel="stylesheet">
    
    <!-- Page specific CSS -->
    <?php if (isset($page_specific_css)): ?>
        <?php foreach ($page_specific_css as $css_file): ?>
            <link href="<?php echo BASE_WEB_PATH . $css_file; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Core scripts -->
    <script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/jquery/jquery.min.js"></script>
    <script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/jquery-easing/jquery.easing.min.js"></script>
</head>
<body id="page-top" class="sidebar-toggled">

<!-- Page Wrapper -->
<div id="wrapper">
