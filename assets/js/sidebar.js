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

    // Enhanced sidebar functionality for AJAX
    $(document).ready(function() {
        // Add AJAX data attributes to navigation links
        $('.nav-link, .collapse-item').each(function() {
            const href = $(this).attr('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript:') && !href.startsWith('http')) {
                $(this).attr('data-ajax', 'true');
            }
        });

        // Update active states based on current URL
        function updateActiveStates() {
            const currentPath = window.location.pathname;
            const currentPage = currentPath.substring(currentPath.lastIndexOf('/') + 1);

            $('.nav-link, .collapse-item').removeClass('active');
            $('.nav-item').removeClass('active');

            // Find and activate current page
            $(`.nav-link[href*="${currentPage}"], .collapse-item[href*="${currentPage}"]`).each(function() {
                $(this).addClass('active');
                $(this).closest('.nav-item').addClass('active');
                $(this).closest('.collapse').addClass('show');
            });
        }

        // Update active states on page load
        updateActiveStates();

        // Listen for AJAX page loads to update active states
        $(document).on('ajax:page:loaded', updateActiveStates);
    });