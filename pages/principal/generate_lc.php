<?php
// Includes and session start
include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check for valid session and role
if (!isset($_COOKIE['encrypted_user_role']) || decrypt_id($_COOKIE['encrypted_user_role']) !== 'schooladmin') {
    header("Location: ../../login.php");
    exit;
}

if (!defined('BASE_WEB_PATH')) {
    define('BASE_WEB_PATH', '/BMC-SMS/');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Generate Leave Certificate - School Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Generate Leave Certificate</h1>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Enter Student Details</h6>
                        </div>
                        <div class="card-body">
                            <form action="process_lc.php" method="POST">
                                <div class="form-group">
                                    <label for="student_email">Student's Email Address</label>
                                    <input type="email" class="form-control" id="student_email" name="student_email" placeholder="Enter student's email" required>
                                </div>
                                <div class="form-group">
                                    <label for="leaving_date">Leaving Date</label>
                                    <input type="date" class="form-control" id="leaving_date" name="leaving_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="reason_for_leaving">Reason for Leaving</label>
                                    <textarea class="form-control" id="reason_for_leaving" name="reason_for_leaving" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Generate LC</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>
</html>