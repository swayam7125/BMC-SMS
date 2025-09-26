<?php
// --- Includes & Setup ---
include_once '../../includes/connect.php';
include_once '../../encryption.php';
include_once '../../includes/ajax_helpers.php'; // For is_ajax_request()

// This check is crucial for the AJAX navigation to work.
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
// $is_ajax_request = is_ajax_request();

// --- Authorization ---
$role = isset($_COOKIE['encrypted_user_role']) ? decrypt_id($_COOKIE['encrypted_user_role']) : null;
$user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

if ($role !== 'librarian' || !$user_id) {
    header("Location: ../../login.php");
    exit;
}

// --- Data Fetching ---
$school_id = null;
$available_books = [];
$issued_books = [];
$returned_history = [];

try {
    // Get the librarian's school ID to scope all queries.
    $stmt = $conn->prepare('SELECT "school_id" FROM "librarian" WHERE "id" = ?');
    $stmt->execute([$user_id]);
    $school_id = $stmt->fetchColumn();

    if (!$school_id) {
        die("Could not determine the librarian's school. Access denied.");
    }

    // Fetch books available for issuing.
    $available_books_stmt = $conn->prepare('SELECT "book_id", "title", "author" FROM "books" WHERE "school_id" = ? AND "quantity_available" > 0 ORDER BY "title"');
    $available_books_stmt->execute([$school_id]);
    $available_books = $available_books_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch books that are currently issued and not yet returned.
    $issued_books_stmt = $conn->prepare('SELECT br.*, b.title, u.email as borrower_email FROM "borrowing_records" br JOIN "books" b ON br.book_id = b.book_id JOIN "users" u ON br.borrower_id = u.id WHERE b.school_id = ? AND br.is_returned = false ORDER BY br.due_date ASC');
    $issued_books_stmt->execute([$school_id]);
    $issued_books = $issued_books_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch the history of all returned books.
    $returned_history_sql = 'SELECT br.borrower_role, br.checkout_date, br.due_date, br.return_date, b.title, u.email AS borrower_email FROM "borrowing_records" br JOIN "books" b ON br.book_id = b.book_id JOIN "users" u ON br.borrower_id = u.id WHERE b.school_id = ? AND br.is_returned = true ORDER BY br.return_date DESC';
    $returned_history_stmt = $conn->prepare($returned_history_sql);
    $returned_history_stmt->execute([$school_id]);
    $returned_history = $returned_history_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
}

?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Issue & Return Books - School Management System</title>
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
        <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
        <link href="../../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/sidebar.css">
        <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
        <link rel="stylesheet" href="../../assets/css/table-to-card.css">
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
    include '../../includes/sidebar.php';
}
?> 
                    <div class="container-fluid">
                        <h1 class="h3 mb-4 text-gray-800">Issue & Return Books</h1>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_GET['success']); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
                        <?php endif; ?>
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_GET['error']); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-xl-5 col-lg-12 mb-4">
                                <div class="card shadow h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Issue a Book (In-Person)</h6>
                                    </div>
                                    <div class="card-body">
                                        <form action="handle_issue.php" method="post">
                                            <div class="form-group"><label for="book_id">Book *</label><select class="form-control" id="book_id" name="book_id" required>
                                                    <option value="">-- Select a Book --</option><?php foreach ($available_books as $book) {
                                                                                                        echo "<option value='{$book['book_id']}'>" . htmlspecialchars($book['title'] . ' by ' . $book['author']) . "</option>";
                                                                                                    } ?>
                                                </select></div>
                                            <div class="form-group"><label for="borrower_role">Borrower Role *</label><select class="form-control" id="borrower_role" name="borrower_role" required>
                                                    <option value="">-- Select Role --</option>
                                                    <option value="student">Student</option>
                                                    <option value="teacher">Teacher</option>
                                                </select></div>
                                            <div class="form-group"><label for="borrower_id">Borrower *</label><select class="form-control" id="borrower_id" name="borrower_id" required>
                                                    <option value="">-- First Select a Role --</option>
                                                </select></div>
                                            <p class="text-info small">Note: The due date will be automatically set to 14 days from today.</p>
                                            <button type="submit" class="btn btn-success"><i class="fas fa-check-circle"></i> Issue Book</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-7 col-lg-12 mb-4">
                                <div class="card shadow h-100">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Currently Issued Books</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover" width="100%">
                                                <thead>
                                                    <tr>
                                                        <th>Book Title</th>
                                                        <th>Borrower</th>
                                                        <th>Due Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($issued_books)): ?>
                                                        <tr>
                                                            <td colspan='4' class='text-center'>No books are currently issued.</td>
                                                        </tr>
                                                        <?php else: foreach ($issued_books as $ib): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($ib['title']) ?></td>
                                                                <td><?= htmlspecialchars($ib['borrower_email']) ?></td>
                                                                <td><?= date('d-m-Y', strtotime($ib['due_date'])) ?></td>
                                                                <td><a href="handle_return.php?record_id=<?= $ib['record_id'] ?>" class="btn btn-info btn-sm" onclick="return confirm('Mark this book as returned?');">Return</a></td>
                                                            </tr>
                                                    <?php endforeach;
                                                    endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Returned Books History</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="returnedHistoryTable" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Book Title</th>
                                                <th>Borrower Email</th>
                                                <th>Role</th>
                                                <th>Checkout Date</th>
                                                <th>Due Date</th>
                                                <th>Return Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($returned_history as $rh): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($rh['title']) ?></td>
                                                    <td><?= htmlspecialchars($rh['borrower_email']) ?></td>
                                                    <td><?= ucfirst(htmlspecialchars($rh['borrower_role'])) ?></td>
                                                    <td><?= date('d-m-Y', strtotime($rh['checkout_date'])) ?></td>
                                                    <td><?= date('d-m-Y', strtotime($rh['due_date'])) ?></td>
                                                    <td><?= date('d-m-Y', strtotime($rh['return_date'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <?php
if (!$is_ajax_request) {
    include '../../includes/sidebar.php';
}
?> 
            </div>
        </div>

        <?php include_once "../../includes/logout_modal.php" ?>
        <script src="../../assets/vendor/jquery/jquery.min.js"></script>
        <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sb-admin-2.min.js"></script>
        <script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="../../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>

        <script>
            $(document).ready(function() {
                // Initialize DataTables on the history table for a better UX
                $('#returnedHistoryTable').DataTable({
                    "responsive": true,
                    "autoWidth": false,
                    "order": [
                        [5, "desc"]
                    ] // Order by the 'Return Date' column descending by default
                });

                // AJAX call to fetch borrowers when the role is changed
                $('#borrower_role').on('change', function() {
                    var role = $(this).val();
                    var borrowerSelect = $('#borrower_id');
                    borrowerSelect.html('<option value="">Loading...</option>').prop('disabled', true);

                    if (role) {
                        $.ajax({
                            url: 'get_borrowers.php',
                            type: 'GET',
                            data: {
                                role: role
                            },
                            dataType: 'json',
                            success: function(data) {
                                borrowerSelect.html('<option value="">-- Select a Borrower --</option>');
                                if (data.length > 0) {
                                    $.each(data, function(key, value) {
                                        borrowerSelect.append(`<option value="${value.id}">${value.name} (${value.email})</option>`);
                                    });
                                } else {
                                    borrowerSelect.html('<option value="">No users found for this role</option>');
                                }
                            },
                            error: function() {
                                borrowerSelect.html('<option value="">Failed to load data</option>');
                            },
                            complete: function() {
                                borrowerSelect.prop('disabled', false);
                            }
                        });
                    } else {
                        borrowerSelect.html('<option value="">-- First Select a Role --</option>');
                    }
                });
            });
        </script>
    </body>

    </html>
<?php
$conn = null;
?>