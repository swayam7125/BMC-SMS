<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once "../../includes/log_system.php"; // ADDED: Log system dependency

$role = null;
$userId = null;
$acting_user_name = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
    $acting_user_name = decrypt_id($_COOKIE['encrypted_user_name'] ?? '') ?? 'Admin';
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
$school_name = 'Unknown School'; // Fallback name

try {
    $conn->beginTransaction();

    // --- 4. ARCHIVE AND DELETE THE SCHOOL (moved up to capture name) ---
    $stmt_school = $conn->prepare('SELECT id, school_logo, school_name, email, phone, school_opening, school_type, education_board, school_medium, school_category, address FROM "school" WHERE "id" = ?');
    $stmt_school->execute([$school_id]);
    $school_data = $stmt_school->fetch(PDO::FETCH_ASSOC);

    if ($school_data) {
        $school_name = $school_data['school_name']; // Captured name for log

        $archive_sc_stmt = $conn->prepare('INSERT INTO "deleted_schools" (id, school_logo, school_name, email, phone, school_opening, school_type, education_board, school_medium, school_category, address, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        
        $params = [
            $school_data['id'], $school_data['school_logo'], $school_data['school_name'], $school_data['email'],
            $school_data['phone'], $school_data['school_opening'], $school_data['school_type'], $school_data['education_board'],
            $school_data['school_medium'], $school_data['school_category'], $school_data['address'], $role
        ];
        $archive_sc_stmt->execute($params);

        if (!empty($school_data['school_logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $school_data['school_logo'])) {
            @unlink($_SERVER['DOCUMENT_ROOT'] . $school_data['school_logo']);
        }

        $delete_school_stmt = $conn->prepare('DELETE FROM "school" WHERE "id" = ?');
        $delete_school_stmt->execute([$school_id]);
    }
    
    // --- 1. ARCHIVE AND DELETE PRINCIPALS ---
    $stmt_principals = $conn->prepare('SELECT id, principal_name, email, phone, dob, gender, blood_group, address, qualification, salary, batch, school_id FROM "principal" WHERE "school_id" = ?');
    $stmt_principals->execute([$school_id]);
    $principals = $stmt_principals->fetchAll(PDO::FETCH_ASSOC);

    $archive_p_stmt = $conn->prepare('INSERT INTO "deleted_principals" (id, principal_name, email, phone, dob, gender, blood_group, address, qualification, salary, batch, school_id, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $delete_user_stmt = $conn->prepare('DELETE FROM "users" WHERE "id" = ?');
    foreach ($principals as $principal) {
        $params = [
            $principal['id'], $principal['principal_name'], $principal['email'], $principal['phone'], $principal['dob'],
            strtolower($principal['gender']),
            $principal['blood_group'], $principal['address'], $principal['qualification'],
            $principal['salary'], $principal['batch'], $principal['school_id'], $role
        ];
        $archive_p_stmt->execute($params);
        $delete_user_stmt->execute([$principal['id']]);
    }

    // --- 2. ARCHIVE AND DELETE TEACHERS ---
    $stmt_teachers = $conn->prepare('SELECT id, teacher_name, email, phone, gender, dob, blood_group, address, school_id, qualification, subject, language_known, salary, std, experience, batch, class_teacher, class_teacher_std FROM "teacher" WHERE "school_id" = ?');
    $stmt_teachers->execute([$school_id]);
    $teachers = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);
    
    $archive_t_stmt = $conn->prepare('INSERT INTO "deleted_teachers" (id, teacher_name, email, phone, gender, dob, blood_group, address, school_id, qualification, subject, language_known, salary, std, experience, batch, class_teacher, class_teacher_std, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($teachers as $teacher) {
        $params = [
            $teacher['id'], $teacher['teacher_name'], $teacher['email'], $teacher['phone'],
            strtolower($teacher['gender']),
            $teacher['dob'], $teacher['blood_group'], $teacher['address'], $teacher['school_id'], $teacher['qualification'], $teacher['subject'],
            $teacher['language_known'], $teacher['salary'], $teacher['std'], $teacher['experience'], $teacher['batch'],
            $teacher['class_teacher'], $teacher['class_teacher_std'], $role
        ];
        $archive_t_stmt->execute($params);
        $delete_user_stmt->execute([$teacher['id']]);
    }

    // --- 3. ARCHIVE AND DELETE STUDENTS ---
    $stmt_students = $conn->prepare('SELECT id, student_name, email, rollno, std, academic_year, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, school_id FROM "student" WHERE "school_id" = ?');
    $stmt_students->execute([$school_id]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    $archive_s_stmt = $conn->prepare('INSERT INTO "deleted_students" (id, student_name, email, rollno, std, academic_year, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, school_id, deleted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($students as $student) {
        $params = [
            $student['id'], $student['student_name'], $student['email'], $student['rollno'], $student['std'],
            $student['academic_year'], $student['dob'],
            strtolower($student['gender']),
            $student['blood_group'], $student['address'],
            $student['father_name'], $student['father_phone'], $student['mother_name'], $student['mother_phone'],
            $student['school_id'], $role
        ];
        $archive_s_stmt->execute($params);
        $delete_user_stmt->execute([$student['id']]);
    }

    $conn->commit();
    
    // ⭐ LOGGING: Log the critical deletion action
    $log_message = "DELETION: School '{$school_name}' (ID: {$school_id}) and all associated users ({$school_name}, {$school_name}, {$school_name}) were successfully deleted and archived.";
    log_interaction($role, $userId, $log_message, $acting_user_name);
    
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