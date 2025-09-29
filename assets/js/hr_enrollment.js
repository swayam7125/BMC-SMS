$(document).ready(function () {
  $("#hrEnrollmentForm").on("submit", function (e) {
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
          $("#imagePreview").attr(
            "src",
            "../../assets/images/undraw_profile.svg"
          );
          $(".closed-checkbox").trigger("change");
          toggleTransportFields();
          if (response.redirect) {
            setTimeout(function () {
              window.location.href = response.redirect;
            }, 1500);
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        let errorMessage = "An unexpected error occurred. Please try again.";
        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
          errorMessage = jqXHR.responseJSON.message;
        } else {
          errorMessage =
            "A server error occurred. Please check the server logs.";
        }
        let alertMessage = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                ${errorMessage}
                                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            </div>`;
        $("#enrollment-alert-placeholder").html(alertMessage);
      },
      complete: function () {
        submitButton.html(originalButtonText).prop("disabled", false);
        $("html, body").animate({ scrollTop: 0 }, "slow");
      },
    });
  });

  // --- The rest of the Javascript is for UI and remains the same ---

  $(".multi-select").select2();
  $("#hr_image").on("change", function (event) {
    if (event.target.files[0]) {
      $("#imagePreview").attr(
        "src",
        URL.createObjectURL(event.target.files[0])
      );
    }
  });
  $(".closed-checkbox")
    .on("change", function () {
      const row = $(this).closest(".timing-row");
      const timeInputs = row.find(".time-input, .ampm-select");
      timeInputs.prop("disabled", $(this).is(":checked"));
    })
    .trigger("change");

  const dateInput = document.getElementById("date_of_joining");
  if (dateInput) {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, "0");
    const day = String(today.getDate()).padStart(2, "0");
    const formattedDate = `${year}-${month}-${day}`;
    dateInput.setAttribute("max", formattedDate);
  }

  const transportModeSelect = document.getElementById("transport_mode");
  const selfTransportSelect = document.getElementById("self_transport_mode");
  const schoolTransportDiv = document.getElementById("transport-stop-div");
  const selfTransportDiv = document.getElementById("self-transport-div");
  const vehicleDetailsDiv = document.getElementById("vehicle-details-div");

  function toggleSelfTransportFields() {
    const selectedMode = selfTransportSelect.value;
    vehicleDetailsDiv.style.display =
      selectedMode === "Bike" || selectedMode === "Car" ? "flex" : "none";
  }

  function toggleTransportFields() {
    const mainMode = transportModeSelect.value;
    if (mainMode === "School Transport") {
      schoolTransportDiv.style.display = "block";
      selfTransportDiv.style.display = "none";
      vehicleDetailsDiv.style.display = "none";
      selfTransportSelect.value = "";
    } else if (mainMode === "Self Transport") {
      selfTransportDiv.style.display = "block";
      schoolTransportDiv.style.display = "none";
      document.getElementById("stop_id").value = "";
      toggleSelfTransportFields();
    }
  }

  toggleTransportFields();
  transportModeSelect.addEventListener("change", toggleTransportFields);
  selfTransportSelect.addEventListener("change", toggleSelfTransportFields);
});
