/**
 * Global AJAX Filter Handler (Production Ready)
 *
 * This script attaches an event listener to any form with the attribute `data-ajax-filter="true"`.
 * It prevents the default page reload, sends the form data to a global PHP handler,
 * and updates the specified table body with the HTML response.
 */
document.addEventListener("DOMContentLoaded", function () {
  const filterForms = document.querySelectorAll(
    'form[data-ajax-filter="true"]'
  );

  filterForms.forEach((form) => {
    form.addEventListener("submit", function (event) {
      event.preventDefault();

      const targetTableBodyId = this.dataset.target;
      const tableBody = document.getElementById(targetTableBodyId);
      const submitButton = this.querySelector('button[type="submit"]');

      if (!tableBody) {
        console.error(
          `AJAX Error: Target element with ID "${targetTableBodyId}" not found.`
        );
        return;
      }

      const originalButtonText = submitButton.innerHTML;
      submitButton.disabled = true;
      submitButton.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Filtering...';

      const colspan =
        tableBody.closest("table").querySelector("thead tr").children.length ||
        12;
      tableBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></td></tr>`;

      const formData = new FormData(this);
      const ajaxUrl = "/BMC-SMS/includes/ajax/global_filter_handler.php"; // Ensure this path is correct

      fetch(ajaxUrl, {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
          }
          return response.text();
        })
        .then((html) => {
          tableBody.innerHTML = html;
        })
        .catch((error) => {
          console.error("AJAX request failed:", error);
          tableBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-danger">An error occurred while fetching data. Please try again.</td></tr>`;
        })
        .finally(() => {
          submitButton.disabled = false;
          submitButton.innerHTML = originalButtonText;
        });
    });
  });
});
