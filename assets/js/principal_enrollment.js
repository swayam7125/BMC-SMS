$(document).ready(function () {
  $("#principalEnrollmentForm").on("submit", function (e) {
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
          $(".closed-checkbox").prop("checked", false).trigger("change");
          toggleTransportFields();
          $("#school_id").html(
            '<option value="">-- Select a Batch First --</option>'
          ); // Reset school dropdown
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

  $("#principal_image").on("change", function (event) {
    if (event.target.files && event.target.files[0]) {
      const reader = new FileReader();
      reader.onload = (e) => $("#imagePreview").attr("src", e.target.result);
      reader.readAsDataURL(event.target.files[0]);
    }
  });

  $(".closed-checkbox")
    .on("change", function () {
      const row = $(this).closest(".timing-row");
      row.find(".time-input, .ampm-select").prop("disabled", this.checked);
    })
    .trigger("change");

  const transportModeSelect = $("#transport_mode");
  const selfTransportSelect = $("#self_transport_mode");
  const schoolTransportDiv = $("#transport-stop-div");
  const selfTransportDiv = $("#self-transport-div");
  const vehicleDetailsDiv = $("#vehicle-details-div");
  const schoolSelect = $("#school_id");
  const batchSelect = $("#batch");

  function fetchAvailableSchools(batch) {
    if (!batch) {
      schoolSelect.html('<option value="">-- Select a Batch First --</option>');
      return;
    }
    $.ajax({
      url: "../get_principal_form_data.php", // Adjusted path
      type: "GET",
      data: { action: "get_schools", batch: batch },
      dataType: "json",
      success: function (schools) {
        let options = '<option value="">-- Select School --</option>';
        if (schools.length > 0) {
          schools.forEach((school) => {
            options += `<option value="${school.id}">${school.school_name}</option>`;
          });
        } else {
          options =
            '<option value="" disabled>No available schools for this batch</option>';
        }
        schoolSelect.html(options);
      },
      error: () =>
        schoolSelect.html(
          '<option value="">-- Error loading schools --</option>'
        ),
    });
  }

  function fetchTransportStops(schoolId) {
    if (!schoolId) {
      $("#stop_id").html('<option value="">-- Select School First --</option>');
      return;
    }
    $.ajax({
      url: "../get_principal_form_data.php", // Adjusted path
      type: "GET",
      data: { action: "get_stops", school_id: schoolId },
      dataType: "json",
      success: function (stops) {
        let options = '<option value="">-- No Transport --</option>';
        let currentRoute = "";
        stops.forEach((stop) => {
          if (stop.route_name !== currentRoute) {
            if (currentRoute !== "") options += "</optgroup>";
            currentRoute = stop.route_name;
            options += `<optgroup label="${currentRoute}">`;
          }
          options += `<option value="${stop.stop_id}">${stop.stop_name}</option>`;
        });
        if (currentRoute !== "") options += "</optgroup>";
        $("#stop_id").html(options);
      },
      error: () =>
        $("#stop_id").html(
          '<option value="">-- Error loading stops --</option>'
        ),
    });
  }

  function toggleSelfTransportFields() {
    const selectedMode = selfTransportSelect.val();
    vehicleDetailsDiv.css(
      "display",
      selectedMode === "Bike" || selectedMode === "Car" ? "flex" : "none"
    );
  }

  function toggleTransportFields() {
    const mainMode = transportModeSelect.val();
    if (mainMode === "School Transport") {
      schoolTransportDiv.show();
      selfTransportDiv.hide();
      vehicleDetailsDiv.hide();
      selfTransportSelect.val("");
      fetchTransportStops(schoolSelect.val());
    } else {
      // Self Transport
      selfTransportDiv.show();
      schoolTransportDiv.hide();
      $("#stop_id").val("");
      toggleSelfTransportFields();
    }
  }

  batchSelect.on("change", () => fetchAvailableSchools(batchSelect.val()));
  schoolSelect.on("change", () => fetchTransportStops(schoolSelect.val()));
  transportModeSelect.on("change", toggleTransportFields);
  selfTransportSelect.on("change", toggleSelfTransportFields);

  toggleTransportFields();
});
