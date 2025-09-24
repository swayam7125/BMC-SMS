<?php
require_once "../connect.php";
require_once "../../encryption.php";
require_once "../ajax_helpers.php";

// Ensure this is an AJAX request
if (!is_ajax_request()) {
    header("Location: ../../dashboard.php");
    exit;
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'errors' => []];

try {
    // Example form processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($name)) {
            $response['errors']['name'] = 'Name is required';
        }
        
        if (empty($email)) {
            $response['errors']['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['errors']['email'] = 'Please enter a valid email address';
        }
        
        if (empty($response['errors'])) {
            // Process the form
            $stmt = $conn->prepare("INSERT INTO example_table (name, email) VALUES (?, ?)");
            if ($stmt->execute([$name, $email])) {
                $response['success'] = true;
                $response['message'] = 'Record added successfully!';
                $response['reload'] = true; // or 'redirect' => '/path/to/page.php'
            } else {
                $response['message'] = 'Failed to save record';
            }
        } else {
            $response['message'] = 'Please fix the errors below';
        }
    }
    
} catch (Exception $e) {
    error_log("Form handler error: " . $e->getMessage());
    $response['message'] = 'An error occurred while processing your request';
}

echo json_encode($response);
?>
<script>
    $(document).ready(function() {
    window.ajaxHandler = new AjaxHandler();
    
    // Mark notification as read when clicked
    $(document).on('click', '[data-notification-type]', function() {
        const notificationType = $(this).data('notification-type');
        if (notificationType) {
            $.post('/BMC-SMS/includes/ajax/mark_notifications_read.php', {
                type: notificationType
            });
        }
    });
});
</script>