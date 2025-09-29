$(document).ready(function () {
  $("#librarianEnrollmentForm").on("submit", function (e) {
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
          $("#imagePreview").attr("src", "../../assets/images/unisex.png");
          toggleTransportFields();
          if (response.redirect) {
            setTimeout(function () {
              window.location.href = response.redirect;
            }, 1500);
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        let errorMessage = "An unexpected error occurred: " + errorThrown;
        $("#enrollment-alert-placeholder").html(
          `<div class="alert alert-danger alert-dismissible fade show" role="alert">${errorMessage}<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>`
        );
      },
      complete: function () {
        submitButton.html(originalButtonText).prop("disabled", false);
        $("html, body").animate({ scrollTop: 0 }, "slow");
      },
    });
  });

  $("#librarian_image").on("change", function (event) {
    if (event.target.files && event.target.files[0]) {
      const reader = new FileReader();
      reader.onload = (e) => $("#imagePreview").attr("src", e.target.result);
      reader.readAsDataURL(event.target.files[0]);
    }
  });

  $(".closed-checkbox")
    .on("change", function () {
      const row = $(this).closest(".timing-row");
      const timeInputs = row.find(".time-input, .ampm-select");
      timeInputs.prop("disabled", $(this).is(":checked"));
    })
    .trigger("change");

  $('button[type="reset"]').on("click", () =>
    $("#imagePreview").attr("src", "../../assets/images/unisex.png")
  );

  const transportModeSelect = document.getElementById("transport_mode");
  const selfTransportSelect = document.getElementById("self_transport_mode");
  const schoolTransportDiv = document.getElementById("transport-stop-div");
  const selfTransportDiv = document.getElementById("self-transport-div");
  const vehicleDetailsDiv = document.getElementById("vehicle-details-div");
  const schoolSelect = document.getElementById("school_id");

  function fetchTransportStops(schoolId) {
    if (!schoolId) {
      $("#stop_id").html(
        '<option value="">-- Select a school first --</option>'
      );
      return;
    }
    $.ajax({
      url: "../../pages/student/get_transport_stops.php",
      type: "GET",
      data: { school_id: schoolId },
      dataType: "json",
      success: function (data) {
        let options = '<option value="">-- No Transport --</option>';
        let currentRoute = "";
        if (data.length > 0) {
          data.forEach((stop) => {
            if (stop.route_name !== currentRoute) {
              if (currentRoute !== "") options += "</optgroup>";
              currentRoute = stop.route_name;
              options += `<optgroup label="${currentRoute}">`;
            }
            options += `<option value="${stop.stop_id}">${stop.stop_name}</option>`;
          });
          if (currentRoute !== "") options += "</optgroup>";
        }
        $("#stop_id").html(options);
      },
      error: function () {
        $("#stop_id").html(
          '<option value="">-- Error loading stops --</option>'
        );
      },
    });
  }

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
      fetchTransportStops($(schoolSelect).val());
    } else {
      // Self Transport
      selfTransportDiv.style.display = "block";
      schoolTransportDiv.style.display = "none";
      document.getElementById("stop_id").value = "";
      toggleSelfTransportFields();
    }
  }

  transportModeSelect.addEventListener("change", toggleTransportFields);
  selfTransportSelect.addEventListener("change", toggleSelfTransportFields);
  if (schoolSelect.tagName === "SELECT") {
    schoolSelect.addEventListener("change", () =>
      fetchTransportStops($(schoolSelect).val())
    );
  }

  // Initial call
  toggleTransportFields();
});
