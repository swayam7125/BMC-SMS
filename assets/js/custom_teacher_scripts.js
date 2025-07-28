// This function must be in the global scope to be called by `onclick` in the HTML.
function confirmDelete(id) {
  // This function finds the modal and sets the correct delete link
  $("#confirmDeleteBtn").attr("href", "delete.php?id=" + id);
  $("#deleteModal").modal("show");
}

$(document).ready(function () {
  // --- Logic for teacher_list.php ---
  // Initialize DataTable for the teacher list.
  if ($("#teacherListTable").length) {
    $("#teacherListTable").DataTable({
      pageLength: 25,
      order: [[0, "asc"]],
    });
  }

  // --- Logic for add_attendance.php ---
  // Initialize DataTable for the attendance sheet with specific settings.
  if ($("#addAttendanceTable").length) {
    $("#addAttendanceTable").DataTable({
      paging: false, // Disable pagination for attendance sheet
      searching: false, // Disable search
      info: false, // Disable info
    });
  }

  // --- Logic for view_attendance.php ---
  // Initialize a standard DataTable for viewing attendance.
  if ($("#viewAttendanceTable").length) {
    $("#viewAttendanceTable").DataTable();
  }

  // --- Logic for teacher_leave_form.php ---
  // Ensures the "To Date" cannot be earlier than the "From Date".
  if ($("#from_date").length) {
    const fromDateInput = document.getElementById("from_date");
    const toDateInput = document.getElementById("to_date");
    const today = new Date().toISOString().split("T")[0];

    fromDateInput.setAttribute("min", today);
    toDateInput.setAttribute("min", today);

    fromDateInput.addEventListener("change", function () {
      const selectedFromDate = this.value;
      toDateInput.setAttribute("min", selectedFromDate);

      if (toDateInput.value < selectedFromDate) {
        toDateInput.value = "";
      }
    });
  }

  // --- Logic for edit.php (Teacher) ---
  // This block handles multiple functionalities on the teacher edit page.
  if ($("#imagePreview").length && $("#class_teacher").length) {
    // Initialize Select2 dropdowns.
    $(".multi-select").select2();

    // Logic for the "Is Class Teacher?" checkbox.
    const isClassTeacherCheckbox = $("#class_teacher");
    const classTeacherStdGroup = $("#classTeacherStdGroup");

    function toggleClassTeacherStd() {
      if (isClassTeacherCheckbox.is(":checked")) {
        classTeacherStdGroup.show();
        $("#class_teacher_std").prop("required", true);
      } else {
        classTeacherStdGroup.hide();
        $("#class_teacher_std").prop("required", false).val("");
      }
    }
    isClassTeacherCheckbox.on("change", toggleClassTeacherStd);
    toggleClassTeacherStd(); // Run on page load.

    // Logic for live image preview.
    $("#teacher_image").on("change", function (event) {
      if (event.target.files[0]) {
        $("#imagePreview").attr(
          "src",
          URL.createObjectURL(event.target.files[0])
        );
      }
    });

    // Logic for weekly timings checkboxes.
    $(".closed-checkbox")
      .on("change", function () {
        const row = $(this).closest(".timing-row");
        const timeInputs = row.find('input[type="time"]');
        timeInputs.prop("disabled", $(this).is(":checked"));
      })
      .trigger("change"); // Run on page load to set initial state.
  }
});
