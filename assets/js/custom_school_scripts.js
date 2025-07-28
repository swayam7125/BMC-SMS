// This function needs to be in the global scope to be called by `onclick` in the HTML.
function confirmDelete(id) {
  // Set the href for the delete button in the modal
  $("#confirmDeleteBtn").attr("href", "delete.php?id=" + id);
  // Show the modal
  $("#deleteModal").modal("show");
}

$(document).ready(function () {
  // --- Logic for school_list.php ---
  // Initialize DataTable if the school list table exists on the page.
  if ($("#schoolListTable").length) {
    $("#schoolListTable").DataTable({
      pageLength: 10,
      order: [[0, "asc"]],
    });
  }

  // --- Logic for edit.php ---
  // Check if the logo preview element exists, which is unique to the edit page.
  if ($("#logoPreview").length) {
    // Initialize all multi-select dropdowns with Select2.
    $(".multi-select").select2();

    // Add event listener for the school logo file input to show a preview.
    document
      .getElementById("school_logo")
      .addEventListener("change", function (event) {
        if (event.target.files[0]) {
          document.getElementById("logoPreview").src = URL.createObjectURL(
            event.target.files[0]
          );
        }
      });
  }
});
