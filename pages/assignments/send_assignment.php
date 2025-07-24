<?php
// Standard setup from your dashboard.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Adjust the path for includes since this file is in a subfolder
include_once "../../encryption.php";
include_once "../../includes/connect.php";

$role = null;
$userId = null;

// Retrieve and decrypt user role and ID from cookies
if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    $role = $decrypted_role ? strtolower(trim($decrypted_role)) : null;
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security Check: Ensure user is logged in and is a teacher
if (!$role || $role !== 'teacher') {
    // Redirect to login or an error page if not authorized
    header("Location: ../../login.php");
    exit;
}

// PHP logic to handle form submission would go here
// For example: if ($_SERVER["REQUEST_METHOD"] == "POST") { ... }

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Teacher - Send Assignment</title>

    <!-- Paths are adjusted to point to the root assets folder -->
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">

        <?php
        // Adjust path for sidebar include
        include '../../includes/sidebar.php';
        ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <?php
                // Adjust path for header include
                include '../../includes/header.php';
                ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Send New Assignment</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Assignment Details</h6>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="assignmentTitle">Assignment Title</label>
                                    <input type="text" class="form-control" id="assignmentTitle" name="assignmentTitle" placeholder="e.g., Chapter 5: Algebra" required>
                                </div>
                                <div class="form-group">
                                    <label for="assignmentDesc">Description</label>
                                    <textarea class="form-control" id="assignmentDesc" name="assignmentDesc" rows="4" placeholder="Provide instructions, topics to cover, and submission guidelines."></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="dueDate">Due Date</label>
                                        <input type="date" class="form-control" id="dueDate" name="dueDate" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="assignTo">Assign to Class</label>
                                        <select id="assignTo" name="assignTo" class="form-control" required>
                                            <option value="" selected disabled>Choose...</option>
                                            <!-- You would populate this dynamically with PHP -->
                                            <option value="10A">Class 10 - A</option>
                                            <option value="10B">Class 10 - B</option>
                                            <option value="11S">Class 11 - Science</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="assignmentFile">Attach File (Optional)</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="assignmentFile" name="assignmentFile">
                                        <label class="custom-file-label" for="assignmentFile">Choose file...</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-icon-split">
                                    <span class="icon text-white-50"><i class="fas fa-paper-plane"></i></span>
                                    <span class="text">Send Assignment</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>

            <?php
            // Adjust path for footer include
            include '../../includes/footer.php';
            ?>
        </div>
    </div>

    <!-- Standard Modals and Scripts from your dashboard -->
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="../../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
    <script>
        // Script to display the name of the file chosen
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).siblings('.custom-file-label').addClass("selected").html(fileName);
        });
    </script>
</body>

</html>