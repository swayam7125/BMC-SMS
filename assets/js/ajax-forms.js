// Utility function to serialize form data to JSON
function serializeFormToJSON(form) {
    const formData = new FormData(form);
    const object = {};
    formData.forEach((value, key) => {
        if (object[key] !== undefined) {
            if (!Array.isArray(object[key])) {
                object[key] = [object[key]];
            }
            object[key].push(value);
        } else {
            object[key] = value;
        }
    });
    return object;
}

// Handle form submissions via AJAX
$(document).on('submit', 'form[data-ajax-form]', function(e) {
    e.preventDefault();
    const $form = $(this);
    const submitBtn = $form.find('[type="submit"]');
    const originalBtnText = submitBtn.html();
    
    // Disable submit button and show loading state
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
    
    $.ajax({
        url: $form.attr('action'),
        method: $form.attr('method') || 'POST',
        data: serializeFormToJSON($form[0]),
        success: function(response) {
            if (response.success) {
                // Show success message
                showNotification('success', response.message || 'Operation completed successfully');
                
                // If redirect URL is provided, navigate to it
                if (response.redirect) {
                    loadPage(response.redirect);
                }
                
                // If form should be reset after success
                if ($form.data('reset-on-success')) {
                    $form[0].reset();
                }
                
                // Trigger success event for custom handling
                $form.trigger('ajaxFormSuccess', [response]);
            } else {
                showNotification('error', response.message || 'An error occurred');
            }
        },
        error: function(xhr, status, error) {
            showNotification('error', 'Server error: ' + error);
        },
        complete: function() {
            // Re-enable submit button and restore original text
            submitBtn.prop('disabled', false).html(originalBtnText);
        }
    });
});

// Function to show notifications
function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `);
    
    // Add alert to the page
    $('#main-content').prepend(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alert.alert('close');
    }, 5000);
}

// Function to handle data tables reinitialization
function reinitializeDataTables() {
    if ($.fn.DataTable) {
        $('table.dataTable').each(function() {
            // Destroy existing DataTable instance if it exists
            if ($.fn.DataTable.isDataTable(this)) {
                $(this).DataTable().destroy();
            }
            // Reinitialize DataTable
            $(this).DataTable();
        });
    }
}

// Function to handle select2 reinitialization
function reinitializeSelect2() {
    if ($.fn.select2) {
        $('.select2').select2();
    }
}

// Listen for content loaded event
$(document).on('contentLoaded', function() {
    reinitializeDataTables();
    reinitializeSelect2();
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-toggle="popover"]').popover();
});
