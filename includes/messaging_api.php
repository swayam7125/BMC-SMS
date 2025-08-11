<?php
header('Content-Type: application/json');

include_once __DIR__ . "/../encryption.php";
include_once __DIR__ . "/connect.php";

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// FIX: Define BASE_WEB_PATH here for consistent path handling
if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}

if (!isset($conn) || $conn === null) {
    error_log("Database connection failed in messaging_api.php.");
    $response['message'] = 'Server configuration error: Could not connect to the database.';
    echo json_encode($response);
    exit;
}

$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$current_user_role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!$current_user_id || !$current_user_role) {
    $response['message'] = 'Authentication failed.';
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        case 'get_contacts':
            $contacts = [];

            if ($current_user_role === 'teacher') {
                $stmt_teacher = $conn->prepare("SELECT school_id, std FROM teacher WHERE id = ?");
                $stmt_teacher->execute([$current_user_id]);
                $teacher_data = $stmt_teacher->fetch(PDO::FETCH_ASSOC);

                if ($teacher_data && !empty($teacher_data['std'])) {
                    $school_id = $teacher_data['school_id'];
                    
                    // 1. Parse the database string (e.g., "{10,11}") into a PHP array
                    $stds_string = trim($teacher_data['std'], '{}');
                    $stds_array = explode(',', $stds_string);

                    if (!empty($stds_array) && !empty($stds_array[0])) {
                        // 2. Manually format the PHP array into a PostgreSQL array literal string
                        $postgres_array_string = '{' . implode(',', $stds_array) . '}';

                        $sql = "SELECT s.id, s.student_name AS name, s.student_image AS image_path
                                FROM student s
                                WHERE s.school_id = ? AND s.std = ANY(?)";
                        
                        $stmt_students = $conn->prepare($sql);

                        // 3. Execute the query, passing the correctly formatted string
                        $stmt_students->execute([$school_id, $postgres_array_string]);
                        $contacts = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

                        // FIX: Normalize image paths to be absolute URLs before sending
                        foreach ($contacts as &$contact) {
                            if (!empty($contact['image_path'])) {
                                $contact['image_path'] = BASE_WEB_PATH . ltrim($contact['image_path'], '/');
                            }
                        }
                    }
                }
            } elseif ($current_user_role === 'student') {
                $stmt_student = $conn->prepare('SELECT std, school_id FROM student WHERE id = ?');
                $stmt_student->execute([$current_user_id]);
                $student_data = $stmt_student->fetch(PDO::FETCH_ASSOC);

                if ($student_data) {
                    $student_std = $student_data['std'];
                    $school_id = $student_data['school_id'];

                    $sql = 'SELECT id, teacher_name AS name, teacher_image AS image_path
                            FROM teacher
                            WHERE school_id = ? AND ? = ANY(std)';
                    $stmt_teachers = $conn->prepare($sql);
                    $stmt_teachers->execute([$school_id, $student_std]);
                    $contacts = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);

                    // FIX: Normalize image paths to be absolute URLs before sending
                    foreach ($contacts as &$contact) {
                        if (!empty($contact['image_path'])) {
                            $contact['image_path'] = BASE_WEB_PATH . ltrim($contact['image_path'], '/');
                        }
                    }
                }
            }
            $response = ['status' => 'success', 'contacts' => $contacts];
            break;

        case 'get_messages':
            $other_user_id = $_POST['other_user_id'] ?? 0;
            if (empty($other_user_id)) {
                $response['message'] = "Contact ID is missing.";
                break;
            }

            // Mark messages from the other user as read
            $stmt_mark_read = $conn->prepare("UPDATE messages SET is_read = true WHERE sender_id = ? AND receiver_id = ? AND is_read = false");
            $stmt_mark_read->execute([$other_user_id, $current_user_id]);

            // UPDATED QUERY: Fetch the conversation AND the sender's image
            $sql = "
                SELECT 
                    m.*,
                    COALESCE(t.teacher_image, s.student_image) AS sender_image
                FROM messages m
                LEFT JOIN teacher t ON m.sender_id = t.id
                LEFT JOIN student s ON m.sender_id = s.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) 
                ORDER BY m.timestamp ASC
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $current_user_id,
                $other_user_id,
                $other_user_id,
                $current_user_id
            ]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // FIX: Normalize sender image paths to be absolute URLs
            foreach ($messages as &$msg) {
                if (!empty($msg['sender_image'])) {
                    $msg['sender_image'] = BASE_WEB_PATH . ltrim($msg['sender_image'], '/');
                }
            }

            $response = ['status' => 'success', 'messages' => $messages];
            break;

        case 'send_message':
            $receiver_id = $_POST['receiver_id'] ?? 0;
            $message_text = trim($_POST['message_text'] ?? '');

            if (empty($receiver_id) || empty($message_text)) {
                $response['message'] = "Receiver or message text is missing.";
                break;
            }
            
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
            $stmt->execute([$current_user_id, $receiver_id, $message_text]);
            
            $response = ['status' => 'success', 'message' => 'Message sent.'];
            break;

        default:
            $response['message'] = 'Invalid action specified.';
            break;
    }
} catch (PDOException $e) {
    error_log("Messaging API Error: " . $e->getMessage());
    $response['message'] = 'A database error occurred on the server.';
}

echo json_encode($response);
$conn = null;
?>
