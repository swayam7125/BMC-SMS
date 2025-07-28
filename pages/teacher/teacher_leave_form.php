<?php
// This logic should be at the very top of your file.
include_once '../../includes/connect.php';
include_once '../../encryption.php';

// Initialize variables
$teacher_id = null;
$teacher_name = 'N/A';
$teacher_email = 'N/A';
$message = '';

// Get Teacher details from cookies
if (isset($_COOKIE['encrypted_user_id'])) {
    $teacher_id = decrypt_id($_COOKIE['encrypted_user_id']);

    // Fetch name and email from the teacher table
    $stmt = $conn->prepare("SELECT teacher_name, email FROM teacher WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $teacher_name = $user['teacher_name'];
        $teacher_email = $user['email'];
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $teacher_id) {
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];
    $reason = $_POST['reason'];

    if (empty($from_date) || empty($to_date) || empty($reason)) {
        $message = '<div class="alert alert-danger">All fields are required.</div>';
    } else {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO leave_applications (teacher_id, from_date, to_date, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $teacher_id, $from_date, $to_date, $reason);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Leave application submitted successfully!</div>';

            // --- START: Notification Logic ---
            // Find the principal of the teacher's school to notify them.
            $stmt_school = $conn->prepare("SELECT school_id FROM teacher WHERE id = ?");
            $stmt_school->bind_param("i", $teacher_id);
            $stmt_school->execute();
            $teacher_data = $stmt_school->get_result()->fetch_assoc();
            $stmt_school->close();

            if ($teacher_data) {
                $school_id = $teacher_data['school_id'];
                
                // Find the principal for this school
                $stmt_principal = $conn->prepare("SELECT id FROM principal WHERE school_id = ?");
                $stmt_principal->bind_param("i", $school_id);
                $stmt_principal->execute();
                $principal_data = $stmt_principal->get_result()->fetch_assoc();
                $stmt_principal->close();

                if ($principal_data) {
                    $principal_user_id = $principal_data['id'];
                    $leave_message = "New leave request from " . htmlspecialchars($teacher_name);
                    $link = "/pages/principal/principal_leave_requests.php";
                    $type = 'leave_request';

                    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
                    $stmt_notify->bind_param("isss", $principal_user_id, $leave_message, $link, $type);
                    $stmt_notify->execute();
                    $stmt_notify->close();
                }
            }
            // --- END: Notification Logic ---
        } else {
            $message = '<div class="alert alert-danger">Error submitting application.</div>';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Apply for Leave</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar_hidden.css">

</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; // Include the dynamic sidebar 
        ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; // Include the header 
                ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Apply for Leave</h1>
                    <?php echo $message; // Display success/error message 
                    ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Leave Application Form</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($teacher_name); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($teacher_email); ?>" readonly>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="from_date">From Date</label>
                                            <input type="date" class="form-control" id="from_date" name="from_date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="to_date">To Date</label>
                                            <input type="date" class="form-control" id="to_date" name="to_date" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="reason">Reason for Leave</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Application</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fromDateInput = document.getElementById('from_date');
            const toDateInput = document.getElementById('to_date');

            const today = new Date().toISOString().split('T')[0];
            fromDateInput.setAttribute('min', today);
            toDateInput.setAttribute('min', today);

            fromDateInput.addEventListener('change', function() {
                const selectedFromDate = this.value;
                toDateInput.setAttribute('min', selectedFromDate);
                if (toDateInput.value < selectedFromDate) {
                    toDateInput.value = '';
                }
            });
        });
    </script>

</body>

</html>
<?php
$conn->close();
?>