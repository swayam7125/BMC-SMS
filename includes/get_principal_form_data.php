<?php
header('Content-Type: application/json');
include_once "connect.php"; // This path is correct as it's in the same directory.

$action = $_GET['action'] ?? null;

if ($action === 'get_schools') {
    $batch = $_GET['batch'] ?? null;
    if (!$batch) {
        echo json_encode([]);
        exit;
    }
    try {
        $stmt = $conn->prepare('
            SELECT s.id, s.school_name 
            FROM school s 
            WHERE NOT EXISTS (
                SELECT 1 FROM principal p WHERE p.school_id = s.id AND p.batch = ?
            ) 
            ORDER BY s.school_name'
        );
        $stmt->execute([$batch]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        error_log("Error fetching available schools: " . $e->getMessage());
        echo json_encode([]);
    }
} elseif ($action === 'get_stops') {
    $school_id = $_GET['school_id'] ?? null;
    if (!$school_id) {
        echo json_encode([]);
        exit;
    }
    try {
        $stmt = $conn->prepare('
            SELECT r.route_name, s.id as stop_id, s.stop_name 
            FROM routes r 
            JOIN stops s ON r.id = s.route_id 
            WHERE r.school_id = ? 
            ORDER BY r.route_name, s.stop_name'
        );
        $stmt->execute([$school_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        error_log("Error fetching transport stops: " . $e->getMessage());
        echo json_encode([]);
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>

