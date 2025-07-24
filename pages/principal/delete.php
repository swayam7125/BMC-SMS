<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in and has a valid role
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if not logged in
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// Ensure a valid Principal ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: principal_list.php?error=Invalid Principal ID provided");
    exit;
}

$principal_id = intval($_GET['id']);

// Start a database transaction to ensure data integrity
mysqli_begin_transaction($conn);

try {
    // Step 1: Fetch the full record of the principal to be deleted
    // We select all columns needed for the `deleted_principals` table.
    $query_fetch_principal = "SELECT * FROM principal WHERE id = ?";
    $stmt_fetch = mysqli_prepare($conn, $query_fetch_principal);
    mysqli_stmt_bind_param($stmt_fetch, "i", $principal_id);
    mysqli_stmt_execute($stmt_fetch);
    $result_principal = mysqli_stmt_get_result($stmt_fetch);
    $principal_data = mysqli_fetch_assoc($result_principal);
    mysqli_stmt_close($stmt_fetch);

    // If no principal record is found, throw an error and stop
    if (!$principal_data) {
        throw new Exception("Principal with ID $principal_id not found.");
    }

    // Step 2: Insert the fetched data into the `deleted_principals` table for archiving.
    $query_archive_principal = "INSERT INTO deleted_principals 
                                (id, principal_name, email, phone, dob, gender, blood_group, address, qualification, salary, batch, school_id, deleted_by_role) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_archive = mysqli_prepare($conn, $query_archive_principal);
    mysqli_stmt_bind_param($stmt_archive, "issssssssssis",
        $principal_data['id'],
        $principal_data['principal_name'],
        $principal_data['email'],
        $principal_data['phone'],
        $principal_data['principal_dob'], // Note: Mapping 'principal_dob' to 'dob'
        $principal_data['gender'],
        $principal_data['blood_group'],
        $principal_data['address'],
        $principal_data['qualification'],
        $principal_data['salary'],
        $principal_data['batch'],
        $principal_data['school_id'],
        $role // The role of the user performing the deletion
    );

    // Execute the archiving query
    if (!mysqli_stmt_execute($stmt_archive)) {
        throw new Exception("Failed to archive principal data: " . mysqli_stmt_error($stmt_archive));
    }
    mysqli_stmt_close($stmt_archive);

    // Step 3: Delete the user from the 'users' table.
    // The `ON DELETE CASCADE` constraint on the `principal` table will automatically delete the principal's record.
    $query_delete_user = "DELETE FROM users WHERE id = ?";
    $stmt_delete = mysqli_prepare($conn, $query_delete_user);
    mysqli_stmt_bind_param($stmt_delete, "i", $principal_id);
    
    if (!mysqli_stmt_execute($stmt_delete)) {
        throw new Exception("Failed to delete principal from active records: " . mysqli_stmt_error($stmt_delete));
    }

    // Verify that a row was actually deleted
    if (mysqli_stmt_affected_rows($stmt_delete) === 0) {
        throw new Exception("Principal could not be deleted (record may have already been removed).");
    }
    mysqli_stmt_close($stmt_delete);

    // Step 4: Delete the principal's image file from the server
    $image_path = $principal_data['principal_image'];
    if (!empty($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }

    // If all steps succeeded, commit the transaction to make the changes permanent
    mysqli_commit($conn);

    // Redirect back to the list with a success message
    header("Location: principal_list.php?success=Principal was successfully deleted and archived.");
    exit;

} catch (Exception $e) {
    // If any step failed, roll back the entire transaction
    mysqli_rollback($conn);

    // Redirect back with an error message
    header("Location: principal_list.php?error=" . urlencode($e->getMessage()));
    exit;

} finally {
    // Always close the database connection
    mysqli_close($conn);
}
?>