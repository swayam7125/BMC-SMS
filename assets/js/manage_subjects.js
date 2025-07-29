$(document).ready(function () {
  $(".multi-select").select2({
    placeholder: "Choose subjects...",
    width: "100%",
  });

  // When a standard is selected, fetch its currently assigned subjects
  $("#standard").change(function () {
    const selectedStandard = $(this).val();
    if (selectedStandard) {
      $.ajax({
        url: "ajax_handler.php",
        type: "POST",
        data: {
          action: "get_subjects_for_standard",
          standard: selectedStandard,
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            $("#subject_ids").val(response.subject_ids).trigger("change");
          }
        },
      });
    } else {
      $("#subject_ids").val(null).trigger("change");
    }
  });

  // Handle saving a new subject from the modal
  $("#saveNewSubjectBtn").click(function () {
    const subjectName = $("#new_subject_name").val().trim();
    if (subjectName) {
      $.ajax({
        url: "ajax_handler.php",
        type: "POST",
        data: {
          action: "add_subject",
          subject_name: subjectName,
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            // Add the new subject to the dropdown
            const newOption = new Option(
              response.subject.subject_name,
              response.subject.subject_id,
              false,
              false
            );
            $("#subject_ids").append(newOption);
            $("#addSubjectModal").modal("hide");
            $("#new_subject_name").val("");
          } else {
            $("#modal-message").html(
              `<div class="alert alert-danger">${response.message}</div>`
            );
          }
        },
        error: function () {
          $("#modal-message").html(
            '<div class="alert alert-danger">An error occurred.</div>'
          );
        },
      });
    } else {
      alert("Please enter a subject name.");
    }
  });
});
