<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in and has a valid role
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if not logged in or role is not authorized (e.g., principal)
if ($role !== 'principal') {
    header("Location: ../../login.php?error=Unauthorized action");
    exit;
}

// Check if a student ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: student_list.php?error=Invalid student ID provided");
    exit;
}

$student_id = intval($_GET['id']);

// PDO Change: Use PDO transactions and error handling
try {
    // Begin a transaction to ensure all operations succeed or none do
    $conn->beginTransaction();

    // Step 1: Fetch all data for the student to be deleted
    $stmt_fetch = $conn->prepare("SELECT * FROM student WHERE id = ?");
    $stmt_fetch->execute([$student_id]);
    $student_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$student_data) {
        throw new Exception("Student with ID $student_id not found.");
    }

    // Step 2: Insert the fetched student data into the `deleted_students` table
   // Step 2: Insert the fetched student data into the `deleted_students` table
    $query_archive_student = "INSERT INTO deleted_students
                                (id, student_name, email, rollno, std, academic_year, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, school_id, deleted_by_role)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_archive = $conn->prepare($query_archive_student);

    // FIX: Capitalize the first letter of the gender value
    $gender_for_db = ucfirst($student_data['gender']);

    $stmt_archive->execute([
        $student_data['id'],
        $student_data['student_name'],
        $student_data['email'],
        $student_data['rollno'],
        $student_data['std'],
        $student_data['academic_year'],
        $student_data['dob'],
        $gender_for_db, // Use the capitalized variable here
        $student_data['blood_group'],
        $student_data['address'],
        $student_data['father_name'],
        $student_data['father_phone'],
        $student_data['mother_name'],
        $student_data['mother_phone'],
        $student_data['school_id'],
        $role // The role of the user performing the deletion
    ]);

    // Step 3: Delete the user record from the 'users' table.
    // Assuming `ON DELETE CASCADE` is set on the `student` table's foreign key.
    $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete->execute([$student_id]);

    if ($stmt_delete->rowCount() === 0) {
        throw new Exception("User record could not be deleted (it may have already been removed).");
    }

    // Step 4: Delete the physical image file from the server, if it exists
    $image_path = $student_data['student_image'];
    if (!empty($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }

    // If all steps were successful, commit the changes
    $conn->commit();

    header("Location: student_list.php?success=Student was successfully deleted and archived.");
    exit;
} catch (Exception $e) {
    // If any step failed, roll back all database changes
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log the error and redirect
    error_log("Deletion Error: " . $e->getMessage());
    header("Location: student_list.php?error=" . urlencode("An error occurred during deletion. Please check the logs."));
    exit;
}
