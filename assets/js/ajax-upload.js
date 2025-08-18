// Handle file uploads with AJAX
function handleFileUpload(formData, url, options = {}) {
    const defaults = {
        onProgress: (percent) => {},
        onSuccess: (response) => {},
        onError: (error) => {},
        maxFileSize: 5 * 1024 * 1024 // 5MB default
    };
    
    const settings = { ...defaults, ...options };
    
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = (e.loaded / e.total) * 100;
                settings.onProgress(percent);
            }
        });
        
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    settings.onSuccess(response);
                    resolve(response);
                } catch (e) {
                    const error = 'Invalid server response';
                    settings.onError(error);
                    reject(error);
                }
            } else {
                const error = `Upload failed: ${xhr.statusText}`;
                settings.onError(error);
                reject(error);
            }
        });
        
        xhr.addEventListener('error', () => {
            const error = 'Upload failed';
            settings.onError(error);
            reject(error);
        });
        
        xhr.open('POST', url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);
    });
}

// Example usage for file upload forms
$(document).on('submit', 'form[data-ajax-upload]', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $submitBtn = $form.find('[type="submit"]');
    const $progressBar = $form.find('.progress-bar');
    const formData = new FormData(this);
    
    // Show progress bar if it exists
    $form.find('.progress').show();
    $submitBtn.prop('disabled', true);
    
    handleFileUpload(formData, $form.attr('action'), {
        onProgress: (percent) => {
            if ($progressBar.length) {
                $progressBar.css('width', `${percent}%`).text(`${Math.round(percent)}%`);
            }
        },
        onSuccess: (response) => {
            if (response.success) {
                showSuccessMessage(response.message || 'Upload successful');
                if (response.redirect) {
                    navigateToPage(response.redirect);
                }
                $form.trigger('uploadSuccess', [response]);
            } else {
                showErrorMessage(response.message || 'Upload failed');
            }
        },
        onError: (error) => {
            showErrorMessage(error);
        }
    }).finally(() => {
        $submitBtn.prop('disabled', false);
        $form.find('.progress').hide();
        $progressBar.css('width', '0%').text('0%');
    });
});
