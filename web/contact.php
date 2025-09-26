<?php
// We do NOT require_once 'connect.php' here because header.php handles it.

// Handle Form Submission
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Ensure connection is available if submitted directly
  @include_once '../includes/connect.php'; 

  $name = trim($_POST['contactName']);
  $email = trim($_POST['contactEmail']);
  $content = trim($_POST['contactMessage']);

  // Simple validation
  if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($content)) {
    $message = '<div class="alert alert-danger">Please fill out all fields correctly.</div>';
  } else if (!isset($conn)) {
      $message = '<div class="alert alert-danger">Cannot submit form: Database connection error.</div>';
  } else {
    try {
      $conn->beginTransaction();

      // 1. Insert message into the database
      $stmt = $conn->prepare("INSERT INTO contact_messages (sender_name, sender_email, message) VALUES (?, ?, ?)");
      $stmt->execute([$name, $email, $content]);
      
      $last_id = $conn->lastInsertId('contact_messages_id_seq');
      
      // 2. Fetch the school ID and HR user IDs to send notification
      // Assuming you have a way to determine the school (e.g., from a session/cookie or config, 
      // but for simplicity, we'll assume a generic notification for all HR users if context isn't enough)
      
      // For a real-world scenario, you should get the school_id relevant to this contact page.
      // Since the header.php is included *after* the POST handling, we'll assume school_info[school_id] is available 
      // if it was set earlier, or we fall back to a simple query later. For now, let's notify ALL HR for the sake of functionality.
      
      $notification_message = "New Contact Message Received (ID: {$last_id}) from " . htmlspecialchars($name);
      $notification_link = '/BMC-SMS/pages/hr/view_contact_messages.php'; // Assuming this is the HR messages page

      // Fetch all HR user IDs from the 'hr' table (HR users should manage contact messages)
      $hr_users_stmt = $conn->query("SELECT id, school_id FROM hr");
      $hr_users = $hr_users_stmt->fetchAll(PDO::FETCH_ASSOC);

      $notifications_to_insert = [];
      foreach ($hr_users as $hr) {
          $notifications_to_insert[] = [
              'user_id' => $hr['id'],
              'message' => $notification_message,
              'link' => $notification_link,
              'type' => 'new_contact_message'
          ];
      }
      
      // Bulk insert notifications
      if (!empty($notifications_to_insert)) {
        $insert_notif_sql = "INSERT INTO notifications (user_id, message, link, type) VALUES ";
        $insert_notif_values = [];
        $insert_notif_placeholders = [];
        foreach ($notifications_to_insert as $notif) {
            $insert_notif_placeholders[] = "(?, ?, ?, ?)";
            $insert_notif_values[] = $notif['user_id'];
            $insert_notif_values[] = $notif['message'];
            $insert_notif_values[] = $notif['link'];
            $insert_notif_values[] = $notif['type'];
        }
        $insert_notif_sql .= implode(', ', $insert_notif_placeholders);
        $stmt_notif = $conn->prepare($insert_notif_sql);
        $stmt_notif->execute($insert_notif_values);
      }

      $conn->commit();
      $message = '<div class="alert alert-success">Thank you for your message! We will get back to you soon.</div>';
    } catch (PDOException $e) {
      $conn->rollBack();
      $message = '<div class="alert alert-danger">Sorry, there was an error sending your message.</div>';
      error_log("Contact form error: " . $e->getMessage());
    }
  }
}

// Fetch dynamic school details by including header.php. 
// This file will establish the connection and populate $school_info.
$pageTitle = 'Contact Us';
include 'header.php'; 

// --- DYNAMIC DATA IS NOW AVAILABLE IN $school_info ---
// We define $contact_info based on $school_info to keep the HTML section clean.
// This relies on $school_info having keys: address, phone, email, etc.
$contact_info = [
  'address' => $school_info['address'] ?? '123 Education Lane, Knowledge City, 456789 (Fallback)', 
  'phone' => $school_info['phone'] ?? '+91 123 456 7890 (Fallback)', 
  'email' => $school_info['email'] ?? 'info@bmcschool.com (Fallback)'
];

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title><?php echo htmlspecialchars($pageTitle); ?> | <?php echo htmlspecialchars($school_info['school_name']); ?></title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="css/style.min.css">
</head>

<body>
  <main>
    <div class="content-wrapper">
      <div class="container">
        <section class="contact-section py-5">
          <div class="content-header">
            <h2>Get in Touch</h2>
            <h6 class="section-subtitle text-muted">We'd love to hear from you. Please feel free to reach out with any questions.</h6>
          </div>

          <?php echo $message; ?>

          <div class="row">
            <div class="col-lg-6 mb-4 mb-lg-0">
              <div class="card shadow-sm h-100">
                <div class="card-body p-4 p-md-5">
                  <h5 class="mb-4 text-primary">Send Us a Message</h5>
                  <form method="POST" action="contact.php" novalidate>
                    <div class="form-group"><label for="contactName">Your Name *</label><input type="text" name="contactName" class="form-control" id="contactName" required></div>
                    <div class="form-group"><label for="contactEmail">Your Email *</label><input type="email" name="contactEmail" class="form-control" id="contactEmail" required></div>
                    <div class="form-group"><label for="contactMessage">Message *</label><textarea name="contactMessage" class="form-control" id="contactMessage" rows="5" required></textarea></div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                  </form>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card shadow-sm h-100">
                <div class="card-body p-4 p-md-5">
                  <h5 class="mb-4 text-primary">Our Information</h5>
                  <p><strong><i class="mdi mdi-map-marker mr-2"></i>Address:</strong><br><span class="ml-4"><?php echo nl2br(htmlspecialchars($contact_info['address'])); ?></span></p>
                  <p><strong><i class="mdi mdi-phone mr-2"></i>Phone:</strong><br><span class="ml-4"><?php echo htmlspecialchars($contact_info['phone']); ?></span></p>
                  <p><strong><i class="mdi mdi-email mr-2"></i>Email:</strong><br><span class="ml-4"><?php echo htmlspecialchars($contact_info['email']); ?></span></p>
                  <p><strong><i class="mdi mdi-clock mr-2"></i>Office Hours:</strong><br><span class="ml-4">Monday - Friday: 8:00 AM - 4:00 PM</span></p>
                </div>
              </div>
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