$(document).ready(function() {
    let activeContactId = null;
    const api_url = window.base_url + 'includes/messaging_api.php';
    const default_avatar = window.base_url + 'assets/img/default-user.jpg';
    let messageInterval = null;

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
                        const contactImage = contact.image_path ? window.base_url + contact.image_path.replace(/^\//, '') : default_avatar;
                        const contactElement = `
                            <li class="list-group-item list-group-item-action contact-item" data-contact-id="${contact.id}" data-contact-name="${escapeHtml(contact.name)}">
                                <div class="d-flex align-items-center">
                                    <img src="${contactImage}" class="rounded-circle mr-3" width="50" height="50" alt="${escapeHtml(contact.name)}" onerror="this.src='${default_avatar}'">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">${escapeHtml(contact.name)}</h6>
                                    </div>
                                </div>
                            </li>`;
                        contactsList.append(contactElement);
                    });
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

    function loadMessages(contactId) {
        if (!contactId) return;
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
                        
                        // Use sender's image, or a default if not available
                        const senderImage = msg.sender_image ? window.base_url + msg.sender_image.replace(/^\//, '') : default_avatar;

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
                    alert('Error sending message: ' + response.message);
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
    
    loadContacts();

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