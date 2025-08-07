$(document).ready(function () {
  $("#marks-form").on("submit", function (e) {
    e.preventDefault(); // Prevent the default form submission

    var exam_type = $("#exam_type").val();
    var academic_year = $("#academic_year").val();
    var reportContainer = $("#marks-report-container");
    var reportBody = $("#marks-report-body");
    var loader = $("#loader");
    var summary = $("#result-summary");

    // Simple validation
    if (!exam_type || !academic_year) {
      alert("Please select an exam type and enter an academic year.");
      return;
    }

    // Show loader and hide previous results
    loader.show();
    reportContainer.hide();
    summary.hide();
    reportBody.empty(); // Clear previous table data

    $.ajax({
      url: "get_my_marks.php",
      type: "POST",
      data: {
        exam_type: exam_type,
        academic_year: academic_year,
      },
      dataType: "json",
      success: function (response) {
        loader.hide();
        if (response.success) {
          // Check if any marks were returned
          if (Object.keys(response.marks).length > 0) {
            $("#student-name-header").text(
              response.student_name + "'s Report Card"
            );

            // Populate the table
            for (var subject in response.marks) {
              var row =
                "<tr>" +
                "<td>" +
                subject +
                "</td>" +
                "<td>" +
                response.marks[subject].marks_obtained +
                "</td>" +
                "<td>" +
                response.marks[subject].total_marks +
                "</td>" +
                "</tr>";
              reportBody.append(row);
            }

            // Populate the footer
            $("#total-obtained").text(response.total_obtained);
            $("#total-possible").text(response.total_possible);

            // Display summary
            var status_class =
              response.status === "Pass" ? "alert-success" : "alert-danger";
            summary
              .removeClass("alert-success alert-danger alert-warning")
              .addClass(status_class);
            summary.html(
              "<strong>Status: " +
                response.status +
                "</strong> | <strong>Percentage: " +
                response.percentage +
                "%</strong>"
            );
            summary.show();

            reportContainer.show();
          } else {
            // No marks found for the criteria
            summary
              .removeClass("alert-success alert-danger")
              .addClass("alert-warning");
            summary.text(
              "No marks have been uploaded for the selected criteria yet."
            );
            summary.show();
          }
        } else {
          // Handle errors from the server
          summary
            .removeClass("alert-success alert-warning")
            .addClass("alert-danger");
          summary.text("Error: " + response.message);
          summary.show();
        }
      },
      error: function () {
        loader.hide();
        // Handle AJAX errors (e.g., server not reachable)
        summary
          .removeClass("alert-success alert-warning")
          .addClass("alert-danger");
        summary.text(
          "An error occurred while communicating with the server. Please try again."
        );
        summary.show();
      },
    });
  });
});
