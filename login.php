<?php
include_once "./includes/connect.php"; // Ensure this path is correct
include_once "encryption.php"; // Ensure this path is correct

// FUNCTION: Calculate distance between two GPS coordinates using the Haversine formula.
function haversine_distance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // Earth radius in kilometers
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c * 1000; // Return distance in meters
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Get latitude and longitude from the hidden form fields.
    $user_lat = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $user_lon = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email or password";
    } else {
        $query = "SELECT id, password, role, account_status FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);

                if (password_verify($password, $user['password'])) {
                    if ($user['account_status'] === 'suspended') {
                        $error_message = "Your account has been suspended. Please contact the administrator.";
                    } else {
                        
                        // --- START: GEOLOCATION ATTENDANCE LOGIC FOR PRINCIPAL ---
                        if ($user['role'] === 'schooladmin') {
                            $principal_id = $user['id'];
                            $attendance_status = 'Absent'; // Default status is 'Absent'

                            // 1. Get the principal's school_id
                            $school_id_query = mysqli_prepare($conn, "SELECT school_id FROM principal WHERE id = ?");
                            mysqli_stmt_bind_param($school_id_query, "i", $principal_id);
                            mysqli_stmt_execute($school_id_query);
                            $school_id_result = mysqli_stmt_get_result($school_id_query);
                            
                            if($school_id_result && mysqli_num_rows($school_id_result) > 0) {
                                $principal_details = mysqli_fetch_assoc($school_id_result);
                                $school_id = $principal_details['school_id'];

                                // 2. Get the school's official coordinates
                                $school_loc_query = mysqli_prepare($conn, "SELECT latitude, longitude FROM school WHERE id = ?");
                                mysqli_stmt_bind_param($school_loc_query, "i", $school_id);
                                mysqli_stmt_execute($school_loc_query);
                                $school_loc_result = mysqli_stmt_get_result($school_loc_query);
                                $school_location = mysqli_fetch_assoc($school_loc_result);

                                // 3. If we have all coordinates, check the distance
                                if ($user_lat && $user_lon && !empty($school_location['latitude']) && !empty($school_location['longitude'])) {
                                    $distance = haversine_distance($user_lat, $user_lon, $school_location['latitude'], $school_location['longitude']);
                                    $tolerance_radius = 300; // Allow a 300-meter radius for accuracy

                                    if ($distance <= $tolerance_radius) {
                                        $attendance_status = 'Present';
                                    }
                                }

                                // 4. Insert or update the attendance record
                                $current_date = date("Y-m-d");

                                // The $current_time variable is removed. We now use the database's clock.
                                $att_stmt = mysqli_prepare($conn,
                                    "INSERT INTO principal_attendance (principal_id, school_id, attendance_date, status, login_latitude, login_longitude, login_time)
                                    VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIME())
                                    ON DUPLICATE KEY UPDATE status = VALUES(status), login_latitude = VALUES(login_latitude), login_longitude = VALUES(login_longitude), login_time = CURRENT_TIME()");

                                // The type string "s" for time and the $current_time variable are removed from this line.
                                mysqli_stmt_bind_param($att_stmt, "iisssd", $principal_id, $school_id, $current_date, $attendance_status, $user_lat, $user_lon);
                                mysqli_stmt_execute($att_stmt);
                            }
                        }
                        // --- END OF ATTENDANCE LOGIC ---

                        // Continue with existing login process
                        $encrypted_id = encrypt_id($user['id']);
                        $encrypted_role = encrypt_id($user['role']);
                        $profile_image = '';
                        $user_name = '';

                        switch ($user['role']) {
                            case 'student':
                                $detail_query = "SELECT student_image, student_name FROM student WHERE id = ?";
                                break;
                            case 'teacher':
                                $detail_query = "SELECT teacher_image, teacher_name FROM teacher WHERE id = ?";
                                break;
                            case 'schooladmin':
                                $detail_query = "SELECT principal_image, principal_name FROM principal WHERE id = ?";
                                break;
                            default:
                                $profile_image = '/BMC-SMS/assets/images/undraw_profile.svg';
                                $user_name = $email;
                                break;
                        }

                        if (isset($detail_query)) {
                            $detail_stmt = mysqli_prepare($conn, $detail_query);
                            if ($detail_stmt) {
                                mysqli_stmt_bind_param($detail_stmt, "i", $user['id']);
                                mysqli_stmt_execute($detail_stmt);
                                $detail_result = mysqli_stmt_get_result($detail_stmt);
                                if ($detail_result && mysqli_num_rows($detail_result) > 0) {
                                    $detail_row = mysqli_fetch_assoc($detail_result);
                                    if ($user['role'] == 'student') {
                                        $profile_image = !empty($detail_row['student_image']) ? 'pages/student/uploads/' . basename($detail_row['student_image']) : '/BMC-SMS/assets/images/undraw_profile.svg';
                                        $user_name = $detail_row['student_name'];
                                    } elseif ($user['role'] == 'teacher') {
                                        $profile_image = !empty($detail_row['teacher_image']) ? 'pages/teacher/uploads/' . basename($detail_row['teacher_image']) : '/BMC-SMS/assets/images/undraw_profile.svg';
                                        $user_name = $detail_row['teacher_name'];
                                    } elseif ($user['role'] == 'schooladmin') {
                                        $profile_image = !empty($detail_row['principal_image']) ? 'pages/principal/uploads/' . basename($detail_row['principal_image']) : '/BMC-SMS/assets/images/undraw_profile.svg';
                                        $user_name = $detail_row['principal_name'];
                                    }
                                }
                                mysqli_stmt_close($detail_stmt);
                            }
                        }

                        $encrypted_profile_image = encrypt_id($profile_image);
                        $encrypted_user_name = encrypt_id($user_name);

                        setcookie("encrypted_user_id", $encrypted_id, time() + 86400, "/");
                        setcookie("encrypted_user_role", $encrypted_role, time() + 86400, "/");
                        setcookie("encrypted_profile_image", $encrypted_profile_image, time() + 86400, "/");
                        setcookie("encrypted_user_name", $encrypted_user_name, time() + 86400, "/");

                        header("Location: index.php");
                        exit();
                    }
                } else {
                    $error_message = "Invalid email or password";
                }
            } else {
                $error_message = "Invalid email or password";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "System error. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>BMC-SMS -- Login</title>
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">Welcome</h1>
                                </div>
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                                <?php endif; ?>
                                <form class="user" method="POST">
                                    <div class="form-group">
                                        <input type="email" class="form-control form-control-user" name="email" placeholder="Enter Email Address..." required>
                                    </div>
                                    <div class="form-group">
                                        <input type="password" class="form-control form-control-user" name="password" placeholder="Password" required>
                                    </div>
                                    <input type="hidden" name="latitude" id="latitude">
                                    <input type="hidden" name="longitude" id="longitude">
                                    <button type="submit" class="btn btn-primary btn-user btn-block">Login</button>
                                    <hr>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/js/sb-admin-2.min.js"></script>

    <script>
    window.addEventListener('load', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                },
                function(error) {
                    console.error("Geolocation error: " + error.message);
                }
            );
        } else {
            console.error("Geolocation is not supported by this browser.");
        }
    });
    </script>
</body>
</html>
