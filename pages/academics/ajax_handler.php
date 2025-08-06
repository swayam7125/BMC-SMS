<?php
header('Content-Type: application/json');
include_once "../../includes/connect.php";

$response = ['success' => false, 'message' => 'Invalid action.'];

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'add_subject':
                if (!empty($_POST['subject_name'])) {
                    $subject_name = trim($_POST['subject_name']);
                    
                    // --- CORRECTED: Using PDO with ON CONFLICT to handle existing subjects ---
                    // This is more efficient than two separate queries.
                    $query = 'INSERT INTO "subjects" ("subject_name") VALUES (?) ON CONFLICT ("subject_name") DO NOTHING';
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$subject_name]);

                    if ($stmt->rowCount() > 0) {
                        $new_id = $conn->lastInsertId();
                        $response['success'] = true;
                        $response['message'] = 'Subject added successfully.';
                        $response['subject'] = ['subject_id' => $new_id, 'subject_name' => $subject_name];
                    } else {
                        $response['message'] = 'Subject already exists.';
                    }
                } else {
                    $response['message'] = 'Subject name cannot be empty.';
                }
                break;

            case 'get_subjects_for_standard':
                if (!empty($_POST['standard'])) {
                    $standard = $_POST['standard'];
                    // --- CORRECTED: Using PDO ---
                    $query = 'SELECT "subject_id" FROM "standard_subjects" WHERE "standard" = ?';
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$standard]);
                    $subject_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Fetch just the first column

                    $response['success'] = true;
                    $response['subject_ids'] = $subject_ids;
                } else {
                    $response['message'] = 'Standard not provided.';
                }
                break;
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
$conn = null; // Close connection
?>
