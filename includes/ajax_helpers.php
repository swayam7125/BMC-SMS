<?php
require_once 'response.php';

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
 * @param string|array $options Path to the content file or options array
 */
function handle_page_request($options) {
    if(is_string($options)) {
        $options = ['content_file' => $options];
    }

    $default_options = [
        'content_file' => null,
        'title' => 'BMC-SMS',
        'scripts' => [],
        'styles' => [],
        'data' => []
    ];

    $page_options = array_merge($default_options, $options);
    
    if (!file_exists($page_options['content_file'])) {
        if(is_ajax_request()) {
            Response::error('Page not found', null, ['code' => 404]);
        } else {
            include('404.php');
            exit;
        }
    }

    // Extract data variables to make them available in the view
    extract($page_options['data']);

    // Start output buffering to capture the content
    ob_start();

    // Include the content file
    include($page_options['content_file']);

    // Get the buffered content
    $content = ob_get_clean();

    // Set up the page data
    $page_data = [
        'title' => $page_options['title'],
        'content' => $content,
        'scripts' => $page_options['scripts'],
        'styles' => $page_options['styles']
    ];

    // Include the template
    require_once 'page_template.php';
    render_page($page_data);
}

/**
 * Handle form submission and file uploads
 * @param array $options Form handling options
 */
function handle_form_submission($options) {
    $default_options = [
        'validation' => null,
        'process' => null,
        'success_message' => 'Operation completed successfully',
        'error_message' => 'An error occurred',
        'redirect' => null,
        'files' => false
    ];

    $form_options = array_merge($default_options, $options);

    // Validate form data
    $validation_result = true;
    if($form_options['validation']) {
        $validation_result = call_user_func($form_options['validation'], $_POST, $_FILES);
        if($validation_result !== true) {
            Response::error($validation_result, $form_options['redirect']);
            return;
        }
    }

    // Process form data
    if($form_options['process']) {
        try {
            $result = call_user_func($form_options['process'], $_POST, $_FILES);
            if($result === true) {
                Response::success($form_options['success_message'], $form_options['redirect']);
            } else {
                Response::error($result ?: $form_options['error_message'], $form_options['redirect']);
            }
        } catch(Exception $e) {
            Response::error($e->getMessage(), $form_options['redirect']);
        }
    }
}
?>
