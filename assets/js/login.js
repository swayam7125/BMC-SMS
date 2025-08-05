// Add this new code block at the top of your login.js file

// --- AJAX Login Form Submission ---
document.getElementById("loginForm").addEventListener("submit", function (e) {
  // Prevent the default form submission (which causes the page reload)
  e.preventDefault();

  const form = this;
  const submitBtn = form.querySelector('button[type="submit"]');
  const alertPlaceholder = document.getElementById("login-alert-placeholder");
  const formData = new FormData(form);

  // Disable the button and show a loading state
  submitBtn.disabled = true;
  submitBtn.innerHTML =
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging In...';

  // Clear any previous alerts
  alertPlaceholder.innerHTML = "";

  fetch(form.action, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        // On success, show a success message and redirect
        showAlert(
          "Login successful! Redirecting...",
          "success",
          alertPlaceholder
        );
        setTimeout(() => {
          window.location.href = data.redirect;
        }, 1000); // Redirect after 1 second
      } else {
        // On error, show the error message from the server
        showAlert(data.message, "danger", alertPlaceholder);
      }
    })
    .catch((error) => {
      // Handle network errors or other unexpected issues
      console.error("Login Error:", error);
      showAlert(
        "An unexpected error occurred. Please try again.",
        "danger",
        alertPlaceholder
      );
    })
    .finally(() => {
      // Re-enable the button unless login was successful
      if (
        document
          .getElementById("login-alert-placeholder")
          .querySelector(".alert-danger")
      ) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = "Login";
      }
    });
});

// --- Your existing code remains below ---

// Geolocation Script
window.addEventListener("load", function () {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function (position) {
        document.getElementById("latitude").value = position.coords.latitude;
        document.getElementById("longitude").value = position.coords.longitude;
      },
      function (error) {
        console.error("Geolocation error: " + error.message);
      }
    );
  } else {
    console.error("Geolocation is not supported by this browser.");
  }
});

// Password Toggle Script
const togglePassword = document.querySelector("#togglePassword");
const passwordInput = document.querySelector("#password");
togglePassword.addEventListener("click", function () {
  const type =
    passwordInput.getAttribute("type") === "password" ? "text" : "password";
  passwordInput.setAttribute("type", type);
  this.classList.toggle("fa-eye-slash");
});

// Forgot Password Modal Logic
const forgotPasswordModal = document.getElementById("forgotPasswordModal");
forgotPasswordModal.addEventListener("show.bs.modal", function (event) {
  // Get email from main form and set it in the modal
  const email = document.getElementById("email").value;
  const resetEmailInput = document.getElementById("resetEmail");
  const hiddenEmailInput = document.getElementById("hiddenEmail");
  const userEmailDisplay = document.getElementById("userEmailDisplay");

  resetEmailInput.value = email;
  hiddenEmailInput.value = email;
  userEmailDisplay.textContent = email;

  // Reset to first step
  document.getElementById("sendOtpForm").classList.remove("d-none");
  document.getElementById("resetPasswordForm").classList.add("d-none");
  document.getElementById("reset-alert-placeholder").innerHTML = "";
});

// Handle OTP Send Form Submission
document.getElementById("sendOtpForm").addEventListener("submit", function (e) {
  e.preventDefault();
  const email = document.getElementById("resetEmail").value;
  const alertPlaceholder = document.getElementById("reset-alert-placeholder");

  if (!email) {
    showAlert(
      "Please enter an email address in the login form first.",
      "danger",
      alertPlaceholder
    );
    return;
  }

  const submitBtn = this.querySelector('button[type="submit"]');
  submitBtn.disabled = true;
  submitBtn.innerHTML =
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';

  fetch("forgot_password.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `email=${encodeURIComponent(email)}`,
  })
    .then((response) => response.json())
    .then((data) => {
      showAlert(
        data.message,
        data.status === "success" ? "success" : "danger",
        alertPlaceholder
      );
      if (data.status === "success") {
        document.getElementById("sendOtpForm").classList.add("d-none");
        document.getElementById("resetPasswordForm").classList.remove("d-none");
      }
    })
    .catch((error) => {
      showAlert(
        "An error occurred. Please try again.",
        "danger",
        alertPlaceholder
      );
    })
    .finally(() => {
      submitBtn.disabled = false;
      submitBtn.innerHTML = "Send OTP";
    });
});

// Handle Password Reset Form Submission
document
  .getElementById("resetPasswordForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const alertPlaceholder = document.getElementById("reset-alert-placeholder");

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Resetting...';

    fetch("reset_password.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        showAlert(
          data.message,
          data.status === "success" ? "success" : "danger",
          alertPlaceholder
        );
        if (data.status === "success") {
          document.getElementById("resetPasswordForm").classList.add("d-none");
          // Optionally close modal after a delay
          setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(forgotPasswordModal);
            modal.hide();
          }, 3000);
        }
      })
      .catch((error) => {
        showAlert(
          "An error occurred. Please try again.",
          "danger",
          alertPlaceholder
        );
      })
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = "Reset Password";
      });
  });

function showAlert(message, type, placeholder) {
  const wrapper = document.createElement("div");
  // Add a margin to the bottom of the alert for spacing
  wrapper.innerHTML =
    `<div class="alert alert-${type} alert-dismissible fade show mb-3" role="alert">` +
    `   <div>${message}</div>` +
    '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
    "</div>";
  placeholder.innerHTML = ""; // Clear old alerts
  placeholder.append(wrapper);
}
