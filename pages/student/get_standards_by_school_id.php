<?php
// Includes your existing database connection script
include_once "../../includes/connect.php";

// Set header to return JSON data
header('Content-Type: application/json');

// Get school ID from the URL parameter
$schoolId = isset($_GET['school_id']) ? intval($_GET['school_id']) : 0;

if ($schoolId > 0) {
    try {
        // Check if the database connection object is valid
        if (!$conn) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed.']);
            exit;
        }

        // Fetch the school categories for the selected school
        $stmt_school_categories = $conn->prepare('SELECT "school_category" FROM "school" WHERE "id" = ?');
        $stmt_school_categories->execute([$schoolId]);
        $school_categories_string = $stmt_school_categories->fetchColumn();

        $standards = [];

        if ($school_categories_string) {
            // A much simpler and more robust way to handle the PostgreSQL array string
            $categories_array_str = trim($school_categories_string, '{}');
            $categories_array = $categories_array_str ? explode(',', $categories_array_str) : [];

            // Trim any potential whitespace and quotes from the category names
            $categories_array = array_map(function($category) {
                return trim($category, ' "');
            }, $categories_array);
            
            // Prepare a query to get standards from our new mapping table
            if (!empty($categories_array)) {
                $placeholders = implode(',', array_fill(0, count($categories_array), '?'));
                
                $stmt_standards = $conn->prepare("
                    SELECT standard_name
                    FROM public.standard_categories_mapping
                    WHERE category_name IN ({$placeholders})
                    ORDER BY
                        CASE 
                            WHEN standard_name = 'Nursery' THEN 1
                            WHEN standard_name = 'Junior' THEN 2
                            WHEN standard_name = 'Senior' THEN 3
                            WHEN standard_name ~ '^[0-9]+$' THEN CAST(standard_name AS INTEGER) + 100 -- Add a large offset to place numeric standards after the non-numeric ones
                            ELSE 999
                        END
                ");
                $stmt_standards->execute($categories_array);
                $standards = $stmt_standards->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // Return the standards as JSON
        echo json_encode($standards);

    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        // Return a more descriptive error message
        echo json_encode(['error' => 'Database error: ' . $e->getMessage() . '. SQLSTATE code: ' . $e->getCode()]);
    }
} else {
    // If no school ID is provided, return an empty array
    echo json_encode([]);
}
?>
