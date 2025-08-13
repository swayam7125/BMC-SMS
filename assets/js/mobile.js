document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    // Get sidebar element
    const sidebar = document.querySelector('#accordionSidebar');
    const sidebarToggleBtn = document.querySelector('#sidebarToggleTop');
    
    function toggleMobileSidebar() {
        sidebar.classList.toggle('toggled');
        overlay.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('toggled') ? 'hidden' : '';
    }

    // Handle sidebar toggle button click
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMobileSidebar();
        });
    }

    // Close sidebar when clicking overlay
    overlay.addEventListener('click', function() {
        if (sidebar.classList.contains('toggled')) {
            toggleMobileSidebar();
        }
    });

    // Handle window resize
    let timeoutId;
    window.addEventListener('resize', function() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(function() {
            if (window.innerWidth > 768) {
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        }, 250);
    });

    // Close sidebar when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('toggled')) {
            toggleMobileSidebar();
        }
    });

    // Make tables responsive
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    // Make images responsive
    const images = document.querySelectorAll('img:not(.profile-img):not(.logo-img)');
    images.forEach(img => {
        img.style.maxWidth = '100%';
        img.style.height = 'auto';
    });
});

// Initialize dropdowns for mobile
document.querySelectorAll('.dropdown-toggle').forEach(item => {
    item.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            e.preventDefault();
            const dropdownMenu = this.nextElementSibling;
            dropdownMenu.classList.toggle('show');
            
            // Close other open dropdowns
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                if (menu !== dropdownMenu) {
                    menu.classList.remove('show');
                }
            });
        }
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.matches('.dropdown-toggle')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});
