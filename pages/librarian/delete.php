<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$role || $role !== 'principal') {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: librarian_list.php?error=Invalid librarian ID provided");
    exit;
}

$librarian_id = intval($_GET['id']);

mysqli_begin_transaction($conn);

try {
    $query_fetch_librarian = "SELECT * FROM librarian WHERE id = ?";
    $stmt_fetch = mysqli_prepare($conn, $query_fetch_librarian);
    mysqli_stmt_bind_param($stmt_fetch, "i", $librarian_id);
    mysqli_stmt_execute($stmt_fetch);
    $result_librarian = mysqli_stmt_get_result($stmt_fetch);
    $librarian_data = mysqli_fetch_assoc($result_librarian);
    mysqli_stmt_close($stmt_fetch);

    if (!$librarian_data) {
        throw new Exception("Librarian with ID $librarian_id not found.");
    }

    $query_archive_librarian = "INSERT INTO deleted_librarians 
                                (id, librarian_name, email, phone, gender, dob, blood_group, address, school_id, 
                                 qualification, salary, deleted_by_role) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_archive = mysqli_prepare($conn, $query_archive_librarian);
    mysqli_stmt_bind_param(
        $stmt_archive,
        "isssssssisds",
        $librarian_data['id'],
        $librarian_data['librarian_name'],
        $librarian_data['email'],
        $librarian_data['phone'],
        $librarian_data['gender'],
        $librarian_data['dob'],
        $librarian_data['blood_group'],
        $librarian_data['address'],
        $librarian_data['school_id'],
        $librarian_data['qualification'],
        $librarian_data['salary'],
        $role
    );

    if (!mysqli_stmt_execute($stmt_archive)) {
        throw new Exception("Failed to archive librarian data: " . mysqli_stmt_error($stmt_archive));
    }
    mysqli_stmt_close($stmt_archive);

    $query_delete_user = "DELETE FROM users WHERE id = ?";
    $stmt_delete = mysqli_prepare($conn, $query_delete_user);
    mysqli_stmt_bind_param($stmt_delete, "i", $librarian_id);

    if (!mysqli_stmt_execute($stmt_delete)) {
        throw new Exception("Failed to delete user record: " . mysqli_stmt_error($stmt_delete));
    }

    if (mysqli_stmt_affected_rows($stmt_delete) === 0) {
        throw new Exception("User record could not be deleted (it may have already been removed).");
    }
    mysqli_stmt_close($stmt_delete);

    // Convert web path to server path and delete the image file
    $image_path = $librarian_data['librarian_image'];
    if (!empty($image_path)) {
        $server_image_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;
        if (file_exists($server_image_path)) {
            unlink($server_image_path);
        }
    }

    mysqli_commit($conn);
    header("Location: librarian_list.php?success=Librarian was successfully deleted and archived.");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: librarian_list.php?error=" . urlencode($e->getMessage()));
    exit;
    
} finally {
    mysqli_close($conn);
}
?>