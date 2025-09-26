// assets/js/ajax-forms.js (Corrected)

document.addEventListener('DOMContentLoaded', function() {

    // Find the student enrollment form on the page
    const studentForm = document.getElementById('studentEnrollmentForm');

    // If the form exists, attach our AJAX submission logic
    if (studentForm) {
        studentForm.addEventListener('submit', function(event) {
            // Prevent the browser's default form submission
            event.preventDefault();

            const alertPlaceholder = document.getElementById('enrollment-alert-placeholder');
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;

            // Disable the button and show a loading indicator to prevent multiple clicks
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            // Use FormData to correctly package all form fields, including the photo
            const formData = new FormData(this);

            // Send the data to the server using the Fetch API
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    // This header tells the PHP script that it's an AJAX request
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                // Check for network errors
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json(); // Parse the JSON response from the server
            })
            .then(data => {
                // Display the success or error message from the server
                const alertClass = data.success ? 'alert-success' : 'alert-danger';
                alertPlaceholder.innerHTML = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        ${data.message}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>`;

                // If enrollment was successful, redirect to the student list
                if (data.success && data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000); // Wait 2 seconds to allow the user to read the message
                }
            })
            .catch(error => {
                // Handle any unexpected errors during the submission
                console.error('Form submission error:', error);
                alertPlaceholder.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        An unexpected error occurred. Please check the console and try again.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>`;
            })
            .finally(() => {
                // This block runs whether the submission succeeded or failed
                // Re-enable the button unless a redirect is happening
                if (!document.querySelector('.alert-success')) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            });
        });
    }
});