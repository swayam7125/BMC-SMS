<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if a user with appropriate permissions is logged in
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;

// Ensure a user is logged in (you can add more specific role checks, e.g., 'principal' or 'superadmin')
if (!$role || $role !== 'principal') {
    header("Location: ../../login.php?error=Unauthorized");
    exit;
}

// Validate that a librarian ID was provided in the URL
if (!isset($_GET['id']) || empty($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: librarian_list.php?error=Invalid librarian ID provided");
    exit;
}

$librarian_id = (int)$_GET['id'];

try {
    // Start a transaction to ensure all operations succeed or none do
    $conn->beginTransaction();

    // 1. Fetch the complete librarian record before deleting
    $stmt_fetch = $conn->prepare("SELECT * FROM librarian WHERE id = ?");
    $stmt_fetch->execute([$librarian_id]);
    $librarian_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    // Check if the librarian exists
    if (!$librarian_data) {
        throw new Exception("Librarian with ID $librarian_id not found.");
    }

    // 2. Archive the fetched data into the 'deleted_librarians' table
    $query_archive_librarian = "INSERT INTO deleted_librarians 
                                (id, librarian_name, email, phone, dob, gender, blood_group, address, 
                                 qualification, salary, school_id, deleted_by_role) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_archive = $conn->prepare($query_archive_librarian);
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
        $role // The role of the user performing the deletion
    ]);

    // 3. Delete the user from the 'users' table.
    // The 'ON DELETE CASCADE' constraint on the 'librarian' table will automatically delete the corresponding librarian record.
    $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete_user->execute([$librarian_id]);

    // Verify that the user was actually deleted to prevent orphaned records
    if ($stmt_delete_user->rowCount() === 0) {
        throw new Exception("User record associated with the librarian could not be deleted.");
    }

    // 4. Delete the librarian's profile image from the server, if it exists
    $image_path = $librarian_data['librarian_image'];
    if (!empty($image_path) && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
        // Using a relative path from the document root for security and reliability
        unlink($_SERVER['DOCUMENT_ROOT'] . $image_path);
    }

    // If all steps were successful, commit the transaction
    $conn->commit();
    header("Location: librarian_list.php?success=Librarian was successfully deleted and archived.");
    exit;

} catch (Exception $e) {
    // If any step fails, roll back the entire transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Log the detailed error for debugging and show a generic error to the user
    error_log("Librarian Deletion Error: " . $e->getMessage());
    header("Location: librarian_list.php?error=" . urlencode("An error occurred during deletion. Please check logs."));
    exit;
}
?>