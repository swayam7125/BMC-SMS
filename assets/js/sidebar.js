document.addEventListener("DOMContentLoaded", function () {
  if (window.jQuery) {
    // Function to handle badge removal on click
    $("#accordionSidebar .nav-link").on("click", function () {
      var link = $(this);
      var badge = link.find(".badge-counter");
      var notificationType = link.data("notification-type"); // Get the type from data attribute

      // If the link has a badge and a defined notification type
      if (badge.length > 0 && notificationType) {
        // 1. Visually remove the badge immediately for good user experience
        badge.fadeOut("fast", function () {
          $(this).remove();
        });

        // 2. Send a request to the backend to mark these notifications as read
        $.post(
          "<?php echo BASE_WEB_PATH; ?>includes/actions/mark_notifications_read.php",
          {
            type: notificationType,
          },
          function (response) {
            if (response.status !== "success") {
              console.error(
                "Failed to mark notifications as read:",
                response.message
              );
            }
          },
          "json" // Expect a JSON response
        ).fail(function () {
          console.error("AJAX request failed.");
        });
      }
    });
  }
});
