$(document).ready(function () {
  $("#submissionsTable").DataTable();

  $("#rejectModal").on("show.bs.modal", function (event) {
    var button = $(event.relatedTarget);
    var submissionId = button.data("submission-id");
    var modal = $(this);
    modal.find("#modalSubmissionId").val(submissionId);
  });
});
