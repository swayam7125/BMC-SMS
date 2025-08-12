<?php
// Initialize any needed variables
$page_specific_css = [
    'assets/css/components/login.css'
];

$page_specific_js = [
    'assets/js/login.js'
];

// Include header
include_once 'includes/head.php';
include_once 'includes/header.php';
include_once 'includes/sidebar.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Your page content here -->
    </div>

</div>
<!-- End of Main Content -->

<?php 
// Include footer and scripts
include_once 'includes/footer.php';
include_once 'includes/scripts.php';
include_once 'includes/logout_modal.php';
?>

</div>
<!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

</body>
</html>
