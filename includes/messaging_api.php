<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once "connect.php";
include_once "../../BMC-SMS/encryption.php"; // Adjust path if needed

header('Content-Type: application/json');

// --- Helper function to build a correct, full image path ---
function build_image_url($db_path) {
    if (empty($db_path)) {
        return null;
    }
    $base_path = '/BMC-SMS/';
    if (strpos($db_path, $base_path) === 0) {
        return $db_path;
    }
    return $base_path . ltrim($db_path, '/');
}

$current_user_id = null;
$current_user_role = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $current_user_role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if (!$current_user_id || ($current_user_role !== 'teacher' && $current_user_role !== 'student')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if (!isset($conn) || !($conn instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_contacts':
        $standard = $_POST['standard'] ?? '';
        $contacts = [];

        try {
            if ($current_user_role === 'teacher') {
                $sql_teacher = "SELECT school_id FROM teacher WHERE id = :id";
                $stmt_teacher = $conn->prepare($sql_teacher);
                $stmt_teacher->execute([':id' => $current_user_id]);
                $teacher = $stmt_teacher->fetch(PDO::FETCH_ASSOC);

                if ($teacher) {
                    $sql_students = "SELECT s.id, s.student_name AS name, s.student_image AS image_path, COUNT(m.id) FILTER (WHERE m.is_read = false AND m.sender_id = s.id) as unread_count
                                     FROM student s
                                     LEFT JOIN messages m ON s.id = m.sender_id AND m.receiver_id = :current_user_id
                                     WHERE s.school_id = :school_id AND s.std = :standard
                                     GROUP BY s.id, s.student_name, s.student_image
                                     ORDER BY s.student_name ASC";
                    $stmt_students = $conn->prepare($sql_students);
                    $stmt_students->execute([':school_id' => $teacher['school_id'], ':standard' => $standard, ':current_user_id' => $current_user_id]);
                    $contacts = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
                }
            } else { // Student's logic
                $sql_student = "SELECT school_id, std FROM student WHERE id = :id";
                $stmt_student = $conn->prepare($sql_student);
                $stmt_student->execute([':id' => $current_user_id]);
                $student = $stmt_student->fetch(PDO::FETCH_ASSOC);

                if ($student) {
                    $sql_teachers = "SELECT t.id, t.teacher_name AS name, t.teacher_image AS image_path, COUNT(m.id) FILTER (WHERE m.is_read = false AND m.sender_id = t.id) as unread_count
                                     FROM teacher t
                                     LEFT JOIN messages m ON t.id = m.sender_id AND m.receiver_id = :current_user_id
                                     WHERE t.school_id = :school_id AND :standard = ANY(t.std)
                                     GROUP BY t.id, t.teacher_name, t.teacher_image
                                     ORDER BY t.teacher_name ASC";
                    $stmt_teachers = $conn->prepare($sql_teachers);
                    $stmt_teachers->execute([':school_id' => $student['school_id'], ':standard' => $student['std'], ':current_user_id' => $current_user_id]);
                    $contacts = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            foreach ($contacts as &$contact) {
                $contact['image_path'] = build_image_url($contact['image_path']);
            }
            echo json_encode(['status' => 'success', 'contacts' => $contacts]);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log("Get Contacts Error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
        break;

    case 'get_messages':
        if (!isset($_POST['other_user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Other user ID not specified.']);
            break;
        }
        $other_user_id = $_POST['other_user_id'];
        $messages = [];

        try {
            $stmt_mark_read = $conn->prepare("UPDATE messages SET is_read = true WHERE sender_id = :other_user_id AND receiver_id = :current_user_id AND is_read = false");
            $stmt_mark_read->execute([':other_user_id' => $other_user_id, ':current_user_id' => $current_user_id]);

            $sql = "SELECT
                        m.id, m.sender_id, m.message_text, m.file_path, m.original_filename, m.file_type, m.timestamp,
                        u.role AS sender_role,
                        CASE
                            WHEN u.role = 'student' THEN s.student_image
                            WHEN u.role = 'teacher' THEN t.teacher_image
                            ELSE NULL
                        END AS sender_image_path
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    LEFT JOIN student s ON m.sender_id = s.id AND u.role = 'student'
                    LEFT JOIN teacher t ON m.sender_id = t.id AND u.role = 'teacher'
                    WHERE (m.sender_id = :current_user_id AND m.receiver_id = :other_user_id)
                       OR (m.sender_id = :other_user_id AND m.receiver_id = :current_user_id)
                    ORDER BY m.timestamp ASC";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':current_user_id' => $current_user_id, ':other_user_id' => $other_user_id]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as $row) {
                $messages[] = [
                    'id' => $row['id'],
                    'sender_id' => $row['sender_id'],
                    'message_text' => $row['message_text'],
                    'file_path' => $row['file_path'],
                    'original_filename' => $row['original_filename'],
                    'file_type' => $row['file_type'],
                    'timestamp' => $row['timestamp'],
                    'sender_image' => build_image_url($row['sender_image_path'])
                ];
            }
            echo json_encode(['status' => 'success', 'messages' => $messages]);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log("Get Messages Error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error while fetching messages.']);
        }
        break;
    
    case 'get_unread_total':
        try {
            $sql = "SELECT COUNT(*) FROM messages WHERE receiver_id = :current_user_id AND is_read = FALSE";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':current_user_id' => $current_user_id]);
            $total_unread = $stmt->fetchColumn();
            echo json_encode(['status' => 'success', 'total_unread' => $total_unread]);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log("Get Unread Total Error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
        break;

    case 'send_message':
        $receiver_id = $_POST['receiver_id'] ?? '';
        $message_text = $_POST['message_text'] ?? '';
        $file_path = null;
        $file_type = null;
        $original_filename = null;

        if (!$receiver_id || (empty($message_text) && !isset($_FILES['attachment']))) {
            echo json_encode(['status' => 'error', 'message' => 'Receiver ID and a message or attachment are required.']);
            exit();
        }
        
        try {
            $stmt_role = $conn->prepare("SELECT role FROM users WHERE id = :id");
            $stmt_role->execute([':id' => $receiver_id]);
            $receiver_user = $stmt_role->fetch(PDO::FETCH_ASSOC);
            if (!$receiver_user) {
                echo json_encode(['status' => 'error', 'message' => 'Receiver not found.']);
                exit();
            }
            $receiver_role = $receiver_user['role'];

            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['attachment'];
                $upload_dir = '../../uploads/messages/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $unique_filename = uniqid('msg_', true) . '_' . basename($file['name']);
                $file_path_full = $upload_dir . $unique_filename;

                if (move_uploaded_file($file['tmp_name'], $file_path_full)) {
                    $file_path = 'uploads/messages/' . $unique_filename;
                    $file_type = $file['type'];
                    $original_filename = $file['name'];
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'File upload failed.']);
                    exit();
                }
            }
            
            // --- THIS IS THE CORRECTED SQL QUERY ---
            $sql = "INSERT INTO messages (sender_id, receiver_id, message_text, file_path, file_type, sender_role, receiver_role, original_filename, is_read) 
                    VALUES (:sender_id, :receiver_id, :message_text, :file_path, :file_type, :sender_role, :receiver_role, :original_filename, FALSE)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt->execute([
                ':sender_id' => $current_user_id,
                ':receiver_id' => $receiver_id,
                ':message_text' => $message_text,
                ':file_path' => $file_path,
                ':file_type' => $file_type,
                ':sender_role' => $current_user_role,
                ':receiver_role' => $receiver_role,
                ':original_filename' => $original_filename
            ])) {
                echo json_encode(['status' => 'success', 'message_id' => $conn->lastInsertId()]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send message.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            error_log("Send Message Error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        break;
}
?>