// ================================
// MAIN AJAX HANDLER (assets/js/ajax-handler.js)
// ================================

class AjaxHandler {
    constructor() {
        this.init();
    }

    init() {
        this.setupGlobalAjaxSettings();
        this.bindNavigationEvents();
        this.bindFormEvents();
        this.bindActionEvents();
        this.updateNotificationCounts();
    }

    setupGlobalAjaxSettings() {
        $.ajaxSetup({
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        // Global AJAX error handler
        $(document).ajaxError((event, xhr, settings, thrownError) => {
            console.error('AJAX Error:', thrownError, 'on URL:', settings.url);
            
            // ⭐ FIX: Check if there's a response before trying to parse it as JSON
            if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.message) {
                        this.showMessage(response.message, 'danger');
                        return;
                    }
                } catch (e) {
                    // The response was not JSON, fall through to the generic message
                    console.error('Could not parse error response as JSON.');
                }
            }
            
            // Generic fallback error message
            this.showMessage('An error occurred. Please try again.', 'danger');
        });
    }

    // Handle navigation links with AJAX
    bindNavigationEvents() {
        $(document).on('click', 'a[data-ajax="true"], .nav-link, .collapse-item', (e) => {
            const $link = $(e.currentTarget);
            const href = $link.attr('href');
            const isDropdown = $link.attr('data-toggle') === 'dropdown'; // <-- ADD THIS LINE

            // Skip if it's a dropdown, an external link, javascript:void(0), or has special attributes
            if (isDropdown || !href || href.startsWith('#') || href.startsWith('javascript:') || // <-- MODIFY THIS LINE
                href.startsWith('http') || $link.hasClass('no-ajax')) {
                return;
            }

            e.preventDefault();
            this.loadPage(href, $link);
        });

         // Handle browser back/forward buttons
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.url) {
                this.loadPage(e.state.url, null, false);
            }
        });
    }

    // Handle form submissions with AJAX
    bindFormEvents() {
        $(document).on('submit', 'form[data-ajax="true"], form:not(.no-ajax)', (e) => {
            e.preventDefault();
            const $form = $(e.currentTarget);
            
            // Skip file upload forms or forms with file inputs
            if ($form.find('input[type="file"]').length > 0) {
                return $form.off('submit').submit();
            }

            this.submitForm($form);
        });
    }

    // Handle action buttons (delete, suspend, etc.)
    bindActionEvents() {
        $(document).on('click', '[data-action]', (e) => {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const action = $btn.data('action');
            const url = $btn.attr('href') || $btn.data('url');
            const message = $btn.data('confirm') || 'Are you sure you want to proceed?';

            this.confirmAction(url, message, action);
        });
    }

    loadPage(url, $link = null, pushState = true) {
        // Show loading indicator
        this.showLoading();

        // Update active states
        if ($link) {
            $('.nav-link, .collapse-item').removeClass('active');
            $link.addClass('active').closest('.nav-item').addClass('active');
        }

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            success: (response) => {
                this.handlePageResponse(response, url, pushState);
            },
            error: (xhr, status, error) => {
                console.error('Page load error:', error);
                this.showMessage('Failed to load page. Please refresh and try again.', 'danger');
                this.hideLoading();
            }
        });
    }

    handlePageResponse(response, url, pushState = true) {
        try {
            // Parse the response to extract content
            const $response = $(response);
            
            // Extract main content (everything between content div)
            let $content = $response.find('#content');
            if ($content.length === 0) {
                // Fallback: look for container-fluid
                $content = $response.find('.container-fluid').first();
            }

            if ($content.length > 0) {
                // Update the main content area
                $('#content').html($content.html());

                // Update page title
                const newTitle = $response.find('title').text();
                if (newTitle) {
                    document.title = newTitle;
                }

                // Update browser history
                if (pushState) {
                    history.pushState({ url: url }, newTitle, url);
                }

                // Reinitialize components
                this.reinitializeComponents();
                
                // Update notification counts
                this.updateNotificationCounts();
            } else {
                throw new Error('Content not found in response');
            }
        } catch (error) {
            console.error('Error processing page response:', error);
            // Fallback to full page reload
            window.location.href = url;
        }

        this.hideLoading();
    }

    submitForm($form) {
        const url = $form.attr('action') || window.location.href;
        const method = $form.attr('method') || 'POST';
        const formData = $form.serialize();

        // Show form loading state
        const $submitBtn = $form.find('[type="submit"]');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Processing...');

        $.ajax({
            url: url,
            type: method,
            data: formData,
            dataType: 'json',
            success: (response) => {
                this.handleFormResponse(response, $form);
            },
            error: (xhr) => {
                try {
                    const response = JSON.parse(xhr.responseText);
                    this.handleFormResponse(response, $form);
                } catch (e) {
                    this.showMessage('An error occurred while processing your request.', 'danger');
                }
            },
            complete: () => {
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    }

    handleFormResponse(response, $form) {
        if (response.success) {
            this.showMessage(response.message || 'Operation completed successfully!', 'success');
            
            if (response.redirect) {
                setTimeout(() => {
                    this.loadPage(response.redirect);
                }, 1500);
            } else if (response.reload) {
                setTimeout(() => {
                    this.loadPage(window.location.href, null, false);
                }, 1500);
            } else {
                // Reset form if no redirect
                $form[0].reset();
            }
        } else {
            this.showMessage(response.message || 'Operation failed.', 'danger');
            
            // Handle validation errors
            if (response.errors) {
                this.displayFormErrors($form, response.errors);
            }
        }
    }

    confirmAction(url, message, actionType) {
        // Create or update confirmation modal
        let $modal = $('#confirmActionModal');
        if ($modal.length === 0) {
            $modal = $(`
                <div class="modal fade" id="confirmActionModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Action</h5>
                                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body" id="confirmActionBody"></div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary" id="confirmActionBtn">Confirm</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            $('body').append($modal);
        }

        $('#confirmActionBody').text(message);
        $('#confirmActionBtn').off('click').on('click', () => {
            $modal.modal('hide');
            this.executeAction(url, actionType);
        });

        $modal.modal('show');
    }

    executeAction(url, actionType) {
        this.showLoading();

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showMessage(response.message || 'Action completed successfully!', 'success');
                    // Reload current page content
                    setTimeout(() => {
                        this.loadPage(window.location.href, null, false);
                    }, 1500);
                } else {
                    this.showMessage(response.message || 'Action failed.', 'danger');
                }
            },
            error: () => {
                this.showMessage('An error occurred while performing the action.', 'danger');
            },
            complete: () => {
                this.hideLoading();
            }
        });
    }

    // Notification system
    updateNotificationCounts() {
        $.ajax({
            url: '/BMC-SMS/includes/ajax/get_notifications.php',
            type: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.updateBadges(response.counts);
                }
            },
            error: () => {
                console.log('Failed to update notification counts');
            }
        });
    }

    updateBadges(counts) {
        // Update sidebar notification badges
        for (const [type, count] of Object.entries(counts)) {
            const $badge = $(`.nav-link[data-notification-type="${type}"] .badge-counter`);
            if (count > 0) {
                if ($badge.length === 0) {
                    const $link = $(`.nav-link[data-notification-type="${type}"] div`);
                    $link.append(`<span class="badge badge-danger badge-counter">${count > 9 ? '9+' : count}</span>`);
                } else {
                    $badge.text(count > 9 ? '9+' : count);
                }
            } else {
                $badge.remove();
            }
        }
    }

    // Utility functions
    showLoading() {
        if ($('#ajaxLoader').length === 0) {
            $('body').append(`
                <div id="ajaxLoader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                     background: rgba(0,0,0,0.5); z-index: 9999; display: flex; justify-content: center; 
                     align-items: center;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            `);
        }
        $('#ajaxLoader').show();
    }

    hideLoading() {
        $('#ajaxLoader').hide();
    }

    showMessage(message, type = 'info') {
        // Remove existing alerts
        $('.alert-ajax').remove();

        const alertClass = `alert-${type}`;
        const $alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show alert-ajax" role="alert" 
                 style="position: fixed; top: 20px; right: 20px; z-index: 1050; min-width: 300px;">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `);

        $('body').append($alert);

        // Auto dismiss after 5 seconds
        setTimeout(() => {
            $alert.alert('close');
        }, 5000);
    }

    displayFormErrors($form, errors) {
        // Clear previous errors
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        // Display new errors
        for (const [field, message] of Object.entries(errors)) {
            const $field = $form.find(`[name="${field}"]`);
            if ($field.length > 0) {
                $field.addClass('is-invalid');
                $field.after(`<div class="invalid-feedback">${message}</div>`);
            }
        }
    }

    reinitializeComponents() {
        // Reinitialize DataTables
        if (typeof $.fn.DataTable !== 'undefined') {
            $('table.dataTable').each(function() {
                if ($.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable().destroy();
                }
            });
            // Reinitialize specific tables
            if (typeof window.dataTableManager !== 'undefined') {
                window.dataTableManager.reinitialize();
            }
        }

        // Reinitialize any other plugins
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2();
        }

        // Reinitialize custom components
        this.bindFormEvents();
        this.bindActionEvents();
    }
    checkDataTableReady(callback) {
        if (typeof $.fn.DataTable !== 'undefined') {
            callback();
        } else {
            setTimeout(() => this.checkDataTableReady(callback), 100);
        }
    }
}

// Initialize AJAX handler when document is ready
$(document).ready(function() {
    window.ajaxHandler = new AjaxHandler();
    
    // Mark notification as read when clicked
    $(document).on('click', '[data-notification-type]', function() {
        const notificationType = $(this).data('notification-type');
        if (notificationType) {
            $.post('/BMC-SMS/includes/ajax/mark_notifications_read.php', {
                type: notificationType
            });
        }
    });
});
