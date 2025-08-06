<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../connect.php";
include_once "../../encryption.php";

$current_user_role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;

if (!$current_user_role) {
    header("Location: ../../login.php");
    exit;
}

$user_id_to_update = isset($_GET['id']) ? intval($_GET['id']) : 0;
$new_status = isset($_GET['status']) && in_array($_GET['status'], ['active', 'suspended']) ? $_GET['status'] : null;
$return_page = isset($_GET['return']) ? $_GET['return'] : '../../dashboard.php';

if ($user_id_to_update <= 0 || !$new_status) {
    header("Location: $return_page?error=Invalid request");
    exit;
}

try {
    // --- CORRECTED: Using PDO to fetch and update user data ---
    $stmt_target = $conn->prepare('SELECT "role" FROM "users" WHERE "id" = ?');
    $stmt_target->execute([$user_id_to_update]);
    $target_user = $stmt_target->fetch(PDO::FETCH_ASSOC);
    $target_role = $target_user ? $target_user['role'] : null;

    if (!$target_role) {
        header("Location: $return_page?error=User not found");
        exit;
    }

    // Permission logic
    $is_authorized = false;
    switch ($current_user_role) {
        case 'superadmin':
            if ($target_role === 'principal') $is_authorized = true;
            break;
        case 'principal':
            if (in_array($target_role, ['teacher', 'student', 'librarian'])) $is_authorized = true;
            break;
    }

    if (!$is_authorized) {
        header("Location: $return_page?error=You are not authorized to perform this action.");
        exit;
    }

    // Update the user status
    $stmt_update = $conn->prepare('UPDATE "users" SET "account_status" = ? WHERE "id" = ?');
    if ($stmt_update->execute([$new_status, $user_id_to_update])) {
        $action = ($new_status === 'suspended') ? 'suspended' : 'reactivated';
        header("Location: $return_page?success=User has been successfully {$action}.");
    } else {
        header("Location: $return_page?error=Failed to update user status.");
    }

} catch (PDOException $e) {
    header("Location: $return_page?error=Database error: " . $e->getMessage());
}

$conn = null;
exit;
?>
