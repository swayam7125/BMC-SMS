<?php
require_once "../connect.php";
require_once "../../encryption.php";
require_once "../ajax_helpers.php";

if (!is_ajax_request()) {
    header("Location: ../../dashboard.php");
    exit;
}

$page = $_GET['page'] ?? '';
$allowed_pages = [
    'dashboard',
    'principal_list',
    'teacher_list',
    'student_list',
    'librarian_list',
    'school_list',
    // Add more pages as needed
];

$page_clean = str_replace('.php', '', basename($page));

if (!in_array($page_clean, $allowed_pages)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Page not found']);
    exit;
}

// Get user role for permission checking
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Include the requested page
$page_path = "../../pages/" . $page_clean . ".php";
if (file_exists($page_path)) {
    // Capture output
    ob_start();
    include $page_path;
    $content = ob_get_clean();
    
    // Extract main content
    if (preg_match('/<div class="container-fluid".*?>(.*?)<\/div>/s', $content, $matches)) {
        echo $matches[1];
    } else {
        echo $content;
    }
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Page not found']);
}
?>
