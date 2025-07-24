<?php
header('Content-Type: application/json');
include_once "../../includes/connect.php";

$response = ['success' => false, 'message' => 'Invalid action.'];

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        // Case for adding a new subject to the 'subjects' table
        case 'add_subject':
            if (!empty($_POST['subject_name'])) {
                $subject_name = trim($_POST['subject_name']);
                try {
                    // Use INSERT IGNORE to prevent errors if the subject already exists
                    $query = "INSERT IGNORE INTO subjects (subject_name) VALUES (?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "s", $subject_name);
                    mysqli_stmt_execute($stmt);
                    
                    if (mysqli_stmt_affected_rows($stmt) > 0) {
                        $new_id = mysqli_insert_id($conn);
                        $response['success'] = true;
                        $response['message'] = 'Subject added successfully.';
                        $response['subject'] = ['subject_id' => $new_id, 'subject_name' => $subject_name];
                    } else {
                        // If no rows were affected, it might already exist
                        $check_query = "SELECT subject_id FROM subjects WHERE subject_name = ?";
                        $stmt_check = mysqli_prepare($conn, $check_query);
                        mysqli_stmt_bind_param($stmt_check, "s", $subject_name);
                        mysqli_stmt_execute($stmt_check);
                        $result = mysqli_stmt_get_result($stmt_check);
                        if ($existing_subject = mysqli_fetch_assoc($result)) {
                             $response['message'] = 'Subject already exists.';
                        } else {
                            $response['message'] = 'Failed to add subject.';
                        }
                    }
                } catch (Exception $e) {
                    $response['message'] = 'Database error: ' . $e->getMessage();
                }
            } else {
                $response['message'] = 'Subject name cannot be empty.';
            }
            break;

        // Case for fetching subjects already assigned to a specific standard
        case 'get_subjects_for_standard':
            if (!empty($_POST['standard'])) {
                $standard = $_POST['standard'];
                try {
                    $query = "SELECT subject_id FROM standard_subjects WHERE standard = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "s", $standard);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $subject_ids = mysqli_fetch_all($result, MYSQLI_ASSOC);

                    $response['success'] = true;
                    // Flatten the array to just a list of IDs for Select2
                    $response['subject_ids'] = array_column($subject_ids, 'subject_id');
                } catch (Exception $e) {
                    $response['message'] = 'Database error: ' . $e->getMessage();
                }
            } else {
                $response['message'] = 'Standard not provided.';
            }
            break;
    }
}

echo json_encode($response);
?>
