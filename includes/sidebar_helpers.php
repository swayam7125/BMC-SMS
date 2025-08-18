<?php
// Add this at the top of your sidebar.php, after the initial role and user_id declarations

function get_link_attributes($href, $is_active = false) {
    $classes = [];
    if ($is_active) {
        $classes[] = 'active';
    }
    
    // Base attributes
    $attrs = [
        'href' => $href,
        'data-ajax-link' => $href, // Add this for AJAX navigation
        'class' => implode(' ', $classes)
    ];
    
    // Build the attributes string
    $attr_string = '';
    foreach ($attrs as $key => $value) {
        $attr_string .= " {$key}=\"{$value}\"";
    }
    return $attr_string;
}
?>
