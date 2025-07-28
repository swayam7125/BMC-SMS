<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in and has a valid role
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if not logged in or role is unknown
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// Check if a student ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: student_list.php?error=Invalid student ID provided");
    exit;
}

$student_id = intval($_GET['id']);

// Begin a transaction to ensure all operations succeed or none do
mysqli_begin_transaction($conn);

try {
    // Step 1: Fetch all data for the student to be deleted
    $query_fetch_student = "SELECT * FROM student WHERE id = ?";
    $stmt_fetch = mysqli_prepare($conn, $query_fetch_student);
    mysqli_stmt_bind_param($stmt_fetch, "i", $student_id);
    mysqli_stmt_execute($stmt_fetch);
    $result_student = mysqli_stmt_get_result($stmt_fetch);
    $student_data = mysqli_fetch_assoc($result_student);
    mysqli_stmt_close($stmt_fetch);

    // If no student is found with that ID, abort the process
    if (!$student_data) {
        throw new Exception("Student with ID $student_id not found.");
    }

    // Step 2: Insert the fetched student data into the `deleted_students` table for logging
    $query_archive_student = "INSERT INTO deleted_students 
                                (id, student_name, email, rollno, std, academic_year, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, school_id, deleted_by_role) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_archive = mysqli_prepare($conn, $query_archive_student);
    mysqli_stmt_bind_param(
        $stmt_archive,
        "issssssssssssiis",
        $student_data['id'],
        $student_data['student_name'],
        $student_data['email'],
        $student_data['rollno'],
        $student_data['std'],
        $student_data['academic_year'],
        $student_data['dob'],
        $student_data['gender'],
        $student_data['blood_group'],
        $student_data['address'],
        $student_data['father_name'],
        $student_data['father_phone'],
        $student_data['mother_name'],
        $student_data['mother_phone'],
        $student_data['school_id'],
        $role // The role of the user performing the deletion
    );

    // Execute the insert query
    if (!mysqli_stmt_execute($stmt_archive)) {
        throw new Exception("Failed to archive student data: " . mysqli_stmt_error($stmt_archive));
    }
    mysqli_stmt_close($stmt_archive);

    // Step 3: Delete the user record from the 'users' table.
    // The `ON DELETE CASCADE` constraint will automatically delete the record from the 'student' table.
    $query_delete_user = "DELETE FROM users WHERE id = ?";
    $stmt_delete = mysqli_prepare($conn, $query_delete_user);
    mysqli_stmt_bind_param($stmt_delete, "i", $student_id);

    if (!mysqli_stmt_execute($stmt_delete)) {
        throw new Exception("Failed to delete user record: " . mysqli_stmt_error($stmt_delete));
    }

    // Check if the deletion was successful
    if (mysqli_stmt_affected_rows($stmt_delete) === 0) {
        throw new Exception("User record could not be deleted (it may have already been removed).");
    }
    mysqli_stmt_close($stmt_delete);

    // Step 4: Delete the physical image file from the server, if it exists
    $image_path = $student_data['student_image'];
    if (!empty($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }

    // If all steps were successful, commit the changes to the database
    mysqli_commit($conn);

    // Redirect back to the student list with a success message
    header("Location: student_list.php?success=Student was successfully deleted and archived.");
    exit;
} catch (Exception $e) {
    // If any step failed, roll back all database changes to prevent partial data loss
    mysqli_rollback($conn);

    // Redirect back to the student list with a detailed error message
    header("Location: student_list.php?error=" . urlencode($e->getMessage()));
    exit;
} finally {
    // Always close the database connection
    mysqli_close($conn);
}
