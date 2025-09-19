<?php
require_once '../../includes/connect.php';
require_once '../../includes/functions.php';
require_once '../../includes/ajax_helpers.php';
require_once '../../encryption.php';

// Authentication
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

// Only the 'principal' role is allowed to perform this action.
if ($role !== 'principal') {
    Response::error('Access denied', url('login.php'));
}

// Validate input
$librarian_id = 0;
if (is_ajax_request()) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        Response::error('Invalid request');
    }
    $librarian_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
} else {
    $librarian_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

if ($librarian_id <= 0) {
    Response::error('Invalid librarian ID provided', url('pages/librarian/librarian_list.php'));
}

try {
    // Authorization Check: The principal must be in the same school as the librarian they are deleting.
    $stmt_principal_school = $conn->prepare('SELECT school_id FROM principal WHERE id = ?');
    $stmt_principal_school->execute([$user_id]);
    $principal_school_id = $stmt_principal_school->fetchColumn();

    $stmt_librarian_school = $conn->prepare('SELECT school_id FROM librarian WHERE id = ?');
    $stmt_librarian_school->execute([$librarian_id]);
    $librarian_school_id = $stmt_librarian_school->fetchColumn();

    if ($principal_school_id !== $librarian_school_id) {
        throw new Exception("Unauthorized access. You can only delete librarians from your school.");
    }
    
    // Begin transaction
    $conn->beginTransaction();

    // Fetch the full record of the librarian to be deleted.
    $sql_fetch_librarian = "SELECT * FROM librarian WHERE id = ?";
    $stmt_fetch = $conn->prepare($sql_fetch_librarian);
    $stmt_fetch->execute([$librarian_id]);
    $librarian_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$librarian_data) {
        throw new Exception("Librarian with ID $librarian_id not found.");
    }

    // Insert the fetched data into the `deleted_librarians` table for archiving.
    $sql_archive_librarian = "INSERT INTO deleted_librarians 
                                (id, librarian_name, email, phone, dob, gender, blood_group, address, qualification, salary, school_id, deleted_by_role, batch) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_archive = $conn->prepare($sql_archive_librarian);
    $archive_params = [
        $librarian_data['id'],
        $librarian_data['librarian_name'],
        $librarian_data['email'],
        $librarian_data['phone'],
        $librarian_data['dob'],
        $librarian_data['gender'],
        $librarian_data['blood_group'],
        $librarian_data['address'],
        $librarian_data['qualification'],
        $librarian_data['salary'],
        $librarian_data['school_id'],
        $role, 
        $librarian_data['batch']
    ];
    $stmt_archive->execute($archive_params);

    // Delete the user record from the 'users' table.
    $sql_delete_user = "DELETE FROM users WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete_user);
    $stmt_delete->execute([$librarian_id]);

    if ($stmt_delete->rowCount() === 0) {
        throw new Exception("User with ID $librarian_id could not be deleted from the users table.");
    }

    // Delete the librarian's image file from the server.
    $image_path = $librarian_data['librarian_image'] ?? null;
    if (!empty($image_path) && file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $image_path)) {
        unlink(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $image_path);
    }
    
    $conn->commit();

    if (is_ajax_request()) {
        Response::success('Librarian deleted successfully');
    } else {
        header('Location: ' . url('pages/librarian/librarian_list.php') . '?success=Librarian deleted successfully');
        exit;
    }

} catch (Exception $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }

    if (is_ajax_request()) {
        Response::error($e->getMessage());
    } else {
        header('Location: ' . url('pages/librarian/librarian_list.php') . '?error=' . urlencode("An error occurred: " . $e->getMessage()));
        exit;
    }
} finally {
    $conn = null;
}