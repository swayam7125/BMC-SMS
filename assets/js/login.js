document.addEventListener('DOMContentLoaded', function () {
  // --- 1. SELECT ALL ELEMENTS ---
  const loginForm = document.getElementById('loginForm');
  const alertPlaceholder = document.getElementById('login-alert-placeholder');
  const submitButton = loginForm ? loginForm.querySelector('button[type="submit"]') : null;
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');

  // Forgot Password Modal Elements
  const forgotPasswordModal = document.getElementById('forgotPasswordModal');
  const sendOtpForm = document.getElementById('sendOtpForm');
  const resetPasswordForm = document.getElementById('resetPasswordForm');
  const resetAlertPlaceholder = document.getElementById('reset-alert-placeholder');

  // --- 2. HELPER FUNCTION (Defined once, used everywhere) ---
  function showAlert(message, type, placeholder) {
      if (!placeholder) return;
      const wrapper = document.createElement("div");
      wrapper.innerHTML = [
          `<div class="alert alert-${type} alert-dismissible fade show mb-3" role="alert">`,
          `   <div>${message}</div>`,
          '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
          "</div>"
      ].join('');
      placeholder.innerHTML = ""; // Clear old alerts before showing a new one
      placeholder.append(wrapper);
  }

  // --- 3. INITIALIZE ALL FEATURES ---

  // --- Geolocation ---
  if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
          function (position) {
              if(document.getElementById("latitude")) {
                  document.getElementById("latitude").value = position.coords.latitude;
              }
              if(document.getElementById("longitude")) {
                  document.getElementById("longitude").value = position.coords.longitude;
              }
          },
          function (error) {
              console.warn("Geolocation error: " + error.message);
          }
      );
  } else {
      console.warn("Geolocation is not supported by this browser.");
  }

  // --- Password Toggle ---
  if (togglePassword && passwordInput) {
      togglePassword.addEventListener("click", function () {
          const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
          passwordInput.setAttribute("type", type);
          this.classList.toggle("fa-eye-slash");
      });
  }

  // --- Main Login Form Submission ---
  if (loginForm) {
      loginForm.addEventListener("submit", function (e) {
          e.preventDefault(); // Prevent the default form redirect

          const formData = new FormData(this);
          const originalButtonText = submitButton.innerHTML;
          submitButton.disabled = true;
          submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging In...';
          alertPlaceholder.innerHTML = "";

          fetch(this.action, {
              method: "POST",
              body: formData,
          })
          .then(response => {
              if (!response.ok) {
                  throw new Error(`HTTP error! status: ${response.status}`);
              }
              return response.json();
          })
          .then(data => {
              if (data.status === "success") {
                  showAlert("Login successful! Redirecting...", "success", alertPlaceholder);
                  setTimeout(() => {
                      window.location.href = data.redirect;
                  }, 1000);
              } else {
                  showAlert(data.message, "danger", alertPlaceholder);
                  submitButton.disabled = false;
                  submitButton.innerHTML = originalButtonText;
              }
          })
          .catch(error => {
              console.error("Login Error:", error);
              showAlert("An unexpected error occurred. Please try again.", "danger", alertPlaceholder);
              submitButton.disabled = false;
              submitButton.innerHTML = originalButtonText;
          });
      });
  }

  // --- Forgot Password Modal Logic ---
  if (forgotPasswordModal) {
      forgotPasswordModal.addEventListener("show.bs.modal", function () {
          const email = document.getElementById("email").value;
          document.getElementById("resetEmail").value = email;
          document.getElementById("hiddenEmail").value = email;
          document.getElementById("userEmailDisplay").textContent = email;

          // Reset to the first step (send OTP) every time modal opens
          sendOtpForm.classList.remove("d-none");
          resetPasswordForm.classList.add("d-none");
          resetAlertPlaceholder.innerHTML = "";
      });

      // Handle OTP Send Form
      if (sendOtpForm) {
          sendOtpForm.addEventListener("submit", function (e) {
              e.preventDefault();
              // Logic for sending OTP...
          });
      }

      // Handle Password Reset Form
      if (resetPasswordForm) {
          resetPasswordForm.addEventListener("submit", function (e) {
              e.preventDefault();
              // Logic for resetting password...
          });
      }
  }
});