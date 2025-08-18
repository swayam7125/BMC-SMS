$(document).ready(function () {
  // Convert all internal links to AJAX links
  function convertInternalLinks() {
    $('a[href^="/BMC-SMS/"]')
      .not("[data-no-ajax]")
      .each(function () {
        $(this).attr("data-ajax-link", $(this).attr("href"));
      });
  }

  // Handle all internal navigation links
  $(document).on("click", "a[data-ajax-link]", function (e) {
    e.preventDefault();
    const page = $(this).attr("data-ajax-link");
    loadPage(page);

    // Update URL without page reload
    window.history.pushState({ page: page }, "", page);
  });

  // Handle form submissions
  $(document).on("submit", "form[data-ajax-form]", function (e) {
    e.preventDefault();
    const form = $(this);
    const url = form.attr("action");
    const method = form.attr("method") || "POST";
    const formData = new FormData(this);

    showLoadingSpinner();

    $.ajax({
      url: url,
      method: method,
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      success: function (response) {
        if (
          typeof response === "string" &&
          response.includes("<!DOCTYPE html>")
        ) {
          // Full page response - likely a redirect or error
          window.location.reload();
        } else {
          $("#main-content").html(response);
          initializePlugins();
          convertInternalLinks();
        }
      },
      error: function (xhr, status, error) {
        showErrorMessage(error);
      },
    });
  });

  // Handle browser back/forward buttons
  window.onpopstate = function (e) {
    if (e.state && e.state.page) {
      loadPage(e.state.page);
    }
  };

  // Function to load pages via AJAX
  function loadPage(url) {
    showLoadingSpinner();

    $.ajax({
      url: url,
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      success: function (response) {
        $("#main-content").html(response);
        initializePlugins();
        convertInternalLinks();

        // Update active states in sidebar
        updateSidebarActiveState(url);
      },
      error: function (xhr, status, error) {
        showErrorMessage(error);
      },
    });
  }

  // Function to update sidebar active state
  function updateSidebarActiveState(url) {
    const currentPage = url.split("/").pop();

    // Remove all active classes
    $(".nav-item.active").removeClass("active");
    $(".collapse-item.active").removeClass("active");

    // Add active class to matching items
    $('a[href$="' + currentPage + '"]')
      .closest(".nav-item")
      .addClass("active");
    $('a[href$="' + currentPage + '"].collapse-item').addClass("active");

    // Handle collapse states
    $(".collapse").removeClass("show");
    $('a[href$="' + currentPage + '"]')
      .closest(".collapse")
      .addClass("show");
  }

  // Function to reinitialize plugins and scripts for dynamic content
  function initializePlugins() {
    // Re-initialize DataTables if present
    if ($.fn.DataTable) {
      $(".dataTable").DataTable();
    }

    // Re-initialize Select2 if present
    if ($.fn.select2) {
      $(".select2").select2();
    }

    // Re-initialize any tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Re-initialize any popovers
    $('[data-toggle="popover"]').popover();

    // Trigger a custom event that other scripts can listen for
    $(document).trigger("contentLoaded");
  }

  // Load initial page if not on the index
  if (
    window.location.pathname !== "/" &&
    window.location.pathname !== "/index.php"
  ) {
    loadPage(window.location.pathname);
  }
});
