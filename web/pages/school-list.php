<?php
// Include the database connection file
include_once "../../includes/connect.php";

// Include the encription file
include_once "../../encryption.php";

// Function to generate star ratings
function generate_stars($rating)
{
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $output .= '<i class="fas fa-star text-warning"></i>';
        } else {
            $output .= '<i class="far fa-star text-warning"></i>';
        }
    }
    return $output;
}

$schools = [];
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';

try {
    // Base SQL query with anti-join to exclude deleted schools
    $sql = '
        SELECT s.id, s.name, s.rating, s.address as location, s.description, 
               s.created_at as establishment_date, s.capacity
        FROM public.school s
        WHERE NOT EXISTS (
            SELECT 1 FROM public.deleted_schools ds 
            WHERE ds.id = s.id
        )';

    // If there is a search query, add it to the WHERE clause
    if (!empty($search_query)) {
        $sql .= " AND (s.name ILIKE :search_query OR s.address ILIKE :search_query)";
    }

    // Add ordering
    $sql .= " ORDER BY s.rating DESC, s.name ASC";

    $stmt = $conn->prepare($sql);

    // Bind the search parameter if it exists
    if (!empty($search_query)) {
        $stmt->bindValue(':search_query', '%' . $search_query . '%');
    }

    $stmt->execute();
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    // You can set an error message to display to the user
    $error_message = "Could not retrieve school data. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Listings</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <!-- Google Fonts (Nunito) -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/custom.css">
</head>

<body>

    <div class="container-fluid p-4">

        <!-- Search & Filter Bar -->
        <div class="search-filter-bar card shadow mb-4 p-3">
            <form action="school-list.php" method="GET">
                <div class="row g-2 align-items-center">
                    <div class="col-lg-6 mb-2 mb-lg-0">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search by school name or address..." name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="filter-tags d-flex flex-wrap align-items-center justify-content-lg-end">
                            <span class="filter-label me-2 text-gray-600">Filters:</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1 active">Area</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1">Popular</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1">New</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1">Old</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm mb-1">Ratings</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- School Listing Grid -->
        <div class="row">
            <?php if (isset($error_message)): ?>
                <div class="col-12">
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                </div>
            <?php elseif (empty($schools)): ?>
                <div class="col-12">
                    <div class="alert alert-info">No schools found matching your criteria.</div>
                </div>
            <?php else: ?>
                <?php foreach ($schools as $school) : ?>
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card school-card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <h5 class="card-title font-weight-bold text-primary"><?php echo htmlspecialchars($school['name']); ?></h5>
                                    <div class="ratings">
                                        <?php echo generate_stars($school['rating']); ?>
                                    </div>
                                </div>
                                <p class="card-text text-gray-600"><i class="fas fa-map-marker-alt me-2 text-gray-400"></i><?php echo htmlspecialchars($school['location']); ?></p>
                                <p class="card-text text-gray-700 flex-grow-1"><?php echo htmlspecialchars($school['description']); ?></p>
                                <a href="school-detail.php?id=<?php echo $school['id']; ?>" class="btn btn-primary mt-auto align-self-start">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../../assets/js/landing_web_page.js" defer></script>
</body>

</html>