<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: school_list.php?error=Invalid school ID provided.");
    exit;
}
$school_id = intval($_GET['id']);

try {
    // --- CORRECTED: Using PDO Transaction ---
    $conn->beginTransaction();

    // --- 1. ARCHIVE AND DELETE PRINCIPALS ---
    $stmt_principals = $conn->prepare('SELECT * FROM "principal" WHERE "school_id" = ?');
    $stmt_principals->execute([$school_id]);
    $principals = $stmt_principals->fetchAll(PDO::FETCH_ASSOC);

    $archive_p_stmt = $conn->prepare('INSERT INTO "deleted_principals" (id, principal_name, email, phone, dob, gender, blood_group, address, qualification, salary, batch, school_id, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $delete_user_stmt = $conn->prepare('DELETE FROM "users" WHERE "id" = ?');
    foreach ($principals as $principal) {
        $archive_p_stmt->execute(array_values($principal));
        $delete_user_stmt->execute([$principal['id']]);
    }

    // --- 2. ARCHIVE AND DELETE TEACHERS ---
    $stmt_teachers = $conn->prepare('SELECT * FROM "teacher" WHERE "school_id" = ?');
    $stmt_teachers->execute([$school_id]);
    $teachers = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);
    
    $archive_t_stmt = $conn->prepare('INSERT INTO "deleted_teachers" (id, teacher_name, email, phone, gender, dob, blood_group, address, school_id, qualification, subject, language_known, salary, std, experience, batch, class_teacher, class_teacher_std, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($teachers as $teacher) {
        $archive_t_stmt->execute(array_values($teacher));
        $delete_user_stmt->execute([$teacher['id']]);
    }

    // --- 3. ARCHIVE AND DELETE STUDENTS ---
    $stmt_students = $conn->prepare('SELECT * FROM "student" WHERE "school_id" = ?');
    $stmt_students->execute([$school_id]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    $archive_s_stmt = $conn->prepare('INSERT INTO "deleted_students" (id, student_name, email, rollno, std, academic_year, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, school_id, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($students as $student) {
        $archive_s_stmt->execute(array_values($student));
        $delete_user_stmt->execute([$student['id']]);
    }

    // --- 4. ARCHIVE AND DELETE THE SCHOOL ---
    $stmt_school = $conn->prepare('SELECT * FROM "school" WHERE "id" = ?');
    $stmt_school->execute([$school_id]);
    $school_data = $stmt_school->fetch(PDO::FETCH_ASSOC);

    if ($school_data) {
        $archive_sc_stmt = $conn->prepare('INSERT INTO "deleted_schools" (id, school_logo, school_name, email, phone, school_opening, school_type, education_board, school_medium, school_category, address, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $archive_sc_stmt->execute(array_values($school_data));

        if (!empty($school_data['school_logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $school_data['school_logo'])) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $school_data['school_logo']);
        }

        $delete_school_stmt = $conn->prepare('DELETE FROM "school" WHERE "id" = ?');
        $delete_school_stmt->execute([$school_id]);
    }

    $conn->commit();
    header("Location: school_list.php?success=School and all associated records have been successfully deleted and archived.");

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header("Location: school_list.php?error=Error deleting school: " . urlencode($e->getMessage()));
} finally {
    $conn = null;
}
exit;
?>
