<?php
require_once "../connect.php";
require_once "../../encryption.php";
require_once "../ajax_helpers.php";

if (!is_ajax_request()) {
    header("Location: ../../dashboard.php");
    exit;
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';
    $user_id = (int) ($_POST['id'] ?? 0);
    
    if (!$user_id || !$action) {
        $response['message'] = 'Invalid request parameters';
        echo json_encode($response);
        exit;
    }
    
    switch ($action) {
        case 'suspend':
            $stmt = $conn->prepare("UPDATE users SET account_status = 'suspended' WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $response['success'] = true;
                $response['message'] = 'User suspended successfully';
            } else {
                $response['message'] = 'Failed to suspend user';
            }
            break;
            
        case 'reactivate':
            $stmt = $conn->prepare("UPDATE users SET account_status = 'active' WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $response['success'] = true;
                $response['message'] = 'User reactivated successfully';
            } else {
                $response['message'] = 'Failed to reactivate user';
            }
            break;
            
        case 'delete':
            // Start transaction for cascading deletes
            $conn->beginTransaction();
            
            try {
                // Delete from specific tables based on user type
                $role_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $role_stmt->execute([$user_id]);
                $user_role = $role_stmt->fetchColumn();
                
                // Delete role-specific data
                switch ($user_role) {
                    case 'principal':
                        $conn->prepare("DELETE FROM principal WHERE id = ?")->execute([$user_id]);
                        break;
                    case 'teacher':
                        $conn->prepare("DELETE FROM teacher WHERE id = ?")->execute([$user_id]);
                        break;
                    case 'student':
                        $conn->prepare("DELETE FROM student WHERE id = ?")->execute([$user_id]);
                        break;
                    case 'librarian':
                        $conn->prepare("DELETE FROM librarian WHERE id = ?")->execute([$user_id]);
                        break;
                }
                
                // Delete from users table
                $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                
                $conn->commit();
                
                $response['success'] = true;
                $response['message'] = 'User deleted successfully';
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
    
} catch (Exception $e) {
    error_log("User action error: " . $e->getMessage());
    $response['message'] = 'An error occurred while processing the request';
}

echo json_encode($response);
?>
