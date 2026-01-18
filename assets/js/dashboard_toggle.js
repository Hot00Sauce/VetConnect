// Dashboard Toggle JavaScript - Messages & Notifications

(function () {
    'use strict';

    // Wait for DOM to be fully loaded
    window.addEventListener('DOMContentLoaded', function () {
        console.log('Dashboard toggle script loaded');

        const messageToggle = document.getElementById('messageToggle');
        const notificationToggle = document.getElementById('notificationToggle');
        const messagesPanel = document.getElementById('messagesPanel');
        const notificationsPanel = document.getElementById('notificationsPanel');
        const messageSearch = document.getElementById('messageSearch');

        // Debug - check if elements exist
        console.log('Elements found:');
        console.log('- messageToggle:', messageToggle ? 'YES' : 'NO');
        console.log('- notificationToggle:', notificationToggle ? 'YES' : 'NO');
        console.log('- messagesPanel:', messagesPanel ? 'YES' : 'NO');
        console.log('- notificationsPanel:', notificationsPanel ? 'YES' : 'NO');

        // Function to show messages panel
        function showMessages() {
            console.log('Showing messages panel');
            if (notificationsPanel) {
                notificationsPanel.classList.remove('active');
            }
            if (messagesPanel) {
                messagesPanel.classList.add('active');
            }
            if (notificationToggle) {
                notificationToggle.classList.remove('active');
            }
            if (messageToggle) {
                messageToggle.classList.add('active');
            }
        }

        // Function to show notifications panel
        function showNotifications() {
            console.log('Showing notifications panel');
            if (messagesPanel) {
                messagesPanel.classList.remove('active');
            }
            if (notificationsPanel) {
                notificationsPanel.classList.add('active');
            }
            if (messageToggle) {
                messageToggle.classList.remove('active');
            }
            if (notificationToggle) {
                notificationToggle.classList.add('active');
            }
        }

        // Attach event to Message toggle
        if (messageToggle) {
            messageToggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Message toggle clicked!');
                showMessages();
                return false;
            });
            console.log('Message toggle event attached');
        } else {
            console.error('Message toggle element not found!');
        }

        // Attach event to Notification toggle
        if (notificationToggle) {
            notificationToggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Notification toggle clicked!');
                showNotifications();
                return false;
            });
            console.log('Notification toggle event attached');
        } else {
            console.error('Notification toggle element not found!');
        }

        // Message search functionality
        if (messageSearch) {
            messageSearch.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const messageContacts = document.querySelectorAll('.messageContact');

                messageContacts.forEach(contact => {
                    const nameEl = contact.querySelector('h4');
                    const messageEl = contact.querySelector('.lastMessage');

                    if (nameEl && messageEl) {
                        const name = nameEl.textContent.toLowerCase();
                        const message = messageEl.textContent.toLowerCase();

                        if (name.includes(searchTerm) || message.includes(searchTerm)) {
                            contact.style.display = 'flex';
                        } else {
                            contact.style.display = 'none';
                        }
                    }
                });
            });
        }

        // Make message contacts clickable
        const messageContacts = document.querySelectorAll('.messageContact');
        messageContacts.forEach(contact => {
            contact.addEventListener('click', function () {
                messageContacts.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                const contactName = this.querySelector('h4');
                if (contactName) {
                    console.log('Contact clicked:', contactName.textContent);
                }
            });
        });

        console.log('Dashboard toggle initialization complete');
    });
})();
