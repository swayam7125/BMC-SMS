// Common JavaScript functionality
document.addEventListener("DOMContentLoaded", function () {
  // Initialize notifications if the element exists
  if (typeof initializeNotifications === "function") {
    initializeNotifications();
  }

  // Initialize common plugins
  initializePlugins();
});

// Function to initialize common plugins and components
function initializePlugins() {
  // Re-initialize DataTables if present
  if (typeof $.fn.DataTable !== "undefined") {
    $(".dataTable").each(function () {
      if (!$.fn.DataTable.isDataTable(this)) {
        $(this).DataTable();
      }
    });
  }

  // Re-initialize Select2 if present
  if (typeof $.fn.select2 !== "undefined") {
    $(".select2").select2();
  }

  // Re-initialize tooltips
  if (typeof $().tooltip === "function") {
    $('[data-toggle="tooltip"]').tooltip();
  }

  // Re-initialize popovers
  if (typeof $().popover === "function") {
    $('[data-toggle="popover"]').popover();
  }

  // Trigger a custom event that other scripts can listen for
  $(document).trigger("contentLoaded");
}

// Function to show loading spinner
function showLoadingSpinner() {
  $("#main-content").html(
    '<div class="d-flex justify-content-center">' +
      '<div class="spinner-border text-primary m-5" role="status">' +
      '<span class="visually-hidden">Loading...</span>' +
      "</div></div>"
  );
}

// Function to show error message
function showErrorMessage(error) {
  $("#main-content").html(
    '<div class="alert alert-danger m-3">' +
      '<h4 class="alert-heading">Error Loading Page</h4>' +
      "<p>There was an error loading the requested page: " +
      error +
      "</p>" +
      "</div>"
  );
}

// Global AJAX error handler
$(document).ajaxError(function (event, jqXHR, settings, error) {
  if (jqXHR.status === 401) {
    window.location.href = "login.php";
  }
});
