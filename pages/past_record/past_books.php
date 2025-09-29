<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php';

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

session_start();

$role = null;
$user_id = null;
$school_id = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($role !== 'librarian') {
    header("Location: ../../login.php");
    exit;
}

$deleted_books = [];
try {
    // --- CORRECTED: Using PDO ---
    if ($user_id) {
        $stmt = $conn->prepare('SELECT "school_id" FROM "librarian" WHERE "id" = ?');
        $stmt->execute([$user_id]);
        $school_id = $stmt->fetchColumn();
    }

    if (!$school_id) {
        die("Could not determine the librarian's school. Access denied.");
    }

    $stmt_books = $conn->prepare('SELECT * FROM "deleted_books" WHERE "school_id" = ? ORDER BY "deleted_at" DESC');
    $stmt_books->execute([$school_id]);
    $deleted_books = $stmt_books->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Past Book Records - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="/BMC-SMS/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/table-to-card.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css" />
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
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Past Book Records</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Deleted Book History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>ISBN</th>
                                            <th>Type</th>
                                            <th>Deleted On</th>
                                            <th>Deleted By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($deleted_books)): ?>
                                            <?php foreach ($deleted_books as $book): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                                    <td><?php echo $book['is_digital'] ? '<span class="badge badge-info">Digital</span>' : '<span class="badge badge-secondary">Physical</span>'; ?></td>
                                                    <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($book['deleted_at']))); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst($book['deleted_by_role'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No past book records found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/js/responsive-tables.js"></script>

    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [
                    [4, "desc"]
                ]
            });
        });
    </script>
</body>

</html>
<?php
$conn = null;
?>