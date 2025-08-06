<?php
include_once "./includes/connect.php";
include_once "encryption.php";

function haversine_distance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c * 1000;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    header('Content-Type: application/json');
    $response = [];

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $user_lat = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $user_lon = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ['status' => 'error', 'message' => 'Invalid email or password.'];
    } else {
        // --- FIXED: Converted to PDO ---
        $query = 'SELECT "id", "password", "role", "account_status" FROM "users" WHERE "email" = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['account_status'] === 'suspended') {
                $response = ['status' => 'error', 'message' => 'Your account has been suspended. Please contact the administrator.'];
            } else {
                // User is valid, set cookies and redirect
                $encrypted_id = encrypt_id($user['id']);
                $encrypted_role = encrypt_id($user['role']);
                setcookie("encrypted_user_id", $encrypted_id, time() + 86400, "/");
                setcookie("encrypted_user_role", $encrypted_role, time() + 86400, "/");
                
                // Additional logic for principal attendance can remain here if needed
                
                $response = ['status' => 'success', 'redirect' => 'index.php'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Invalid email or password.'];
        }
    }
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Notification History</title>

    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <link href="/BMC-SMS/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/sidebar.css" rel="stylesheet">
    <link href="/BMC-SMS/assets/css/scrollbar_hidden.css" rel="stylesheet">
    
    <style>
        .icon-circle {
            height: 2.5rem;
            width: 2.5rem;
            border-radius: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .filter-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .filter-inputs {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
    </style>
    </head>

<body id="page-top">
    <div id="wrapper">

        <?php include $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <?php include $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/header.php'; ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Notification History</h1>
                        <a href="/BMC-SMS/dashboard.php" class="btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Dashboard
                        </a>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="notification_history.php" method="GET">
                                <div class="filter-toolbar">
                                    <div class="filter-inputs">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <label class="input-group-text" for="filter_type">Type</label>
                                            </div>
                                            <select id="filter_type" name="filter_type" class="custom-select">
                                                <option value="">All</option>
                                                <?php foreach ($notification_types as $type): ?>
                                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($filter_type == $type) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $type))); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="input-group">
                                             <div class="input-group-prepend">
                                                <span class="input-group-text">Date</span>
                                            </div>
                                            <input type="date" id="start_date" name="start_date" class="form-control form-control-date" value="<?php echo htmlspecialchars($start_date); ?>" title="Start Date">
                                            <input type="date" id="end_date" name="end_date" class="form-control form-control-date" value="<?php echo htmlspecialchars($end_date); ?>" title="End Date">
                                        </div>
                                    </div>
                                    
                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-check"></i> Apply
                                        </button>
                                        <a href="notification_history.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-undo"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Notifications</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php if (empty($all_notifications)): ?>
                                    <div class="list-group-item text-center text-gray-500 py-4">
                                        No notifications found matching your criteria.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($all_notifications as $notification): ?>
                                        <?php 
                                            if (!defined('BASE_WEB_PATH')) {
                                                define('BASE_WEB_PATH', '/BMC-SMS/');
                                            }
                                            $link = htmlspecialchars(BASE_WEB_PATH . ltrim($notification['link'], '/')); 
                                        ?>
                                        <a href="<?php echo $link; ?>" class="list-group-item list-group-item-action d-flex align-items-center">
                                            <div class="mr-3">
                                                <div class="icon-circle bg-primary">
                                                    <i class="<?php echo getNotificationIcon($notification['type']); ?>"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="small text-gray-500">
                                                    <?php echo date('F j, Y, g:i a', strtotime($notification['created_at'])); ?>
                                                </div>
                                                <span class="font-weight-bold"><?php echo htmlspecialchars($notification['message']); ?></span>
                                            </div>
                                            <div class="ml-3">
                                                <span class="badge badge-light p-2"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $notification['type']))); ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include $_SERVER['DOCUMENT_ROOT'] . '/BMC-SMS/includes/footer.php'; ?>

        </div>
    </div>
    
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
       <?php include_once "../../includes/logout_modal.php"?>


    <script src="/BMC-SMS/assets/vendor/jquery/jquery.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/BMC-SMS/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="/BMC-SMS/assets/js/sb-admin-2.min.js"></script>
    </body>

</html>
<?php
$conn = null;
?>