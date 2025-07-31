<?php
// --- Centralized Chart Data Provider ---
// Place this file in: /includes/chart_data/dynamic_chart_data.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Use a more reliable path for the database connection file
include_once __DIR__ . "/../connect.php";

// Get role and user ID from the request
$role = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : '';
$userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;

if (empty($role)) {
    echo json_encode(['error' => 'User role is not defined.']);
    exit;
}

// --- Main Switch for Role-Based Data Fetching ---
switch ($role) {
    case 'bmc':
        generateBmcData($conn);
        break;
    case 'schooladmin':
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

// Close the database connection
$conn->close();

// --- Function for BMC: School Growth ---
function generateBmcData($conn)
{
    $sql = "SELECT YEAR(school_opening) as year, COUNT(id) as school_count 
            FROM school 
            WHERE school_opening IS NOT NULL 
            GROUP BY YEAR(school_opening) 
            ORDER BY year ASC";
    $result = $conn->query($sql);

    $yearly_data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $yearly_data[] = $row;
        }
    } else {
        echo json_encode(['error' => 'No school growth data available.']);
        return;
    }

    // Process data into 5-year intervals
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

    // *** NEW: Check if there is enough data to draw a line chart ***
    if (count($labels) < 2) {
        echo json_encode(['error' => 'Insufficient data to draw a growth chart. At least two different 5-year periods with new schools are required.']);
        return;
    }

    $response = [
        'title' => 'New School Growth Overview',
        'labels' => $labels,
        'datasets' => [[
            'label' => 'New Schools Added',
            'data' => $data,
            'borderColor' => 'rgba(78, 115, 223, 1)',
            'backgroundColor' => 'rgba(78, 115, 223, 0.05)',
            'lineTension' => 0.3,
        ]],
        'options' => [
            'scales' => [
                'yAxes' => [[
                    'ticks' => [
                        'beginAtZero' => true,
                        'suggestedMin' => 3, // Set minimum y-axis value to 3
                        'stepSize' => 1
                    ]
                ]]
            ]
        ]
    ];

    echo json_encode($response);
}

// --- Function for Principal/SchoolAdmin: Admission Growth ---
function generatePrincipalData($conn, $userId)
{
    // First, get the school_id for the principal
    $stmt_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    if (!$stmt_school) {
        echo json_encode(['error' => 'Failed to prepare statement to find school.']);
        return;
    }
    $stmt_school->bind_param("i", $userId);
    $stmt_school->execute();
    $result_school = $stmt_school->get_result();
    if ($result_school->num_rows === 0) {
        echo json_encode(['error' => 'Principal not found or not assigned to a school.']);
        return;
    }
    $schoolId = $result_school->fetch_assoc()['school_id'];
    $stmt_school->close();

    // Now, get admission data for that school
    $sql = "SELECT academic_year, COUNT(id) as admission_count 
            FROM student 
            WHERE school_id = ?
            GROUP BY academic_year 
            ORDER BY academic_year ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Failed to prepare statement for admission data.']);
        return;
    }
    $stmt->bind_param("i", $schoolId);
    $stmt->execute();
    $result = $stmt->get_result();
    $labels = [];
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['academic_year'];
            $data[] = $row['admission_count'];
        }
    }
    $stmt->close();

    if (count($labels) < 2) {
        echo json_encode(['error' => 'Insufficient admission data to draw a chart. At least two academic years with admissions are needed.']);
        return;
    }

    $response = [
        'title' => 'Yearly Admission Growth',
        'labels' => $labels,
        'datasets' => [[
            'label' => 'Admissions',
            'data' => $data,
            'borderColor' => 'rgba(28, 200, 138, 1)',
            'backgroundColor' => 'rgba(28, 200, 138, 0.05)',
            'lineTension' => 0.3
        ]]
    ];
    echo json_encode($response);
}

// --- Function for Teacher: Subject-wise Results ---
function generateTeacherData($conn, $userId)
{
    // Get teacher's subjects and school_id
    $stmt_teacher = $conn->prepare("SELECT subject, school_id FROM teacher WHERE id = ?");
    if (!$stmt_teacher) {
        echo json_encode(['error' => 'Failed to prepare statement for teacher data.']);
        return;
    }
    $stmt_teacher->bind_param("i", $userId);
    $stmt_teacher->execute();
    $teacher_result = $stmt_teacher->get_result();
    if ($teacher_result->num_rows === 0) {
        echo json_encode(['error' => 'Teacher data not found.']);
        return;
    }
    $teacher_data = $teacher_result->fetch_assoc();
    $schoolId = $teacher_data['school_id'];
    // Assuming subjects are comma-separated, e.g., "maths,science"
    $subjects = array_map('trim', explode(',', $teacher_data['subject']));
    $stmt_teacher->close();

    if (empty($subjects)) {
        echo json_encode(['error' => 'No subjects assigned to this teacher.']);
        return;
    }

    $datasets = [];
    $all_years = [];
    $colors = [
        ['border' => 'rgba(246, 194, 62, 1)', 'bg' => 'rgba(246, 194, 62, 0.05)'],
        ['border' => 'rgba(231, 74, 59, 1)', 'bg' => 'rgba(231, 74, 59, 0.05)'],
        ['border' => 'rgba(54, 185, 204, 1)', 'bg' => 'rgba(54, 185, 204, 0.05)'],
        ['border' => 'rgba(78, 115, 223, 1)', 'bg' => 'rgba(78, 115, 223, 0.05)'],
    ];
    $color_index = 0;

    $sql = "SELECT academic_year, AVG(marks_obtained) as average_marks 
            FROM student_marks 
            WHERE school_id = ? AND subject_name = ?
            GROUP BY academic_year 
            ORDER BY academic_year ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Failed to prepare statement for subject marks.']);
        return;
    }

    foreach ($subjects as $subject) {
        $stmt->bind_param("is", $schoolId, $subject);
        $stmt->execute();
        $result = $stmt->get_result();

        $subject_data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $subject_data[$row['academic_year']] = round($row['average_marks'], 2);
                if (!in_array($row['academic_year'], $all_years)) {
                    $all_years[] = $row['academic_year'];
                }
            }
        }

        if (!empty($subject_data)) {
            $color = $colors[$color_index % count($colors)];
            $datasets[] = [
                'label' => ucfirst($subject) . ' Avg. Marks',
                'data' => $subject_data, // Use associative array for now
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
                'lineTension' => 0.3,
            ];
            $color_index++;
        }
    }
    $stmt->close();

    if (empty($datasets)) {
        echo json_encode(['error' => 'No result data found for your subjects.']);
        return;
    }

    sort($all_years); // Chronological order

    if (count($all_years) < 2) {
        echo json_encode(['error' => 'Insufficient data for a yearly comparison. Results from at least two different years are needed.']);
        return;
    }

    // Align data for all subjects with the master list of years
    foreach ($datasets as &$dataset) {
        $aligned_data = [];
        foreach ($all_years as $year) {
            $aligned_data[] = $dataset['data'][$year] ?? 0; // Default to 0 if no data for that year
        }
        $dataset['data'] = $aligned_data;
    }

    $response = [
        'title' => 'Yearly Subject Performance',
        'labels' => $all_years,
        'datasets' => $datasets
    ];

    echo json_encode($response);
}

// --- Function for Student: Overall Yearly Result ---
function generateStudentData($conn, $userId)
{
    $sql = "SELECT academic_year, AVG((marks_obtained / total_marks) * 100) as overall_percentage 
            FROM student_marks 
            WHERE student_id = ?
            GROUP BY academic_year 
            ORDER BY academic_year ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Failed to prepare statement for student results.']);
        return;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $labels = [];
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['academic_year'];
            $data[] = round($row['overall_percentage'], 2);
        }
    }
    $stmt->close();

    if (count($labels) < 2) {
        echo json_encode(['error' => 'Insufficient data for a yearly comparison. Your results from at least two different years are needed.']);
        return;
    }

    $response = [
        'title' => 'My Yearly Performance (%)',
        'labels' => $labels,
        'datasets' => [[
            'label' => 'Overall Percentage',
            'data' => $data,
            'borderColor' => 'rgba(246, 194, 62, 1)',
            'backgroundColor' => 'rgba(246, 194, 62, 0.05)',
            'lineTension' => 0.3
        ]]
    ];
    echo json_encode($response);
}
