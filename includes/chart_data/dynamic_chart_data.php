<?php
// --- Centralized Chart Data Provider ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

include_once __DIR__ . "/../connect.php";

$role = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : '';
$userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;

if (empty($role)) {
    echo json_encode(['error' => 'User role is not defined.']);
    exit;
}

try {
    switch ($role) {
        case 'superadmin':
            generateSuperAdminData($conn);
            break;
        case 'principal':
            generatePrincipalData($conn, $userId);
            break;
        case 'teacher':
            generateTeacherData($conn, $userId);
            break;
        case 'student':
            generateStudentData($conn, $userId);
            break;
        default:
            echo json_encode(['error' => 'No chart data available for this role.']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
}

$conn = null; // Close PDO connection

// --- Function for SuperAdmin: School Growth ---
function generateSuperAdminData($conn)
{
    // --- CORRECTED: Using EXTRACT for PostgreSQL ---
    $sql = 'SELECT EXTRACT(YEAR FROM "school_opening") as year, COUNT("id") as school_count 
            FROM "school" 
            WHERE "school_opening" IS NOT NULL 
            GROUP BY year 
            ORDER BY year ASC';
    $stmt = $conn->query($sql);
    $yearly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($yearly_data)) {
        echo json_encode(['error' => 'No school growth data available.']);
        return;
    }

    $five_year_summary = [];
    foreach ($yearly_data as $data) {
        $year = intval($data['year']);
        $count = intval($data['school_count']);
        $interval_start = floor($year / 5) * 5;
        $interval_end = $interval_start + 4;
        $label = "$interval_start-$interval_end";
        if (!isset($five_year_summary[$label])) {
            $five_year_summary[$label] = 0;
        }
        $five_year_summary[$label] += $count;
    }
    ksort($five_year_summary);

    $labels = array_keys($five_year_summary);
    $data = array_values($five_year_summary);

    if (count($labels) < 2) {
        echo json_encode(['error' => 'Insufficient data to draw a growth chart.']);
        return;
    }

    $response = [
        'title' => 'New School Growth Overview',
        'labels' => $labels,
        'datasets' => [['label' => 'New Schools Added', 'data' => $data, 'borderColor' => 'rgba(78, 115, 223, 1)', 'backgroundColor' => 'rgba(78, 115, 223, 0.05)', 'lineTension' => 0.3]],
        'options' => ['scales' => ['yAxes' => [['ticks' => ['beginAtZero' => true, 'suggestedMin' => 3, 'stepSize' => 1]]]]]
    ];
    echo json_encode($response);
}

// --- Function for Principal: Admission Growth ---
function generatePrincipalData($conn, $userId)
{
    $stmt_school = $conn->prepare('SELECT "school_id" FROM "principal" WHERE "id" = ?');
    $stmt_school->execute([$userId]);
    $schoolId = $stmt_school->fetchColumn();

    if (!$schoolId) {
        echo json_encode(['error' => 'Principal not found or not assigned to a school.']);
        return;
    }

    $sql = 'SELECT "academic_year", COUNT("id") as admission_count 
            FROM "student" 
            WHERE "school_id" = ?
            GROUP BY "academic_year" 
            ORDER BY "academic_year" ASC';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$schoolId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = array_column($result, 'academic_year');
    $data = array_column($result, 'admission_count');

    if (count($labels) < 2) {
        echo json_encode(['error' => 'Insufficient admission data to draw a chart.']);
        return;
    }

    $response = ['title' => 'Yearly Admission Growth', 'labels' => $labels, 'datasets' => [['label' => 'Admissions', 'data' => $data, 'borderColor' => 'rgba(28, 200, 138, 1)', 'backgroundColor' => 'rgba(28, 200, 138, 0.05)', 'lineTension' => 0.3]]];
    echo json_encode($response);
}

// --- Function for Teacher: Subject-wise Results ---
function generateTeacherData($conn, $userId)
{
    $stmt_teacher = $conn->prepare('SELECT "subject", "school_id" FROM "teacher" WHERE "id" = ?');
    $stmt_teacher->execute([$userId]);
    $teacher_data = $stmt_teacher->fetch(PDO::FETCH_ASSOC);

    if (!$teacher_data) {
        echo json_encode(['error' => 'Teacher data not found.']);
        return;
    }
    $schoolId = $teacher_data['school_id'];
    $subjects = array_map('trim', explode(',', $teacher_data['subject']));

    if (empty($subjects)) {
        echo json_encode(['error' => 'No subjects assigned to this teacher.']);
        return;
    }
    
    $datasets = [];
    $all_years = [];
    $colors = [['border' => 'rgba(246, 194, 62, 1)', 'bg' => 'rgba(246, 194, 62, 0.05)'], ['border' => 'rgba(231, 74, 59, 1)', 'bg' => 'rgba(231, 74, 59, 0.05)'], ['border' => 'rgba(54, 185, 204, 1)', 'bg' => 'rgba(54, 185, 204, 0.05)'], ['border' => 'rgba(78, 115, 223, 1)', 'bg' => 'rgba(78, 115, 223, 0.05)']];
    $color_index = 0;

    $sql = 'SELECT "academic_year", AVG("marks_obtained") as average_marks 
            FROM "student_marks" 
            WHERE "school_id" = ? AND "subject_name" = ?
            GROUP BY "academic_year" 
            ORDER BY "academic_year" ASC';
    $stmt = $conn->prepare($sql);

    foreach ($subjects as $subject) {
        $stmt->execute([$schoolId, $subject]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $subject_data = [];
        foreach ($result as $row) {
            $subject_data[$row['academic_year']] = round($row['average_marks'], 2);
            if (!in_array($row['academic_year'], $all_years)) {
                $all_years[] = $row['academic_year'];
            }
        }
        if (!empty($subject_data)) {
            $color = $colors[$color_index % count($colors)];
            $datasets[] = ['label' => ucfirst($subject) . ' Avg. Marks', 'data' => $subject_data, 'borderColor' => $color['border'], 'backgroundColor' => $color['bg'], 'lineTension' => 0.3];
            $color_index++;
        }
    }

    if (empty($datasets)) {
        echo json_encode(['error' => 'No result data found for your subjects.']);
        return;
    }
    sort($all_years);
    if (count($all_years) < 2) {
        echo json_encode(['error' => 'Insufficient data for a yearly comparison.']);
        return;
    }

    foreach ($datasets as &$dataset) {
        $aligned_data = [];
        foreach ($all_years as $year) {
            $aligned_data[] = $dataset['data'][$year] ?? 0;
        }
        $dataset['data'] = $aligned_data;
    }

    $response = ['title' => 'Yearly Subject Performance', 'labels' => $all_years, 'datasets' => $datasets];
    echo json_encode($response);
}

// --- Function for Student: Overall Yearly Result ---
function generateStudentData($conn, $userId)
{
    $sql = 'SELECT "academic_year", AVG(("marks_obtained" / "total_marks") * 100) as overall_percentage 
            FROM "student_marks" 
            WHERE "student_id" = ?
            GROUP BY "academic_year" 
            ORDER BY "academic_year" ASC';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = array_column($result, 'academic_year');
    $data = array_map(function($row) { return round($row['overall_percentage'], 2); }, $result);

    if (count($labels) < 2) {
        echo json_encode(['error' => 'Insufficient data for a yearly comparison.']);
        return;
    }

    $response = ['title' => 'My Yearly Performance (%)', 'labels' => $labels, 'datasets' => [['label' => 'Overall Percentage', 'data' => $data, 'borderColor' => 'rgba(246, 194, 62, 1)', 'backgroundColor' => 'rgba(246, 194, 62, 0.05)', 'lineTension' => 0.3]]];
    echo json_encode($response);
}
?>
