// This function needs to be in the global scope to be called by `onclick` in the HTML.
function confirmDelete(id) {
  // Set the href for the delete button in the modal
  $("#confirmDeleteBtn").attr("href", "delete.php?id=" + id);
  // Show the modal
  $("#deleteModal").modal("show");
}

$(document).ready(function () {
  // --- Logic for student_list.php ---
  if ($("#studentListTable").length) {
    $("#studentListTable").DataTable({
      pageLength: 25,
      order: [[0, "asc"]],
    });
  }

  // --- Logic for edit.php ---
  if ($("#imagePreview").length) {
    document
      .getElementById("student_image")
      .addEventListener("change", function (event) {
        const file = event.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = function (e) {
            document.getElementById("imagePreview").src = e.target.result;
          };
          reader.readAsDataURL(file);
        }
      });
  }

  // --- Logic for view_attendance.php ---
  if ($("#attendanceTable").length) {
    $("#attendanceTable").DataTable({
      order: [[0, "desc"]], // Order by date descending
    });
  }

  // --- Logic for view_notes.php ---
  if ($("#notesTable").length) {
    $("#notesTable").DataTable({
      order: [[4, "desc"]], // Sort by the 'Date' column
      dom:
        "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    });
  }

  // --- Logic for view_timetable.php ---
  if ($("#timetableTable").length) {
    $("#timetableTable").DataTable({
      order: [[2, "desc"]], // Sort by 'Date Uploaded' column
    });
  }

  // --- Logic for view_my_marks.php ---
  if ($("#viewReportBtn").length) {
    $("#viewReportBtn").click(function () {
      const examType = $("#exam_type").val();
      const academicYear = $("#academic_year").val();

      if (examType && academicYear) {
        $("#marks-report-body").html(
          '<tr><td colspan="3" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading report...</td></tr>'
        );
        $("#result-summary").hide();
        $("#marks-report-container").slideDown();

        $.ajax({
          url: "get_my_marks.php",
          type: "POST",
          data: {
            exam_type: examType,
            academic_year: academicYear,
          },
          dataType: "json",
          success: function (response) {
            $("#marks-report-body").empty();
            if (response.success) {
              $("#student-name-header").text(
                "Report for: " + (response.student_name || "")
              );

              if (response.total_possible > 0) {
                let summaryAlertClass = "alert-info";
                if (response.status === "Pass") {
                  summaryAlertClass = "alert-success";
                } else if (response.status === "Fail") {
                  summaryAlertClass = "alert-danger";
                }

                const summaryHtml = `
                                <h5 class="alert-heading">Result Summary</h5>
                                <p class="mb-1"><strong>Overall Percentage:</strong> <span class="font-weight-bold">${response.percentage}%</span></p>
                                <p class="mb-0"><strong>Total Marks:</strong> ${response.total_obtained} / ${response.total_possible}</p>
                                <hr>
                                <p class="mb-0"><strong>Status:</strong> <span class="font-weight-bold">${response.status}</span></p>
                            `;

                $("#result-summary")
                  .removeClass("alert-info alert-success alert-danger")
                  .addClass(summaryAlertClass)
                  .html(summaryHtml)
                  .show();
              } else {
                $("#result-summary").hide();
              }

              if (Object.keys(response.marks).length > 0) {
                for (const subject in response.marks) {
                  const mark_data = response.marks[subject];
                  let row = `<tr>
                                               <td>${subject}</td>
                                               <td>${mark_data.marks_obtained}</td>
                                               <td>${mark_data.total_marks}</td>
                                           </tr>`;
                  $("#marks-report-body").append(row);
                }
              } else {
                $("#marks-report-body").html(
                  `<tr><td colspan="3" class="text-center">No marks have been entered for the selected criteria.</td></tr>`
                );
              }
            } else {
              $("#result-summary").hide();
              $("#marks-report-body").html(
                `<tr><td colspan="3" class="text-center text-danger">${response.message}</td></tr>`
              );
            }
          },
          error: function () {
            $("#marks-report-body").html(
              '<tr><td colspan="3" class="text-center text-danger">An error occurred while fetching your marks.</td></tr>'
            );
          },
        });
      } else {
        alert("Please select both Exam Type and Academic Year.");
      }
    });
  }
});
