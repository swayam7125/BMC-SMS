<?php
// Define BASE_WEB_PATH if not already defined
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}
?>
<!-- Page level plugins -->
<script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/chart.js/Chart.min.js"></script>
<script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="<?php echo BASE_WEB_PATH; ?>assets/js/sb-admin-2.min.js"></script>

<!-- Custom scripts for components -->
<script src="<?php echo BASE_WEB_PATH; ?>assets/js/sidebar.js"></script>
<script src="<?php echo BASE_WEB_PATH; ?>assets/js/notification.js"></script>
<script src="<?php echo BASE_WEB_PATH; ?>assets/js/message.js"></script>

<!-- Page specific scripts -->
<?php if (isset($page_specific_js)): ?>
    <?php foreach ($page_specific_js as $js_file): ?>
        <script src="<?php echo BASE_WEB_PATH . $js_file; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
