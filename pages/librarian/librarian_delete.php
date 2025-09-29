<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/log_system.php"; // ADDED: Log system dependency

// --- Authorization ---
// Ensure only a user with the 'principal' role can execute this script.
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null; // Added for logging
$acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'Principal'; // Added for logging

if (!$role || $role !== 'principal') {
    header("Location: ../../login.php?error=Unauthorized");
    exit;
}

// --- Input Validation ---
// Check that a valid, integer ID was provided in the URL.
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: librarian_list.php?error=Invalid_ID");
    exit;
}
$librarian_id = (int)$_GET['id'];
$librarian_name = 'Unknown Librarian';

try {
    // --- Database Transaction ---
    // A transaction ensures that all database operations succeed or fail together,
    // preventing partial data deletion and maintaining data integrity.
    $conn->beginTransaction();

    // Step 1: Fetch the full librarian record before deletion for archiving.
    $stmt_fetch = $conn->prepare("SELECT * FROM librarian WHERE id = ?");
    $stmt_fetch->execute([$librarian_id]);
    $librarian_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$librarian_data) {
        throw new Exception("Librarian with ID $librarian_id not found.");
    }
    $librarian_name = $librarian_data['librarian_name']; // Capture name for log

    // Step 2: Archive the data into the 'deleted_librarians' table for record-keeping.
    $query_archive = "INSERT INTO deleted_librarians (id, librarian_name, email, phone, dob, gender, blood_group, address, qualification, salary, school_id, deleted_by_role) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_archive = $conn->prepare($query_archive);
    $stmt_archive->execute([
        $librarian_data['id'],
        $librarian_data['librarian_name'],
        $librarian_data['email'],
        $librarian_data['phone'],
        $librarian_data['dob'],
        $librarian_data['gender'],
        $librarian_data['blood_group'],
        $librarian_data['address'],
        $librarian_data['qualification'],
        $librarian_data['salary'],
        $librarian_data['school_id'],
        $role
    ]);

    // Step 3: Delete the user from the central 'users' table.
    // The database's 'ON DELETE CASCADE' constraint will automatically delete the corresponding record from the 'librarian' table.
    $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete_user->execute([$librarian_id]);

    if ($stmt_delete_user->rowCount() === 0) {
        throw new Exception("The user record could not be deleted, preventing the librarian deletion.");
    }

    // Step 4: Delete the librarian's profile image from the server, if it exists.
    $image_path = $librarian_data['librarian_image'];
    if (!empty($image_path) && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $image_path);
    }

    // If all steps were successful, commit the transaction.
    $conn->commit();
    
    // ⭐ LOGGING: Log the critical deletion action
    $log_message = "DELETION: Librarian '{$librarian_name}' (ID: {$librarian_id}) was successfully deleted and archived by {$role}.";
    log_interaction($role, $current_user_id, $log_message, $acting_user_name);
    
    header("Location: librarian_list.php?success=Librarian has been successfully deleted.");
    exit;
} catch (Exception $e) {
    // If any step failed, roll back the entire transaction.
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Librarian Deletion Error: " . $e->getMessage());
    header("Location: librarian_list.php?error=" . urlencode("An error occurred during deletion."));
    exit;
}