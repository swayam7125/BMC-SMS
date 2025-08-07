<?php
// Include necessary files
include_once '../../includes/connect.php'; // This must create a PDO object $conn
include_once '../../encryption.php';

// Initialize variables
$role = null;
$userId = null;
$errorMessage = '';
$principalDetails = null;
$school_id = null;

$edit_librarian_id = isset($_GET['edit_librarian_id']) ? (int)$_GET['edit_librarian_id'] : null;

// Cookie and Authorization
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (!$role || $role !== 'principal' || !$userId) {
    header("Location: /BMC-SMS/login.php");
    exit();
}

try {
    // Fetch principal's school_id
    $stmt = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
    $stmt->execute([$userId]);
    $principalDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $school_id = $principalDetails['school_id'] ?? null;

    if (!$school_id) {
        throw new Exception("Access Denied: You are not assigned to a school.");
    }
} catch (PDOException $e) {
    $errorMessage = "Database Error: " . $e->getMessage();
}

// --- START of date validation logic ---
// Get the current date to use as a maximum for the date input
$current_date = date('Y-m-d');
$attendance_date_display = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : $current_date;

// Server-side check to prevent future dates, even if the URL is manipulated
if ($attendance_date_display > $current_date) {
    $attendance_date_display = $current_date;
    $errorMessage = "You cannot mark attendance for a future date. The date has been reset to today.";
}
// --- END of date validation logic ---

// === POST HANDLERS for both scenarios ===

// Handle BULK update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance']) && $school_id) {
    $attendance_data = $_POST['attendance'];
    $attendance_date = $_POST['attendance_date'];
    
    // Final server-side check on the submitted date
    if ($attendance_date > $current_date) {
        $attendance_date = $current_date;
    }

    try {
        $conn->beginTransaction(); // Start PDO transaction

        // PostgreSQL Change: Using ON CONFLICT for INSERT/UPDATE
        $upsert_sql = "INSERT INTO librarian_attendance (librarian_id, school_id, attendance_date, status, marked_by_user_id)
                       VALUES (?, ?, ?, ?, ?)
                       ON CONFLICT (librarian_id, attendance_date)
                       DO UPDATE SET status = EXCLUDED.status, marked_by_user_id = EXCLUDED.marked_by_user_id";
        $stmt_upsert = $conn->prepare($upsert_sql);

        foreach ($attendance_data as $librarian_id => $status) {
            $stmt_upsert->execute([$librarian_id, $school_id, $attendance_date, $status, $userId]);
        }
        $conn->commit(); // Commit transaction
        $success_message = "Attendance for " . htmlspecialchars($attendance_date) . " has been successfully saved!";
        header("Location: view_librarian_attendance.php?date=" . urlencode($attendance_date) . "&success=" . urlencode($success_message));
        exit();
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback on error
        $errorMessage = "Failed to update attendance: " . $e->getMessage();
    }
}

// Handle SINGLE librarian update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['librarian_id']) && $school_id) {
    $librarian_id_to_update = $_POST['librarian_id'];
    $status = $_POST['status'];
    $attendance_date = $_POST['attendance_date'];

    // Final server-side check on the submitted date
    if ($attendance_date > $current_date) {
        $attendance_date = $current_date;
    }

    try {
        $conn->beginTransaction(); // Start PDO transaction

        // PostgreSQL Change: Using ON CONFLICT for INSERT/UPDATE
        $upsert_sql = "INSERT INTO librarian_attendance (librarian_id, school_id, attendance_date, status, marked_by_user_id)
                       VALUES (?, ?, ?, ?, ?)
                       ON CONFLICT (librarian_id, attendance_date)
                       DO UPDATE SET status = EXCLUDED.status, marked_by_user_id = EXCLUDED.marked_by_user_id";
        $stmt_upsert = $conn->prepare($upsert_sql);
        $stmt_upsert->execute([$librarian_id_to_update, $school_id, $attendance_date, $status, $userId]);
        
        $conn->commit(); // Commit transaction
        $success_message = "Attendance for the librarian has been updated successfully!";
        header("Location: view_librarian_attendance.php?date=" . urlencode($attendance_date) . "&success=" . urlencode($success_message));
        exit();
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback on error
        $errorMessage = "Failed to update attendance: " . $e->getMessage();
    }
}

// --- Data Fetching for Display (Optimized to use fewer queries) ---
$librarians_with_details = [];

if (empty($errorMessage) && $school_id) {
    try {
        // --- START of a new code section ---
        if ($edit_librarian_id) {
            // Fetch only the specific librarian if an ID is provided
            $lib_stmt = $conn->prepare("SELECT id, librarian_name FROM librarian WHERE id = ? AND school_id = ?");
            $lib_stmt->execute([$edit_librarian_id, $school_id]);
        } else {
            // Otherwise, fetch all librarians for the bulk edit view
            $lib_stmt = $conn->prepare("SELECT id, librarian_name FROM librarian WHERE school_id = ? ORDER BY librarian_name ASC");
            $lib_stmt->execute([$school_id]);
        }
        $all_librarians = $lib_stmt->fetchAll(PDO::FETCH_ASSOC);
        // --- END of a new code section ---
        
        // Query 2: Get all attendance records for that specific date
        $att_stmt = $conn->prepare("SELECT librarian_id, status FROM librarian_attendance WHERE school_id = ? AND attendance_date = ?");
        $att_stmt->execute([$school_id, $attendance_date_display]);
        $attendance_records_raw = $att_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Creates a [librarian_id => status] map

        // Map the attendance records to the librarians
        foreach ($all_librarians as $librarian) {
            $librarian['status'] = $attendance_records_raw[$librarian['id']] ?? 'Present'; // Default to 'Present' if no record exists
            $librarians_with_details[] = $librarian;
        }

    } catch (PDOException $e) {
        $errorMessage = "Failed to load attendance data: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Update Librarian Attendance - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    </head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Update Librarian Attendance</h1>
                        <a href="view_librarian_attendance.php?date=<?php echo htmlspecialchars($attendance_date_display); ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-eye fa-sm text-white-50"></i> View History</a>
                    </div>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    Attendance for 
                                    <?php echo ($edit_librarian_id && count($librarians_with_details) > 0) ? htmlspecialchars($librarians_with_details[0]['librarian_name']) : 'Librarians'; ?>
                                    on <?php echo htmlspecialchars($attendance_date_display); ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <!-- A single form for date selection that automatically reloads the page -->
                                    <div class="form-inline">
                                        <div class="form-group">
                                            <label for="attendance_date" class="mr-2">Date:</label>
                                            <input type="date" id="attendance_date" name="attendance_date" class="form-control" value="<?php echo htmlspecialchars($attendance_date_display); ?>" max="<?php echo $current_date; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="customSearchBox" class="mr-2">Search:</label>
                                        <input type="text" id="customSearchBox" class="form-control" placeholder="Search librarians...">
                                    </div>
                                </div>
                                <?php if ($edit_librarian_id): ?>
                                    <p class="text-info">Single Edit Mode: Only one librarian is editable.</p>
                                    <form method="POST" action="">
                                        <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($attendance_date_display); ?>">
                                        <input type="hidden" name="librarian_id" value="<?php echo htmlspecialchars($edit_librarian_id); ?>">
                                        <div class="table-responsive">
                                            <table class="table table-bordered" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Librarian Name</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($librarians_with_details)): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($librarians_with_details[0]['librarian_name']); ?></td>
                                                            <td>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="status" value="Present" <?php if ($librarians_with_details[0]['status'] == 'Present') echo 'checked'; ?>>
                                                                    <label>Present</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="status" value="Absent" <?php if ($librarians_with_details[0]['status'] == 'Absent') echo 'checked'; ?>>
                                                                    <label>Absent</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="status" value="Leave" <?php if ($librarians_with_details[0]['status'] == 'Leave') echo 'checked'; ?>>
                                                                    <label>Leave</label>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="2" class="text-center">No librarian found.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php if (!empty($librarians_with_details)): ?>
                                            <button type="submit" class="btn btn-success mt-3"><i class="fas fa-save"></i> Save Attendance</button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <p class="text-info">Bulk Edit Mode: All librarians are editable.</p>
                                    <form method="POST" action="">
                                        <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($attendance_date_display); ?>">
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Librarian Name</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($librarians_with_details as $librarian): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($librarian['librarian_name']); ?></td>
                                                            <td>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $librarian['id']; ?>]" value="Present" <?php if ($librarian['status'] == 'Present') echo 'checked'; ?>>
                                                                    <label>Present</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $librarian['id']; ?>]" value="Absent" <?php if ($librarian['status'] == 'Absent') echo 'checked'; ?>>
                                                                    <label>Absent</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="attendance[<?php echo $librarian['id']; ?>]" value="Leave" <?php if ($librarian['status'] == 'Leave') echo 'checked'; ?>>
                                                                    <label>Leave</label>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php if (!empty($librarians_with_details)): ?>
                                            <button type="submit" class="btn btn-success mt-3"><i class="fas fa-save"></i> Save All Attendance</button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#dataTable').DataTable({
                "paging": false,
                "info": false,
                "dom": '<"table-responsive"t>'
            });
            $('#customSearchBox').on('keyup', function() {
                table.search(this.value).draw();
            });

            // This script reloads the page with the new date as soon as a date is selected.
            // This eliminates the need for a separate "Load" button.
            $('#attendance_date').on('change', function() {
                var selectedDate = $(this).val();
                var editLibrarianId = '<?php echo $edit_librarian_id; ?>';
                var redirectUrl = 'librarian_attendance.php?attendance_date=' + selectedDate;
                if (editLibrarianId) {
                    redirectUrl += '&edit_librarian_id=' + editLibrarianId;
                }
                window.location.href = redirectUrl;
            });
        });
    </script>
</body>
</html>
