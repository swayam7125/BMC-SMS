$(document).ready(function () {
  // Check for the past principals table
  if ($("#pastPrincipalTable").length) {
    $("#pastPrincipalTable").DataTable({
      pageLength: 10,
      order: [[7, "desc"]], // Sort by the "Deleted At" column
    });
  }

  // Check for the past schools table
  if ($("#pastSchoolTable").length) {
    $("#pastSchoolTable").DataTable({
      order: [[6, "desc"]], // Sort by "Deleted At" column
    });
  }

  // Check for the past students table
  if ($("#pastStudentTable").length) {
    $("#pastStudentTable").DataTable({
      pageLength: 10,
      order: [
        [15, "desc"], // Sort by "Deleted At" column
      ],
    });
  }

  // Check for the past teachers table
  if ($("#pastTeacherTable").length) {
    $("#pastTeacherTable").DataTable({
      pageLength: 10,
      order: [
        [18, "desc"], // Sort by "Deleted At" column
      ],
    });
  }
});
