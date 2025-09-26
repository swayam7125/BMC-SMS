<?php
require_once '../includes/connect.php';
$result = null;
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['admission_id'])) {
    $admission_id = trim($_POST['admission_id']);

    try {
        // Fetch all required columns, including the meeting schedule fields
        $stmt = $conn->prepare("SELECT *, first_name, last_name, status, meeting_date, meeting_time, remarks 
                                FROM admission_applications 
                                WHERE admission_id = ?");
        $stmt->execute([$admission_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            $error = "No application found with this ID. Please check the ID and try again.";
        }
    } catch (PDOException $e) {
        $error = "A database error occurred. Please try again later.";
        error_log("Track admission error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Track Admission Status | BMC School</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="css/style.min.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <main>
        <div class="content-wrapper">
            <div class="container">
                <section class="track-admission-section py-5">
                    <div class="content-header">
                        <h2>Track Your Application Status</h2>
                        <h6 class="section-subtitle text-muted">Enter your 10-character Admission ID to see the latest updates.</h6>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <div class="card shadow-sm">
                                <div class="card-body p-4">
                                    <form method="POST" action="track_admission.php">
                                        <div class="form-group"><label for="admission_id">Admission ID</label><input type="text" class="form-control" name="admission_id" maxlength="10" value="<?php echo htmlspecialchars($_POST['admission_id'] ?? ''); ?>" required></div>
                                        <button type="submit" class="btn btn-primary">Track Status</button>
                                    </form>
                                </div>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger mt-4"><?php echo $error; ?></div>
                            <?php endif; ?>

                            <?php if ($result): ?>
                                <div class="card shadow-sm mt-4">
                                    <div class="card-header">
                                        <h5 class="m-0">Application Status for ID: <?php echo htmlspecialchars($result['admission_id']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Applicant Name:</strong> <?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></p>
                                        <p><strong>Current Status:</strong> <span class="badge badge-<?php 
                                            // Set badge color based on status
                                            if ($result['status'] == 'Accepted') { echo 'success'; } 
                                            else if ($result['status'] == 'Rejected') { echo 'danger'; } 
                                            else { echo 'info'; } 
                                        ?>"><?php echo htmlspecialchars($result['status']); ?></span></p>
                                        <hr>
                                        <h6 class="text-primary">Next Steps</h6>
                                        
                                        <?php 
                                        // ⭐ CHECK STATUS AND MEETING DETAILS
                                        $meeting_date = $result['meeting_date'] ?? null;
                                        $meeting_time = $result['meeting_time'] ?? null;
                                        
                                        if ($result['status'] == 'Accepted' && $meeting_date && $meeting_time): 
                                        ?>
                                            <p>Congratulations! Your application has been **Accepted**. </p>
                                            <p>Your **Orientation/Sign-up Session** is scheduled for: 
                                                <br><strong>Date:</strong> <?php echo date('F j, Y', strtotime($meeting_date)); ?>
                                                <br><strong>Time:</strong> <?php echo date('g:i A', strtotime($meeting_time)); ?>
                                            </p>
                                            <div class="alert alert-success mt-3">Please arrive at the school office 15 minutes early and bring the required documents listed below.</div>

                                        <?php elseif ($result['status'] == 'Accepted'): ?>
                                            <p>Congratulations! Your application has been **Accepted**. </p>
                                            <p>Please contact the school office by phone or email to schedule your final sign-up session.</p>
                                            
                                        <?php elseif ($result['status'] == 'Rejected'): ?>
                                            <p class="text-danger">We regret to inform you that your application was **Rejected** at this time.</p>
                                            <?php if ($result['remarks']): ?>
                                                <p><strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($result['remarks'])); ?></p>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <p>Your application is currently **<?php echo htmlspecialchars($result['status']); ?>**. Please check back later for an update on your next steps.</p>
                                            <?php if ($result['remarks']): ?>
                                                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($result['remarks'])); ?></p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        // This section is commented out as the documents field is not standard and will cause an error
                                        /* if ($result['required_documents']): 
                                            <p><strong>Documents to Bring:</strong></p>
                                            <div><?php echo nl2br(htmlspecialchars($result['required_documents'])); ?></div>
                                        endif; 
                                        */
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="vendors/jquery/jquery.min.js"></script>
    <script src="vendors/bootstrap/bootstrap.min.js"></script>
</body>

</html>