<?php
/**
 * Check if the current request is an AJAX request
 * @return boolean
 */
function is_ajax_request() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}

/**
 * Handle page routing for AJAX requests
 * @param string $content_file Path to the content file
 */
function handle_page_request($content_file) {
    if(is_ajax_request()) {
        // For AJAX requests, only return the content
        include($content_file);
    } else {
        // For direct access, include the full layout
        include('includes/layout.php');
    }
}
?>
