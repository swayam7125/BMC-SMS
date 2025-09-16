// assets/js/login.js

// This listener forces the page to reload if you use the back button, ensuring a clean form.
window.addEventListener("pageshow", function (event) {
  if (event.persisted) {
    window.location.reload();
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("loginForm");
  const alertPlaceholder = document.getElementById("login-alert-placeholder");
  const submitButton = loginForm
    ? loginForm.querySelector('button[type="submit"]')
    : null;
  const togglePassword = document.getElementById("togglePassword");
  const passwordInput = document.getElementById("password");
  const forgotPasswordModal = document.getElementById("forgotPasswordModal");
  const sendOtpForm = document.getElementById("sendOtpForm");
  const resetPasswordForm = document.getElementById("resetPasswordForm");
  const resetAlertPlaceholder = document.getElementById(
    "reset-alert-placeholder"
  );

  // Function to show alert messages
  function showAlert(message, type, placeholder) {
    if (!placeholder) return;
    const wrapper = document.createElement("div");
    wrapper.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show mb-3" role="alert">
      <div>${message}</div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>`;
    placeholder.innerHTML = "";
    placeholder.append(wrapper);
  }

  // Function to clear all alerts
  function clearAlerts(placeholder) {
    if (placeholder) {
      placeholder.innerHTML = "";
    }
  }

  // Get user's geolocation
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function (position) {
        const latInput = document.getElementById("latitude");
        const lonInput = document.getElementById("longitude");
        if (latInput) latInput.value = position.coords.latitude;
        if (lonInput) lonInput.value = position.coords.longitude;
      },
      function (error) {
        console.warn("Geolocation error: " + error.message);
        // Don't show error to user as geolocation is optional
      }
    );
  }

  // Password toggle functionality
  if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", function () {
      const type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      this.classList.toggle("fa-eye-slash");
    });
  }

  // Main login form submission
  if (loginForm && submitButton) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const emailInput = document.getElementById("email");
      const passwordInput = document.getElementById("password");

      clearAlerts(alertPlaceholder); // Clear previous alerts

      // Client-side validation
      if (!emailInput.value.trim() && !passwordInput.value.trim()) {
        showAlert(
          "Please enter your email and password.",
          "danger",
          alertPlaceholder
        );
        emailInput.focus();
        return;
      }

      if (!emailInput.value.trim()) {
        showAlert("Please provide your Email.", "danger", alertPlaceholder);
        emailInput.focus();
        return;
      }

      if (!passwordInput.value.trim()) {
        showAlert("Please provide your Password.", "danger", alertPlaceholder);
        passwordInput.focus();
        return;
      }

      // Email format validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(emailInput.value.trim())) {
        showAlert(
          "Please enter a valid email address.",
          "danger",
          alertPlaceholder
        );
        emailInput.focus();
        return;
      }

      // Prepare form data
      const formData = new FormData(this);

      // Store original button content and disable button
      const originalButtonText = submitButton.innerHTML;
      submitButton.disabled = true;
      submitButton.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Logging In...';
      
      // --- START OF LOGIC CHANGE ---

      // Send login request
      fetch("login.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin", // Include cookies
      })
      .then(response => {
          // This logic reads the JSON body first, regardless of the HTTP status code.
          // This allows us to get the specific error message from the server on failed logins.
          return response.json().then(data => {
              if (!response.ok) {
                  // If the response status is an error (e.g., 401), we create a new Error object
                  // but use the specific message from the server's JSON payload.
                  throw new Error(data.message || 'An unknown server error occurred.');
              }
              // If the response is ok (status 200), we pass the data to the next .then() block.
              return data;
          });
      })
      .then(data => {
          // This block now only runs for successful (2xx) responses.
          console.log("Login response:", data); // For debugging
          if (data.status === "success" || data.success === true) {
            showAlert(
              data.message || "Login successful! Redirecting...",
              "success",
              alertPlaceholder
            );

            // Redirect after a short delay
            setTimeout(() => {
              if (data.redirect) {
                window.location.href = data.redirect;
              } else {
                // Fallback redirect
                window.location.href = "index.php?page=dashboard";
              }
            }, 1500);
          } else {
            // This is a fallback for a 200 OK response that still indicates an error
            showAlert(
              data.message || "Login failed. Please try again.",
              "danger",
              alertPlaceholder
            );
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
            emailInput.focus();
          }
      })
      .catch(error => {
          // This .catch() now receives the specific error message from the server
          // OR a real network error if the server is unreachable.
          console.error("Login Error:", error);
          showAlert(
            error.message, // This now displays the specific error message
            "danger",
            alertPlaceholder
          );
          submitButton.disabled = false;
          submitButton.innerHTML = originalButtonText;
          emailInput.focus();
      });

      // --- END OF LOGIC CHANGE ---
    });
  }

  // --- FORGOT PASSWORD MODAL LOGIC ---
  if (forgotPasswordModal) {
    forgotPasswordModal.addEventListener("show.bs.modal", function () {
      // Reset forms and clear alerts
      if (sendOtpForm) sendOtpForm.reset();
      if (resetPasswordForm) resetPasswordForm.reset();
      clearAlerts(resetAlertPlaceholder);

      // Get the email from the main form and set it in the modal
      const emailValue = document.getElementById("email")?.value || "";
      const resetEmailField = document.getElementById("resetEmail");
      const hiddenEmailField = document.getElementById("hiddenEmail");
      const userEmailDisplay = document.getElementById("userEmailDisplay");

      if (resetEmailField) resetEmailField.value = emailValue;
      if (hiddenEmailField) hiddenEmailField.value = emailValue;
      if (userEmailDisplay) userEmailDisplay.textContent = emailValue;

      // Set the visibility of the forms to the initial state
      if (sendOtpForm) sendOtpForm.classList.remove("d-none");
      if (resetPasswordForm) resetPasswordForm.classList.add("d-none");
    });

    // Send OTP form submission
    if (sendOtpForm) {
      sendOtpForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const btn = this.querySelector('button[type="submit"]');
        const resetEmailField = document.getElementById("resetEmail");

        if (!btn || !resetEmailField) return;

        const originalBtnText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';

        const formData = new FormData();
        formData.append("email", resetEmailField.value);

        fetch("forgot_password.php", {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        })
          .then((res) => {
            if (!res.ok) {
              throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
          })
          .then((data) => {
            showAlert(
              data.message || "Response received",
              data.status === "success" ? "success" : "danger",
              resetAlertPlaceholder
            );
            if (data.status === "success") {
              sendOtpForm.classList.add("d-none");
              resetPasswordForm.classList.remove("d-none");

              // Update the email display
              const userEmailDisplay =
                document.getElementById("userEmailDisplay");
              if (userEmailDisplay)
                userEmailDisplay.textContent = resetEmailField.value;
            }
          })
          .catch((err) => {
            console.error("Send OTP Error:", err);
            showAlert(
              "An error occurred while sending OTP. Please try again.",
              "danger",
              resetAlertPlaceholder
            );
          })
          .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
          });
      });
    }

    // Reset password form submission
    if (resetPasswordForm) {
      resetPasswordForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const btn = this.querySelector('button[type="submit"]');
        const newPassword = document.getElementById("new_password")?.value;
        const confirmPassword =
          document.getElementById("confirm_password")?.value;

        if (!btn) return;

        // Client-side password validation
        if (newPassword !== confirmPassword) {
          showAlert("Passwords do not match.", "danger", resetAlertPlaceholder);
          return;
        }

        if (newPassword.length < 8) {
          showAlert(
            "Password must be at least 8 characters long.",
            "danger",
            resetAlertPlaceholder
          );
          return;
        }

        const originalBtnText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Resetting...';

        const formData = new FormData(this);

        fetch("reset_password.php", {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        })
          .then((res) => {
            if (!res.ok) {
              throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
          })
          .then((data) => {
            showAlert(
              data.message || "Response received",
              data.status === "success" ? "success" : "danger",
              resetAlertPlaceholder
            );
            if (data.status === "success") {
              resetPasswordForm.classList.add("d-none");
              setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(forgotPasswordModal);
                if (modal) modal.hide();

                // Show success message on main page
                showAlert(
                  "Password reset successful! Please login with your new password.",
                  "success",
                  alertPlaceholder
                );
              }, 2000);
            }
          })
          .catch((err) => {
            console.error("Reset Password Error:", err);
            showAlert(
              "An error occurred while resetting password. Please try again.",
              "danger",
              resetAlertPlaceholder
            );
          })
          .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
          });
      });
    }
  }

  // Auto-focus on email field when page loads
  const emailField = document.getElementById("email");
  if (emailField && !emailField.value) {
    emailField.focus();
  }
});