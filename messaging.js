// Messaging System
let currentConversationId = null;
let currentReceiverId = null;
let messageRefreshInterval = null;

// Load conversations
function loadConversations() {
    fetch('api/get_conversations.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversations(data.conversations);
            } else {
                console.error('Failed to load conversations:', data.error);
            }
        })
        .catch(error => console.error('Error loading conversations:', error));
}

// Display conversations in the list
function displayConversations(conversations) {
    const messageList = document.getElementById('messageList');
    if (!messageList) return;

    if (conversations.length === 0) {
        messageList.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #666;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                <p>No conversations yet</p>
                <p style="font-size: 0.85rem; margin-top: 0.5rem;">Click "Contact" on a veterinarian to start messaging</p>
            </div>
        `;
        return;
    }

    messageList.innerHTML = conversations.map(conv => {
        const isUnread = conv.unread_count > 0;
        const lastMessagePreview = conv.last_message
            ? (conv.last_message.length > 50 ? conv.last_message.substring(0, 50) + '...' : conv.last_message)
            : 'No messages yet';

        return `
            <div class="messageItem ${isUnread ? 'unread' : ''}" onclick="openConversation(${conv.conversation_id}, ${conv.other_user_id}, '${conv.profile_name.replace(/'/g, "\\'")}', '${conv.profile_picture}')">
                <img src="${conv.profile_picture}" alt="${conv.profile_name}">
                <div class="messageInfo">
                    <h4>${conv.profile_name} ${isUnread ? '<span class="unread-badge">' + conv.unread_count + '</span>' : ''}</h4>
                    <p>${lastMessagePreview}</p>
                </div>
            </div>
        `;
    }).join('');
}

// Open a conversation
function openConversation(conversationId, receiverId, receiverName, receiverPicture) {
    currentConversationId = conversationId;
    currentReceiverId = receiverId;

    // Update chat header - only show profile once with "Online" status
    const chatHeader = document.querySelector('.chatHeader');
    if (chatHeader) {
        chatHeader.innerHTML = `
            <img src="${receiverPicture}" alt="${receiverName}">
            <div>
                <h3>${receiverName}</h3>
                <p>Online</p>
            </div>
        `;
    }

    // Show chat area, hide placeholder
    const chatPlaceholder = document.querySelector('.chatPlaceholder');
    const chatArea = document.querySelector('.chatArea');

    if (chatPlaceholder) {
        chatPlaceholder.style.display = 'none';
    }

    if (chatArea) {
        chatArea.classList.add('active');
        chatArea.style.display = 'flex';
    }

    // Load messages
    loadMessages(conversationId);

    // Start auto-refresh
    if (messageRefreshInterval) {
        clearInterval(messageRefreshInterval);
    }
    messageRefreshInterval = setInterval(() => loadMessages(conversationId), 3000);
}

// Load messages for a conversation
function loadMessages(conversationId) {
    fetch(`api/get_messages.php?conversation_id=${conversationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessages(data.messages);
                // Reload conversations to update unread counts
                loadConversations();
            } else {
                console.error('Failed to load messages:', data.error);
            }
        })
        .catch(error => console.error('Error loading messages:', error));
}

// Display messages in chat
function displayMessages(messages) {
    const chatMessages = document.getElementById('chatMessages');
    if (!chatMessages) return;

    const currentUserId = parseInt(document.body.dataset.userId || '0');

    chatMessages.innerHTML = messages.map(msg => {
        const isSent = msg.sender_id === currentUserId;
        const time = new Date(msg.created_at).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });

        return `
            <div class="message ${isSent ? 'sent' : 'received'}">
                ${!isSent ? `<img src="${msg.sender_picture}" alt="${msg.sender_name}">` : ''}
                <div class="messageBubble">
                    <p>${escapeHtml(msg.message)}</p>
                    <span class="messageTime">${time}</span>
                </div>
            </div>
        `;
    }).join('');

    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Send message
function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    if (!messageInput || !currentConversationId) return;

    const message = messageInput.value.trim();
    if (message === '') return;

    fetch('api/send_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            conversation_id: currentConversationId,
            message: message
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                loadMessages(currentConversationId);
            } else {
                alert('Failed to send message: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            alert('Error sending message. Please try again.');
        });
}

// Start a new conversation
function startConversation(vetId, vetName) {
    fetch('api/start_conversation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            other_user_id: vetId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Switch to Messages tab
                toggleToMessages();

                // Load conversations
                loadConversations();

                // Open the conversation after a short delay to ensure it's loaded
                setTimeout(() => {
                    const messageItems = document.querySelectorAll('.messageItem');
                    messageItems.forEach(item => {
                        item.click();
                    });
                }, 500);
            } else {
                alert('Failed to start conversation: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error starting conversation:', error);
            alert('Error starting conversation. Please try again.');
        });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Handle Enter key in message input
document.addEventListener('DOMContentLoaded', function () {
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    // Load conversations on page load
    loadConversations();

    // Store user ID in body dataset for message display
    const userIdElement = document.querySelector('[data-user-id]');
    if (userIdElement) {
        document.body.dataset.userId = userIdElement.dataset.userId;
    }
});

// Clean up interval on page unload
window.addEventListener('beforeunload', function () {
    if (messageRefreshInterval) {
        clearInterval(messageRefreshInterval);
    }
});
