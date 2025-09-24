$(document).ready(function() {
    let activeContactId = null;
    let selectedFile = null;
    const api_url = window.base_url + 'includes/messaging_api.php';
    const default_avatar = window.base_url + 'assets/images/unisex.png';
    let messageInterval = null;
    let lastMessageDate = null; // Add a variable to track the date of the last displayed message

    const contactsList = $('#contacts-list');
    const messageArea = $('#message-area');
    const messageForm = $('#message-form');
    const messageText = $('#message-text');
    const sendButton = $('#send-button');
    const attachButton = $('#attach-file-button');
    const fileInput = $('#file-input');
    const filePreviewContainer = $('#file-preview-container');
    const filePreviewName = $('#file-preview-name');
    const cancelFileButton = $('#cancel-file-button');
    
    // New elements for filtering
    const standardTabs = $('#standard-tabs');

    function formatDateSeparator(dateString) {
        const date = new Date(dateString);
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);

        const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        const yesterdayDate = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate());

        if (messageDate.getTime() === todayDate.getTime()) {
            return 'Today';
        }
        if (messageDate.getTime() === yesterdayDate.getTime()) {
            return 'Yesterday';
        }
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function pollForNotifications() {
        $.ajax({
            url: api_url,
            type: 'GET',
            dataType: 'json',
            data: { action: 'get_unread_total' },
            success: function(response) {
                if (response.status === 'success' && response.total_unread > 0) {
                    $('#messages-badge').text(response.total_unread > 9 ? '9+' : response.total_unread).show();
                } else {
                    $('#messages-badge').hide();
                }
            }
        });
    }

    function loadContacts(standard = '') {
        // This is a new contact load, so we clear the active chat
        activeContactId = null;
        if (messageInterval) clearInterval(messageInterval);
        
        contactsList.empty();
        contactsList.html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
        
        $.ajax({
            url: api_url,
            type: 'POST',
            dataType: 'json',
            data: { 
                action: 'get_contacts',
                standard: standard // Pass the selected standard to the API
            },
            success: function(response) {
                contactsList.empty();
                if (response.status === 'success' && response.contacts.length > 0) {
                    response.contacts.forEach(contact => {
                        const contactImage = contact.image_path ? contact.image_path : default_avatar;
                        const unreadBadge = contact.unread_count > 0 ? `<span class="badge badge-danger badge-counter">${contact.unread_count}</span>` : '';
                        
                        const contactHtml = `
                            <li class="list-group-item list-group-item-action contact-item" data-contact-id="${contact.id}" data-contact-name="${escapeHtml(contact.name)}">
                                <div class="d-flex align-items-center">
                                    <img src="${contactImage}" class="rounded-circle mr-3" width="50" height="50" alt="${escapeHtml(contact.name)}" onerror="this.src='${default_avatar}'">
                                    <div class="flex-grow-1"><h6 class="mb-1">${escapeHtml(contact.name)}</h6></div>
                                    ${unreadBadge}
                                </div>
                            </li>`;
                        contactsList.append(contactHtml);
                    });
                    if (activeContactId !== null) {
                        $(`.contact-item[data-contact-id="${activeContactId}"]`).addClass('active');
                    }
                } else {
                    contactsList.html('<li class="list-group-item text-center text-muted">No contacts found.</li>');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error loading contacts:", xhr.responseText);
                contactsList.html('<li class="list-group-item text-center text-danger">Failed to load contacts. Please try again.</li>');
            }
        });
    }

    // ** Corrected logic for loading messages **
    function loadMessages(contactId, isInitialLoad = false) {
        if (!contactId) return;
        
        // This variable should be scoped locally to the function call
        let lastMessageDate = null; 

        $.ajax({
            url: api_url,
            type: 'POST',
            dataType: 'json',
            data: { action: 'get_messages', other_user_id: contactId },
            success: function(response) {
                let hadNewMessages = false;
                
                if (isInitialLoad) {
                    messageArea.empty();
                }

                if (response.status === 'success' && response.messages.length > 0) {
                    const existingMessageIds = new Set(messageArea.find('.message-wrapper').map(function() {
                        return $(this).data('message-id');
                    }).get());

                    response.messages.forEach(msg => {
                        // Prevent duplicate messages
                        if (existingMessageIds.has(msg.id)) return;
                        hadNewMessages = true;

                        const currentMessageDate = new Date(msg.timestamp).toDateString();
                        
                        // Check if the current message date is different from the last message date
                        if (currentMessageDate !== lastMessageDate) {
                            const separatorHtml = `
                                <div class="date-separator">
                                    <span>${formatDateSeparator(msg.timestamp)}</span>
                                </div>`;
                            messageArea.append(separatorHtml);
                            lastMessageDate = currentMessageDate;
                        }

                        const isSender = parseInt(msg.sender_id) === parseInt(window.currentUserId);
                        const wrapperClass = isSender ? 'sent' : 'received';
                        const senderImage = msg.sender_image ? msg.sender_image : default_avatar;

                        let attachmentHtml = '';
                        if (msg.file_path) {
                            const uniqueFilename = msg.file_path.split('/').pop();
                            const downloadUrl = `${window.base_url}includes/download.php?file=${encodeURIComponent(uniqueFilename)}`;
                            const fileUrl = window.base_url + msg.file_path;
                            
                            if (msg.file_type && msg.file_type.startsWith('image/')) {
                                attachmentHtml = `
                                    <div class="message-attachment">
                                        <a href="${fileUrl}" target="_blank">
                                            <img src="${fileUrl}" class="chat-image" alt="Attachment">
                                        </a>
                                    </div>`;
                            } else if (msg.file_type && msg.file_type.startsWith('video/')) {
                                attachmentHtml = `
                                    <div class="message-attachment">
                                        <video src="${fileUrl}" class="chat-video" controls></video>
                                    </div>`;
                            } else {
                                const originalFileName = msg.original_filename || uniqueFilename.substring(14);
                                attachmentHtml = `
                                <div class="message-attachment">
                                    <a href="${downloadUrl}" class="file-link-wrapper" download="${escapeHtml(originalFileName)}">
                                        <i class="fas fa-file-alt file-icon"></i>
                                        <div class="file-info"><span class="file-name">${escapeHtml(originalFileName)}</span></div>
                                    </a>
                                </div>`;
                            }
                        }

                        const messageHtml = `
                            <div class="message-wrapper ${wrapperClass}" data-message-id="${msg.id}">
                                <div class="message-bubble ${wrapperClass}">
                                    <img src="${senderImage}" class="chat-avatar" alt="User" onerror="this.src='${default_avatar}'">
                                    <div class="message-content">
                                        ${msg.message_text ? `<p>${escapeHtml(msg.message_text)}</p>` : ''}
                                        ${attachmentHtml}
                                        <span class="message-time">${formatTimestamp(msg.timestamp)}</span>
                                    </div>
                                </div>
                            </div>`;
                        messageArea.append(messageHtml);
                    });
                    
                    // Auto-scroll to the bottom only if new messages were added or it's an initial load
                    if (hadNewMessages || isInitialLoad) {
                        messageArea.scrollTop(messageArea[0].scrollHeight);
                    }
                } else if (isInitialLoad) {
                    messageArea.html('<div class="text-center h-100 d-flex justify-content-center align-items-center text-muted"><p>Start the conversation!</p></div>');
                }
                
                if (hadNewMessages) {
                    // loadContacts(standardFilter.val()); // <--- PROBLEM SOLVED BY REMOVING THIS LINE
                    pollForNotifications();
                }
            }
        });
    }
    
    // ** Corrected logic for sending messages **
    function sendMessage() {
        const messageTextVal = messageText.val().trim();
        if ((messageTextVal === '' && !selectedFile) || !activeContactId) return;

        sendButton.prop('disabled', true);
        messageText.prop('disabled', true);
        
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('receiver_id', activeContactId);
        formData.append('message_text', messageTextVal);
        if (selectedFile) {
            formData.append('attachment', selectedFile);
        }

        $.ajax({
            url: api_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    messageText.val(''); // Clear the input field
                    cancelFileSelection();
                    loadMessages(activeContactId, true); // Re-load all messages to ensure correct order
                } else {
                    alert('Error sending message: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error("Error sending message:", xhr.responseText);
                alert('Failed to send message. Please try again.');
            },
            complete: function() {
                // Re-enable the button and input field regardless of success or failure
                sendButton.prop('disabled', false);
                messageText.prop('disabled', false); 
                messageText.focus(); // Return focus to the input field
            }
        });
    }
    
    function cancelFileSelection() {
        selectedFile = null;
        fileInput.val('');
        filePreviewContainer.hide();
    }

    contactsList.on('click', '.contact-item', function(e) { // <--- CHANGE 1: Added 'e' here
        e.preventDefault(); // <--- CHANGE 2: Added this line to prevent page reload
    
        // Immediately find the badge within the clicked item and fade it out.
        $(this).find('.badge-counter').fadeOut('fast');
        
        const contactId = $(this).data('contact-id');
        if (activeContactId === contactId) return;
        
        activeContactId = contactId;
        $('.contact-item').removeClass('active');
        $(this).addClass('active');
        
        $('#chat-with-name').text('Chat with ' + $(this).data('contact-name'));
        messageText.add(sendButton).add(attachButton).prop('disabled', false);
        messageText.focus();
    
        if (messageInterval) clearInterval(messageInterval);
        cancelFileSelection();
        loadMessages(contactId, true); // Pass true to clear the chat area and load all messages
        messageInterval = setInterval(() => loadMessages(contactId, false), 5000);
    });

    messageForm.on('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    attachButton.on('click', () => fileInput.click());

    fileInput.on('change', function() {
        if (this.files && this.files[0]) {
            selectedFile = this.files[0];
            filePreviewName.text(selectedFile.name);
            filePreviewContainer.show();
        }
    });

    cancelFileButton.on('click', cancelFileSelection);
    
    // ** UPDATED EVENT LISTENER FOR TABS **
    standardTabs.on('click', 'a.nav-link', function(e) {
        e.preventDefault();
        
        standardTabs.find('a.nav-link').removeClass('active');
        $(this).addClass('active');
        
        const selectedStandard = $(this).data('standard-id');
        
        loadContacts(selectedStandard);
    });

    // Initial Load
    const initialStandard = standardTabs.find('a.nav-link.active').data('standard-id');
    loadContacts(initialStandard);
    pollForNotifications();
    setInterval(pollForNotifications, 15000);

    function formatTimestamp(timestamp) {
        return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return $('<div/>').text(text).html();
    }
});