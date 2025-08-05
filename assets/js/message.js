// assets/js/messaging.js

document.addEventListener('DOMContentLoaded', function() {
    const contactsList = document.getElementById('contacts-list');
    const messageArea = document.getElementById('message-area');
    const chatWithName = document.getElementById('chat-with-name');
    const messageInput = document.getElementById('message-text');
    const sendButton = document.getElementById('send-button');

    let otherUserId = null;
    let otherUserName = '';
    const currentUserId = parseInt(window.currentUserId, 10);
    let messageInterval = null;
    let lastMessageId = 0;

    function loadContacts() {
        let formData = new FormData();
        formData.append('action', 'get_contacts');

        fetch('../../includes/messaging_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                contactsList.innerHTML = '';
                let defaultImage = '/BMC-SMS/assets/images/undraw_profile.svg';

                data.contacts.forEach(contact => {
                    const li = document.createElement('a');
                    li.href = '#';
                    li.classList.add('list-group-item', 'list-group-item-action', 'contact-item');
                    li.dataset.userId = contact.id;

                    const img = document.createElement('img');
                    img.src = contact.image_path ? contact.image_path : defaultImage;
                    img.classList.add('contact-image');
                    img.alt = contact.name;
                    
                    const span = document.createElement('span');
                    span.textContent = contact.name;

                    li.appendChild(img);
                    li.appendChild(span);
                    
                    li.addEventListener('click', (e) => {
                        e.preventDefault();
                        selectContact(li);
                    });
                    contactsList.appendChild(li);
                });
            } else {
                contactsList.innerHTML = '<li class="list-group-item">Error loading contacts.</li>';
                console.error('Error loading contacts:', data.message);
            }
        });
    }

    function selectContact(li) {
        document.querySelectorAll('.contact-item').forEach(item => {
            item.classList.remove('active');
        });
        li.classList.add('active');

        otherUserId = li.dataset.userId;
        otherUserName = li.querySelector('span').textContent;
        chatWithName.textContent = `Chat with ${otherUserName}`;
        messageInput.disabled = false;
        sendButton.disabled = false;
        lastMessageId = 0; 
        loadMessages(); 

        if (messageInterval) {
            clearInterval(messageInterval);
        }
        messageInterval = setInterval(loadMessages, 3000); 
    }

    function loadMessages() {
        if (!otherUserId) return;

        let formData = new FormData();
        formData.append('action', 'get_messages');
        formData.append('other_user_id', otherUserId);
        formData.append('last_message_id', lastMessageId);

        fetch('../../includes/messaging_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.messages.length > 0) {
                if (lastMessageId === 0) {
                    messageArea.innerHTML = ''; 
                }
                appendMessages(data.messages);
                lastMessageId = data.messages[data.messages.length - 1].id;
                messageArea.scrollTop = messageArea.scrollHeight;
            } else if (data.status !== 'success') {
                console.error('Error loading messages:', data.message);
            }
        });
    }

    function appendMessages(messages) {
        let previousSenderId = (messageArea.lastElementChild) ? messageArea.lastElementChild.dataset.senderId : null;

        messages.forEach(msg => {
            // =================================================================
            // === DEBUGGING CODE: This will print information to the console ===
            // =================================================================
            console.log(`--- Checking Message ---`);
            console.log(`Message Sender ID: ${msg.sender_id} (Type: ${typeof msg.sender_id})`);
            console.log(`Current User ID: ${currentUserId} (Type: ${typeof currentUserId})`);
            
            const messageContainer = document.createElement('div');
            messageContainer.dataset.senderId = msg.sender_id;

            if (msg.sender_id == previousSenderId) {
                messageContainer.classList.add('message-container-grouped');
            } else {
                messageContainer.classList.add('message-container');
            }
            
            if (parseInt(msg.sender_id) == currentUserId) {
                console.log('✅ RESULT: MATCH. This is a SENT message.');
                messageContainer.classList.add('sent');
                messageContainer.style.marginLeft = 'auto'; 
                messageContainer.style.marginRight = '0';
            } else {
                console.log('❌ RESULT: NO MATCH. This is a RECEIVED message.');
                messageContainer.classList.add('received');
                messageContainer.style.marginRight = 'auto';
                messageContainer.style.marginLeft = '0';
            }

            const img = document.createElement('img');
            img.src = msg.sender_image;
            img.classList.add('message-image');
            img.alt = '';
            
            const messageBubble = document.createElement('div');
            messageBubble.classList.add('message-bubble');

            const messageText = document.createElement('p');
            messageText.classList.add('message-text');
            messageText.textContent = msg.message_text;

            const messageTimestamp = document.createElement('span');
            messageTimestamp.classList.add('message-timestamp');
            messageTimestamp.textContent = new Date(msg.timestamp).toLocaleString();

            messageBubble.appendChild(messageText);
            messageBubble.appendChild(messageTimestamp);

            messageContainer.appendChild(img);
            messageContainer.appendChild(messageBubble);

            messageArea.appendChild(messageContainer);
            previousSenderId = msg.sender_id;
        });
    }

    function sendMessage() {
        const messageText = messageInput.value.trim();
        if (messageText === '' || !otherUserId) {
            return;
        }

        let formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('receiver_id', otherUserId);
        formData.append('message_text', messageText);

        fetch('../../includes/messaging_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                messageInput.value = '';
                loadMessages(); 
            } else {
                console.error('Error sending message:', data.message);
                alert('Could not send message. Please try again.');
            }
        });
    }

    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    loadContacts();
});