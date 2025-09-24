<?php
// --- Includes & Security ---
// Adjust the paths to your existing project structure
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// --- Authorization Check (ensure only HR/Principal can access) ---
// $role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
// if ($role !== 'principal' && $role !== 'hr') {
//     header("Location: ../../login.php?error=Unauthorized");
//     exit;
// }

try {
    // --- Fetch all admission inquiries from the database ---
    $stmt = $conn->query("SELECT id, admission_id, student_name, grade_applying_for, parent_phone, status FROM admission_inquiries ORDER BY created_at DESC");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admission Applications - BMC-SMS</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Manage Admission Applications</h1>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($_GET['success']); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($_GET['error']); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Applications</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="admissionsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Admission ID</th>
                                            <th>Student Name</th>
                                            <th>Grade Applied</th>
                                            <th>Parent Phone</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($app['admission_id']); ?></td>
                                                <td><?php echo htmlspecialchars($app['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($app['grade_applying_for']); ?></td>
                                                <td><?php echo htmlspecialchars($app['parent_phone']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                                                switch ($app['status']) {
                                                                                    case 'Accepted':
                                                                                        echo 'success';
                                                                                        break;
                                                                                    case 'Rejected':
                                                                                        echo 'danger';
                                                                                        break;
                                                                                    case 'In Review':
                                                                                        echo 'warning';
                                                                                        break;
                                                                                    default:
                                                                                        echo 'primary';
                                                                                }
                                                                                ?>"><?php echo htmlspecialchars($app['status']); ?></span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-info btn-sm view-details" data-id="<?php echo $app['id']; ?>" data-toggle="modal" data-target="#detailsModal">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
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
    </div>

    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button class="close" type="button" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="modalBodyContent">
                    <p class="text-center">Loading details...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize the data table for sorting and searching
            $('#admissionsTable').DataTable();

            // Handle the 'View Details' button click
            $('.view-details').on('click', function() {
                var applicationId = $(this).data('id');
                $('#modalBodyContent').html('<p class="text-center">Loading details...</p>'); // Show loading text

                // Fetch application details via AJAX
                $.ajax({
                    url: 'get_admission_details.php',
                    type: 'GET',
                    data: {
                        id: applicationId
                    },
                    success: function(response) {
                        $('#modalBodyContent').html(response);
                    },
                    error: function() {
                        $('#modalBodyContent').html('<p class="text-center text-danger">Could not load details.</p>');
                    }
                });
            });

            // Logic to show/hide fields in the modal form based on the selected action
            $(document).on('change', '#status_action', function() {
                var action = $(this).val();
                if (action === 'Accepted') {
                    $('#acceptFields').show();
                    $('#rejectFields').hide();
                } else if (action === 'Rejected') {
                    $('#acceptFields').hide();
                    $('#rejectFields').show();
                } else {
                    $('#acceptFields').hide();
                    $('#rejectFields').hide();
                }
            });
        });
    </script>
</body>

</html>