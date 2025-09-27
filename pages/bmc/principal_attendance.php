<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
*/

// Core Includes
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/ajax_helpers.php';


// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

// --- Authorization ---
if (!isset($_COOKIE['encrypted_user_role']) || decrypt_id($_COOKIE['encrypted_user_role']) !== 'superadmin') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// --- AJAX Handler for Detailed View ---
// This handles the existing functionality of fetching monthly details when a user clicks the '+' icon.
if (is_ajax_request() && isset($_POST['action']) && $_POST['action'] === 'get_monthly_details') {
    header('Content-Type: application/json');
    $principal_id = (int)$_POST['principal_id'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $response = ['success' => false, 'html' => ''];

    try {
        $detail_stmt = $conn->prepare(
            'SELECT attendance_date, status FROM principal_attendance
             WHERE principal_id = ? AND EXTRACT(MONTH FROM attendance_date) = ? AND EXTRACT(YEAR FROM attendance_date) = ?
             ORDER BY attendance_date ASC'
        );
        $detail_stmt->execute([$principal_id, $month, $year]);
        $details = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($details) {
            $html = '<table class="table table-sm table-striped mb-0"><thead><tr><th>Date</th><th>Status</th></tr></thead><tbody>';
            foreach ($details as $detail) {
                $status_class = $detail['status'] === 'Present' ? 'text-success' : 'text-danger';
                $html .= '<tr><td>' . date('d-m-Y', strtotime($detail['attendance_date'])) . '</td><td class="' . $status_class . '">' . htmlspecialchars($detail['status']) . '</td></tr>';
            }
            $html .= '</tbody></table>';
            $response['html'] = $html;
        } else {
            $response['html'] = '<div class="alert alert-info mb-0">No attendance records found for this month.</div>';
        }
        $response['success'] = true;
    } catch (PDOException $e) {
        error_log("AJAX Detail Fetch Error: " . $e->getMessage());
        $response['html'] = 'Error fetching details.';
    }
    echo json_encode($response);
    exit(); // Important: Stop script execution for this AJAX action
}

// This check is crucial for the main page navigation to work.
$is_ajax_request = is_ajax_request();

// --- Initialization for Page Load ---
$message = '';
$principals = [];
$attendance_summary = [];
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

try {
    // --- Attendance Submission Handling ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
        $attendance_date = $_POST['attendance_date'];
        $statuses = $_POST['status'];

        $stmt = $conn->prepare("
            INSERT INTO principal_attendance (principal_id, attendance_date, status) VALUES (?, ?, ?)
            ON CONFLICT (principal_id, attendance_date) DO UPDATE SET status = EXCLUDED.status
        ");

        foreach ($statuses as $principal_id => $status) {
            $stmt->execute([(int)$principal_id, $attendance_date, $status]);
        }
        $message = "Attendance for " . date("F j, Y", strtotime($attendance_date)) . " has been saved!";
    }

    // --- Data Fetching for Display ---
    // Fetch all principals and their schools
    $stmt_principals = $conn->query("
        SELECT p.id, p.principal_name, s.school_name
        FROM principal p
        JOIN school s ON p.school_id = s.id
        ORDER BY p.principal_name ASC
    ");
    $principals = $stmt_principals->fetchAll(PDO::FETCH_ASSOC);

    // Fetch monthly attendance summary for each principal
    $stmt_summary = $conn->prepare("
        SELECT principal_id,
               COUNT(*) FILTER (WHERE status = 'Present') AS present_days,
               COUNT(*) FILTER (WHERE status = 'Absent') AS absent_days
        FROM principal_attendance
        WHERE EXTRACT(MONTH FROM attendance_date) = ? AND EXTRACT(YEAR FROM attendance_date) = ?
        GROUP BY principal_id
    ");
    $stmt_summary->execute([$selected_month, $selected_year]);
    while ($row = $stmt_summary->fetch(PDO::FETCH_ASSOC)) {
        $attendance_summary[$row['principal_id']] = $row;
    }
} catch (PDOException $e) {
    error_log("Database Error in principal_attendance.php: " . $e->getMessage());
    $message = "A database error occurred. Please try again.";
}

?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Principal Attendance</title>
        <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
        <link rel="stylesheet" href="../../assets/css/sidebar.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
        <style>
            .details-row {
                display: none;
            }

            .details-row .details-content {
                padding: 1rem;
                background-color: #f8f9fc;
            }

            .toggle-details {
                cursor: pointer;
            }
        </style>
    </head>

    <body id="page-top">
        <div id="wrapper">
            <?php
            if (!$is_ajax_request) {
                include '../../includes/sidebar.php';
            }
            ?>
            <div id="content-wrapper" class="d-flex flex-column">
                <div id="content">
                    <?php
                    if (!$is_ajax_request) {
                        include '../../includes/header.php';
                    }
                    ?>
                    <div id="main-content">
                        <div class="container-fluid">
                            <h1 class="h3 mb-4 text-gray-800">Principal Attendance</h1>

                        <?php if ($message): ?>
                            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>

                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Mark Today's Attendance</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="form-group">
                                        <label for="attendance_date">Date</label>
                                        <input type="date" name="attendance_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Principal Name</th>
                                                    <th>School Name</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($principals as $principal): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($principal['principal_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($principal['school_name']); ?></td>
                                                        <td>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="status[<?php echo $principal['id']; ?>]" value="Present" checked>
                                                                <label class="form-check-label">Present</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="status[<?php echo $principal['id']; ?>]" value="Absent">
                                                                <label class="form-check-label">Absent</label>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="submit" name="mark_attendance" class="btn btn-primary">Save Attendance</button>
                                </form>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Monthly Attendance Summary</h6>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="form-inline mb-3">
                                    <label for="month" class="mr-2">Month:</label>
                                    <select name="month" id="month" class="form-control mr-2">
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($selected_month == $i) ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $i, 10)); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <label for="year" class="mr-2">Year:</label>
                                    <input type="number" name="year" id="year" class="form-control mr-2" value="<?php echo $selected_year; ?>">
                                    <button type="submit" class="btn btn-info">View</button>
                                </form>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Principal Name</th>
                                                <th>Present</th>
                                                <th>Absent</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($principals as $principal):
                                                $present = $attendance_summary[$principal['id']]['present_days'] ?? 0;
                                                $absent = $attendance_summary[$principal['id']]['absent_days'] ?? 0;
                                            ?>
                                                <tr class="toggle-details" data-principal-id="<?php echo $principal['id']; ?>">
                                                    <td><i class="fas fa-plus-circle text-primary"></i></td>
                                                    <td><?php echo htmlspecialchars($principal['principal_name']); ?></td>
                                                    <td><?php echo $present; ?></td>
                                                    <td><?php echo $absent; ?></td>
                                                </tr>
                                                <tr class="details-row">
                                                    <td colspan="4" class="p-0">
                                                        <div class="details-content">Loading...</div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
                <?php
                if (!$is_ajax_request) {
                    include '../../includes/footer.php';
                }
                ?>
            </div>
        </div>
        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>
        <script>
            $(document).ready(function() {
                $('.toggle-details').on('click', function() {
                    const clickedElement = $(this);
                    const detailsRow = clickedElement.next('.details-row');
                    detailsRow.toggle();

                    if (detailsRow.is(':visible') && !detailsRow.data('loaded')) {
                        const principalId = clickedElement.data('principal-id');
                        const month = $('#month').val();
                        const year = $('#year').val();
                        const detailsContent = detailsRow.find('.details-content');

                        $.ajax({
                            url: 'principal_attendance.php',
                            type: 'POST',
                            data: {
                                action: 'get_monthly_details',
                                principal_id: principalId,
                                month: month,
                                year: year
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    detailsContent.html(response.html);
                                    detailsRow.data('loaded', true);
                                } else {
                                    detailsContent.html('<div class="alert alert-danger mb-0">Could not load details.</div>');
                                }
                            },
                            error: function() {
                                detailsContent.html('<div class="alert alert-danger mb-0">An error occurred while fetching details.</div>');
                            }
                        });
                    }
                });
            });
        </script>
    </body>

    </html>