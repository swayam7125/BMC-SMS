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

// Check if a valid teacher ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: teacher_list.php?error=Invalid teacher ID provided");
    exit;
}

$teacher_id = intval($_GET['id']);

// Start a database transaction to ensure all operations succeed or none do
mysqli_begin_transaction($conn);

try {
    // Step 1: Fetch the full record of the teacher to be deleted from the 'teacher' table
    $query_fetch_teacher = "SELECT * FROM teacher WHERE id = ?";
    $stmt_fetch = mysqli_prepare($conn, $query_fetch_teacher);
    mysqli_stmt_bind_param($stmt_fetch, "i", $teacher_id);
    mysqli_stmt_execute($stmt_fetch);
    $result_teacher = mysqli_stmt_get_result($stmt_fetch);
    $teacher_data = mysqli_fetch_assoc($result_teacher);
    mysqli_stmt_close($stmt_fetch);

    // If no teacher is found with that ID, abort the process
    if (!$teacher_data) {
        throw new Exception("Teacher with ID $teacher_id not found.");
    }

    // Step 2: Insert the fetched teacher data into the `deleted_teachers` table for logging
    $query_archive_teacher = "INSERT INTO deleted_teachers 
                                (id, teacher_name, email, phone, gender, dob, blood_group, address, school_id, 
                                 qualification, subject, language_known, salary, std, experience, batch, 
                                 class_teacher, class_teacher_std, deleted_by_role) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_archive = mysqli_prepare($conn, $query_archive_teacher);
    mysqli_stmt_bind_param($stmt_archive, "isssssssisssisssiss",
        $teacher_data['id'],
        $teacher_data['teacher_name'],
        $teacher_data['email'],
        $teacher_data['phone'],
        $teacher_data['gender'],
        $teacher_data['dob'],
        $teacher_data['blood_group'],
        $teacher_data['address'],
        $teacher_data['school_id'],
        $teacher_data['qualification'],
        $teacher_data['subject'],
        $teacher_data['language_known'],
        $teacher_data['salary'],
        $teacher_data['std'],
        $teacher_data['experience'],
        $teacher_data['batch'],
        $teacher_data['class_teacher'],
        $teacher_data['class_teacher_std'],
        $role // The role of the user performing the deletion
    );

    // Execute the insert query
    if (!mysqli_stmt_execute($stmt_archive)) {
        throw new Exception("Failed to archive teacher data: " . mysqli_stmt_error($stmt_archive));
    }
    mysqli_stmt_close($stmt_archive);

    // Step 3: Delete the user record from the 'users' table.
    // The `ON DELETE CASCADE` constraint will automatically delete the record from the 'teacher' table.
    $query_delete_user = "DELETE FROM users WHERE id = ?";
    $stmt_delete = mysqli_prepare($conn, $query_delete_user);
    mysqli_stmt_bind_param($stmt_delete, "i", $teacher_id);
    
    if (!mysqli_stmt_execute($stmt_delete)) {
        throw new Exception("Failed to delete user record: " . mysqli_stmt_error($stmt_delete));
    }

    // Check if the deletion was successful
    if (mysqli_stmt_affected_rows($stmt_delete) === 0) {
        throw new Exception("User record could not be deleted (it may have already been removed).");
    }
    mysqli_stmt_close($stmt_delete);

    // Step 4: Delete the physical image file from the server, if it exists
    $image_path = $teacher_data['teacher_image'];
    if (!empty($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }

    // If all steps were successful, commit the changes to the database
    mysqli_commit($conn);

    // Redirect back to the teacher list with a success message
    header("Location: teacher_list.php?success=Teacher was successfully deleted and archived.");
    exit;

} catch (Exception $e) {
    // If any step failed, roll back all database changes to prevent partial data loss
    mysqli_rollback($conn);

    // Redirect back to the teacher list with a detailed error message
    header("Location: teacher_list.php?error=" . urlencode($e->getMessage()));
    exit;

} finally {
    // Always close the database connection
    mysqli_close($conn);
}
?>