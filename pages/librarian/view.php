<?php
/*
 * Filename: view.php
 * Description: Displays a detailed profile page for a specific librarian.
 */

// --- Includes & Setup ---
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
$current_user_id = isset($_COOKIE['encrypted_user_id']) ? decrypt_id($_COOKIE['encrypted_user_id']) : null;

// Only principal and hr can view a librarian's profile
if ($role !== 'principal' && $role !== 'hr') {
    header("Location: ../../login.php");
    exit;
}

// --- Input Validation ---
$librarian_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($librarian_id <= 0) {
    header("Location: librarian_list.php?error=Invalid_ID");
    exit;
}

// --- Data Fetching ---
$librarian = null;
$timings = [];

try {
    // Check if the user is authorized to view this librarian
    $query_access = "SELECT school_id FROM librarian WHERE id = ?";
    $stmt_access = $conn->prepare($query_access);
    $stmt_access->execute([$librarian_id]);
    $target_school_id = $stmt_access->fetchColumn();

    $user_school_id = null;
    if ($role === 'principal') {
        $stmt_user_school = $conn->prepare("SELECT school_id FROM principal WHERE id = ?");
        $stmt_user_school->execute([$current_user_id]);
        $user_school_id = $stmt_user_school->fetchColumn();
    } elseif ($role === 'hr') {
        $stmt_user_school = $conn->prepare("SELECT school_id FROM hr WHERE id = ?");
        $stmt_user_school->execute([$current_user_id]);
        $user_school_id = $stmt_user_school->fetchColumn();
    }
    
    if ($target_school_id != $user_school_id) {
        $redirect_url = ($role === 'hr') ? '../hr/librarian_list.php' : 'librarian_list.php';
        header("Location: " . $redirect_url . "?error=Unauthorized access to this profile.");
        exit;
    }

    // Fetch main librarian details including transportation fields by joining tables
    $query_librarian = 'SELECT l.*, s.school_name, s.email AS school_email, s.phone AS school_phone,
                        st.stop_name, r.route_name, v.vehicle_number as school_vehicle_number
                        FROM "librarian" l
                        LEFT JOIN "school" s ON l.school_id = s.id
                        LEFT JOIN "stops" st ON l.stop_id = st.id
                        LEFT JOIN "routes" r ON st.route_id = r.id
                        LEFT JOIN "vehicles" v ON r.vehicle_id = v.id
                        WHERE l.id = ?';
    $stmt_librarian = $conn->prepare($query_librarian);
    $stmt_librarian->execute([$librarian_id]);
    $librarian = $stmt_librarian->fetch(PDO::FETCH_ASSOC);

    if (!$librarian) {
        header("Location: librarian_list.php?error=Librarian_not_found");
        exit;
    }

    // Fetch librarian's weekly schedule.
    $stmt_timings = $conn->prepare('SELECT * FROM "librarian_timings" WHERE "librarian_id" = ?');
    $stmt_timings->execute([$librarian_id]);
    foreach ($stmt_timings->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $timings[$row['day_of_week']] = $row;
    }

    // Determine the correct photo path, using a default if none exists.
    $photo_path = !empty($librarian['librarian_image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $librarian['librarian_image'])
        ? $librarian['librarian_image']
        : '/BMC-SMS/assets/images/unisex.png';
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Librarian - <?php echo htmlspecialchars($librarian['librarian_name']); ?></title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">
    <style>
        .profile-photo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #e3e6f0;
        }

        .info-label {
            font-weight: 600;
            color: #5a5c69;
        }

        .info-value {
            color: #858796;
        }

        .card .table-timings th {
            width: 120px;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Librarian's Profile</h1>
                        <div>
                            <a href="librarian_list.php" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                            <a href="edit.php?id=<?php echo $librarian['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit fa-sm"></i> Edit Profile</a>
                        </div>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">Librarian details updated successfully!<button type="button" class="close" data-dismiss="alert">&times;</button></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xl-4 col-lg-5 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center">
                                    <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="<?php echo htmlspecialchars($librarian['librarian_name']); ?>" class="profile-photo mb-3">
                                    <h4 class="font-weight-bold text-gray-800 mb-1"><?php echo htmlspecialchars($librarian['librarian_name']); ?></h4>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($librarian['email']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-8 col-lg-7 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i> Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Phone:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['phone']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Date of Birth:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars(date("d M Y", strtotime($librarian['dob']))); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Gender:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['gender']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Blood Group:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['blood_group']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 info-label">Address:</div>
                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['address']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-briefcase"></i> Professional Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-5 info-label">School Name:</div>
                                        <div class="col-sm-7 info-value"><?php echo htmlspecialchars($librarian['school_name']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-5 info-label">Qualification:</div>
                                        <div class="col-sm-7 info-value"><?php echo htmlspecialchars($librarian['qualification']); ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-5 info-label">Date of Joining:</div>
                                        <div class="col-sm-7 info-value"><?php echo !empty($librarian['date_of_joining']) ? htmlspecialchars(date("d F Y", strtotime($librarian['date_of_joining']))) : "N/A"; ?></div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-5 info-label">Salary:</div>
                                        <div class="col-sm-7 info-value font-weight-bold text-success">₹<?php echo number_format($librarian['salary'], 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-clock"></i> Batch & Timings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-sm-4 info-label">Assigned Batch:</div>
                                        <div class="col-sm-8 info-value"><span class="badge badge-info p-2"><?php echo htmlspecialchars($librarian['batch'] ?? 'N/A'); ?></span></div>
                                    </div>
                                    <hr class="mt-0">
                                    <h6 class="info-label mb-2">Weekly Schedule:</h6>
                                    <table class="table table-sm table-bordered table-striped table-timings">
                                        <tbody>
                                            <?php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                            foreach ($days as $day): $day_timing = $timings[$day] ?? null; ?>
                                                <tr>
                                                    <th><?php echo $day; ?></th>
                                                    <td><?php if ($day_timing && !empty($day_timing['is_closed'])): ?><span class="badge badge-danger">Closed</span><?php elseif ($day_timing && !empty($day_timing['opens_at'])): ?><?php echo date("g:i A", strtotime($day_timing['opens_at'])); ?> - <?php echo date("g:i A", strtotime($day_timing['closes_at'])); ?><?php else: ?><span class="text-muted">Not Set</span><?php endif; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bus"></i> Transportation Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="row">
                                                <div class="col-sm-6 info-label">Mode:</div>
                                                <div class="col-sm-6 info-value"><?php echo htmlspecialchars($librarian['transport_mode'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                        <?php if ($librarian['transport_mode'] === 'School Transport'): ?>
                                            <div class="col-md-8">
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="row">
                                                            <div class="col-sm-5 info-label">Route:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars($librarian['route_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="row">
                                                            <div class="col-sm-5 info-label">Stop:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars($librarian['stop_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="row">
                                                            <div class="col-sm-5 info-label">Vehicle:</div>
                                                            <div class="col-sm-7 info-value"><?php echo htmlspecialchars($librarian['school_vehicle_number'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php elseif ($librarian['transport_mode'] === 'Self Transport'): ?>
                                            <div class="col-md-8">
                                                <div class="row">
                                                    <div class="col-sm-4 info-label">Self-Transport:</div>
                                                    <div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['self_transport_mode'] ?? 'N/A'); ?></div>
                                                </div>
                                                <?php if (in_array($librarian['self_transport_mode'], ['Bike', 'Car'])): ?>
                                                    <hr class="w-100">
                                                    <div class="row">
                                                        <div class="col-sm-4 info-label">Vehicle No:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['vehicle_number'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <hr class="w-100">
                                                    <div class="row">
                                                        <div class="col-sm-4 info-label">License No:</div>
                                                        <div class="col-sm-8 info-value"><?php echo htmlspecialchars($librarian['license_number'] ?? 'N/A'); ?></div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <?php include_once "../../includes/logout_modal.php" ?>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>
</body>

</html>
<?php $conn = null; ?>