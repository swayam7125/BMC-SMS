<?php
include_once '../../includes/connect.php';
include_once '../../encryption.php';

if (!isset($_GET['id']) || !isset($conn)) {
    http_response_code(400);
    echo "Missing application ID or database connection.";
    exit;
}

$application_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

try {
    $stmt = $conn->prepare("SELECT * FROM admission_applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        echo '<p class="text-center text-danger">Application not found.</p>';
        exit;
    }

    // Outputting HTML for the modal body
    ?>
    <span data-current-status="<?php echo htmlspecialchars($app['status']); ?>" style="display:none;"></span>
    <span data-current-roll="<?php echo htmlspecialchars($app['roll_no'] ?? ''); ?>" style="display:none;"></span>
    <span data-current-class="<?php echo htmlspecialchars($app['class'] ?? ''); ?>" style="display:none;"></span>
    <span data-current-comment="<?php echo htmlspecialchars($app['remarks'] ?? ''); ?>" style="display:none;"></span>
    <span data-student-name="<?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>" style="display:none;"></span>
    <span data-meeting-date="<?php echo htmlspecialchars($app['meeting_date'] ?? ''); ?>" style="display:none;"></span>
    <span data-meeting-time="<?php echo htmlspecialchars($app['meeting_time'] ?? ''); ?>" style="display:none;"></span>


    <div class="row">
        <div class="col-md-6 mb-3"><strong>Admission ID:</strong> <?php echo htmlspecialchars($app['admission_id']); ?></div>
        <div class="col-md-6 mb-3"><strong>Status:</strong> 
            <span class="badge badge-<?php 
                switch ($app['status']) {
                    case 'Accepted': echo 'success'; break;
                    case 'Rejected': echo 'danger'; break;
                    default: echo 'warning';
                }
            ?>"><?php echo htmlspecialchars($app['status']); ?></span>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3"><strong>Student Name:</strong> <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['middle_name'] . ' ' . $app['last_name']); ?></div>
        <div class="col-md-6 mb-3"><strong>Applied For Grade:</strong> <?php echo htmlspecialchars($app['grade_applying_for'] ?? 'N/A'); ?></div> 
    </div>
    <div class="row">
        <div class="col-md-6 mb-3"><strong>Phone:</strong> <?php echo htmlspecialchars($app['phone']); ?></div>
        <div class="col-md-6 mb-3"><strong>Email:</strong> <?php echo htmlspecialchars($app['email']); ?></div>
    </div>
    
    <?php if ($app['status'] === 'Accepted'): ?>
    <hr>
    <h6 class="mt-3 text-success">Enrollment Details</h6>
    <div class="row">
        <div class="col-md-6 mb-3"><strong>Assigned Roll No:</strong> <?php echo htmlspecialchars($app['roll_no'] ?? 'N/A'); ?></div>
        <div class="col-md-6 mb-3"><strong>Assigned Class:</strong> <?php echo htmlspecialchars($app['class'] ?? 'N/A'); ?></div>
    </div>
    
        <?php if ($app['meeting_date']): ?>
        <h6 class="mt-3 text-info">Scheduled Orientation/Sign-up Meeting</h6>
        <div class="row">
            <div class="col-md-6 mb-3"><strong>Date:</strong> <?php echo htmlspecialchars($app['meeting_date']); ?></div>
            <div class="col-md-6 mb-3"><strong>Time:</strong> <?php echo htmlspecialchars(date('h:i A', strtotime($app['meeting_time']))); ?></div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning mt-3">Enrollment details complete, but no meeting was scheduled by HR.</div>
        <?php endif; ?>
        
    <?php endif; ?>

    <h6 class="mt-3">Previous School Details</h6>
    <div class="row">
        <div class="col-md-6 mb-3"><strong>School:</strong> <?php echo htmlspecialchars($app['previous_school'] ?? 'N/A'); ?></div>
        <div class="col-md-6 mb-3"><strong>Grade/Year:</strong> <?php echo htmlspecialchars($app['previous_grade'] ?? 'N/A') . ' (' . htmlspecialchars($app['previous_year'] ?? 'N/A') . ')'; ?></div>
    </div>

    <h6 class="mt-3">Attached Documents</h6>
    <div class="row">
        <div class="col-md-6 mb-3">
            <strong>Marksheet:</strong> 
            <?php if (!empty($app['marksheet_path'])): ?>
                <a href="../../<?php echo htmlspecialchars($app['marksheet_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary ml-2"><i class="fas fa-file-pdf"></i> View</a>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </div>
        <div class="col-md-6 mb-3">
            <strong>Aadhaar:</strong> 
            <?php if (!empty($app['aadhaar_path'])): ?>
                <a href="../../<?php echo htmlspecialchars($app['aadhaar_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary ml-2"><i class="fas fa-file-pdf"></i> View</a>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </div>
    </div>
    
    <h6 class="mt-3">Previous Remarks</h6>
    <p><?php echo !empty($app['remarks']) ? nl2br(htmlspecialchars($app['remarks'])) : 'No remarks yet.'; ?></p>
    <?php
    
} catch (PDOException $e) {
    http_response_code(500);
    echo '<p class="text-center text-danger">Error fetching details: ' . $e->getMessage() . '</p>';
}
?>