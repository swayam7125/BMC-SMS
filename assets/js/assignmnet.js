$(document).ready(function () {
  // Only run if the assignment history table exists
  if ($("#assignmentHistoryTable").length) {
    $("#assignmentHistoryTable").DataTable({
      order: [[3, "desc"]],
    });
  }

  // Only run if the assignment submission table exists
  if ($("#assignmentSubmissionTable").length) {
    $("#assignmentSubmissionTable").DataTable({
      order: [[0, "asc"]],
    });
  }

  // Only run if the upload modal exists
  if ($("#uploadModal").length) {
    $("#uploadModal").on("show.bs.modal", function (event) {
      var button = $(event.relatedTarget);
      var assignmentId = button.data("assignment-id");
      var assignmentTitle = button.data("assignment-title");
      var modal = $(this);
      modal.find("#modalAssignmentTitle").text(assignmentTitle);
      modal.find("#modalAssignmentId").val(assignmentId);
    });
  }

  // Only run if a custom file input exists
  if ($(".custom-file-input").length) {
    $(".custom-file-input").on("change", function () {
      var fileName = $(this).val().split("\\").pop();
      $(this)
        .siblings(".custom-file-label")
        .addClass("selected")
        .html(fileName);
    });
  }
});
