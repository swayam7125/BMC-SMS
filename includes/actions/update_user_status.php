<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../connect.php";
include_once "../../encryption.php";

// Get current user's role AND ID
$current_user_role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if (!$current_user_role || !$current_user_id) {
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
    $stmt_target = $conn->prepare('SELECT "role" FROM "users" WHERE "id" = ?');
    $stmt_target->execute([$user_id_to_update]);
    $target_user = $stmt_target->fetch(PDO::FETCH_ASSOC);
    $target_role = $target_user ? $target_user['role'] : null;

    if (!$target_role) {
        header("Location: $return_page?error=User not found");
        exit;
    }

    // --- NEW & IMPROVED PERMISSION LOGIC ---
    $is_authorized = false;
    
    // Check if the current user is authorized to perform the action
    switch ($current_user_role) {
        case 'superadmin':
            // Superadmin can manage principals
            if ($target_role === 'principal') {
                $is_authorized = true;
            }
            break;
        case 'principal':
            // Principal can manage HR, teachers, students, and librarians
            $allowed_roles_for_principal = ['hr', 'teacher', 'student', 'librarian'];
            if (in_array($target_role, $allowed_roles_for_principal)) {
                $stmt_principal_school = $conn->prepare('SELECT "school_id" FROM "principal" WHERE "id" = ?');
                $stmt_principal_school->execute([$current_user_id]);
                $principal_school_id = $stmt_principal_school->fetchColumn();

                $sql_target_school = sprintf('SELECT "school_id" FROM "%s" WHERE "id" = ?', $target_role);
                $stmt_target_school = $conn->prepare($sql_target_school);
                $stmt_target_school->execute([$user_id_to_update]);
                $target_school_id = $stmt_target_school->fetchColumn();

                if ($principal_school_id && $principal_school_id === $target_school_id) {
                    $is_authorized = true;
                }
            }
            break;
        case 'hr':
            // HR can manage teachers, students, and librarians
            $allowed_roles_for_hr = ['teacher', 'student', 'librarian'];
            if (in_array($target_role, $allowed_roles_for_hr)) {
                $stmt_hr_school = $conn->prepare('SELECT "school_id" FROM "hr" WHERE "id" = ?');
                $stmt_hr_school->execute([$current_user_id]);
                $hr_school_id = $stmt_hr_school->fetchColumn();

                $sql_target_school = sprintf('SELECT "school_id" FROM "%s" WHERE "id" = ?', $target_role);
                $stmt_target_school = $conn->prepare($sql_target_school);
                $stmt_target_school->execute([$user_id_to_update]);
                $target_school_id = $stmt_target_school->fetchColumn();

                if ($hr_school_id && $hr_school_id === $target_school_id) {
                    $is_authorized = true;
                }
            }
            break;
    }

    if (!$is_authorized) {
        header("Location: $return_page?error=You are not authorized to perform this action.");
        exit;
    }

    // Update the user status if authorized
    $stmt_update = $conn->prepare('UPDATE "users" SET "account_status" = ? WHERE "id" = ?');
    if ($stmt_update->execute([$new_status, $user_id_to_update])) {
        $action = ($new_status === 'suspended') ? 'suspended' : 'reactivated';
        header("Location: $return_page?success=User has been successfully {$action}.");
    } else {
        header("Location: $return_page?error=Failed to update user status.");
    }

} catch (PDOException $e) {
    header("Location: $return_page?error=Database error.");
    error_log("Update status error: " . $e->getMessage()); // Log error for debugging
}

$conn = null;
exit;
?>