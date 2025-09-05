<?php
// --- PDF GENERATION SETUP ---
require_once '../../includes/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// --- PDF GENERATION LOGIC ---
if (isset($_POST['download_pdf'])) {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $html = $_POST['pdf_html'];
    $filename = $_POST['pdf_filename'] ?? 'Holiday_List.pdf';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($filename, ["Attachment" => 1]);
    exit();
}

include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

date_default_timezone_set('Asia/Kolkata');

// --- User Authentication and Authorization ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$userId = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'principal' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

$school_id = null;
$school_name = "School Management System";
$errorMessage = '';
$successMessage = '';
$holidays = [];
$available_years = [];

try {
    // Fetch the principal's assigned school ID and name
    $stmt_school = $conn->prepare("SELECT s.id, s.school_name FROM principal p JOIN school s ON p.school_id = s.id WHERE p.id = ?");
    $stmt_school->execute([$userId]);
    $principalDetails = $stmt_school->fetch(PDO::FETCH_ASSOC);
    $school_id = $principalDetails['id'] ?? null;
    $school_name = $principalDetails['school_name'] ?? 'School Management System';

    if (!$school_id) {
        throw new Exception("Access Denied: You are not assigned to a school.");
    }

    // --- HANDLE FORM SUBMISSIONS (ADD/DELETE) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        $holiday_date = $_POST['holiday_date'];
        $description = trim($_POST['description']);

        if (empty($holiday_date) || empty($description)) {
            $errorMessage = "Both date and description are required.";
        } else {
            $checkStmt = $conn->prepare("SELECT id FROM holidays WHERE school_id = ? AND holiday_date = ?");
            $checkStmt->execute([$school_id, $holiday_date]);
            if ($checkStmt->fetch()) {
                $errorMessage = "A holiday for this date already exists.";
            } else {
                $insertStmt = $conn->prepare("INSERT INTO holidays (school_id, holiday_date, description) VALUES (?, ?, ?)");
                if ($insertStmt->execute([$school_id, $holiday_date, $description])) {
                    $successMessage = "Holiday added successfully!";
                } else {
                    $errorMessage = "Failed to add holiday.";
                }
            }
        }
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $holiday_id = $_GET['id'];
        $deleteStmt = $conn->prepare("DELETE FROM holidays WHERE id = ? AND school_id = ?");
        if ($deleteStmt->execute([$holiday_id, $school_id])) {
            $successMessage = "Holiday deleted successfully!";
        } else {
            $errorMessage = "Failed to delete holiday.";
        }
    }

    // --- FETCH DATA FOR DISPLAY ---
    $stmt_years = $conn->prepare("SELECT DISTINCT EXTRACT(YEAR FROM holiday_date) as year FROM holidays WHERE school_id = ? ORDER BY year ASC");
    $stmt_years->execute([$school_id]);
    $available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN, 0);

    $current_year = date('Y');
    $selected_year = isset($_GET['year']) ? $_GET['year'] : (in_array($current_year, $available_years) ? $current_year : 'all');

    $params = [$school_id];
    $query = "SELECT id, holiday_date, description FROM holidays WHERE school_id = ?";

    if ($selected_year !== 'all' && is_numeric($selected_year)) {
        $query .= " AND EXTRACT(YEAR FROM holiday_date) = ?";
        $params[] = $selected_year;
    }

    $query .= " ORDER BY holiday_date ASC";
    $stmt_holidays = $conn->prepare($query);
    $stmt_holidays->execute($params);
    $holidays = $stmt_holidays->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
}

if (!is_ajax_request()) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Holiday Management - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
<?php
}
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Holiday Management</h1>

                    <?php if ($successMessage): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($successMessage); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
                    <?php if ($errorMessage): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($errorMessage); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add a New Holiday</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="manage_holidays.php">
                                <input type="hidden" name="action" value="add">
                                <div class="form-row">
                                    <div class="form-group col-md-5"><label for="holiday_date">Holiday Date</label><input type="date" class="form-control" id="holiday_date" name="holiday_date" required></div>
                                    <div class="form-group col-md-5"><label for="description">Description</label><input type="text" class="form-control" id="description" name="description" placeholder="e.g., Ram Navami" required></div>
                                    <div class="form-group col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-success btn-block"><i class="fas fa-plus"></i> Add</button></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">List of Festivals</h6>
                            <div class="d-flex align-items-center">
                                <form id="download-form" method="POST" action="manage_holidays.php">
                                    <input type="hidden" name="download_pdf" value="1">
                                    <input type="hidden" id="pdf_html" name="pdf_html">
                                    <input type="hidden" id="pdf_filename" name="pdf_filename">
                                    <button type="button" id="download-list-btn" class="btn btn-primary btn-sm mr-2" <?php echo empty($holidays) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-download"></i> Download List
                                    </button>
                                </form>
                                <form action="manage_holidays.php" method="GET" class="form-inline">
                                    <div class="form-group">
                                        <label for="year" class="mr-2">Year:</label>
                                        <select name="year" id="year" class="form-control form-control-sm" onchange="this.form.submit()">
                                            <option value="all" <?php echo ($selected_year == 'all') ? 'selected' : ''; ?>>All Years</option>
                                            <?php foreach ($available_years as $year): ?>
                                                <option value="<?php echo $year; ?>" <?php echo ($selected_year == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="holiday-table-for-pdf">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                     <tbody>
                                        <?php if (empty($holidays)): ?>
                                            <tr><td colspan="3" class="text-center">No holidays found for the selected year.</td></tr>
                                        <?php else: ?>
                                            <?php
                                            $last_year = null;
                                            foreach ($holidays as $holiday):
                                                $current_year_for_row = date('Y', strtotime($holiday['holiday_date']));
                                                if ($selected_year === 'all' && $current_year_for_row !== $last_year) {
                                                    echo '<tr><td colspan="3" class="text-center font-weight-bold bg-light">' . $current_year_for_row . '</td></tr>';
                                                    $last_year = $current_year_for_row;
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo date('d M, Y', strtotime($holiday['holiday_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($holiday['description']); ?></td>
                                                    <td>
                                                        <a href="manage_holidays.php?action=delete&id=<?php echo $holiday['id']; ?>" class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Are you sure to delete the holiday : <?php echo htmlspecialchars($holiday['description'], ENT_QUOTES, 'UTF-8'); ?>?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
<?php
if (!is_ajax_request()) {
?>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>

    <?php include_once "../../includes/logout_modal.php"?>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
    document.getElementById('download-list-btn').addEventListener('click', function() {
        const table = document.getElementById('holiday-table-for-pdf');
        
        // Clone the table element so we can modify it without affecting the original page layout
        const tableClone = table.cloneNode(true);
        
        // Remove the 'Action' column headers
        const headerRow = tableClone.querySelector('thead tr');
        if (headerRow) {
            const actionHeader = headerRow.querySelector('th:last-child');
            if (actionHeader && actionHeader.textContent.trim() === 'Action') {
                actionHeader.remove();
            }
        }
        
        // Remove the 'Action' column data cells
        const bodyRows = tableClone.querySelectorAll('tbody tr');
        bodyRows.forEach(row => {
            const actionCell = row.querySelector('td:last-child');
            // Ensure we don't accidentally remove a column from a year header row
            if (actionCell && actionCell.parentElement.querySelectorAll('td').length > 2) {
                actionCell.remove();
            }
        });

        const year = document.getElementById('year').value;
        const schoolName = "<?php echo htmlspecialchars($school_name); ?>";

        const pdfHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Holiday List</title>
                <style>
                    body { font-family: sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 20px; }
                    h1 { font-size: 1.5rem; margin: 0; }
                    h2 { font-size: 1.2rem; font-weight: normal; margin-top: 5px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .text-center { text-align: center; }
                    .font-weight-bold { font-weight: bold; }
                    .bg-light { background-color: #f8f9fa; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Holiday List</h1>
                    <h2>For ${schoolName}</h2>
                    <h3>Year: ${year === 'all' ? 'All Years' : year}</h3>
                </div>
                ${tableClone.outerHTML}
            </body>
            </html>
        `;

        const filename_part = year === 'all' ? 'AllYears' : year;
        document.getElementById('pdf_html').value = pdfHtml;
        document.getElementById('pdf_filename').value = 'Holiday_List_' + filename_part + '.pdf';
        document.getElementById('download-form').submit();
    });
    </script>
</body>
</html>
<?php
}
?>