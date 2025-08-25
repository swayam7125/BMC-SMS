$(document).ready(function() {
    let activeContactId = null;
    const api_url = window.base_url + 'includes/messaging_api.php';
    const default_avatar = window.base_url + 'assets/images/unisex.png'; 
    let messageInterval = null;

    // A new polling function to get the total unread messages for the header icon
    function pollForNotifications() {
        $.ajax({
            url: api_url,
            type: 'GET',
            dataType: 'json',
            data: { action: 'get_unread_total' },
            success: function(response) {
                if (response.status === 'success' && response.total_unread > 0) {
                    $('#message-notification-badge').text(response.total_unread).show();
                } else {
                    $('#message-notification-badge').hide().text('');
                }
            },
            error: function(xhr) {
                console.error("Error polling for notifications:", xhr.responseText);
            }
        });
    }

    // Function to load contacts and their unread counts
    function loadContacts() {
        $.ajax({
            url: api_url,
            type: 'POST',
            dataType: 'json',
            data: { action: 'get_contacts' },
            success: function(response) {
                const contactsList = $('#contacts-list');
                contactsList.empty();
                if (response.status === 'success' && response.contacts.length > 0) {
                    response.contacts.forEach(contact => {
                        // FIX: Use the full path provided by the API directly
                        const contactImage = contact.image_path || default_avatar;
                        // NEW: Create a badge for unread messages if count is > 0
                        const unreadBadge = contact.unread_count > 0 ? 
                            `<span class="badge badge-danger badge-counter">${contact.unread_count}</span>` : '';
                        
                        const contactElement = `
                            <li class="list-group-item list-group-item-action contact-item" data-contact-id="${contact.id}" data-contact-name="${escapeHtml(contact.name)}">
                                <div class="d-flex align-items-center">
                                    <img src="${contactImage}" class="rounded-circle mr-3" width="50" height="50" alt="${escapeHtml(contact.name)}" onerror="this.src='${default_avatar}'">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">${escapeHtml(contact.name)}</h6>
                                    </div>
                                    ${unreadBadge}
                                </div>
                            </li>`;
                        contactsList.append(contactElement);
                    });
                    
                    // NEW: Re-apply the active class to the previously selected contact
                    if (activeContactId !== null) {
                        $(`.contact-item[data-contact-id="${activeContactId}"]`).addClass('active');
                    }
                    
                } else {
                    contactsList.html('<li class="list-group-item text-center text-muted">No contacts found.</li>');
                }
            },
            error: function(xhr) {
                console.error("Error loading contacts:", xhr.responseText);
                $('#contacts-list').html('<li class="list-group-item text-center text-danger">Failed to load contacts.</li>');
            }
        });
    }

    // Function to load and display messages
    function loadMessages(contactId) {
        if (!contactId) return;
        // NEW: Set the active contact ID before loading messages
        activeContactId = contactId;
        const messageArea = $('#message-area');
        
        $.ajax({
            url: api_url,
            type: 'POST',
            dataType: 'json',
            data: { action: 'get_messages', other_user_id: contactId },
            success: function(response) {
                messageArea.empty();
                if (response.status === 'success' && response.messages.length > 0) {
                    response.messages.forEach(msg => {
                        const isSender = parseInt(msg.sender_id) === parseInt(window.currentUserId);
                        const wrapperClass = isSender ? 'sent' : 'received';
                        const bubbleClass = isSender ? 'sent' : 'received';
                        
                        // FIX: Use the full path provided by the API directly
                        const senderImage = msg.sender_image || default_avatar;

                        const messageHtml = `
                            <div class="message-wrapper ${wrapperClass}">
                                <div class="message-bubble ${bubbleClass}">
                                    <img src="${senderImage}" class="chat-avatar" alt="User" onerror="this.src='${default_avatar}'">
                                    <div class="message-content">
                                        <p>${escapeHtml(msg.message_text)}</p>
                                        <span class="message-time">${formatTimestamp(msg.timestamp)}</span>
                                    </div>
                                </div>
                            </div>`;
                        messageArea.append(messageHtml);
                    });
                    // Scroll to the latest message
                    messageArea.scrollTop(messageArea[0].scrollHeight);
                } else {
                    messageArea.html('<div class="text-center h-100 d-flex justify-content-center align-items-center text-muted"><p>Start the conversation!</p></div>');
                }
                // After loading messages for a contact, reload the contacts to update the unread badge
                loadContacts(); 
                // Also update the main header notification
                pollForNotifications();
            },
            error: function(xhr) {
                console.error("Error loading messages:", xhr.responseText);
            }
        });
    }
    
    function sendMessage() {
        const messageText = $('#message-text').val().trim();
        if (messageText === '' || !activeContactId) return;

        $('#send-button').prop('disabled', true);

        $.ajax({
            url: api_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'send_message',
                receiver_id: activeContactId,
                message_text: messageText
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('#message-text').val('');
                    loadMessages(activeContactId); // Reload messages after sending
                } else {
                    // Use a custom modal instead of alert
                    showCustomAlert('Error sending message: ' + response.message);
                }
            },
            complete: function() {
                $('#send-button').prop('disabled', false);
                $('#message-text').focus();
            }
        });
    }

    $('#contacts-list').on('click', '.contact-item', function() {
        const contactId = $(this).data('contact-id');
        const contactName = $(this).data('contact-name');
        
        $('.contact-item').removeClass('active');
        $(this).addClass('active');
        
        $('#chat-with-name').text('Chat with ' + contactName);
        $('#message-text, #send-button').prop('disabled', false);
        $('#message-text').focus();

        if (messageInterval) clearInterval(messageInterval);
        loadMessages(contactId);
        messageInterval = setInterval(() => loadMessages(contactId), 5000); // Refresh chat every 5 seconds
    });

    $('#send-button').on('click', sendMessage);
    $('#message-text').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // Initial loads and polling
    loadContacts();
    pollForNotifications();
    setInterval(pollForNotifications, 30000); // Poll for new notifications every 30 seconds

    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        return $('<div/>').text(text).html();
    }
});