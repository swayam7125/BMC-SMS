    <!-- Footer -->
    <footer class="sticky-footer bg-white">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
                <span>Copyright &copy;BMC-SMS -- School Management System</span>
            </div>
        </div>
    </footer>
    <!-- End of Footer -->

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Bootstrap core JavaScript-->
<script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/jquery/jquery.min.js"></script>
<script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="<?php echo BASE_WEB_PATH; ?>assets/js/sb-admin-2.min.js"></script>

    <!-- Mobile responsiveness script -->
    <script src="<?php echo BASE_WEB_PATH; ?>assets/js/mobile.min.js"></script><?php if (isset($page_specific_js)): ?>
    <?php foreach ($page_specific_js as $js_file): ?>
        <script src="<?php echo BASE_WEB_PATH . $js_file; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
