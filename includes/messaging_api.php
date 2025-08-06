<?php
// includes/messaging_api.php

include_once '../encryption.php';
include_once 'connect.php';

header('Content-Type: application/json');

$current_user_id = null;
$current_user_role = null;

if (isset($_COOKIE['encrypted_user_id']) && isset($_COOKIE['encrypted_user_role'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
    $current_user_role = decrypt_id($_COOKIE['encrypted_user_role']);
}

if (!$current_user_id || !$current_user_role) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

$action = $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'get_unread_count':
            // --- CORRECTED: Using PDO ---
            $stmt = $conn->prepare('SELECT COUNT("id") FROM "messages" WHERE "receiver_id" = ? AND "is_read" = false');
            $stmt->execute([$current_user_id]);
            $unread_count = $stmt->fetchColumn();
            echo json_encode(['status' => 'success', 'unread_count' => $unread_count]);
            break;

        case 'mark_all_messages_as_read':
            // --- CORRECTED: Using PDO ---
            $stmt = $conn->prepare('UPDATE "messages" SET "is_read" = true WHERE "receiver_id" = ? AND "is_read" = false');
            $stmt->execute([$current_user_id]);
            echo json_encode(['status' => 'success']);
            break;

        case 'get_contacts':
            $contacts = [];
            $base_path = '/BMC-SMS/';
            $default_image = $base_path . 'assets/images/undraw_profile.svg';

            if ($current_user_role == 'teacher') {
                $stmt = $conn->prepare('SELECT "std", "school_id" FROM "teacher" WHERE "id" = ?');
                $stmt->execute([$current_user_id]);
                $teacher_data = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($teacher_data && !empty($teacher_data['std'])) {
                    $school_id = $teacher_data['school_id'];
                    // The 'std' column is a text array like {'8','9','10'}
                    $teacher_stds = $teacher_data['std']; 
                    
                    // Create placeholders for the IN clause
                    $placeholders = implode(',', array_fill(0, count($teacher_stds), '?'));
                    $sql = 'SELECT "id", "student_name" AS "name", "student_image" AS "image_path" FROM "student" WHERE "school_id" = ? AND "std" IN (' . $placeholders . ')';
                    
                    $stmt_students = $conn->prepare($sql);
                    $params = array_merge([$school_id], $teacher_stds);
                    $stmt_students->execute($params);
                    $contacts_data = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($contacts_data as $row) {
                        $db_path = $row['image_path'];
                        $clean_path = str_replace(['../../', '../'], '', $db_path);
                        $row['image_path'] = $db_path ? $base_path . $clean_path : $default_image;
                        $contacts[] = $row;
                    }
                }
            } elseif ($current_user_role == 'student') {
                $stmt = $conn->prepare('SELECT "std", "school_id" FROM "student" WHERE "id" = ?');
                $stmt->execute([$current_user_id]);
                $student_data = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($student_data) {
                    $student_std = $student_data['std'];
                    $school_id = $student_data['school_id'];
                    
                    // --- CORRECTED: Replaced FIND_IN_SET with PostgreSQL array syntax ---
                    $sql = 'SELECT "id", "teacher_name" AS "name", "teacher_image" AS "image_path" FROM "teacher" WHERE "school_id" = ? AND ? = ANY("std")';
                    
                    $stmt_teachers = $conn->prepare($sql);
                    $stmt_teachers->execute([$school_id, $student_std]);
                    $contacts_data = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($contacts_data as $row) {
                        $db_path = $row['image_path'];
                        $clean_path = str_replace(['../../', '../'], '', $db_path);
                        $row['image_path'] = $db_path ? $base_path . $clean_path : $default_image;
                        $contacts[] = $row;
                    }
                }
            }
            echo json_encode(['status' => 'success', 'contacts' => $contacts]);
            break;

        case 'send_message':
            $receiver_id = $_POST['receiver_id'] ?? null;
            $message_text = $_POST['message_text'] ?? null;

            if (!$receiver_id || !$message_text) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
                exit();
            }
            // --- CORRECTED: Using PDO ---
            $stmt = $conn->prepare('INSERT INTO "messages" ("sender_id", "receiver_id", "message_text") VALUES (?, ?, ?)');
            if ($stmt->execute([$current_user_id, $receiver_id, $message_text])) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send message.']);
            }
            break;

        case 'get_messages':
            $other_user_id = $_POST['other_user_id'] ?? null;
            $last_message_id = isset($_POST['last_message_id']) ? (int)$_POST['last_message_id'] : 0;

            if (!$other_user_id) {
                echo json_encode(['status' => 'error', 'message' => 'Missing user ID.']);
                exit();
            }
            
            $base_path = '/BMC-SMS/';
            $default_image = $base_path . 'assets/images/undraw_profile.svg';

            $params = [$current_user_id, $other_user_id, $other_user_id, $current_user_id];
            $sql_condition = "";
            if ($last_message_id > 0) {
                $sql_condition = ' AND m."id" > ?';
                $params[] = $last_message_id;
            }

            // --- CORRECTED: Using PDO ---
            $sql = '
                SELECT 
                    m."id", m."sender_id", m."receiver_id", m."message_text", m."timestamp",
                    COALESCE(s."student_image", t."teacher_image") AS "sender_image_path"
                FROM "messages" m
                LEFT JOIN "student" s ON m."sender_id" = s."id"
                LEFT JOIN "teacher" t ON m."sender_id" = t."id"
                WHERE ((m."sender_id" = ? AND m."receiver_id" = ?) OR (m."sender_id" = ? AND m."receiver_id" = ?))
                ' . $sql_condition . '
                ORDER BY m."timestamp" ASC
            ';

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $messages_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $messages = [];
            foreach ($messages_data as $row) {
                $db_path = $row['sender_image_path'];
                $clean_path = str_replace(['../../', '../'], '', $db_path);
                $row['sender_image'] = $db_path ? $base_path . $clean_path : $default_image;
                $messages[] = $row;
            }

            $stmt_update = $conn->prepare('UPDATE "messages" SET "is_read" = true WHERE "receiver_id" = ? AND "sender_id" = ? AND "is_read" = false');
            $stmt_update->execute([$current_user_id, $other_user_id]);

            echo json_encode(['status' => 'success', 'messages' => $messages]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn = null; // Close the connection
?>
