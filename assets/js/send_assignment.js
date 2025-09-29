document.addEventListener("DOMContentLoaded", function () {
  const dueDateInput = document.getElementById("due_date");
  const today = new Date().toISOString().split("T")[0];
  dueDateInput.setAttribute("min", today);

  $(".custom-file-input").on("change", function () {
    var fileName = $(this).val().split("\\").pop();
    $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
  });
});
