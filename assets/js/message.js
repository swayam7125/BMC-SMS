$(document).ready(function() {
    let activeContactId = null;
    const api_url = window.base_url + 'includes/messaging_api.php';
    const default_avatar = window.base_url + 'assets/images/unisex.png';
    let messageInterval = null;

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
            },
            error: function(xhr) {
                console.error("Error polling for notifications:", xhr.responseText);
            }
        });
    }

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
                        const contactImage = contact.image_path || default_avatar;
                        const unreadBadge = contact.unread_count > 0 ?
                            `<span class="badge badge-danger badge-counter">${contact.unread_count}</span>` : '';
                        
                        const contactHtml = `
                            <li class="list-group-item list-group-item-action contact-item" data-contact-id="${contact.id}" data-contact-name="${escapeHtml(contact.name)}">
                                <div class="d-flex align-items-center">
                                    <img src="${contactImage}" class="rounded-circle mr-3" width="50" height="50" alt="${escapeHtml(contact.name)}" onerror="this.src='${default_avatar}'">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">${escapeHtml(contact.name)}</h6>
                                    </div>
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
            error: function(xhr) {
                console.error("Error loading contacts:", xhr.responseText);
                $('#contacts-list').html('<li class="list-group-item text-center text-danger">Failed to load contacts.</li>');
            }
        });
    }

    function loadMessages(contactId, isInitialLoad = false) {
        if (!contactId) return;
        const messageArea = $('#message-area');

        $.ajax({
            url: api_url,
            type: 'POST',
            dataType: 'json',
            data: { action: 'get_messages', other_user_id: contactId },
            success: function(response) {
                let hadNewMessages = false;
                let lastDate = messageArea.find('.date-separator:last').data('date-string') || messageArea.find('.message-wrapper:last').data('date-string') || null;

                if (response.status === 'success' && response.messages.length > 0) {
                    response.messages.forEach((msg, index) => {
                        if ($(`.message-wrapper[data-message-id="${msg.id}"]`).length > 0) return;
                        hadNewMessages = true;

                        const messageDate = new Date(msg.timestamp);
                        const messageDateString = messageDate.toDateString();
                        let $dateSeparator;

                        if (lastDate !== messageDateString) {
                            const dateSeparatorHtml = `<div class="date-separator" data-date-string="${messageDateString}"><span>${formatDateSeparator(messageDate)}</span></div>`;
                            $dateSeparator = $(dateSeparatorHtml);
                            lastDate = messageDateString;
                        }

                        const isSender = parseInt(msg.sender_id) === parseInt(window.currentUserId);
                        const wrapperClass = isSender ? 'sent' : 'received';
                        const bubbleClass = isSender ? 'sent' : 'received';
                        const senderImage = msg.sender_image || default_avatar;
                        
                        const messageHtml = `
                            <div class="message-wrapper ${wrapperClass}" data-message-id="${msg.id}" data-date-string="${messageDateString}">
                                <div class="message-bubble ${bubbleClass}">
                                    <img src="${senderImage}" class="chat-avatar" alt="User" onerror="this.src='${default_avatar}'">
                                    <div class="message-content">
                                        <p>${escapeHtml(msg.message_text)}</p>
                                        <span class="message-time">${formatTimestamp(msg.timestamp)}</span>
                                    </div>
                                </div>
                            </div>`;

                        const $messageElement = $(messageHtml);
                        
                        if (isInitialLoad) {
                            if ($dateSeparator) $dateSeparator.addClass('animate-in');
                            $messageElement.addClass('animate-in');
                        }

                        if ($dateSeparator) messageArea.append($dateSeparator);
                        messageArea.append($messageElement);

                        if (isInitialLoad) {
                            const delay = index * 50;
                            setTimeout(() => {
                                if ($dateSeparator) $dateSeparator.removeClass('animate-in');
                                $messageElement.removeClass('animate-in');
                            }, delay + 10);
                        }
                    });

                    // --- IMPROVEMENT ---
                    // The animated scroll created a jarring "scrolling down" effect.
                    // This is now an instantaneous scroll, so the user immediately sees the latest messages.
                    if (hadNewMessages || isInitialLoad) {
                         messageArea.scrollTop(messageArea[0].scrollHeight);
                    }
                } else if (isInitialLoad) {
                    messageArea.html('<div class="text-center h-100 d-flex justify-content-center align-items-center text-muted"><p>Start the conversation!</p></div>');
                }

                if (hadNewMessages) {
                    loadContacts();
                    pollForNotifications();
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
            data: { action: 'send_message', receiver_id: activeContactId, message_text: messageText },
            success: function(response) {
                if (response.status === 'success') {
                    $('#message-text').val('');
                    loadMessages(activeContactId, false);
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
        
        // --- IMPROVEMENT ---
        // Prevents reloading the chat if the user clicks the same active contact again.
        if (activeContactId === contactId) return;
        
        activeContactId = contactId;

        $('.contact-item').removeClass('active');
        $(this).addClass('active');
        
        $('#chat-with-name').text('Chat with ' + contactName);
        $('#message-text, #send-button').prop('disabled', false);
        $('#message-text').focus();

        $('#message-area').empty();
        if (messageInterval) clearInterval(messageInterval);

        loadMessages(contactId, true);
        messageInterval = setInterval(() => loadMessages(contactId, false), 5000);
    });
    
    $('#send-button').on('click', sendMessage);
    $('#message-text').on('keypress', function(e) { if (e.which === 13) { e.preventDefault(); sendMessage(); } });

    loadContacts();
    pollForNotifications();
    setInterval(pollForNotifications, 15000);

    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    function formatDateSeparator(date) {
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);
        if (date.toDateString() === today.toDateString()) return 'Today';
        if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return $('<div/>').text(text).html();
    }
});