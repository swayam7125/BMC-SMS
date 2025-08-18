<?php
require_once 'layout.php';
require_once 'page_assets.php';

/**
 * A template for creating consistent page layouts
 * 
 * @param array $data Array containing:
 *   - title: Page title
 *   - content: Main content HTML/PHP
 *   - scripts: Array of additional script files to include
 *   - styles: Array of additional style files to include
 */
function render_page($data = []) {
    $default_data = [
        'title' => 'BMC-SMS',
        'content' => '',
        'scripts' => [],
        'styles' => [],
        'active_page' => basename($_SERVER['PHP_SELF'])
    ];
    
    // Merge default data with provided data
    $page_data = array_merge($default_data, $data);
    
    // Get page-specific assets
    $page_assets = get_page_assets($page_data['active_page']);
    
    // Merge page-specific assets with provided assets
    $page_data['scripts'] = array_merge($page_assets['scripts'], $page_data['scripts']);
    $page_data['styles'] = array_merge($page_assets['styles'], $page_data['styles']);

    // Initialize and render layout
    $layout = new Layout($page_data);
    $layout->render();
}