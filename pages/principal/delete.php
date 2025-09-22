<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/log_system.php"; // ADDED: Log system dependency

// --- User Authentication and Role Check (using Cookie) ---
$role = null;
$userId = null;
$acting_user_name = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
    $acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'Admin';
}

// Redirect to login if the user is not logged in or has no role.
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// --- Input Validation ---
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: principal_list.php?error=Invalid or missing Principal ID.");
    exit;
}

$principal_id = (int)$_GET['id'];
$principal_name = 'Unknown Principal'; // Fallback name

// --- Database Transaction Logic ---
try {
    $conn->beginTransaction();

    // Step 1: Fetch the full record of the principal to be deleted.
    $sql_fetch_principal = "SELECT * FROM principal WHERE id = ?";
    $stmt_fetch = $conn->prepare($sql_fetch_principal);
    $stmt_fetch->execute([$principal_id]);
    $principal_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$principal_data) {
        throw new Exception("Principal with ID $principal_id not found.");
    }
    $principal_name = $principal_data['principal_name']; // Captured name for log

    // Step 2: Insert the fetched data into the `deleted_principals` table for archiving.
    $sql_archive_principal = "INSERT INTO deleted_principals 
                                (id, principal_name, email, phone, dob, gender, blood_group, address, qualification, salary, batch, school_id, deleted_by_role) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_archive = $conn->prepare($sql_archive_principal);

    // Create an array of values to be inserted.
    $archive_params = [
        $principal_data['id'],
        $principal_data['principal_name'],
        $principal_data['email'],
        $principal_data['phone'],
        $principal_data['dob'],
        strtolower($principal_data['gender']),
        $principal_data['blood_group'],
        $principal_data['address'],
        $principal_data['qualification'],
        $principal_data['salary'],
        $principal_data['batch'],
        $principal_data['school_id'],
        $role 
    ];

    $stmt_archive->execute($archive_params);

    // Step 3: Delete the user from the 'users' table.
    $sql_delete_user = "DELETE FROM users WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete_user);
    $stmt_delete->execute([$principal_id]);

    if ($stmt_delete->rowCount() === 0) {
        throw new Exception("Principal user record could not be deleted (record may have already been removed).");
    }

    // Step 4: Delete the principal's image file from the server.
    $image_path = $principal_data['principal_image'] ?? null;
    if (!empty($image_path) && file_exists($image_path)) {
        @unlink($image_path);
    }

    $conn->commit();
    
    // ⭐ LOGGING: Log the critical deletion action
    $log_message = "DELETION: Principal '{$principal_name}' (ID: {$principal_id}) was successfully deleted and archived.";
    log_interaction($role, $userId, $log_message, $acting_user_name);

    header("Location: principal_list.php?success=Principal was successfully deleted and archived.");
    exit;
} catch (Exception $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    header("Location: principal_list.php?error=" . urlencode($e->getMessage()));
    exit;
} finally {
    $conn = null;
}