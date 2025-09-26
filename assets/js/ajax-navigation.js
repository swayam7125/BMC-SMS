$(document).ready(function () {
  // This is the container where all dynamic page content will be loaded.
  const mainContentContainer = "#main-content";
  let isAjaxInProgress = false; // Prevents multiple rapid clicks from firing multiple requests

  /**
   * The core function to load page content via AJAX.
   * @param {string} url - The URL of the page to fetch.
   * @param {boolean} isPopState - True if this function is called by the browser's back/forward buttons.
   */
  function loadPage(url, isPopState = false) {
    if (isAjaxInProgress) {
      return; // Don't start a new request if one is already running
    }
    isAjaxInProgress = true;

    // Show a visual loading indicator for a better user experience.
    $(mainContentContainer).html(
      '<div class="d-flex justify-content-center align-items-center" style="height: 50vh;"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>'
    );

    $.ajax({
      url: url,
      method: "GET",
      // This header is crucial! It tells our PHP files that this is an AJAX request.
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      success: function (response) {
        // Fade out the old content, replace it, and fade in the new.
        $(mainContentContainer).fadeOut(200, function () {
          $(this).html(response).fadeIn(200);

          // Update the URL in the browser's address bar without reloading the page.
          // This is only done for new link clicks, not for back/forward navigation.
          if (!isPopState) {
            window.history.pushState({ path: url }, "", url);
          }

          // After loading the new content, re-initialize any necessary JavaScript plugins.
          initializePluginsForNewContent();

          // Update which link is marked 'active' in the sidebar.
          updateSidebarActiveState(url);

          // Scroll the content area to the top.
          $("#content-wrapper").scrollTop(0);
        });
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error: Could not load page.", status, error);
        // Display a user-friendly error message inside the content area.
        $(mainContentContainer).html(
          '<div class="container-fluid"><div class="alert alert-danger">Sorry, the page could not be loaded. Please check your connection and try again.</div></div>'
        );
      },
      complete: function () {
        // Allow new AJAX requests to be made.
        isAjaxInProgress = false;
      },
    });
  }

  /**
   * Highlights the correct navigation link in the sidebar based on the current page's URL.
   * @param {string} url - The URL of the currently loaded page.
   */
  function updateSidebarActiveState(url) {
    // Extract the clean filename from the URL (e.g., "teacher_list.php").
    const currentPage = url.split("/").pop().split("?")[0];

    // Remove 'active' from all sidebar links and items.
    $(
      "#accordionSidebar .nav-item, #accordionSidebar .collapse-item"
    ).removeClass("active");
    $("#accordionSidebar .collapse").removeClass("show");

    // Find the link that points to the current page.
    let activeLink = $(`#accordionSidebar a[href$="${currentPage}"]`);

    if (activeLink.length) {
      // If the link is inside a collapsible menu, make it active.
      if (activeLink.hasClass("collapse-item")) {
        activeLink.addClass("active");
      }
      // Make its parent list item active and expand the parent menu.
      activeLink.closest(".nav-item").addClass("active");
      activeLink.closest(".collapse").addClass("show");
    }
  }

  /**
   * Re-initializes JavaScript plugins that are needed for dynamically loaded content.
   */
  function initializePluginsForNewContent() {
    // Re-initialize Bootstrap tooltips.
    $('[data-toggle="tooltip"]').tooltip();

    // Re-initialize DataTables if a table with the class exists in the new content.
    if (typeof window.dataTableManager !== "undefined") {
      window.dataTableManager.reinitialize();
    }

    // Trigger a custom event that other specific scripts can listen for.
    // This is useful if you have other custom JS for specific pages.
    $(document).trigger("ajaxPageLoad");
  }

  // --- EVENT LISTENERS ---

  // Intercept clicks on all internal links to load them via AJAX.
  // This selector is broad but excludes links for modals, new tabs, etc.
  $(document).on(
    "click",
    'a:not([data-toggle="modal"]):not([target="_blank"]):not([href^="#"]):not([data-no-ajax])',
    function (e) {
      const targetUrl = $(this).attr("href");

      // We only want to handle internal links for our application.
      if (
        targetUrl &&
        (targetUrl.startsWith("/BMC-SMS/") ||
          targetUrl.startsWith("./") ||
          targetUrl.startsWith("pages/"))
      ) {
        e.preventDefault(); // Stop the browser from navigating normally.
        loadPage(targetUrl); // Load the page using our AJAX function.
      }
    }
  );

  // Handle the browser's back and forward buttons.
  $(window).on("popstate", function (e) {
    // Check if the history state has a path to load.
    if (e.originalEvent.state && e.originalEvent.state.path) {
      loadPage(e.originalEvent.state.path, true); // Load the historical page.
    }
  });

  // --- INITIAL PAGE LOAD ---
  // On the very first visit, we need to load the initial content into our shell.
  // This ensures that bookmarks and direct navigation to sub-pages still work.
  loadPage(window.location.pathname, true);
});
