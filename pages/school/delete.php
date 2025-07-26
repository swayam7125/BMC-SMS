<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check user role
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// Validate that a school ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: school_list.php?error=Invalid school ID provided.");
    exit;
}

$school_id = intval($_GET['id']);

// Start a transaction to ensure all operations are completed successfully
mysqli_begin_transaction($conn);

try {
    // --- 1. ARCHIVE AND DELETE ANY ASSOCIATED PRINCIPALS ---
    $principals_res = mysqli_query($conn, "SELECT * FROM principal WHERE school_id = $school_id");
    while ($principal = mysqli_fetch_assoc($principals_res)) {
        // First, archive the record to deleted_principals
        $archive_p_stmt = mysqli_prepare($conn, "INSERT INTO deleted_principals (id, principal_name, email, phone, dob, gender, blood_group, address, qualification, salary, batch, school_id, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($archive_p_stmt, "issssssssssis", $principal['id'], $principal['principal_name'], $principal['email'], $principal['phone'], $principal['principal_dob'], $principal['gender'], $principal['blood_group'], $principal['address'], $principal['qualification'], $principal['salary'], $principal['batch'], $principal['school_id'], $role);
        mysqli_stmt_execute($archive_p_stmt);
        mysqli_stmt_close($archive_p_stmt);
        // Then, delete the user, which will cascade and delete the principal record
        mysqli_query($conn, "DELETE FROM users WHERE id = {$principal['id']}");
    }

    // --- 2. ARCHIVE AND DELETE ANY ASSOCIATED TEACHERS ---
    $teachers_res = mysqli_query($conn, "SELECT * FROM teacher WHERE school_id = $school_id");
    while ($teacher = mysqli_fetch_assoc($teachers_res)) {
        $archive_t_stmt = mysqli_prepare($conn, "INSERT INTO deleted_teachers (id, teacher_name, email, phone, gender, dob, blood_group, address, school_id, qualification, subject, language_known, salary, std, experience, batch, class_teacher, class_teacher_std, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($archive_t_stmt, "isssssssisssisssiss", $teacher['id'], $teacher['teacher_name'], $teacher['email'], $teacher['phone'], $teacher['gender'], $teacher['dob'], $teacher['blood_group'], $teacher['address'], $teacher['school_id'], $teacher['qualification'], $teacher['subject'], $teacher['language_known'], $teacher['salary'], $teacher['std'], $teacher['experience'], $teacher['batch'], $teacher['class_teacher'], $teacher['class_teacher_std'], $role);
        mysqli_stmt_execute($archive_t_stmt);
        mysqli_stmt_close($archive_t_stmt);
        mysqli_query($conn, "DELETE FROM users WHERE id = {$teacher['id']}");
    }

    // --- 3. ARCHIVE AND DELETE ANY ASSOCIATED STUDENTS ---
    $students_res = mysqli_query($conn, "SELECT * FROM student WHERE school_id = $school_id");
    while ($student = mysqli_fetch_assoc($students_res)) {
        $archive_s_stmt = mysqli_prepare($conn, "INSERT INTO deleted_students (id, student_name, email, rollno, std, academic_year, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, school_id, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($archive_s_stmt, "issssssssssssiis", $student['id'], $student['student_name'], $student['email'], $student['rollno'], $student['std'], $student['academic_year'], $student['dob'], $student['gender'], $student['blood_group'], $student['address'], $student['father_name'], $student['father_phone'], $student['mother_name'], $student['mother_phone'], $student['school_id'], $role);
        mysqli_stmt_execute($archive_s_stmt);
        mysqli_stmt_close($archive_s_stmt);
        mysqli_query($conn, "DELETE FROM users WHERE id = {$student['id']}");
    }

    // --- 4. FINALLY, ARCHIVE AND DELETE THE SCHOOL ITSELF ---
    $school_res = mysqli_query($conn, "SELECT * FROM school WHERE id = $school_id");
    if (mysqli_num_rows($school_res) > 0) {
        $school_data = mysqli_fetch_assoc($school_res);

        // Archive the school record into the new table
        $archive_sc_stmt = mysqli_prepare($conn, "INSERT INTO deleted_schools (id, school_logo, school_name, email, phone, school_opening, school_type, education_board, school_medium, school_category, address, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($archive_sc_stmt, "isssssssssss", $school_data['id'], $school_data['school_logo'], $school_data['school_name'], $school_data['email'], $school_data['phone'], $school_data['school_opening'], $school_data['school_type'], $school_data['education_board'], $school_data['school_medium'], $school_data['school_category'], $school_data['address'], $role);
        mysqli_stmt_execute($archive_sc_stmt);
        mysqli_stmt_close($archive_sc_stmt);

        // Delete the school's logo file from the server
        if (!empty($school_data['school_logo']) && file_exists($school_data['school_logo'])) {
            unlink($school_data['school_logo']);
        }

        // Delete the school from the active `school` table
        $delete_school_stmt = mysqli_prepare($conn, "DELETE FROM school WHERE id = ?");
        mysqli_stmt_bind_param($delete_school_stmt, "i", $school_id);
        mysqli_stmt_execute($delete_school_stmt);
        mysqli_stmt_close($delete_school_stmt);
    }

    // If all steps were successful, commit the transaction
    mysqli_commit($conn);
    header("Location: school_list.php?success=School and all associated records have been successfully deleted and archived.");
} catch (Exception $e) {
    // If any step failed, roll back all database changes
    mysqli_rollback($conn);
    header("Location: school_list.php?error=Error deleting school: " . urlencode($e->getMessage()));
} finally {
    mysqli_close($conn);
}
exit;
