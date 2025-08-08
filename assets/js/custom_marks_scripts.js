$(document).ready(function () {
  // Get the class standard from the body's data attribute.
  const classStd = $("body").data("class-std");

  // --- Logic for marks_entry.php ---
  if ($("#loadStudentsBtn").length) {
    function loadStudents() {
      const academicYear = $("#academic_year").val();
      const examType = $("#exam_type").val();

      $("#academic_year_hidden").val(academicYear);
      $("#exam_type_hidden").val(examType);

      if (academicYear && examType) {
        $("#students-list-body").html(
          '<tr><td colspan="10" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>'
        );
        $("#marks-table-container").slideDown();

        $.ajax({
          url: "get_students_for_marks.php",
          type: "POST",
          data: {
            class_std: classStd,
            exam_type: examType,
            academic_year: academicYear,
          },
          dataType: "json",
          success: function (response) {
            $("#marks-table-header").empty();
            $("#students-list-body").empty();

            if (response.success) {
              let headerRow = "<tr><th>Roll No</th><th>Student Name</th>";
              response.subjects.forEach((subject) => {
                headerRow += `<th>${subject}</th>`;
              });
              headerRow += "</tr>";
              $("#marks-table-header").html(headerRow);

              if (response.students.length > 0) {
                response.students.forEach((student) => {
                  let row = `<tr><td>${student.rollno}</td><td>${student.student_name}</td>`;
                  response.subjects.forEach((subject) => {
                    const marks = student.marks[subject] || "";
                    row += `<td><input type="number" class="form-control" name="marks[${student.id}][${subject}]" value="${marks}" min="0" max="100" placeholder="N/A"></td>`;
                  });
                  row += `</tr>`;
                  $("#students-list-body").append(row);
                });
              } else {
                $("#students-list-body").html(
                  `<tr><td colspan="${
                    response.subjects.length + 2
                  }" class="text-center">No students found for this class.</td></tr>`
                );
              }
            } else {
              $("#students-list-body").html(
                `<tr><td colspan="10" class="text-center text-danger">${response.message}</td></tr>`
              );
            }
          },
          error: function () {
            $("#students-list-body").html(
              '<tr><td colspan="10" class="text-center text-danger">Error fetching data. Please try again.</td></tr>'
            );
          },
        });
      } else {
        alert("Please select an Exam Type and provide an Academic Year.");
        $("#marks-table-container").slideUp();
      }
    }

    $("#loadStudentsBtn").click(loadStudents);

    $("#marksForm").submit(function (e) {
      e.preventDefault();
      $.ajax({
        url: "save_marks.php",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (response) {
          let messageBox = `<div class="alert alert-${
            response.success ? "success" : "danger"
          } alert-dismissible fade show" role="alert">${
            response.message
          }<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button></div>`;
          $("#message-container").html(messageBox);
          $("html, body").animate({ scrollTop: 0 }, "slow");
        },
        error: function () {
          $("#message-container").html(
            '<div class="alert alert-danger">An unknown error occurred.</div>'
          );
        },
      });
    });
  }

  // --- Logic for view_marks.php ---
  if ($("#viewReportBtn").length) {
    $("#viewReportBtn").click(function () {
      const examType = $("#exam_type").val();
      const academicYear = $("#academic_year").val();

      if (examType && academicYear) {
        $("#marks-report-body").html(
          '<tr><td colspan="15" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading report...</td></tr>'
        );
        $("#marks-report-container").slideDown();

        $.ajax({
          url: "get_marks_report.php",
          type: "POST",
          data: {
            class_std: classStd,
            exam_type: examType,
            academic_year: academicYear,
          },
          dataType: "json",
          success: function (response) {
            $("#marks-report-header").empty();
            $("#marks-report-body").empty();
            if (response.success) {
              let headerRow = "<tr><th>Roll No</th><th>Student Name</th>";
              response.subjects.forEach((subject) => {
                headerRow += `<th>${subject}</th>`;
              });
              headerRow +=
                "<th>Total Obtained</th><th>Total Possible</th><th>Percentage</th><th>Status</th></tr>";
              $("#marks-report-header").html(headerRow);

              if (response.students.length > 0) {
                response.students.forEach((student) => {
                  let row = `<tr><td>${student.rollno}</td><td>${student.student_name}</td>`;
                  
                  response.subjects.forEach((subject) => {
                    let marksCell = '<span class="text-muted">N/A</span>';
                    if (student.marks && student.marks[subject]) {
                        marksCell = student.marks[subject].obtained;
                    }
                    row += `<td>${marksCell}</td>`;
                  });

                  row += `<td><strong>${student.total_obtained}</strong></td>`;
                  row += `<td>${student.total_possible}</td>`;
                  row += `<td><strong class="text-primary">${student.percentage}%</strong></td>`;

                  let statusClass = "badge-secondary";
                  if (student.status === "Pass") {
                    statusClass = "badge-success";
                  } else if (student.status === "Fail") {
                    // TYPO FIX: Changed 'status-class' to 'statusClass'
                    statusClass = "badge-danger";
                  }
                  row += `<td class="font-weight-bold"><span class="badge ${statusClass}" style="font-size: 0.9rem;">${student.status}</span></td>`;
                  row += `</tr>`;
                  $("#marks-report-body").append(row);
                });
              } else {
                $("#marks-report-body").html(
                  `<tr><td colspan="${
                    response.subjects.length + 6
                  }" class="text-center">No marks found for the selected criteria.</td></tr>`
                );
              }
            } else {
              $("#marks-report-body").html(
                `<tr><td colspan="15" class="text-center text-danger">${response.message}</td></tr>`
              );
            }
          },
          error: function () {
            $("#marks-report-header").empty();
            $("#marks-report-body").html(
              '<tr><td colspan="15" class="text-center text-danger">Error fetching marks data. Please try again.</td></tr>'
            );
          },
        });
      } else {
        alert("Please select both Exam Type and Academic Year.");
      }
    });
  }
});