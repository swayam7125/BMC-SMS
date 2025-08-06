<?php
// No session handling is used, as requested.

// Include your existing PDO connection file for PostgreSQL.
// This file should create a PDO object named $conn.
include_once "../../includes/connect.php";

// Include your custom encryption library.
include_once "../../encryption.php";

// --- User Authentication and Role Check (using Cookie) ---
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    // Assuming decrypt_id is a function from your encryption.php file.
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if the user is not logged in or has no role.
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// --- Input Validation ---
// Ensure a valid Principal ID is provided in the URL.
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: principal_list.php?error=Invalid or missing Principal ID.");
    exit;
}

$principal_id = (int)$_GET['id'];

// --- Database Transaction Logic ---
try {
    // Start a database transaction using your $conn object.
    // This ensures that all queries must succeed, or none of them will be saved.
    $conn->beginTransaction();

    // Step 1: Fetch the full record of the principal to be deleted.
    $sql_fetch_principal = "SELECT * FROM principal WHERE id = ?";
    $stmt_fetch = $conn->prepare($sql_fetch_principal);

    // Execute the statement, passing the parameter in an array.
    $stmt_fetch->execute([$principal_id]);

    // Fetch the data as an associative array.
    $principal_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    // If no record is found, throw an exception to stop the process and roll back.
    if (!$principal_data) {
        throw new Exception("Principal with ID $principal_id not found.");
    }

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
        $principal_data['gender'],
        $principal_data['blood_group'],
        $principal_data['address'],
        $principal_data['qualification'],
        $principal_data['salary'],
        $principal_data['batch'],
        $principal_data['school_id'],
        $role // The role of the user performing the deletion.
    ];

    // Execute the insert statement with the parameters.
    $stmt_archive->execute($archive_params);

    // Step 3: Delete the user from the 'users' table.
    // Assuming `ON DELETE CASCADE` is set up in your PostgreSQL database,
    // this will also delete the corresponding record from the `principal` table.
    $sql_delete_user = "DELETE FROM users WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete_user);
    $stmt_delete->execute([$principal_id]);

    // Verify that a row was actually deleted using rowCount().
    if ($stmt_delete->rowCount() === 0) {
        throw new Exception("Principal could not be deleted (record may have already been removed).");
    }

    // Step 4: Delete the principal's image file from the server.
    $image_path = $principal_data['principal_image'] ?? null;
    if (!empty($image_path) && file_exists($image_path)) {
        if (!unlink($image_path)) {
            // If file deletion fails, you might want to log this error,
            // but we'll allow the database transaction to commit regardless,
            // matching the original script's behavior.
        }
    }

    // If all steps succeeded, commit the transaction to make the changes permanent.
    $conn->commit();

    // Redirect back to the list with a success message.
    header("Location: principal_list.php?success=Principal was successfully deleted and archived.");
    exit;
} catch (Exception $e) {
    // If any step failed, roll back the entire transaction.
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Redirect back with an error message.
    header("Location: principal_list.php?error=" . urlencode($e->getMessage()));
    exit;
} finally {
    // Close the database connection by setting the PDO object to null.
    $conn = null;
}
