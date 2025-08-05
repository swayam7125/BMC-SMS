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

switch ($action) {
    
    case 'get_unread_count':
        $unread_count = 0;
        $stmt = $conn->prepare("SELECT COUNT(id) as unread_count FROM messages WHERE receiver_id = ? AND is_read = 0");
        if ($stmt) {
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $unread_count = $result['unread_count'] ?? 0;
            $stmt->close();
        }
        echo json_encode(['status' => 'success', 'unread_count' => $unread_count]);
        break;

    // --- NEW ACTION TO MARK ALL MESSAGES AS READ ---
    case 'mark_all_messages_as_read':
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0");
        if ($stmt) {
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $stmt->close();
        }
        echo json_encode(['status' => 'success']);
        break;

    case 'get_contacts':
        // This case remains unchanged
        $contacts = [];
        $base_path = '/BMC-SMS/';
        $default_image = $base_path . 'assets/images/undraw_profile.svg';

        if ($current_user_role == 'teacher') {
            $stmt = $conn->prepare("SELECT std, school_id FROM teacher WHERE id = ?");
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($teacher_data = $result->fetch_assoc()) {
                $teacher_stds = explode(',', $teacher_data['std']);
                $school_id = $teacher_data['school_id'];
                
                $placeholders = implode(',', array_fill(0, count($teacher_stds), '?'));
                $sql = "SELECT id, student_name AS name, student_image AS image_path FROM student WHERE school_id = ? AND std IN ($placeholders)";

                $stmt_students = $conn->prepare($sql);
                $types = 's' . str_repeat('s', count($teacher_stds));
                $params = array_merge([$school_id], $teacher_stds);
                $stmt_students->bind_param($types, ...$params);

                $stmt_students->execute();
                $result_students = $stmt_students->get_result();

                while ($row = $result_students->fetch_assoc()) {
                    $db_path = $row['image_path'];
                    $clean_path = str_replace(['../../', '../'], '', $db_path);
                    $row['image_path'] = $db_path ? $base_path . $clean_path : $default_image;
                    $contacts[] = $row;
                }
                $stmt_students->close();
            }
            $stmt->close();

        } elseif ($current_user_role == 'student') {
            $stmt = $conn->prepare("SELECT std, school_id FROM student WHERE id = ?");
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($student_data = $result->fetch_assoc()) {
                $student_std = $student_data['std'];
                $school_id = $student_data['school_id'];
                
                $sql = "SELECT id, teacher_name AS name, teacher_image AS image_path FROM teacher WHERE school_id = ? AND FIND_IN_SET(?, std)";

                $stmt_teachers = $conn->prepare($sql);
                $stmt_teachers->bind_param("is", $school_id, $student_std);
                $stmt_teachers->execute();
                $result_teachers = $stmt_teachers->get_result();

                while ($row = $result_teachers->fetch_assoc()) {
                    $db_path = $row['image_path'];
                    $clean_path = str_replace(['../../', '../'], '', $db_path);
                    $row['image_path'] = $db_path ? $base_path . $clean_path : $default_image;
                    $contacts[] = $row;
                }
                 $stmt_teachers->close();
            }
            $stmt->close();
        }

        echo json_encode(['status' => 'success', 'contacts' => $contacts]);
        break;

    case 'send_message':
        // This case remains unchanged
        $receiver_id = $_POST['receiver_id'] ?? null;
        $message_text = $_POST['message_text'] ?? null;

        if (!$receiver_id || !$message_text) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $current_user_id, $receiver_id, $message_text);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
        break;

    case 'get_messages':
        // This case remains unchanged
        $other_user_id = $_POST['other_user_id'] ?? null;
        $last_message_id = isset($_POST['last_message_id']) ? (int)$_POST['last_message_id'] : 0;

        if (!$other_user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing user ID.']);
            exit();
        }
        
        $base_path = '/BMC-SMS/';
        $default_image = $base_path . 'assets/images/undraw_profile.svg';

        $params = [$current_user_id, $other_user_id, $other_user_id, $current_user_id];
        $types = "iiii";

        $sql_condition = "";
        if ($last_message_id > 0) {
            $sql_condition = " AND m.id > ?";
            $params[] = $last_message_id;
            $types .= "i";
        }

        $sql = "
            SELECT 
                m.id, m.sender_id, m.receiver_id, m.message_text, m.timestamp,
                COALESCE(s.student_image, t.teacher_image) AS sender_image_path
            FROM messages m
            LEFT JOIN student s ON m.sender_id = s.id
            LEFT JOIN teacher t ON m.sender_id = t.id
            WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
            $sql_condition
            ORDER BY m.timestamp ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $db_path = $row['sender_image_path'];
            $clean_path = str_replace(['../../', '../'], '', $db_path);
            $row['sender_image'] = $db_path ? $base_path . $clean_path : $default_image;
            $messages[] = $row;
        }
        $stmt->close();

        $stmt_update = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
        $stmt_update->bind_param("ii", $current_user_id, $other_user_id);
        $stmt_update->execute();
        $stmt_update->close();

        echo json_encode(['status' => 'success', 'messages' => $messages]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        break;
}

$conn->close();
?>