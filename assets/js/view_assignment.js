$(document).ready(function () {
  // Form submission on filter/sort change
  $("#filterBy, #sortBy").on("change", function () {
    $("#filterForm").submit();
  });

  // Modal population
  $("#uploadModal").on("show.bs.modal", function (event) {
    var button = $(event.relatedTarget);
    var assignmentId = button.data("assignment-id");
    var assignmentTitle = button.data("assignment-title");
    var modal = $(this);
    modal.find("#modalAssignmentTitle").text(assignmentTitle);
    modal.find("#modalAssignmentId").val(assignmentId);
  });

  // Custom file input label
  $(".custom-file-input").on("change", function () {
    var fileName = $(this).val().split("\\").pop();
    $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
  });
});
