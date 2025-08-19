<?php
// Include the database connection file
include_once "../../includes/connect.php";

// Include the encription file
include_once "../../encryption.php";

// Get the school ID from the URL
$school_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($school_id === 0) {
    die("Invalid School ID.");
}

$school_details = null;

try {
    // Prepare and execute the query to get details for a specific non-deleted school
    $stmt = $conn->prepare('
        SELECT s.id, s.name, s.rating, s.grades, 
               (SELECT COUNT(*) FROM public.teacher t WHERE t.school_id = s.id 
                AND NOT EXISTS (SELECT 1 FROM public.deleted_teachers dt WHERE dt.id = t.id)) as teachers,
               (SELECT COUNT(*) FROM public.student st WHERE st.school_id = s.id 
                AND NOT EXISTS (SELECT 1 FROM public.deleted_students ds WHERE ds.id = st.id)) as students,
               s.email, s.contact, s.address, s.description,
               s.created_at as establishment_date, s.capacity,
               COALESCE(p.name, \'Not Assigned\') as principal
        FROM public.school s
        LEFT JOIN public.principal p ON s.id = p.school_id 
            AND NOT EXISTS (SELECT 1 FROM public.deleted_principals dp WHERE dp.id = p.id)
        WHERE s.id = ? 
        AND NOT EXISTS (SELECT 1 FROM public.deleted_schools ds WHERE ds.id = s.id)
    ');
    $stmt->execute([$school_id]);
    $school_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$school_details) {
        die("School not found.");
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    die("Could not retrieve school details. Please try again later.");
}


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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Detail - <?php echo htmlspecialchars($school_details['name']); ?></title>
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

    <div class="container-fluid p-3 p-md-4">

        <!-- Search & Filter Bar (Consistent across pages) -->
        <div class="search-filter-bar card shadow mb-4 p-3">
            <form action="school-list.php" method="GET">
                <div class="row g-2 align-items-center">
                    <div class="col-lg-6 mb-2 mb-lg-0">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search by school name or address..." name="search_query">
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

        <!-- School Detail Header -->
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
            <div class="text-center text-md-start mb-3 mb-md-0">
                <a href="school-list.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left me-2"></i>Back to List</a>
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold"><?php echo htmlspecialchars($school_details['name']); ?></h1>
            </div>
            <div class="ratings h4 text-center text-md-end">
                <?php echo generate_stars($school_details['rating']); ?>
            </div>
        </div>

        <!-- Information Cards -->
        <div class="row">
            <!-- Grades/Standards -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-primary text-white mb-3 mx-auto"><i class="fas fa-graduation-cap"></i></div>
                        <h6 class="card-subtitle mb-2 text-muted text-uppercase">Grades/Standards</h6>
                        <p class="card-text h5 font-weight-bold text-gray-800"><?php echo htmlspecialchars($school_details['grades']); ?></p>
                    </div>
                </div>
            </div>
            <!-- Number of Teachers -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-success text-white mb-3 mx-auto"><i class="fas fa-person-chalkboard"></i></div>
                        <h6 class="card-subtitle mb-2 text-muted text-uppercase">Teachers</h6>
                        <p class="card-text h5 font-weight-bold text-gray-800"><?php echo htmlspecialchars($school_details['teachers']); ?></p>
                    </div>
                </div>
            </div>
            <!-- Number of Students -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-info text-white mb-3 mx-auto"><i class="fas fa-children"></i></div>
                        <h6 class="card-subtitle mb-2 text-muted text-uppercase">Students</h6>
                        <p class="card-text h5 font-weight-bold text-gray-800"><?php echo number_format($school_details['students']); ?></p>
                    </div>
                </div>
            </div>
            <!-- Contact Info -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-warning text-white mb-3 mx-auto"><i class="fas fa-address-card"></i></div>
                        <h6 class="card-subtitle mb-2 text-muted text-uppercase">Contact Information</h6>
                        <p class="card-text h6 text-gray-800"><i class="fas fa-envelope me-2 text-gray-400"></i><?php echo htmlspecialchars($school_details['email']); ?></p>
                        <p class="card-text h6 text-gray-800"><i class="fas fa-phone me-2 text-gray-400"></i><?php echo htmlspecialchars($school_details['contact']); ?></p>
                    </div>
                </div>
            </div>
            <!-- Principal's Name -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-danger text-white mb-3 mx-auto"><i class="fas fa-user-tie"></i></div>
                        <h6 class="card-subtitle mb-2 text-muted text-uppercase">Principal</h6>
                        <p class="card-text h5 font-weight-bold text-gray-800"><?php echo htmlspecialchars($school_details['principal']); ?></p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../../assets/js/landing_web_page.js" defer></script>
</body>

</html>