<?php
header('Content-Type: application/json');
include_once "connect.php";

$school_id = $_GET['school_id'] ?? null;

if (!$school_id) {
    echo json_encode(['stops' => [], 'standards' => []]);
    exit;
}

$response = [
    'stops' => [],
    'standards' => []
];

try {
    // Fetch Transport Stops
    $stmt_stops = $conn->prepare('SELECT r.route_name, s.id as stop_id, s.stop_name FROM routes r JOIN stops s ON r.id = s.route_id WHERE r.school_id = ? ORDER BY r.route_name, s.stop_name');
    $stmt_stops->execute([$school_id]);
    $response['stops'] = $stmt_stops->fetchAll(PDO::FETCH_ASSOC);

    // Fetch the school's categories (which is a PostgreSQL array)
    $stmt_school_cat = $conn->prepare('SELECT school_category FROM school WHERE id = ?');
    $stmt_school_cat->execute([$school_id]);
    $categories_pg_array = $stmt_school_cat->fetchColumn();

    if ($categories_pg_array) {
        // Convert PostgreSQL array string to a proper PHP array e.g., "{Primary,Secondary}" -> ['Primary', 'Secondary']
        $categories = explode(',', trim($categories_pg_array, '{}'));
        // Trim whitespace and quotes from each category name, which might be present from the DB format
        $categories = array_map(function($value) { return trim(trim($value), '"'); }, $categories);

        if (!empty($categories)) {
            // Create placeholders for the IN clause of the prepared statement
            $placeholders = rtrim(str_repeat('?,', count($categories)), ',');
            
            // Prepare the SQL to select standards based on the categories
            $sql_standards = "SELECT DISTINCT standard_name FROM standard_categories_mapping WHERE category_name IN ($placeholders)";
            
            $stmt_standards = $conn->prepare($sql_standards);
            $stmt_standards->execute($categories);
            $standards_result = $stmt_standards->fetchAll(PDO::FETCH_COLUMN);

            // Sort standards correctly (e.g., 1, 2, 10, 11, Nursery, Senior)
            usort($standards_result, function($a, $b) {
                if (is_numeric($a) && is_numeric($b)) {
                    return intval($a) - intval($b);
                }
                if (is_numeric($a)) {
                    return -1;
                }
                if (is_numeric($b)) {
                    return 1;
                }
                return strcasecmp($a, $b);
            });
            $response['standards'] = $standards_result;
        }
    }

} catch (PDOException $e) {
    // In case of error, return empty arrays and log the error for debugging
    error_log("Error fetching school data in get_school_data.php: " . $e->getMessage());
}

echo json_encode($response);
?>

