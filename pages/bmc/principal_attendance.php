<?php
/*
|--------------------------------------------------------------------------
| BACKEND LOGIC (CONTROLLER)
|--------------------------------------------------------------------------
| This section handles all server-side operations, including the new logic
| for the monthly summary and the AJAX request for detailed attendance.
*/

// Core Includes - Using absolute paths for reliability
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/encryption.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/ajax_helpers.php'; // For is_ajax_request()

// --- Authorization ---
if (!isset($_COOKIE['encrypted_user_role']) || decrypt_id($_COOKIE['encrypted_user_role']) !== 'superadmin') {
    header("Location: /BMC-SMS/login.php");
    exit();
}

// --- AJAX Handler for Detailed View ---
if (is_ajax_request() && isset($_POST['action']) && $_POST['action'] === 'get_monthly_details') {
    header('Content-Type: application/json');
    $principal_id = (int)$_POST['principal_id'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $response = ['success' => false, 'html' => ''];

    try {
        $detail_stmt = $conn->prepare('
            SELECT attendance_date, status 
            FROM principal_attendance 
            WHERE principal_id = ? AND EXTRACT(MONTH FROM attendance_date) = ? AND EXTRACT(YEAR FROM attendance_date) = ?
            ORDER BY attendance_date ASC
        ');
        $detail_stmt->execute([$principal_id, $month, $year]);
        $details = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($details) {
            $html = '<div class="details-grid">';
            foreach ($details as $detail) {
                $status = $detail['status'];
                $badge_class = 'secondary';
                if ($status == 'Present') $badge_class = 'success';
                if ($status == 'Absent') $badge_class = 'danger';
                if ($status == 'Leave') $badge_class = 'warning';

                $html .= '<div class="detail-item">';
                $html .= '<span class="detail-date">' . date("d M Y", strtotime($detail['attendance_date'])) . '</span>';
                $html .= "<span class='badge badge-{$badge_class}'>" . htmlspecialchars($status) . "</span>";
                $html .= '</div>';
            }
            $html .= '</div>';
            $response = ['success' => true, 'html' => $html];
        } else {
            $response['html'] = '<div class="text-center p-3">No detailed records found for this month.</div>';
        }
    } catch (PDOException $e) {
        $response['html'] = 'Error fetching details: ' . $e->getMessage();
    }
    echo json_encode($response);
    exit;
}

// --- Main Page Logic & Data Fetching ---
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');
$attendance_summary = [];
$schools = [];

try {
    $schools_stmt = $conn->query('SELECT "id", "school_name" FROM "school" ORDER BY "school_name" ASC');
    $schools = $schools_stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary_query = '
        SELECT 
            p.id as principal_id,
            p.principal_name, 
            s.school_name,
            p.batch,
            COUNT(CASE WHEN pa.status = \'Present\' THEN 1 END) as total_present_days
        FROM principal p
        JOIN school s ON p.school_id = s.id
        LEFT JOIN principal_attendance pa ON p.id = pa.principal_id 
                                        AND EXTRACT(MONTH FROM pa.attendance_date) = ? 
                                        AND EXTRACT(YEAR FROM pa.attendance_date) = ?
        GROUP BY p.id, p.principal_name, s.school_name, p.batch
        ORDER BY s.school_name, p.principal_name ASC
    ';

    $stmt = $conn->prepare($summary_query);
    $stmt->execute([$selected_month, $selected_year]);
    $attendance_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = "Principal Attendance Summary";
?>

<?php
/*
|--------------------------------------------------------------------------
| RESPONSIVE & PROFESSIONAL FRONTEND (VIEW)
|--------------------------------------------------------------------------
*/
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link href="/BMC-SMS/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BMC-SMS/assets/css/sidebar.css">
    <link rel="stylesheet" href="/BMC-SMS/assets/css/scrollbar_hidden.css">

    <style>
        .mobile-card-view {
            display: none;
        }

        /* Hide mobile cards by default */

        /* Responsive Breakpoint for Tablets and below */
        @media (max-width: 991.98px) {
            .desktop-table {
                display: none;
            }

            /* Hide table on small screens */
            .mobile-card-view {
                display: block;
            }

            /* Show cards on small screens */
        }

        .expandable-row {
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }

        .expandable-row:hover {
            background-color: #f8f9fc;
        }

        .details-row {
            display: none;
        }

        .details-cell {
            padding: 0 !important;
            background-color: #f8f9fc;
        }

        .details-content {
            padding: 1.25rem;
            border-top: 2px solid #4e73df;
        }

        .expand-icon {
            transition: transform 0.2s ease-in-out;
        }

        .expanded .expand-icon {
            transform: rotate(90deg);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            background-color: #fff;
            border: 1px solid #e3e6f0;
            border-radius: .35rem;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
        }

        .detail-date {
            font-size: 0.85rem;
            color: #5a5c69;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Principal Attendance Summary</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i>Filter Records</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="form-row align-items-end">
                                    <div class="form-group col-md-5 col-sm-12">
                                        <label for="month">Month:</label>
                                        <select name="month" id="month" class="form-control">
                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" <?php if ($selected_month == $m) echo 'selected'; ?>>
                                                    <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-5 col-sm-6">
                                        <label for="year">Year:</label>
                                        <select name="year" id="year" class="form-control">
                                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                                <option value="<?php echo $y; ?>" <?php if ($selected_year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2 col-sm-6">
                                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Attendance Summary for <?php echo date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive desktop-table">
                                <table class="table table-hover" id="attendanceSummaryTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Principal Name</th>
                                            <th>School</th>
                                            <th>Batch</th>
                                            <th class="text-center">Total Present Days</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($attendance_summary)): ?>
                                            <?php foreach ($attendance_summary as $record): ?>
                                                <tr class="expandable-row" data-principal-id="<?php echo $record['principal_id']; ?>">
                                                    <td><?php echo htmlspecialchars($record['principal_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['school_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['batch']); ?></td>
                                                    <td class="text-center font-weight-bold text-success"><?php echo htmlspecialchars($record['total_present_days']); ?></td>
                                                </tr>
                                                <tr class="details-row">
                                                    <td colspan="5" class="details-cell">
                                                        <div class="details-content">
                                                            <div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading details...</div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mobile-card-view">
                                <?php if (!empty($attendance_summary)): ?>
                                    <?php foreach ($attendance_summary as $record): ?>
                                        <div class="card shadow-sm mb-3">
                                            <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center expandable-row" data-principal-id="<?php echo $record['principal_id']; ?>">
                                                <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($record['principal_name']); ?></h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between py-2 border-bottom">
                                                    <span class="font-weight-bold">School:</span>
                                                    <span><?php echo htmlspecialchars($record['school_name']); ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between py-2 border-bottom">
                                                    <span class="font-weight-bold">Batch:</span>
                                                    <span><?php echo htmlspecialchars($record['batch']); ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between py-2">
                                                    <span class="font-weight-bold">Total Present:</span>
                                                    <span class="font-weight-bold text-success"><?php echo htmlspecialchars($record['total_present_days']); ?></span>
                                                </div>
                                            </div>
                                            <div class="details-row card-footer">
                                                <div class="details-content">
                                                    <div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading details...</div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <?php if (empty($attendance_summary)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5"><i class="fas fa-user-times fa-2x text-gray-300"></i>
                                        <p class="mt-2">No principals found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/logout_modal.php'; ?>

    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Use event delegation to handle clicks on both table rows and card headers
            $(document).on('click', '.expandable-row', function() {
                const clickedElement = $(this);
                const isCardHeader = clickedElement.hasClass('card-header');

                // Determine the context (table row or card)
                const container = isCardHeader ? clickedElement.closest('.card') : clickedElement;
                const detailsRow = container.find('.details-row').first(); // Find the associated details row/footer

                container.toggleClass('expanded');
                detailsRow.toggle();

                if (container.hasClass('expanded') && !detailsRow.data('loaded')) {
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