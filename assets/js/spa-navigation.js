// spa-navigation.js - FINAL VERSION

$(document).ready(function() {
    // This is the container where page content will be loaded.
    const contentContainer = '#main-content';
    
    /**
     * This function fetches content from a URL and loads it into our container.
     * @param {string} url - The URL of the page content to load.
     * @param {boolean} isPopState - True if the call is from the back/forward button.
     */
    function loadContent(url, isPopState = false) {
        // Show a loading indicator for better user experience.
        $(contentContainer).html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');

        $.ajax({
            url: url,
            type: 'GET',
            // The success function will receive the pure HTML content from our PHP files.
            success: function(response) {
                // The response from the server will ONLY be the content, so we inject it directly.
                $(contentContainer).html(response);

                // Update the browser's URL in the address bar, but only for new clicks.
                if (!isPopState) {
                    window.history.pushState({ path: url }, '', url);
                }
            },
            error: function() {
                // If the AJAX call fails, show an error.
                $(contentContainer).html('<p class="text-center text-danger">Error: Could not load page content.</p>');
            }
        });
    }

    // --- INTERCEPT ALL CLICKS ---
    // This part captures a click on any link and loads its content via AJAX.
    $(document).on('click', 'a', function(e) {
        const href = $(this).attr('href');

        // Ignore links that are not meant for AJAX navigation.
        if (!href || href.startsWith('#') || $(this).attr('target') === '_blank' || href.startsWith('http') || href.startsWith('javascript:')) {
            return;
        }
        
        // Prevent the browser from doing a full page reload.
        e.preventDefault();
        
        // Load the content from the link's href.
        loadContent(href);
    });

    // --- HANDLE BACK/FORWARD BUTTONS ---
    $(window).on('popstate', function(e) {
        // When the user hits back or forward, load the content for that history state.
        if (e.originalEvent.state && e.originalEvent.state.path) {
            loadContent(e.originalEvent.state.path, true);
        }
    });

    // --- LOAD INITIAL CONTENT ---
    // When the main page first loads, we need to load the dashboard content into our empty shell.
    // Check the current path to decide what to load initially.
    // This assumes the main entry point is index.php or dashboard.php
    let initialUrl = window.location.pathname.endsWith('index.php') || window.location.pathname.endsWith('/') 
                   ? '/BMC-SMS/dashboard.php' 
                   : window.location.pathname;
    loadContent(initialUrl, true);
});