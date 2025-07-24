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
        } else {
            $message = '<div class="alert alert-danger">Error submitting application.</div>';
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for Leave</title>
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
     <!-- Corrected Font Awesome link -->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; // Include the dynamic sidebar ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/header.php'; // Include the header ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Apply for Leave</h1>
                    <?php echo $message; // Display success/error message ?>
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

            // 1. Set the minimum date for the 'From Date' to today
            // Gets today's date in YYYY-MM-DD format
            const today = new Date().toISOString().split('T')[0];
            fromDateInput.setAttribute('min', today);
            toDateInput.setAttribute('min', today); // Also set 'To Date' min initially

            // 2. Add an event listener to the 'From Date' input
            // This will update the 'To Date' whenever the 'From Date' changes
            fromDateInput.addEventListener('change', function() {
                const selectedFromDate = this.value;

                // Set the minimum date for the 'To Date' to be the selected 'From Date'
                toDateInput.setAttribute('min', selectedFromDate);

                // If the current 'To Date' is now before the new minimum 'From Date', clear it
                if (toDateInput.value < selectedFromDate) {
                    toDateInput.value = '';
                }
            });
        });
    </script>

</body>
</html>