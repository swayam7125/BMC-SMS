<?php
// ajax_handler.php

include_once "../../includes/connect.php";
include_once "../../encryption.php";

header('Content-Type: application/json');

// --- SECURITY: Re-added authorization check for all actions ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
if ($role !== 'principal') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action specified.'];

try {
    switch ($action) {
        
        case 'add_subject':
            $subject_name = trim($_POST['subject_name'] ?? '');
            if (!empty($subject_name)) {
                // Using ON CONFLICT is a good, efficient approach for PostgreSQL.
                $query = 'INSERT INTO subjects (subject_name) VALUES (?) ON CONFLICT (subject_name) DO NOTHING';
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

        // --- FIX #1: The action name now matches the JavaScript file ---
        case 'get_assigned_subjects': 
            $standard = $_POST['standard'] ?? '';
            if (!empty($standard)) {
                $query = 'SELECT subject_id FROM standard_subjects WHERE standard = ?';
                $stmt = $conn->prepare($query);
                $stmt->execute([$standard]);
                $subject_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

                $response['success'] = true;
                // --- FIX #2: The JSON key now matches what the JavaScript expects ---
                $response['assigned_subjects'] = $subject_ids; 
                $response['message'] = 'Subjects fetched.';
            } else {
                $response['message'] = 'Standard not provided.';
            }
            break;

        // --- ADDED: This function is needed to refresh the table after saving changes ---
        case 'get_all_assignments':
             $stmt = $conn->query("
                SELECT ss.standard, s.subject_name
                FROM standard_subjects ss
                JOIN subjects s ON ss.subject_id = s.subject_id
                ORDER BY ss.standard, s.subject_name
            ");
            $raw_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $all_assignments = [];
            foreach ($raw_assignments as $assignment) {
                $all_assignments[$assignment['standard']][] = $assignment['subject_name'];
            }
            $response['success'] = true;
            $response['assignments'] = $all_assignments;
            $response['message'] = 'All assignments fetched.';
            break;
    }
} catch (PDOException $e) {
    error_log("AJAX Handler Error: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
    http_response_code(500);
}

echo json_encode($response);
$conn = null; // Close connection