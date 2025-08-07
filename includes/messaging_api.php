<?php
header('Content-Type: application/json');

// Use __DIR__ for reliable pathing. This is the most important fix.
// It ensures that files are found correctly from the '/includes/' directory.
include_once __DIR__ . "/../encryption.php";
include_once __DIR__ . "/connect.php";

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// Check if the database connection was successful
if (!isset($conn) || $conn === null) {
    error_log("Database connection failed in messaging_api.php.");
    $response['message'] = 'Server configuration error: Could not connect to the database.';
    echo json_encode($response);
    exit;
}

$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;
$current_user_role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$action = $_POST['action'] ?? '';

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
                    // Correctly parse PostgreSQL array string like '{10,11}' into a PHP array
                    $stds_string = trim($teacher_data['std'], '{}'); 
                    $stds = explode(',', $stds_string); 

                    if (!empty($stds)) {
                        $placeholders = implode(',', array_fill(0, count($stds), '?'));
                        $query = "
                            SELECT u.id, s.student_name AS name, s.student_image AS image_path
                            FROM users u
                            JOIN student s ON u.id = s.id
                            WHERE u.role = 'student' AND s.school_id = ? AND s.std IN ($placeholders)
                            ORDER BY s.student_name ASC
                        ";
                        $stmt_students = $conn->prepare($query);
                        $params = array_merge([$school_id], $stds);
                        $stmt_students->execute($params);
                        $contacts = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            } elseif ($current_user_role === 'student') {
                $stmt_student = $conn->prepare('SELECT "std", "school_id" FROM "student" WHERE "id" = ?');
                $stmt_student->execute([$current_user_id]);
                $student_data = $stmt_student->fetch(PDO::FETCH_ASSOC);

                if ($student_data) {
                    $student_std = $student_data['std'];
                    $school_id = $student_data['school_id'];
                    
                    // Use ANY() for PostgreSQL array matching
                    $sql = 'SELECT "id", "teacher_name" AS "name", "teacher_image" AS "image_path" FROM "teacher" WHERE "school_id" = ? AND ? = ANY("std")';
                    $stmt_teachers = $conn->prepare($sql);
                    $stmt_teachers->execute([$school_id, $student_std]);
                    $contacts = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);
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
            
            $stmt_mark_read = $conn->prepare("UPDATE messages SET is_read = true WHERE sender_id = ? AND receiver_id = ?");
            $stmt_mark_read->execute([$other_user_id, $current_user_id]);

            $stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY timestamp ASC");
            $stmt->execute([$current_user_id, $other_user_id, $other_user_id, $current_user_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
    $response['message'] = 'A database error occurred.';
}

echo json_encode($response);
$conn = null;
?>
