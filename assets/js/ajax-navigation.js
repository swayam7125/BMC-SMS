$(document).ready(function() {
    // Handle all internal navigation links
    $(document).on('click', 'a[data-ajax-link]', function(e) {
        e.preventDefault();
        const page = $(this).attr('data-ajax-link');
        loadPage(page);
        
        // Update URL without page reload
        window.history.pushState({page: page}, '', page);
    });

    // Handle browser back/forward buttons
    window.onpopstate = function(e) {
        if(e.state && e.state.page) {
            loadPage(e.state.page);
        }
    };

    // Function to load pages via AJAX
    function loadPage(url) {
        // Show loading spinner
        $('#main-content').html(
            '<div class="d-flex justify-content-center">' +
            '<div class="spinner-border text-primary m-5" role="status">' +
            '<span class="visually-hidden">Loading...</span>' +
            '</div></div>'
        );

        $.ajax({
            url: url,
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                $('#main-content').html(response);
                // Reinitialize any plugins or scripts needed for the new content
                initializePlugins();
            },
            error: function(xhr, status, error) {
                $('#main-content').html(
                    '<div class="alert alert-danger m-3">' +
                    '<h4 class="alert-heading">Error Loading Page</h4>' +
                    '<p>There was an error loading the requested page: ' + error + '</p>' +
                    '</div>'
                );
            }
        });
    }

    // Function to reinitialize plugins and scripts for dynamic content
    function initializePlugins() {
        // Re-initialize DataTables if present
        if($.fn.DataTable) {
            $('.dataTable').DataTable();
        }

        // Re-initialize Select2 if present
        if($.fn.select2) {
            $('.select2').select2();
        }

        // Re-initialize any tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Re-initialize any popovers
        $('[data-toggle="popover"]').popover();

        // Trigger a custom event that other scripts can listen for
        $(document).trigger('contentLoaded');
    }

    // Load initial page if not on the index
    if(window.location.pathname !== '/' && window.location.pathname !== '/index.php') {
        loadPage(window.location.pathname);
    }
});
