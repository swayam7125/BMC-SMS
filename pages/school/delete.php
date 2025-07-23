<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check login
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// Validate school ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: school_list.php?error=Invalid ID provided");
    exit;
}

$school_id = intval($_GET['id']);

// Start transaction
mysqli_autocommit($conn, false);

try {
    // --- 1. Log and Delete PRINCIPALS associated with the school ---
    // Fetch principal data for logging and to get user_ids for deletion (if not cascaded from principal to users)
    $q_principals = "SELECT * FROM principal WHERE school_id = ?";
    $stmt_principals = mysqli_prepare($conn, $q_principals);
    mysqli_stmt_bind_param($stmt_principals, "i", $school_id);
    mysqli_stmt_execute($stmt_principals);
    $res_principals = mysqli_stmt_get_result($stmt_principals);

    $principal_user_ids = [];
    while ($p = mysqli_fetch_assoc($res_principals)) {
        // Log to deleted_principals
        $log_principal = mysqli_prepare($conn, "
            INSERT INTO deleted_principals (
                id, principal_name, email, phone, dob, gender, blood_group, address,
                qualification, salary, batch, school_id, deleted_by_role
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        // Note: Added `id` to the INSERT for deleted_principals to preserve original ID from users table
        mysqli_stmt_bind_param($log_principal, "isssssssssdss",
            $p['id'], $p['principal_name'], $p['email'], $p['phone'], $p['principal_dob'],
            $p['gender'], $p['blood_group'], $p['address'], $p['qualification'],
            $p['salary'], $p['batch'], $p['school_id'], $role
        );
        mysqli_stmt_execute($log_principal);
        mysqli_stmt_close($log_principal); // Close the log statement for each iteration

        $principal_user_ids[] = $p['id']; // Collect user_ids of principals to delete from users table
    }
    mysqli_stmt_close($stmt_principals); // Close the select statement

    // Now delete from the principal table for this school_id
    // This will trigger cascade delete to `users` if `principal.id` is FK to `users.id` with CASCADE.
    $delete_principals = "DELETE FROM principal WHERE school_id = ?";
    $stmt_del_principals = mysqli_prepare($conn, $delete_principals);
    mysqli_stmt_bind_param($stmt_del_principals, "i", $school_id);
    mysqli_stmt_execute($stmt_del_principals);
    mysqli_stmt_close($stmt_del_principals);


    // --- 2. Log and Delete TEACHERS associated with the school ---
    $q_teachers = "SELECT * FROM teacher WHERE school_id = ?";
    $stmt_teachers = mysqli_prepare($conn, $q_teachers);
    mysqli_stmt_bind_param($stmt_teachers, "i", $school_id);
    mysqli_stmt_execute($stmt_teachers);
    $res_teachers = mysqli_stmt_get_result($stmt_teachers);

    $teacher_user_ids = [];
    while ($t = mysqli_fetch_assoc($res_teachers)) {
        // Log to deleted_teachers
        $log_teacher = mysqli_prepare($conn, "
            INSERT INTO deleted_teachers (
                id, teacher_name, email, phone, gender, dob, blood_group, address, school_id,
                qualification, subject, language_known, salary, std, experience,
                batch, class_teacher, class_teacher_std, deleted_by_role
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        // Note: Added `id` to the INSERT for deleted_teachers
        mysqli_stmt_bind_param($log_teacher, "isssssssisssssisssis",
            $t['id'], $t['teacher_name'], $t['email'], $t['phone'], $t['gender'], $t['dob'], $t['blood_group'], $t['address'],
            $t['school_id'], $t['qualification'], $t['subject'], $t['language_known'], $t['salary'],
            $t['std'], $t['experience'], $t['batch'], $t['class_teacher'], $t['class_teacher_std'], $role
        );
        mysqli_stmt_execute($log_teacher);
        mysqli_stmt_close($log_teacher); // Close the log statement for each iteration

        $teacher_user_ids[] = $t['id']; // Collect user_ids of teachers
    }
    mysqli_stmt_close($stmt_teachers);

    // Now delete from the teacher table for this school_id
    $delete_teachers = "DELETE FROM teacher WHERE school_id = ?";
    $stmt_del_teachers = mysqli_prepare($conn, $delete_teachers);
    mysqli_stmt_bind_param($stmt_del_teachers, "i", $school_id);
    mysqli_stmt_execute($stmt_del_teachers);
    mysqli_stmt_close($stmt_del_teachers);


    // --- 3. Log and Delete STUDENTS associated with the school ---
    $q_students = "SELECT * FROM student WHERE school_id = ?";
    $stmt_students = mysqli_prepare($conn, $q_students);
    mysqli_stmt_bind_param($stmt_students, "i", $school_id);
    mysqli_stmt_execute($stmt_students);
    $res_students = mysqli_stmt_get_result($stmt_students);

    $student_user_ids = [];
    while ($s = mysqli_fetch_assoc($res_students)) {
        // Log to deleted_students
        $log_student = mysqli_prepare($conn, "
            INSERT INTO deleted_students (
                id, student_name, email, rollno, std, academic_year, dob, gender, blood_group,
                address, father_name, father_phone, mother_name, mother_phone, school_id, deleted_by_role
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        // Note: Added `id` to the INSERT for deleted_students
        mysqli_stmt_bind_param($log_student, "issssssssssssssis",
            $s['id'], $s['student_name'], $s['email'], $s['rollno'], $s['std'], $s['academic_year'],
            $s['dob'], $s['gender'], $s['blood_group'], $s['address'],
            $s['father_name'], $s['father_phone'], $s['mother_name'], $s['mother_phone'],
            $s['school_id'], $role
        );
        mysqli_stmt_execute($log_student);
        mysqli_stmt_close($log_student); // Close the log statement for each iteration

        $student_user_ids[] = $s['id']; // Collect user_ids of students
    }
    mysqli_stmt_close($stmt_students);

    // Now delete from the student table for this school_id
    $delete_students = "DELETE FROM student WHERE school_id = ?";
    $stmt_del_students = mysqli_prepare($conn, $delete_students);
    mysqli_stmt_bind_param($stmt_del_students, "i", $school_id);
    mysqli_stmt_execute($stmt_del_students);
    mysqli_stmt_close($stmt_del_students);


    // --- 4. Delete the school itself ---
    // This delete will cascade to principal, teacher, student if school_id FKs have CASCADE.
    // If not, the above manual deletions ensure data consistency.
    $delete_school = "DELETE FROM school WHERE id = ?";
    $stmt_school = mysqli_prepare($conn, $delete_school);
    mysqli_stmt_bind_param($stmt_school, "i", $school_id);
    mysqli_stmt_execute($stmt_school);
    mysqli_stmt_close($stmt_school);

    // --- 5. Commit transaction ---
    mysqli_commit($conn);
    header("Location: school_list.php?success=School and related records deleted successfully");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: school_list.php?error=" . urlencode($e->getMessage()));
    exit;
} finally {
    mysqli_close($conn); // Close connection in finally block
}
?>