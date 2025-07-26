$(document).ready(function () {
  // This function runs when the upload modal is about to be shown
  $("#uploadModal").on("show.bs.modal", function (event) {
    // Get the card that triggered the modal
    var card = $(event.relatedTarget);

    // Extract info from the data-* attributes of the card
    var assignmentTitle = card.data("assignment-title");
    var assignmentId = card.data("assignment-id");

    // Get the modal itself
    var modal = $(this);

    // Update the modal's content with the info from the card
    modal.find("#modalAssignmentTitle").text(assignmentTitle);
    modal.find("#modalAssignmentId").val(assignmentId);

    // Reset the file input field
    modal.find(".custom-file-label").html("Choose PDF file...");
    modal.find("#pdfUpload").val("");
  });

  // This function updates the file input label with the name of the selected file
  $(".custom-file-input").on("change", function () {
    var fileName = $(this).val().split("\\").pop();
    if (fileName) {
      $(this)
        .siblings(".custom-file-label")
        .addClass("selected")
        .html(fileName);
    } else {
      $(this).siblings(".custom-file-label").html("Choose PDF file...");
    }
  });
});
