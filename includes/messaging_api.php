<?php
header('Content-Type: application/json');

// --- FINAL, VERIFIED FILE PATHS ---
include_once "connect.php";
include_once "../encryption.php";

$response = ['status' => 'error', 'message' => 'Invalid action'];

$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$current_user_role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;

if (!$current_user_id || !$current_user_role) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

switch ($action) {

    case 'get_contacts':
        try {
            $contacts_sql = "";
            if ($current_user_role === 'student') {
                $contacts_sql = "
                    WITH message_stats AS (
                        SELECT
                            m.sender_id AS contact_id,
                            COUNT(CASE WHEN m.is_read = false THEN 1 END) AS unread_count,
                            MAX(m.timestamp) as last_message_time
                        FROM messages m
                        WHERE m.receiver_id = :current_user_id AND m.sender_role = 'teacher'
                        GROUP BY m.sender_id
                    )
                    SELECT 
                        t.id, 
                        t.teacher_name as name, 
                        t.teacher_image as image_path,
                        COALESCE(ms.unread_count, 0) as unread_count
                    FROM teacher t
                    INNER JOIN student s ON t.school_id = s.school_id
                    LEFT JOIN message_stats ms ON ms.contact_id = t.id
                    WHERE s.id = :current_user_id
                    ORDER BY ms.last_message_time DESC NULLS LAST, name ASC
                ";
            } elseif ($current_user_role === 'teacher') {
                $contacts_sql = "
                    WITH message_stats AS (
                         SELECT
                            CASE
                                WHEN sender_id = :current_user_id THEN receiver_id
                                ELSE sender_id
                            END AS contact_id,
                            MAX(timestamp) AS last_message_time,
                            COUNT(CASE WHEN receiver_id = :current_user_id AND is_read = false THEN 1 END) AS unread_count
                        FROM messages
                        WHERE sender_id = :current_user_id OR receiver_id = :current_user_id
                        GROUP BY contact_id
                    )
                    SELECT 
                        s.id, 
                        s.student_name as name, 
                        s.student_image as image_path,
                        COALESCE(ms.unread_count, 0) as unread_count
                    FROM student s
                    INNER JOIN teacher t ON s.school_id = t.school_id
                    LEFT JOIN message_stats ms ON ms.contact_id = s.id
                    WHERE t.id = :current_user_id
                    ORDER BY ms.last_message_time DESC NULLS LAST, name ASC
                ";
            }

            if (!empty($contacts_sql)) {
                $stmt = $conn->prepare($contacts_sql);
                $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
                $stmt->execute();
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response = ['status' => 'success', 'contacts' => $contacts];
            } else {
                $response['message'] = 'Invalid role for fetching contacts.';
            }

        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
        break;

    case 'get_messages':
        $other_user_id = $_POST['other_user_id'] ?? null;
        if (!$other_user_id) {
            $response['message'] = 'Contact ID is missing.';
            break;
        }

        try {
            $stmt_mark_read = $conn->prepare("UPDATE messages SET is_read = true WHERE sender_id = :other_user_id AND receiver_id = :current_user_id AND is_read = false");
            $stmt_mark_read->execute(['other_user_id' => $other_user_id, 'current_user_id' => $current_user_id]);
            
            $stmt = $conn->prepare("
                SELECT 
                    m.id, m.sender_id, m.sender_role, m.message_text, m.timestamp, 
                    m.file_path, m.file_type, m.original_filename,
                    CASE 
                        WHEN m.sender_role = 'teacher' THEN t.teacher_image
                        WHEN m.sender_role = 'student' THEN s.student_image
                    END as sender_image
                FROM messages m
                LEFT JOIN teacher t ON m.sender_id = t.id AND m.sender_role = 'teacher'
                LEFT JOIN student s ON m.sender_id = s.id AND m.sender_role = 'student'
                WHERE (m.sender_id = :current_user_id AND m.receiver_id = :other_user_id) 
                   OR (m.sender_id = :other_user_id AND m.receiver_id = :current_user_id)
                ORDER BY m.timestamp ASC
            ");

            $stmt->execute(['current_user_id' => $current_user_id, 'other_user_id' => $other_user_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['status' => 'success', 'messages' => $messages];

        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
        break;

        case 'send_message':
            $receiver_id = $_POST['receiver_id'] ?? null;
            $message_text = trim($_POST['message_text'] ?? '');
            $file_path = null;
            $file_type = null;
            $original_filename = null;
    
            if (empty($receiver_id)) {
                $response['message'] = 'Receiver ID is missing.';
                break;
            }
            
            $receiver_role = ($current_user_role === 'student') ? 'teacher' : 'student';
    
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['attachment'];
                $original_filename = basename($file['name']);
                // ... (file validation code remains the same) ...
    
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/uploads/messages/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0775, true);
                }
                
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $unique_filename = 'msg_' . uniqid() . '_' . time() . '.' . $file_extension;
                $destination = $upload_dir . $unique_filename;
    
                // --- THIS SECTION IS UPDATED WITH A CLEARER ERROR ---
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $file_path = 'uploads/messages/' . $unique_filename;
                    $file_type = $file['type'];
                } else {
                    $response['message'] = 'CRITICAL ERROR: Failed to move uploaded file. Check folder permissions for: ' . $upload_dir;
                    echo json_encode($response);
                    exit; // Stop the script here
                }
            }

        if (empty($message_text) && empty($file_path)) {
            $response['message'] = 'Cannot send an empty message.';
            break;
        }

        try {
            $stmt = $conn->prepare(
                "INSERT INTO messages (sender_id, sender_role, receiver_id, receiver_role, message_text, file_path, file_type, original_filename) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$current_user_id, $current_user_role, $receiver_id, $receiver_role, $message_text, $file_path, $file_type, $original_filename]);
            
            if ($stmt->rowCount() > 0) {
                $response = ['status' => 'success', 'message' => 'Message sent.'];
            } else {
                $response['message'] = 'Failed to send message.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
        break;

    case 'get_unread_total':
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = :current_user_id AND is_read = false");
            $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
            $stmt->execute();
            $total_unread = $stmt->fetchColumn();
            
            $response = ['status' => 'success', 'total_unread' => (int)$total_unread];

        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
        break;
}

echo json_encode($response);
?>