<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once "connect.php";
include_once "../../BMC-SMS/encryption.php";

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
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed. Please check your credentials in connect.php.']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
header('Content-Type: application/json');

switch ($action) {
    case 'get_contacts':
        $standard = $_POST['standard'] ?? '';
        
        $contacts = [];

        if ($current_user_role === 'teacher') {
            try {
                $sql_teacher = "SELECT school_id, std FROM teacher WHERE id = :id";
                $stmt_teacher = $conn->prepare($sql_teacher);
                $stmt_teacher->bindParam(':id', $current_user_id, PDO::PARAM_INT);
                $stmt_teacher->execute();
                $teacher = $stmt_teacher->fetch(PDO::FETCH_ASSOC);

                if ($teacher) {
                    $school_id = $teacher['school_id'];
                    $teacher_standards_str = trim($teacher['std'], '{}'); 
                    $teacher_standards = explode(',', $teacher_standards_str);

                    $sql_students = "SELECT id, student_name AS name, student_image AS image_path FROM student WHERE school_id = :school_id";
                    $params = ['school_id' => $school_id];

                    if (!empty($standard)) {
                        $sql_students .= " AND std = :standard";
                        $params['standard'] = $standard;
                    } else {
                        // Correctly build the IN clause with named placeholders
                        $placeholders = [];
                        foreach ($teacher_standards as $index => $std) {
                            $placeholder = ":std" . $index;
                            $placeholders[] = $placeholder;
                            $params[$placeholder] = (int)$std; // Cast to int for security
                        }
                        $sql_students .= " AND std IN (" . implode(',', $placeholders) . ")";
                    }
                    
                    // The bug was here: execute() was not correctly handling parameters.
                    // Pass the $params array directly to execute()
                    $stmt_students = $conn->prepare($sql_students);
                    $stmt_students->execute($params);
                    $contacts = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode(['status' => 'success', 'contacts' => $contacts]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Teacher record not found.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        else { // Correct logic for student panel
             try {
                $sql = "SELECT school_id, std FROM student WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $current_user_id, PDO::PARAM_INT);
                $stmt->execute();
                $student = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($student) {
                    $student_school_id = $student['school_id'];
                    $student_standard = $student['std'];
                    
                    // Use a proper PostgreSQL array check instead of LIKE
                    $sql_teachers = "SELECT id, teacher_name AS name, teacher_image AS image_path FROM teacher WHERE school_id = :school_id AND :student_standard = ANY(std)";
                    $stmt_teachers = $conn->prepare($sql_teachers);
                    $stmt_teachers->bindParam(':school_id', $student_school_id, PDO::PARAM_INT);
                    $stmt_teachers->bindParam(':student_standard', $student_standard);
                    $stmt_teachers->execute();
                    $contacts = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['status' => 'success', 'contacts' => $contacts]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Student not found.']);
                }
             } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
             }
        }
        break;

    case 'get_messages':
        $other_user_id = $_POST['other_user_id'] ?? '';
        if (!$other_user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Other user ID is required.']);
            exit();
        }

        try {
            $sql = "SELECT id, sender_id, receiver_id, message_text, timestamp, file_path, file_type, original_filename FROM messages WHERE (sender_id = :current_user_id AND receiver_id = :other_user_id) OR (sender_id = :other_user_id AND receiver_id = :current_user_id) ORDER BY timestamp ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':other_user_id', $other_user_id, PDO::PARAM_INT);
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $update_sql = "UPDATE messages SET is_read = TRUE WHERE receiver_id = :current_user_id AND sender_id = :other_user_id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
            $update_stmt->bindParam(':other_user_id', $other_user_id, PDO::PARAM_INT);
            $update_stmt->execute();

            echo json_encode(['status' => 'success', 'messages' => $messages]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
    
    case 'get_unread_total':
        try {
            $sql = "SELECT COUNT(*) FROM messages WHERE receiver_id = :current_user_id AND is_read = FALSE";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
            $stmt->execute();
            $total_unread = $stmt->fetchColumn();
            
            echo json_encode(['status' => 'success', 'total_unread' => $total_unread]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
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

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $upload_dir = 'uploads/messages/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $unique_filename = uniqid('msg_', true) . '_' . basename($file['name']);
            $file_path_full = $upload_dir . $unique_filename;

            if (move_uploaded_file($file['tmp_name'], $file_path_full)) {
                $file_path = $file_path_full;
                $file_type = $file['type'];
                $original_filename = $file['name'];
            } else {
                echo json_encode(['status' => 'error', 'message' => 'File upload failed.']);
                exit();
            }
        }
        
        try {
            $sql = "INSERT INTO messages (sender_id, receiver_id, message_text, file_path, file_type, sender_role, receiver_role, original_filename) VALUES (:sender_id, :receiver_id, :message_text, :file_path, :file_type, :sender_role, :receiver_role, :original_filename) RETURNING id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':sender_id', $current_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':receiver_id', $receiver_id, PDO::PARAM_INT);
            $stmt->bindParam(':message_text', $message_text, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
            $stmt->bindParam(':file_type', $file_type, PDO::PARAM_STR);
            $stmt->bindParam(':sender_role', $current_user_role, PDO::PARAM_STR);
            $receiver_role = 'student'; 
            $stmt->bindParam(':receiver_role', $receiver_role, PDO::PARAM_STR); 
            $stmt->bindParam(':original_filename', $original_filename, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $last_id = $conn->lastInsertId();
                echo json_encode(['status' => 'success', 'message_id' => $last_id]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send message.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        break;
}
?>