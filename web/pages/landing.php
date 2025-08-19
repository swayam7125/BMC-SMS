<?php
// Include the database connection file
include_once "../../includes/connect.php";

// Include the encryption file
include_once "../../encryption.php";

// Initialize variables
$totalSchools = $totalTeachers = $totalStudents = 0;
$enrollmentRate = "0%";
$years = [];
$opened_data = [];
$featured_schools = [];

try {
    // Get total counts from the actual tables, excluding deleted records
    $totalSchools = $conn->query('
        SELECT COUNT(*) 
        FROM public.school s 
        WHERE NOT EXISTS (
            SELECT 1 FROM public.deleted_schools ds 
            WHERE ds.id = s.id
        )
    ')->fetchColumn();

    $totalTeachers = $conn->query('
        SELECT COUNT(*) 
        FROM public.teacher t 
        WHERE NOT EXISTS (
            SELECT 1 FROM public.deleted_teachers dt 
            WHERE dt.id = t.id
        )
    ')->fetchColumn();

    $totalStudents = $conn->query('
        SELECT COUNT(*) 
        FROM public.student s 
        WHERE NOT EXISTS (
            SELECT 1 FROM public.deleted_students ds 
            WHERE ds.id = s.id
        )
    ')->fetchColumn();

    // Calculate enrollment rate based on active students vs school capacity
    $totalCapacity = $conn->query('
        SELECT COALESCE(SUM(s.capacity), 0) 
        FROM public.school s 
        WHERE NOT EXISTS (
            SELECT 1 FROM public.deleted_schools ds 
            WHERE ds.id = s.id
        )
    ')->fetchColumn();

    $enrollmentRate = ($totalStudents > 0 && $totalCapacity > 0)
        ? round(($totalStudents / $totalCapacity) * 100, 1) . '%'
        : '0%';

    // Get school growth data grouped by 5-year intervals
    $growth_data = $conn->query('
        SELECT 
            FLOOR(EXTRACT(YEAR FROM created_at) / 5) * 5 as interval_start,
            COUNT(*) as school_count
        FROM public.school s
        WHERE NOT EXISTS (
            SELECT 1 FROM public.deleted_schools ds 
            WHERE ds.id = s.id
        )
        GROUP BY interval_start
        ORDER BY interval_start ASC
    ')->fetchAll(PDO::FETCH_ASSOC);

    // Process data into 5-year intervals
    $five_year_summary = [];
    foreach ($growth_data as $data) {
        $interval_start = intval($data['interval_start']);
        $interval_end = $interval_start + 4;
        $label = "$interval_start-$interval_end";
        $five_year_summary[$label] = intval($data['school_count']);
    }
    ksort($five_year_summary);

    // Prepare data for the chart
    $years = array_keys($five_year_summary);
    $opened_data = array_values($five_year_summary);

    // Prepare chart data JSON
    $chart_years_json = json_encode($years);
    $schools_opened_json = json_encode($opened_data);

    // Get featured schools (top 3 non-deleted schools)
    $featured_schools = $conn->query('
        SELECT s.id, s.name, s.address as location, s.rating, s.description,
               s.created_at as establishment_date, s.capacity, s.email, s.contact
        FROM public.school s
        WHERE NOT EXISTS (
            SELECT 1 FROM public.deleted_schools ds 
            WHERE ds.id = s.id
        )
        ORDER BY s.rating DESC, s.created_at DESC
        LIMIT 3
    ')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error
    error_log("Database Error: " . $e->getMessage());

    // Set default values for error case
    $totalSchools = 0;
    $totalTeachers = 0;
    $totalStudents = 0;
    $enrollmentRate = "0%";

    // Set empty chart data
    $currentYear = date('Y');
    $years = range($currentYear - 4, $currentYear);
    $chart_years_json = json_encode($years);
    $schools_opened_json = json_encode(array_fill(0, 5, 0));
    $schools_closed_json = json_encode(array_fill(0, 5, 0));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management Dashboard</title>
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

        <!-- Section 1: Stat Cards -->
        <div class="row">
            <!-- Number of Schools Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card stat-card border-start-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Number of Schools</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalSchools; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-school fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Number of Teachers Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card stat-card border-start-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Number of Teachers</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalTeachers); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-person-chalkboard fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Number of Students Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card stat-card border-start-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Number of Students</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalStudents); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-children fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enrollment Rate Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card stat-card border-start-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Enrollment Rate</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $enrollmentRate; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-percent fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Growth Area Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">School Growth Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 320px">
                            <canvas id="schoolGrowthChart"
                                data-years='<?php echo json_encode($years); ?>'
                                data-opened='<?php echo json_encode($opened_data); ?>'></canvas>
                        </div>
                        <hr class="mt-4">
                        <div class="text-center small mt-2">
                            <span class="me-2">
                                <i class="fas fa-circle text-primary"></i> Total Schools
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Featured Schools -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-2 mb-md-0">Featured Schools</h6>
                        <a href="school-list.php" class="btn btn-primary btn-sm">View All Schools &rarr;</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($featured_schools)): ?>
                            <div class="row">
                                <?php foreach ($featured_schools as $school): ?>
                                    <div class="col-lg-4 col-md-6 mb-4">
                                        <div class="card school-card h-100">
                                            <div class="card-body d-flex flex-column">
                                                <div class="mb-2">
                                                    <h5 class="card-title font-weight-bold text-primary">
                                                        <?php echo htmlspecialchars($school['name']); ?>
                                                    </h5>
                                                    <div class="ratings">
                                                        <?php
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            echo $i <= $school['rating']
                                                                ? '<i class="fas fa-star text-warning"></i>'
                                                                : '<i class="far fa-star text-warning"></i>';
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <p class="card-text text-gray-600">
                                                    <i class="fas fa-map-marker-alt me-2 text-gray-400"></i>
                                                    <?php echo htmlspecialchars($school['location']); ?>
                                                </p>
                                                <p class="card-text text-gray-700 flex-grow-1">
                                                    <?php echo htmlspecialchars(substr($school['description'], 0, 100)) . (strlen($school['description']) > 100 ? '...' : ''); ?>
                                                </p>
                                                <a href="school-detail.php?id=<?php echo encrypt_id($school['id']); ?>"
                                                    class="btn btn-primary mt-auto align-self-start">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">No featured schools available at the moment.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="../../assets/vendor/chart.js/Chart.min.js"></script>
    <!-- Custom JS -->
    <script src="../../assets/js/landing_web_page.js" defer></script>
</body>

</html>