<?php
// A single, unified message file for both teachers and students.
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$current_user_id = null;
$current_user_role = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $current_user_role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

// Security check to ensure a valid user is logged in
if ($current_user_role !== 'teacher' && $current_user_role !== 'student') {
    header("Location: ../../login.php");
    exit();
}

// Dynamically set page titles and content based on the user's role
$page_title = ($current_user_role === 'teacher') ? "Message Students" : "Message Teachers";
$contacts_title = ($current_user_role === 'teacher') ? "Students" : "Teachers";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Dashboard</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <link rel="stylesheet" href="../../assets/css/message.css?v=1.3">
    </head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800"><?php echo htmlspecialchars($page_title); ?></h1>
                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($contacts_title); ?></h6>
                                </div>
                                <div class="card-body">
                                    <div id="contacts-list-container" style="max-height: 60vh; overflow-y: auto;">
                                        <ul class="list-group" id="contacts-list">
                                            <div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary" id="chat-with-name">Select a contact to start chatting</h6></div>
                                <div class="card-body message-display" id="message-area">
                                     <div class="text-center h-100 d-flex flex-column justify-content-center align-items-center">
                                        <i class="fas fa-comments fa-4x text-gray-300"></i>
                                        <p class="mt-3 text-gray-500">Your messages will appear here.</p>
                                    </div>
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="input-group">
                                        <input type="text" id="message-text" class="form-control" placeholder="Type a message..." disabled>
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" id="send-button" type="button" disabled><i class="fas fa-paper-plane"></i> Send</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"; ?>
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <script>
        // Pass PHP variables to JavaScript so the script knows who is logged in
        window.currentUserId = '<?php echo $current_user_id; ?>';
        window.currentUserRole = '<?php echo $current_user_role; ?>';
        window.base_url = '/BMC-SMS/';
    </script>
    <script src="/BMC-SMS/assets/js/message.js"></script>
</body>
</html>