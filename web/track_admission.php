<?php
require_once '../includes/connect.php';
$result = null;
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['admission_id'])) {
    $admission_id = trim($_POST['admission_id']);

    try {
        $stmt = $conn->prepare("SELECT * FROM admission_applications WHERE admission_id = ?");
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
                                        <div class="form-group"><label for="admission_id">Admission ID</label><input type="text" class="form-control" name="admission_id" maxlength="10" required></div>
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
                                        <p><strong>Current Status:</strong> <span class="badge badge-info"><?php echo htmlspecialchars($result['status']); ?></span></p>
                                        <hr>
                                        <h6 class="text-primary">Next Steps</h6>
                                        <?php if ($result['interview_datetime']): ?>
                                            <p><strong>Face-to-Face Session:</strong> Your session is scheduled for <strong><?php echo date('F j, Y \a\t g:i A', strtotime($result['interview_datetime'])); ?></strong>. Please arrive at the school office 15 minutes early.</p>
                                        <?php else: ?>
                                            <p>Your application is currently under review. Please check back later for an update on your face-to-face session.</p>
                                        <?php endif; ?>
                                        <?php if ($result['required_documents']): ?>
                                            <p><strong>Documents to Bring:</strong></p>
                                            <div><?php echo nl2br(htmlspecialchars($result['required_documents'])); ?></div>
                                        <?php endif; ?>
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