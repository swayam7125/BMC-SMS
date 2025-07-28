// This function must be in the global scope to be called by `onclick` in the HTML.
function confirmDelete(id) {
  // Set the href for the delete button in the modal
  $("#confirmDeleteBtn").attr("href", "delete.php?id=" + id);
  // Show the modal
  $("#deleteModal").modal("show");
}

$(document).ready(function () {
  // --- Logic for principal_list.php ---
  // Initialize DataTable for the principal list if the table exists.
  if ($("#principalListTable").length) {
    $("#principalListTable").DataTable({
      pageLength: 25,
      order: [[0, "asc"]],
    });
  }

  // --- Logic for edit.php ---
  // Check if the image preview element exists, indicating we're on the edit page.
  if ($("#imagePreview").length) {
    // Script to show a preview of the selected image file.
    document
      .getElementById("principal_image")
      .addEventListener("change", function (event) {
        if (event.target.files[0]) {
          document.getElementById("imagePreview").src = URL.createObjectURL(
            event.target.files[0]
          );
        }
      });

    // Logic to disable/enable time inputs when the "Closed" checkbox is changed.
    $(".closed-checkbox").on("change", function () {
      const row = $(this).closest(".timing-row");
      const timeInputs = row.find('input[type="time"]');
      timeInputs.prop("disabled", $(this).is(":checked"));
    });

    // Trigger the change event on page load to set the initial disabled state correctly.
    $(".closed-checkbox").trigger("change");
  }

  // --- Logic for principal_leave_requests.php ---
  // Check if the rejection modal exists.
  if ($("#rejectionModal").length) {
    // Pass the leave application ID to the hidden input field in the rejection modal.
    $(document).on("click", ".reject-btn", function () {
      var leaveId = $(this).data("id");
      $("#rejectionModal .modal-body #leave_id_input").val(leaveId);
    });
  }

  // --- Logic for send_notice.php ---
  // Check if the main recipient dropdown exists.
  if ($("#send_to_group").length) {
    // Initialize all multi-select dropdowns with Select2.
    $(".multi-select").select2({
      placeholder: "Select one or more options",
      allowClear: true,
    });

    // Show/hide specific recipient dropdowns based on the main selection.
    $("#send_to_group").on("change", function () {
      var selectedGroup = $(this).val();
      var teacherGroup = $("#teacher_group");
      var studentGroup = $("#student_group");

      switch (selectedGroup) {
        case "teacher":
          teacherGroup.show();
          studentGroup.hide();
          break;
        case "student":
          teacherGroup.hide();
          studentGroup.show();
          break;
        default: // Handles 'both' and empty selection
          teacherGroup.hide();
          studentGroup.hide();
      }
    });

    // Function to handle the "All" option in multi-selects.
    function handleAllSelection(selector) {
      $(selector).on("change", function () {
        var selected = $(this).val() || [];
        // If 'all' is selected along with others, deselect the others.
        if (selected.includes("all") && selected.length > 1) {
          $(this).val("all").trigger("change.select2");
        }
      });
    }

    // Apply the 'all' selection logic to both teacher and standard dropdowns.
    handleAllSelection("#teacher_ids");
    handleAllSelection("#standard_ids");
  }

  // --- Logic for view_notice.php ---
  // Initialize DataTable for the notice list if the table exists.
  if ($("#principal-viewNoticeTable").length) {
    $("#principal-viewNoticeTable").DataTable({
      order: [
        [3, "desc"], // Order by date descending
      ],
      dom:
        "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    });
  }
});
