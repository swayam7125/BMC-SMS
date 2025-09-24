<?php
// /pages/user/message.php (UPDATED AND FINAL CODE)

include_once "../../includes/connect.php";
include_once "../../encryption.php";
include_once '../../includes/ajax_helpers.php';

$current_user_id = null;
$current_user_role = null;

if (isset($_COOKIE['encrypted_user_role'])) {
    $current_user_role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $current_user_id = decrypt_id($_COOKIE['encrypted_user_id']);
}

if ($current_user_role !== 'teacher' && $current_user_role !== 'student') {
    header("Location: ../../login.php");
    exit();
}

$page_title = ($current_user_role === 'teacher') ? "Message Students" : "Message Teachers";
$contacts_title = ($current_user_role === 'teacher') ? "Students" : "Teachers";

$standards = [];
$teacher_school_id = null;

if ($current_user_role === 'teacher') {
    try {
        $sql = "SELECT school_id, std FROM teacher WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $teacher_school_id = $row['school_id'];
            if ($row['std']) {
                preg_match_all('/(\d+)/', $row['std'], $matches);
                if (!empty($matches[1])) {
                    $standards = $matches[1];
                }
            }
        }
    } catch (PDOException $e) {
        // Handle error
    }
}

// This part will ONLY run on a normal page load, NOT during an AJAX call.
if (!is_ajax_request()):
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
    <link rel="stylesheet" href="../../assets/css/message.css?v=1.4">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; ?>
<?php
endif; // End of the header/template section wrapper.

// This is the main content. It will run for BOTH normal loads and AJAX requests.
?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800"><?php echo htmlspecialchars($page_title); ?></h1>
                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow h-100 d-flex flex-column">
                                <div class="card-header py-3 d-flex align-items-center">
                                    <?php if ($current_user_role === 'teacher'): ?>
                                        <ul class="nav nav-pills" id="standard-tabs" role="tablist">
                                            <?php foreach (array_unique($standards) as $index => $standard): ?>
                                                <li class="nav-item mr-2" role="presentation">
                                                    <a class="nav-link btn btn-outline-primary <?php echo $index === 0 ? 'active' : ''; ?>"
                                                       id="standard-<?php echo htmlspecialchars($standard); ?>-tab"
                                                       data-toggle="tab"
                                                       role="tab"
                                                       data-standard-id="<?php echo htmlspecialchars($standard); ?>">
                                                        Std <?php echo htmlspecialchars($standard); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <div id="contacts-list-container" style="max-height: 60vh; overflow-y: auto;">
                                        <ul class="list-group" id="contacts-list">
                                            <div class="text-center p-4">
                                                <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
                                            </div>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow h-100 d-flex flex-column">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary" id="chat-with-name">Select a contact to start chatting</h6>
                                </div>
                                <div class="card-body message-display" id="message-area">
                                    <div class="text-center h-100 d-flex flex-column justify-content-center align-items-center">
                                        <i class="fas fa-comments fa-4x text-gray-300"></i>
                                        <p class="mt-3 text-gray-500">Your messages will appear here.</p>
                                    </div>
                                </div>
                                <div class="card-footer bg-white">
                                    <div id="file-preview-container" class="mb-2" style="display: none;">
                                        <div class="d-flex align-items-center p-2 border rounded">
                                            <i class="fas fa-file-alt fa-2x text-gray-500 mr-2"></i>
                                            <span id="file-preview-name" class="text-truncate"></span>
                                            <button id="cancel-file-button" type="button" class="close ml-auto" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    </div>
                                    <form id="message-form">
                                        <input type="file" id="file-input" style="display: none;" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <button class="btn btn-light" id="attach-file-button" type="button" disabled>
                                                    <i class="fas fa-paper-clip"></i>
                                                </button>
                                            </div>
                                            <input type="text" id="message-text" class="form-control" placeholder="Type a message..." disabled>
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" id="send-button" type="submit" disabled><i class="fas fa-paper-plane"></i> Send</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
<?php
// This part will also ONLY run on a normal page load.
if (!is_ajax_request()):
?>
            </div>
            <?php include_once '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php"; ?>
    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    <script>
        window.currentUserId = '<?php echo $current_user_id; ?>';
        window.currentUserRole = '<?php echo $current_user_role; ?>';
        window.base_url = '/BMC-SMS/';
        window.teacherStandards = <?php echo json_encode($standards); ?>;
    </script>
    <script src="/BMC-SMS/assets/js/message.js?v=1.1"></script>
</body>
</html>
<?php
endif;