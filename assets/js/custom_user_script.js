$(document).ready(function () {
  // --- Logic for edit_profile.php ---
  // Check if the image preview element exists to ensure this code only runs on the edit profile page.
  if ($("#imagePreview").length) {
    // Preview the new image when a file is selected.
    $("#profile_image").on("change", function (event) {
      if (event.target.files && event.target.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
          $("#imagePreview").attr("src", e.target.result);
        };

        reader.readAsDataURL(event.target.files[0]);
      }
    });
  }
});
