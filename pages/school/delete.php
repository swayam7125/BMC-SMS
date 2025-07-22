<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$role) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: school_list.php?error=Invalid ID provided");
    exit;
}

$school_id = intval($_GET['id']);

// Start transaction
mysqli_autocommit($conn, false);

try {
    // 🔹 1. Delete PRINCIPAL(s) and user accounts
    $principal_query = "SELECT email FROM principal WHERE school_id = ?";
    $stmt = mysqli_prepare($conn, $principal_query);
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $principal_result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($principal_result)) {
        $email = $row['email'];
        mysqli_query($conn, "DELETE FROM users WHERE email = '$email'");
    }
    mysqli_stmt_close($stmt);
    mysqli_query($conn, "DELETE FROM principal WHERE school_id = $school_id");

    // 🔹 2. Delete TEACHER(s) and user accounts
    $teacher_query = "SELECT email FROM teacher WHERE school_id = ?";
    $stmt = mysqli_prepare($conn, $teacher_query);
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $teacher_result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($teacher_result)) {
        $email = $row['email'];
        mysqli_query($conn, "DELETE FROM users WHERE email = '$email'");
    }
    mysqli_stmt_close($stmt);
    mysqli_query($conn, "DELETE FROM teacher WHERE school_id = $school_id");

    // 🔹 3. Delete STUDENT(s) and user accounts
    $student_query = "SELECT email FROM student WHERE school_id = ?";
    $stmt = mysqli_prepare($conn, $student_query);
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $student_result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($student_result)) {
        $email = $row['email'];
        mysqli_query($conn, "DELETE FROM users WHERE email = '$email'");
    }
    mysqli_stmt_close($stmt);
    mysqli_query($conn, "DELETE FROM student WHERE school_id = $school_id");

    // 🔹 4. Delete the SCHOOL
    $delete_school = "DELETE FROM school WHERE id = ?";
    $stmt_school = mysqli_prepare($conn, $delete_school);
    mysqli_stmt_bind_param($stmt_school, "i", $school_id);
    if (!mysqli_stmt_execute($stmt_school)) {
        throw new Exception("Error deleting school record: " . mysqli_stmt_error($stmt_school));
    }

    if (mysqli_stmt_affected_rows($stmt_school) === 0) {
        throw new Exception("No school found with the provided ID");
    }

    // Commit all deletions
    mysqli_commit($conn);

    mysqli_stmt_close($stmt_school);
    header("Location: ../school/school_list.php?success=School and related records deleted successfully");
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    if (isset($stmt_school)) mysqli_stmt_close($stmt_school);
    header("Location: ../school/school_list.php?error=" . urlencode($e->getMessage()));
    exit;
}

mysqli_close($conn);
?>
