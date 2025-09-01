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

  function showAlert(message, type, placeholder) {
    if (!placeholder) return;
    const wrapper = document.createElement("div");
    wrapper.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show mb-3" role="alert"><div>${message}</div><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
    placeholder.innerHTML = "";
    placeholder.append(wrapper);
  }
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
      }
    );
  }
  if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", function () {
      const type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      this.classList.toggle("fa-eye-slash");
    });
  }
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const emailInput = document.getElementById("email");
      const passwordInput = document.getElementById("password");
      alertPlaceholder.innerHTML = ""; // Clear previous alerts

      if (emailInput.value.trim() === "" && passwordInput.value.trim() === "") {
        showAlert("Please enter your email and password.", "danger", alertPlaceholder);
        return; // Stop the function
      }

      if (emailInput.value.trim() === "") {
        showAlert("Please provide your Email.", "danger", alertPlaceholder);
        return; // Stop the function
      }

      if (passwordInput.value.trim() === "") {
        showAlert("Please provide your Password.", "danger", alertPlaceholder);
        return; // Stop the function
      }

      const formData = new FormData(this);
      const originalButtonText = submitButton.innerHTML;
      submitButton.disabled = true;
      submitButton.innerHTML =
        '<span class="spinner-border spinner-border-sm"></span> Logging In...';
      alertPlaceholder.innerHTML = "";
      fetch("login.php", { method: "POST", body: formData })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            showAlert(
              "Login successful! Redirecting...",
              "success",
              alertPlaceholder
            );
            setTimeout(() => {
              window.location.href = data.redirect;
            }, 1000);
          } else {
            showAlert(data.message, "danger", alertPlaceholder);
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
          }
        })
        .catch((error) => {
          console.error("Login Error:", error);
          showAlert(
            "Please Enter Email-Id and Password!",
            "danger",
            alertPlaceholder
          );
          submitButton.disabled = false;
          submitButton.innerHTML = originalButtonText;
        });
    });
  }

  // --- FORGOT PASSWORD MODAL LOGIC ---
  if (forgotPasswordModal) {
    forgotPasswordModal.addEventListener("show.bs.modal", function () {
      // FIX: Reset forms FIRST to clear any old data.
      sendOtpForm.reset();
      resetPasswordForm.reset();
      resetAlertPlaceholder.innerHTML = "";

      // Now, get the email from the main form and set it in the modal.
      const emailValue = document.getElementById("email").value;
      document.getElementById("resetEmail").value = emailValue;
      document.getElementById("hiddenEmail").value = emailValue;
      document.getElementById("userEmailDisplay").textContent = emailValue;

      // Set the visibility of the forms to the initial state.
      sendOtpForm.classList.remove("d-none");
      resetPasswordForm.classList.add("d-none");
    });

    if (sendOtpForm) {
      sendOtpForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalBtnText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML =
          '<span class="spinner-border spinner-border-sm"></span> Sending...';
        const formData = new FormData();
        formData.append("email", document.getElementById("resetEmail").value);
        fetch("forgot_password.php", { method: "POST", body: formData })
          .then((res) => res.json())
          .then((data) => {
            showAlert(
              data.message,
              data.status === "success" ? "success" : "danger",
              resetAlertPlaceholder
            );
            if (data.status === "success") {
              sendOtpForm.classList.add("d-none");
              resetPasswordForm.classList.remove("d-none");
            }
          })
          .catch((err) =>
            showAlert(
              "An error occurred. Please try again.",
              "danger",
              resetAlertPlaceholder
            )
          )
          .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
          });
      });
    }
    if (resetPasswordForm) {
      resetPasswordForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalBtnText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML =
          '<span class="spinner-border spinner-border-sm"></span> Resetting...';
        const formData = new FormData(this);
        fetch("reset_password.php", { method: "POST", body: formData })
          .then((res) => res.json())
          .then((data) => {
            showAlert(
              data.message,
              data.status === "success" ? "success" : "danger",
              resetAlertPlaceholder
            );
            if (data.status === "success") {
              resetPasswordForm.classList.add("d-none");
              setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(forgotPasswordModal);
                if (modal) modal.hide();
              }, 3000);
            }
          })
          .catch((err) =>
            showAlert(
              "An error occurred. Please try again.",
              "danger",
              resetAlertPlaceholder
            )
          )
          .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
          });
      });
    }
  }
});
