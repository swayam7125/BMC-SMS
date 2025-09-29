<?php
/**
 * Global helper functions for BMC-SMS
 */

/**
 * Format a flash message for display
 * * @param string $message The message to display
 * @param string $type The type of message (success, error, warning, info)
 * @return string Formatted HTML for the message
 */
if (!function_exists('format_flash_message')) {
function format_flash_message($message, $type = 'success') {
    $icon = [
        'success' => 'check-circle',
        'error' => 'exclamation-circle',
        'warning' => 'exclamation-triangle',
        'info' => 'info-circle'
    ][$type] ?? 'info-circle';

    $alert_type = ($type == 'error') ? 'danger' : $type;

    return '
    <div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">
        <i class="fas fa-' . $icon . ' mr-1"></i>
        ' . htmlspecialchars($message) . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>';
}
}


/**
 * Display flash messages
 */
if (!function_exists('display_flash_messages')) {
function display_flash_messages() {
    $message_types = ['success', 'error', 'warning', 'info'];
    foreach($message_types as $type) {
        if(isset($_SESSION[$type])) {
            echo format_flash_message($_SESSION[$type], $type);
            unset($_SESSION[$type]);
        }
    }
}
}

/**
 * Generate a CSRF token
 * * @return string CSRF token
 */
if (!function_exists('generate_csrf_token')) {
function generate_csrf_token() {
    if(empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
}

/**
 * Validate CSRF token
 * * @param string $token Token to validate
 * @return bool True if valid
 */
if (!function_exists('validate_csrf_token')) {
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
}

/**
 * Add CSRF field to a form
 * * @return string HTML for CSRF field
 */
if (!function_exists('csrf_field')) {
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}
}

/**
 * Get the current page name from URL
 * * @return string Current page name
 */
if (!function_exists('get_current_page')) {
function get_current_page() {
    return basename($_SERVER['PHP_SELF']);
}
}

/**
 * Check if the current page matches any in the array
 * * @param array $pages Array of page names to check
 * @return bool True if current page is in array
 */
if (!function_exists('is_active_page')) {
function is_active_page($pages)
{
    $current_page = basename($_SERVER['SCRIPT_NAME']);
    // Ensure $pages is an array before using in_array
    return is_array($pages) && in_array($current_page, $pages);
}
}

/**
 * Format a date according to the application's default format
 * * @param string $date Date string
 * @param string $format Optional format string
 * @return string Formatted date
 */
if (!function_exists('format_date')) {
function format_date($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}
}

/**
 * Sanitize output for HTML display
 * * @param string $str String to sanitize
 * @return string Sanitized string
 */
if (!function_exists('h')) {
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
}

/**
 * Get the base URL for the application
 * * @return string Base URL
 */
if (!function_exists('base_url')) {
function base_url() {
    return '/BMC-SMS/';
}
}

/**
 * Convert a path to a URL relative to the base URL
 * * @param string $path Path to convert
 * @return string URL
 */
if (!function_exists('url')) {
function url($path) {
    return rtrim(base_url(), '/') . '/' . ltrim($path, '/');
}
}

/**
 * Format a number as currency
 * * @param float $amount Amount to format
 * @param string $currency Currency code
 * @return string Formatted amount
 */
if (!function_exists('format_currency')) {
function format_currency($amount, $currency = 'INR') {
    $fmt = new NumberFormatter('en_IN', NumberFormatter::CURRENCY);
    return $fmt->formatCurrency($amount, $currency);
}
}
// Removed the trailing '}' which was closing the PHP block incorrectly in the original file

// End of functions.php
?>