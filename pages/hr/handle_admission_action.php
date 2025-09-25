<?php
require_once '../../includes/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_admissions.php");
    exit;
}

// --- Get and Validate Data ---
$application_id = filter_var($_POST['application_id'], FILTER_VALIDATE_INT);
$action = $_POST['status_action'] ?? '';

if (!$application_id || empty($action)) {
    header("Location: manage_admissions.php?error=Invalid data provided.");
    exit;
}

try {
    // --- Update Logic based on Action ---
    if ($action === 'Accepted') {
        $interview_datetime = $_POST['interview_datetime'];
        $required_documents = trim($_POST['required_documents']);

        $stmt = $conn->prepare(
            "UPDATE admission_applications SET status = 'Accepted', interview_datetime = ?, required_documents = ? WHERE id = ?"
        );
        $stmt->execute([$interview_datetime, $required_documents, $application_id]);
    } elseif ($action === 'Rejected') {
        $rejection_reason = trim($_POST['rejection_reason']);

        $stmt = $conn->prepare(
            "UPDATE admission_applications SET status = 'Rejected', rejection_reason = ? WHERE id = ?"
        );
        $stmt->execute([$rejection_reason, $application_id]);
    } elseif ($action === 'In Review') {
        $stmt = $conn->prepare("UPDATE admission_applications SET status = 'In Review' WHERE id = ?");
        $stmt->execute([$application_id]);
    }

    // --- (Optional) Here you could add logic to send an email or SMS notification to the parent ---

    header("Location: manage_admissions.php?success=Application status updated successfully.");
    exit;
} catch (PDOException $e) {
    header("Location: manage_admissions.php?error=Database update failed.");
    exit;
}
