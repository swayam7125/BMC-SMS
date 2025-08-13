        </div>
        <!-- End of Main Content -->

        <!-- Footer -->
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; BMC-SMS -- School Management System</span>
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

<!-- Core JavaScript-->
<script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/jquery/jquery.min.js"></script>
<script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="<?php echo BASE_WEB_PATH; ?>assets/vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Enhanced Mobile Navigation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const scrollToTop = document.querySelector('.scroll-to-top');
    
    // Sidebar Toggle
    function toggleSidebar() {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
        body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
    }
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            toggleSidebar();
        }
    });
    
    // Responsive tables
    document.querySelectorAll('table').forEach(table => {
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
    
    // Responsive images
    document.querySelectorAll('img').forEach(img => {
        if (!img.classList.contains('profile-img') && !img.classList.contains('logo-img')) {
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
        }
    });
    
    // Scroll to top button visibility
    window.addEventListener('scroll', function() {
        if (scrollToTop) {
            scrollToTop.style.display = window.pageYOffset > 100 ? 'block' : 'none';
        }
    });
    
    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                body.style.overflow = '';
            }
        }, 250);
    });
});
</script>

<!-- Page specific JavaScript -->
<?php if (isset($page_specific_js)): ?>
    <?php foreach ($page_specific_js as $js_file): ?>
        <script src="<?php echo BASE_WEB_PATH . $js_file; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
