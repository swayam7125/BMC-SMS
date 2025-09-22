<?php
// Include necessary files with corrected paths
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Get user role and ID from cookies
$role = null;
$userId = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security: Only principals can perform this action
if ($role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

// Get the payroll user ID to delete from the URL
$hr_user_id_to_delete = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
if (!$hr_user_id_to_delete) {
    header("Location: hr_list.php?error=Invalid user ID.");
    exit;
}

// Get the principal's school ID for security
$admin_school_id = null;
if ($userId) {
    $stmt = $conn->prepare('SELECT "school_id" FROM "principal" WHERE "id" = ?');
    $stmt->execute([$userId]);
    $admin_school_id = $stmt->fetchColumn();
}

try {
    // Start database transaction
    $conn->beginTransaction();

    // 1. Security Check & Fetch Data: Verify the user being deleted belongs to the principal's school
    $stmt_fetch = $conn->prepare('SELECT h.* FROM hr h WHERE id = ?');
    $stmt_fetch->execute([$hr_user_id_to_delete]);
    $hr_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$hr_data || $hr_data['school_id'] != $admin_school_id) {
        $conn->rollBack();
        header("Location: hr_list.php?error=You do not have permission to delete this user.");
        exit;
    }
    
    // 2. Archive the HR user's data before deletion
    $query_archive_hr = "INSERT INTO deleted_hr
                                (id, hr_name, email, phone, school_id, dob, gender, blood_group, address, 
                                 qualification, language_known, salary, experience, batch, date_of_joining, 
                                 transport_mode, self_transport_mode, vehicle_number, license_number, stop_id, deleted_by_role, hr_image)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_archive = $conn->prepare($query_archive_hr);
    $stmt_archive->execute([
        $hr_data['id'],
        $hr_data['hr_name'],
        $hr_data['email'],
        $hr_data['phone'],
        $hr_data['school_id'],
        $hr_data['dob'],
        $hr_data['gender'],
        $hr_data['blood_group'],
        $hr_data['address'],
        $hr_data['qualification'],
        $hr_data['language_known'],
        $hr_data['salary'],
        $hr_data['experience'],
        $hr_data['batch'],
        $hr_data['date_of_joining'],
        $hr_data['transport_mode'],
        $hr_data['self_transport_mode'],
        $hr_data['vehicle_number'],
        $hr_data['license_number'],
        $hr_data['stop_id'],
        $role,
        $hr_data['hr_image']
    ]);

    // 3. Delete the user's image file if it exists
    $image_path = $hr_data['hr_image'];
    if (!empty($image_path)) {
        $full_filesystem_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;
        if (file_exists($full_filesystem_path)) {
            unlink($full_filesystem_path);
        }
    }

    // 4. Delete the user record from the main 'users' table.
    // The ON DELETE CASCADE constraint will handle the deletion from 'hr' and 'hr_timings'.
    $stmt_delete = $conn->prepare('DELETE FROM users WHERE id = ? AND role = \'hr\'');
    $stmt_delete->execute([$hr_user_id_to_delete]);

    if ($stmt_delete->rowCount() === 0) {
        throw new Exception("User record could not be deleted.");
    }
    
    // Commit the transaction
    $conn->commit();

    header("Location: hr_list.php?success=HR user deleted and archived successfully.");
    exit();

} catch (Exception $e) {
    // If something goes wrong, roll back the transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error deleting HR user: " . $e->getMessage());
    header("Location: hr_list.php?error=An error occurred during deletion.");
    exit;
}
?>