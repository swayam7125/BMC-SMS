<?php
include_once "connect.php";
include_once "../encryption.php";

// Check if the user is logged in (basic security)
$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
if (!$current_user_id) {
    die("Access denied. You must be logged in to download files.");
}

if (isset($_GET['file'])) {
    $filename = basename($_GET['file']); // Sanitize the filename to prevent directory traversal
    $filepath = '../uploads/messages/' . $filename;

    // Security check: ensure the file is within the intended directory
    $real_filepath = realpath($filepath);
    $base_dir = realpath('../uploads/messages');

    if ($real_filepath && strpos($real_filepath, $base_dir) === 0 && file_exists($real_filepath)) {
        // Get the original filename stored in the database
        $stmt = $conn->prepare("SELECT original_filename FROM messages WHERE file_path = ?");
        $db_path = 'uploads/messages/' . $filename;
        $stmt->execute([$db_path]);
        $original_filename = $stmt->fetchColumn();

        // If no original name is stored, use the unique name
        $download_filename = $original_filename ? $original_filename : $filename;

        // Set headers to force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $download_filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($real_filepath));
        flush(); // Flush system output buffer
        readfile($real_filepath);
        exit;
    } else {
        http_response_code(404);
        die("File not found or access denied.");
    }
} else {
    http_response_code(400);
    die("No file specified.");
}
?>