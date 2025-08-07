<?php
header('Content-Type: application/json');

// Use __DIR__ for reliable pathing, ensuring files are found from within the '/includes/' directory.
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
$action = $_POST['action'] ?? $_GET['action'] ?? ''; // Allow GET for simple tests

if (!$current_user_id || !$current_user_role) {
    $response['message'] = 'Authentication failed.';
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        case 'get_contacts':
            $contacts = [];

            // --- Logic for when a TEACHER is logged in ---
            if ($current_user_role === 'teacher') {
                $stmt_teacher = $conn->prepare("SELECT school_id, std FROM teacher WHERE id = ?");
                $stmt_teacher->execute([$current_user_id]);
                $teacher_data = $stmt_teacher->fetch(PDO::FETCH_ASSOC);

                if ($teacher_data && !empty($teacher_data['std'])) {
                    $school_id = $teacher_data['school_id'];

                    // THE FIX: Convert the array string from PostgreSQL (e.g., "{10,11}") into a PHP array.
                    $stds_string = trim($teacher_data['std'], '{}'); // Remove curly braces
                    $stds_array = explode(',', $stds_string);      // Split by comma

                    if (!empty($stds_array)) {
                        // Find all students whose 'std' is in the teacher's list of standards
                        $sql = "SELECT s.id, s.student_name AS name, s.student_image AS image_path
                                FROM student s
                                WHERE s.school_id = ? AND s.std = ANY(?)";
                        
                        $stmt_students = $conn->prepare($sql);
                        // Execute with the correctly formatted PHP array
                        $stmt_students->execute([$school_id, $stds_array]);
                        $contacts = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            }
            // --- Logic for when a STUDENT is logged in ---
            elseif ($current_user_role === 'student') {
                $stmt_student = $conn->prepare('SELECT std, school_id FROM student WHERE id = ?');
                $stmt_student->execute([$current_user_id]);
                $student_data = $stmt_student->fetch(PDO::FETCH_ASSOC);

                if ($student_data) {
                    $student_std = $student_data['std'];
                    $school_id = $student_data['school_id'];

                    // This query correctly finds all teachers whose 'std' array contains the student's standard.
                    $sql = 'SELECT id, teacher_name AS name, teacher_image AS image_path
                            FROM teacher
                            WHERE school_id = ? AND ? = ANY(std)';
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

            // Mark messages from the other user as read
            $stmt_mark_read = $conn->prepare("UPDATE messages SET is_read = true WHERE sender_id = ? AND receiver_id = ? AND is_read = false");
            $stmt_mark_read->execute([$other_user_id, $current_user_id]);

            // Fetch the conversation
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
    // Log the actual error to your server's error log for debugging
    error_log("Messaging API Error: " . $e->getMessage());
    // Provide a generic error to the user
    $response['message'] = 'A database error occurred on the server.';
}

echo json_encode($response);
$conn = null;
?>