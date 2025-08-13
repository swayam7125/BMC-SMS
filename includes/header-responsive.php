<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, maximum-scale=1.0, user-scalable=no">
    <title>BMC-SMS</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_WEB_PATH; ?>assets/images/favicon.ico">

    <!-- Custom fonts -->
    <link href="<?php echo BASE_WEB_PATH; ?>assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Core theme CSS -->
    <link href="<?php echo BASE_WEB_PATH; ?>assets/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Enhanced Responsive CSS -->
    <link href="<?php echo BASE_WEB_PATH; ?>assets/css/responsive-enhanced.css" rel="stylesheet">
    
    <!-- Page specific CSS -->
    <?php if (isset($page_specific_css)): ?>
        <?php foreach ($page_specific_css as $css_file): ?>
            <link href="<?php echo BASE_WEB_PATH . $css_file; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Responsive Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="format-detection" content="telephone=no">
</head>
<body id="page-top" class="sidebar-toggled">

<!-- Page Wrapper -->
<div class="wrapper">
    <!-- Overlay for mobile sidebar -->
    <div class="sidebar-overlay"></div>
