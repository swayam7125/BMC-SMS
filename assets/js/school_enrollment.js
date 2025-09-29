$(document).ready(function () {
  $(".multi-select").select2({
    placeholder: "Select options",
    allowClear: true,
  });

  $("#schoolEnrollmentForm").on("submit", function (e) {
    e.preventDefault();
    const form = $(this);
    const submitButton = form.find('button[type="submit"]');
    const originalButtonText = submitButton.html();
    submitButton
      .html('<i class="fas fa-spinner fa-spin"></i> Processing...')
      .prop("disabled", true);

    const formData = new FormData(this);

    $.ajax({
      url: form.attr("action"),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (response) {
        let alertClass = response.success ? "alert-success" : "alert-danger";
        let alertMessage = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                                        ${response.message}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>`;
        $("#enrollment-alert-placeholder").html(alertMessage);

        if (response.success) {
          form[0].reset();
          $(".multi-select").val(null).trigger("change");
          if (response.redirect) {
            setTimeout(() => {
              window.location.href = response.redirect;
            }, 1500);
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $("#enrollment-alert-placeholder").html(
          `<div class="alert alert-danger">An unexpected error occurred: ${errorThrown}</div>`
        );
      },
      complete: function () {
        submitButton.html(originalButtonText).prop("disabled", false);
        $("html, body").animate({ scrollTop: 0 }, "slow");
      },
    });
  });

  $('button[type="reset"]').on("click", function () {
    $(".multi-select").val(null).trigger("change");
  });
});
