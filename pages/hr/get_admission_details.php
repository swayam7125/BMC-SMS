<?php
require_once '../../includes/connect.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo '<p class="text-danger">Invalid ID.</p>';
    exit;
}
$application_id = (int)$_GET['id'];

try {
    $stmt = $conn->prepare("SELECT * FROM admission_applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        echo '<p class="text-danger">Application not found.</p>';
        exit;
    }
} catch (PDOException $e) {
    echo '<p class="text-danger">Database error.</p>';
    exit;
}

// --- Display the details and the action form ---
?>
<h5>Applicant: <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h5>
<p><strong>Admission ID:</strong> <?php echo htmlspecialchars($app['admission_id']); ?></p>
<hr>

<h6>Student Details</h6>
<ul>
    <li><strong>Full Name:</strong> <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['middle_name'] . ' ' . $app['last_name']); ?></li>
    <li><strong>Date of Birth:</strong> <?php echo date('F j, Y', strtotime($app['student_dob'])); ?></li>
</ul>

<h6>Academic History</h6>
<ul>
    <li><strong>Previous School:</strong> <?php echo htmlspecialchars($app['previous_school'] ?? 'N/A'); ?></li>
    <li><strong>Previous Grade:</strong> <?php echo htmlspecialchars($app['previous_grade'] ?? 'N/A'); ?></li>
    <li><strong>Year:</strong> <?php echo htmlspecialchars($app['previous_year'] ?? 'N/A'); ?></li>
</ul>

<h6>Parent/Guardian Details</h6>
<ul>
    <li><strong>Name:</strong> <?php echo htmlspecialchars($app['parent_name']); ?></li>
    <li><strong>Email:</strong> <?php echo htmlspecialchars($app['parent_email']); ?></li>
    <li><strong>Phone:</strong> <?php echo htmlspecialchars($app['parent_phone']); ?></li>
</ul>

<h6>Uploaded Documents</h6>
<ul>
    <li><strong>Marksheet:</strong>
        <?php if ($app['marksheet_path']): ?>
            <a href="../../web/<?php echo htmlspecialchars($app['marksheet_path']); ?>" target="_blank">View/Download</a>
        <?php else: echo 'Not provided';
        endif; ?>
    </li>
    <li><strong>Aadhaar Card:</strong>
        <?php if ($app['aadhaar_path']): ?>
            <a href="../../web/<?php echo htmlspecialchars($app['aadhaar_path']); ?>" target="_blank">View/Download</a>
        <?php else: echo 'Not provided';
        endif; ?>
    </li>
</ul>
<hr>

<h5 class="mt-4">Take Action</h5>
<form action="handle_admission_action.php" method="POST">
    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
    <div class="form-group">
        <label for="status_action">Change Status</label>
        <select name="status_action" id="status_action" class="form-control">
            <option value="">-- Select Action --</option>
            <option value="In Review">Mark as "In Review"</option>
            <option value="Accepted">Accept Application</option>
            <option value="Rejected">Reject Application</option>
        </select>
    </div>

    <div id="acceptFields" style="display:none;">
        <div class="form-group">
            <label for="interview_datetime">Schedule Face-to-Face Session (Date and Time)</label>
            <input type="datetime-local" name="interview_datetime" class="form-control">
        </div>
        <div class="form-group">
            <label for="required_documents">List of Documents to Submit (one per line)</label>
            <textarea name="required_documents" class="form-control" rows="4"></textarea>
        </div>
    </div>

    <div id="rejectFields" style="display:none;">
        <div class="form-group">
            <label for="rejection_reason">Reason for Rejection</label>
            <textarea name="rejection_reason" class="form-control" rows="3"></textarea>
        </div>
    </div>

    <hr>
    <button type="submit" class="btn btn-primary">Update Status</button>
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</form>