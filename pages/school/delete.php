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
    // 🔁 Delete PRINCIPALS and log
    $q1 = "SELECT * FROM principal WHERE school_id = ?";
    $stmt1 = mysqli_prepare($conn, $q1);
    mysqli_stmt_bind_param($stmt1, "i", $school_id);
    mysqli_stmt_execute($stmt1);
    $res1 = mysqli_stmt_get_result($stmt1);
    while ($p = mysqli_fetch_assoc($res1)) {
        $log = mysqli_prepare($conn, "
            INSERT INTO deleted_principals (
                principal_name, email, phone, dob, gender, blood_group, address,
                qualification, salary, batch, school_id, deleted_by_role
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($log, "sssssssssdss",
            $p['principal_name'], $p['email'], $p['phone'], $p['principal_dob'],
            $p['gender'], $p['blood_group'], $p['address'], $p['qualification'],
            $p['salary'], $p['batch'], $p['school_id'], $role
        );
        mysqli_stmt_execute($log);
        mysqli_query($conn, "DELETE FROM users WHERE email = '{$p['email']}'");
    }
    mysqli_stmt_close($stmt1);
    mysqli_query($conn, "DELETE FROM principal WHERE school_id = $school_id");

    // 🔁 Delete TEACHERS and log
    $q2 = "SELECT * FROM teacher WHERE school_id = ?";
    $stmt2 = mysqli_prepare($conn, $q2);
    mysqli_stmt_bind_param($stmt2, "i", $school_id);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    while ($t = mysqli_fetch_assoc($res2)) {
        $log = mysqli_prepare($conn, "
            INSERT INTO deleted_teachers (
                teacher_name, email, phone, gender, dob, blood_group, address, school_id,
                qualification, subject, language_known, salary, std, experience,
                batch, class_teacher, class_teacher_std, deleted_by_role
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($log, "ssssssisssssisssis",
            $t['teacher_name'], $t['email'], $t['phone'], $t['gender'], $t['dob'], $t['blood_group'], $t['address'],
            $t['school_id'], $t['qualification'], $t['subject'], $t['language_known'], $t['salary'],
            $t['std'], $t['experience'], $t['batch'], $t['class_teacher'], $t['class_teacher_std'], $role
        );
        mysqli_stmt_execute($log);
        mysqli_query($conn, "DELETE FROM users WHERE email = '{$t['email']}'");
    }
    mysqli_stmt_close($stmt2);
    mysqli_query($conn, "DELETE FROM teacher WHERE school_id = $school_id");

    // 🔁 Delete STUDENTS and log
    $q3 = "SELECT * FROM student WHERE school_id = ?";
    $stmt3 = mysqli_prepare($conn, $q3);
    mysqli_stmt_bind_param($stmt3, "i", $school_id);
    mysqli_stmt_execute($stmt3);
    $res3 = mysqli_stmt_get_result($stmt3);
    while ($s = mysqli_fetch_assoc($res3)) {
        $log = mysqli_prepare($conn, "
            INSERT INTO deleted_students (
                student_name, email, rollno, std, academic_year, dob, gender, blood_group,
                address, father_name, father_phone, mother_name, mother_phone, school_id, deleted_by_role
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($log, "sssssssssssssis",
            $s['student_name'], $s['email'], $s['rollno'], $s['std'], $s['academic_year'],
            $s['dob'], $s['gender'], $s['blood_group'], $s['address'],
            $s['father_name'], $s['father_phone'], $s['mother_name'], $s['mother_phone'],
            $s['school_id'], $role
        );
        mysqli_stmt_execute($log);
        mysqli_query($conn, "DELETE FROM users WHERE email = '{$s['email']}'");
    }
    mysqli_stmt_close($stmt3);
    mysqli_query($conn, "DELETE FROM student WHERE school_id = $school_id");

    // 🏫 Delete the school itself
    $delete_school = "DELETE FROM school WHERE id = ?";
    $stmt_school = mysqli_prepare($conn, $delete_school);
    mysqli_stmt_bind_param($stmt_school, "i", $school_id);
    mysqli_stmt_execute($stmt_school);
    mysqli_stmt_close($stmt_school);

    // ✅ Commit transaction
    mysqli_commit($conn);
    header("Location: ../school/school_list.php?success=School and related records deleted successfully");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: ../school/school_list.php?error=" . urlencode($e->getMessage()));
    exit;
}

mysqli_close($conn);
?>
