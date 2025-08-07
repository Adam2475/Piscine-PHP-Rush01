if (typeof window.openChat !== 'function') {
    console.warn('openChat function not defined in template, defining fallback');
    window.openChat = function(recipientId, recipientName) {
        console.log('openChat (fallback) called with:', recipientId, recipientName);
        
        function attemptOpenChat() {
            if (window.mainChatInstance) {
                console.log('MainChat instance found, starting chat');
                window.mainChatInstance.startPrivateChat(recipientId);
                return true;
            }
            console.warn('MainChat instance not yet available');
            return false;
        }
        if (attemptOpenChat()) {
            return;
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(attemptOpenChat, 200);
            });
        } else {
            setTimeout(function() {
                if (!attemptOpenChat()) {
                    setTimeout(attemptOpenChat, 500);
                }
            }, 200);
        }
    };
}

console.log('openChat function defined:', typeof window.openChat === 'function');

class MainChat {
    constructor(currentUserId) {
        this.currentUserId = currentUserId;
        this.currentRecipientId = null;
        this.currentProjectId = null;
        this.chatType = 'private';
        this.pollInterval = null;
        this.init();
    }

    init() {
        const urlParams = new URLSearchParams(window.location.search);
        const userParam = urlParams.get('user');
        if (userParam) {
            setTimeout(() => {
                this.startPrivateChat(userParam);
            }, 500);
        }

        // Inizializza l'header con il messaggio di default
        this.renderChatHeader('Select a conversation', null, 'none');

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tabName = e.target.dataset.tab;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                const tabElement = document.getElementById(tabName + '-tab');
                if (tabElement) {
                    tabElement.classList.add('active');
                }
                
                if (tabName === 'users') {
                    this.loadUsers();
                } else if (tabName === 'conversations') {
                    console.log('Loading conversations from tab click');
                    this.loadConversations();
                }
            });
        });

        const userSearchElement = document.getElementById('user-search');
        if (userSearchElement) {
            userSearchElement.addEventListener('input', (e) => {
                const query = e.target.value;
                if (query.length > 2) {
                    this.searchUsers(query);
                } else {
                    const usersListElement = document.getElementById('users-list');
                    if (usersListElement) {
                        usersListElement.innerHTML = '';
                    }
                }
            });
        }

        const chatFormElement = document.getElementById('chat-form');
        if (chatFormElement) {
            chatFormElement.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
        }

        const mediaBtnElement = document.getElementById('media-btn');
        if (mediaBtnElement) {
            mediaBtnElement.addEventListener('click', () => {
                const mediaInputElement = document.getElementById('media-input');
                if (mediaInputElement) {
                    mediaInputElement.click();
                }
            });
        }

        const mediaInputElement = document.getElementById('media-input');
        if (mediaInputElement) {
            mediaInputElement.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.showFilePreview(file);
                }
            });
        }

        this.loadUsers();
        
        this.loadConversations();
        
        this.attachConversationListeners();
    }

    attachConversationListeners() {
        const conversationItems = document.querySelectorAll('.conversation-item');
        console.log('Attaching listeners to', conversationItems.length, 'conversation items');
        
        conversationItems.forEach(item => {
            const newItem = item.cloneNode(true);
            item.parentNode.replaceChild(newItem, item);
            
            newItem.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const type = newItem.dataset.type;
                const id = newItem.dataset.id;
                
                console.log('Conversation clicked:', { type, id });
                
                if (type === 'private' && id) {
                    this.startPrivateChat(id);
                } else if (type === 'project' && id) {
                    this.startProjectChat(id);
                }
            });
        });
    }

    loadUsers() {
        fetch('/api/search/users?q=')
            .then(response => response.json())
            .then(users => {
                const usersList = document.getElementById('users-list');
                if (!usersList) {
                    console.warn('users-list element not found');
                    return;
                }
                
                usersList.innerHTML = users.map(user => `
                    <div class="user-item" data-user-id="${user.id}">
                        <strong>${user.firstName} ${user.lastName}</strong><br>
                        <small>${user.email}</small>
                    </div>
                `).join('');
                
                usersList.querySelectorAll('.user-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        const userId = e.currentTarget.dataset.userId;
                        this.startPrivateChat(userId);
                    });
                });
            })
            .catch(error => {
                console.error('Error loading users:', error);
            });
    }

    loadConversations() {
        console.log('loadConversations called - avatar removed version');
        return fetch('/chat/api/conversations')
            .then(response => {
                console.log('Conversations API response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(conversations => {
                console.log('Conversations loaded:', conversations);
                const conversationsList = document.querySelector('.conversations-list');
                
                if (!conversationsList) {
                    console.warn('conversations-list element not found');
                    return;
                }
                
                if (conversations.length === 0) {
                    conversationsList.innerHTML = `
                        <div class="no-conversations">
                            <p>No conversations yet</p>
                            <small>Start chatting with users to see conversations here</small>
                        </div>
                    `;
                    return;
                }
                
                conversations.sort((a, b) => {
                    const dateA = new Date(a.lastMessageTime);
                    const dateB = new Date(b.lastMessageTime);
                    return dateB - dateA;
                });
                
                conversationsList.innerHTML = conversations.map(conversation => {
                    const timeStr = this.formatTime(conversation.lastMessageTime);
                    const unreadBadge = conversation.unreadCount > 0 
                        ? `<span class="unread-badge">${conversation.unreadCount}</span>` 
                        : '';
                    console.log('Rendering conversation with avatar:', conversation.name);
                
                    let avatarElement = '';
                    if (conversation.type === 'private' && conversation.avatar) {
                        avatarElement = `<img src="${conversation.avatar}" alt="${conversation.name}" class="conversation-avatar">`;
                    } else if (conversation.type === 'private') {
                        const initials = conversation.name.split(' ').map(word => word[0]).join('').toUpperCase();
                        avatarElement = `<div class="conversation-avatar-placeholder">${initials}</div>`;
                    } else {
                        avatarElement = `<div class="conversation-avatar-placeholder">📂</div>`;
                    }
                    
                    return `
                        <div class="conversation-item ${conversation.unreadCount > 0 ? 'unread' : ''}" 
                             data-type="${conversation.type}" 
                             data-id="${conversation.id}">
                            ${avatarElement}
                            <div class="conversation-info">
                                <div class="conversation-header">
                                    <strong class="conversation-name">${conversation.name}</strong>
                                    ${unreadBadge}
                                </div>
                                <div class="conversation-preview">
                                    ${conversation.lastMessage || 'No messages yet'}
                                </div>
                                <div class="conversation-meta">
                                    <span class="conversation-time">${timeStr}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                this.attachConversationListeners();
            })
            .catch(error => {
                console.error('Error loading conversations:', error);
                const conversationsList = document.querySelector('.conversations-list');
                if (conversationsList) {
                    conversationsList.innerHTML = `
                        <div class="error-message">
                            <p>Error loading conversations</p>
                            <button onclick="window.location.reload()">Retry</button>
                        </div>
                    `;
                }
            });
    }

    formatTime(timeString) {
        const date = new Date(timeString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) {
            return 'Just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes}m ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours}h ago`;
        } else if (diffInSeconds < 604800) {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days}d ago`;
        } else {
            return date.toLocaleDateString();
        }
    }

    searchUsers(query) {
        fetch(`/api/search/users?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(users => {
                const usersList = document.getElementById('users-list');
                if (!usersList) {
                    console.warn('users-list element not found');
                    return;
                }
                
                usersList.innerHTML = users.map(user => `
                    <div class="user-item" data-user-id="${user.id}">
                        <strong>${user.firstName} ${user.lastName}</strong><br>
                        <small>${user.email}</small>
                    </div>
                `).join('');
                
                usersList.querySelectorAll('.user-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        const userId = e.currentTarget.dataset.userId;
                        this.startPrivateChat(userId);
                    });
                });
            })
            .catch(error => {
                console.error('Error searching users:', error);
            });
    }

    startPrivateChat(userId) {
        console.log('Starting private chat with user:', userId);
        
        this.currentRecipientId = userId;
        this.currentProjectId = null;
        this.chatType = 'private';
        
        document.querySelectorAll('.user-item, .conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const userElement = document.querySelector(`[data-user-id="${userId}"]`);
        if (userElement) {
            userElement.classList.add('active');
            console.log('Activated user element');
        }
        
        const conversationElement = document.querySelector(`[data-type="private"][data-id="${userId}"]`);
        if (conversationElement) {
            conversationElement.classList.add('active');
            console.log('Activated conversation element');
        }
        
        this.loadMessages();
        
        const chatInputContainer = document.getElementById('chat-input-container');
        if (chatInputContainer) {
            chatInputContainer.style.display = 'block';
        }
        
        this.startPolling();
        
        this.updateConversationReadStatus(userId);
        
        // Aggiorna l'header della chat con avatar e nome
        this.updateChatHeader(userId, 'private');
    }

    startProjectChat(projectId) {
        console.log('Starting project chat:', projectId);
        
        this.currentProjectId = projectId;
        this.currentRecipientId = null;
        this.chatType = 'project';
        
        document.querySelectorAll('.user-item, .conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const conversationElement = document.querySelector(`[data-type="project"][data-id="${projectId}"]`);
        if (conversationElement) {
            conversationElement.classList.add('active');
            console.log('Activated project conversation element');
        }
        
        this.loadMessages();
        
        const chatInputContainer = document.getElementById('chat-input-container');
        if (chatInputContainer) {
            chatInputContainer.style.display = 'block';
        }
        
        this.startPolling();
        
        // Aggiorna l'header della chat per il progetto
        this.updateChatHeader(projectId, 'project');
    }

    updateConversationReadStatus(userId) {
        const conversationElement = document.querySelector(`[data-type="private"][data-id="${userId}"]`);
        if (conversationElement) {
            conversationElement.classList.remove('unread');
            const unreadBadge = conversationElement.querySelector('.unread-badge');
            if (unreadBadge) {
                unreadBadge.remove();
            }
        }
    }

    updateChatHeader(id, type) {
        const chatHeader = document.getElementById('chat-header');
        if (!chatHeader) {
            console.warn('chat-header element not found');
            return;
        }

        if (type === 'private') {
            // Per chat private, cerca le informazioni dell'utente dalle conversazioni
            fetch('/chat/api/conversations')
                .then(response => response.json())
                .then(conversations => {
                    const conversation = conversations.find(c => c.type === 'private' && c.id == id);
                    if (conversation) {
                        this.renderChatHeader(conversation.name, conversation.avatar, 'private');
                    } else {
                        // Fallback: cerca nelle API degli utenti
                        return fetch('/api/search/users?q=');
                    }
                })
                .then(response => {
                    if (response) {
                        return response.json();
                    }
                    return null;
                })
                .then(users => {
                    if (users) {
                        const user = users.find(u => u.id == id);
                        if (user) {
                            this.renderChatHeader(`${user.firstName} ${user.lastName}`, null, 'private');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching user info:', error);
                    this.renderChatHeader('Select a conversation', null, 'none');
                });
        } else if (type === 'project') {
            // Per chat di progetto
            fetch('/chat/api/conversations')
                .then(response => response.json())
                .then(conversations => {
                    const conversation = conversations.find(c => c.type === 'project' && c.id == id);
                    if (conversation) {
                        this.renderChatHeader(conversation.name, null, 'project');
                    } else {
                        this.renderChatHeader(`Project Chat #${id}`, null, 'project');
                    }
                })
                .catch(error => {
                    console.error('Error fetching project info:', error);
                    this.renderChatHeader(`Project Chat #${id}`, null, 'project');
                });
        }
    }

    renderChatHeader(name, avatarUrl, type) {
        const chatHeader = document.getElementById('chat-header');
        if (!chatHeader) return;

        let avatarElement = '';
        if (type === 'private' && avatarUrl) {
            avatarElement = `<img src="${avatarUrl}" alt="${name}" class="chat-header-avatar">`;
        } else if (type === 'private') {
            // Avatar placeholder per chat private
            const initials = name.split(' ').map(word => word[0]).join('').toUpperCase();
            avatarElement = `<div class="chat-header-avatar-placeholder">${initials}</div>`;
        } else if (type === 'project') {
            // Icona per progetti
            avatarElement = `<div class="chat-header-avatar-placeholder">📂</div>`;
        }

        if (type === 'none') {
            chatHeader.innerHTML = `<span>${name}</span>`;
        } else {
            chatHeader.innerHTML = `
                <div class="chat-header-content">
                    ${avatarElement}
                    <span class="chat-header-name">${name}</span>
                </div>
            `;
        }
    }

    loadMessages() {
        let url;
        if (this.chatType === 'private') {
            url = `/chat/api/messages/private/${this.currentRecipientId}`;
        } else {
            url = `/chat/api/messages/project/${this.currentProjectId}`;
        }
        
        console.log('Loading messages from:', url);
        
        fetch(url)
            .then(response => {
                console.log('Messages response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(messages => {
                console.log('Messages loaded:', messages.length);
                this.displayMessages(messages);
            })
            .catch(error => {
                console.error('Error loading messages:', error);
            });
    }

    displayMessages(messages) {
        console.log('Displaying messages:', messages);
        const messagesContainer = document.getElementById('chat-messages');
        
        if (!messagesContainer) {
            console.warn('chat-messages container not found');
            return;
        }
        
        if (messages.length === 0) {
            messagesContainer.innerHTML = '<div class="no-messages"><p>No messages yet. Start the conversation!</p></div>';
            return;
        }
        
        messagesContainer.innerHTML = messages.map(message => {
            const isSent = message.sender.id === this.currentUserId;
            const messageClass = isSent ? 'sent' : 'received';
            const time = new Date(message.createdAt).toLocaleTimeString('it-IT', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            let mediaContent = '';
            if (message.mediaUrl) {
                const fileExt = message.mediaUrl.split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                    mediaContent = `<img src="${message.mediaUrl}" alt="Immagine">`;
                } else if (['mp4', 'webm', 'ogg'].includes(fileExt)) {
                    mediaContent = `<video controls><source src="${message.mediaUrl}"></video>`;
                } else if (['mp3', 'wav', 'ogg'].includes(fileExt)) {
                    mediaContent = `<audio controls><source src="${message.mediaUrl}"></audio>`;
                } else {
                    mediaContent = `<a href="${message.mediaUrl}" class="file-link" target="_blank">📎 ${message.mediaName || 'File allegato'}</a>`;
                }
            }
            
            return `
                <div class="message ${messageClass} ${message.mediaUrl ? 'media' : ''}">
                    <div class="message-content">${message.content}</div>
                    ${mediaContent}
                    <div class="message-meta">
                        ${!isSent ? message.sender.firstName + ' ' + message.sender.lastName + ' - ' : ''}${time}
                    </div>
                </div>
            `;
        }).join('');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    sendMessage() {
        const input = document.getElementById('message-input');
        const fileInput = document.getElementById('media-input');
        
        if (!input) {
            console.error('Message input not found');
            return;
        }
        
        const content = input.value.trim();
        const file = fileInput ? fileInput.files[0] : null;
        
        if (!content && !file) return;
        
        const formData = new FormData();
        if (content) formData.append('content', content);
        if (file) formData.append('media', file);
        formData.append('type', this.chatType);
        
        if (this.chatType === 'private') {
            formData.append('recipientId', this.currentRecipientId);
        } else {
            formData.append('projectId', this.currentProjectId);
        }
        
        fetch('/chat/api/send', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
            } else {
                input.value = '';
                if (fileInput) fileInput.value = '';
                this.hideFilePreview();
                this.loadMessages();
                this.loadConversations().then(() => {
                    this.maintainActiveConversation();
                });
            }
        })
        .catch(error => console.error('Error:', error));
    }

    maintainActiveConversation() {
        if (this.currentRecipientId && this.chatType === 'private') {
            const conversationElement = document.querySelector(`[data-type="private"][data-id="${this.currentRecipientId}"]`);
            if (conversationElement) {
                conversationElement.classList.add('active');
                console.log('Re-activated conversation element after reload');
            }
        } else if (this.currentProjectId && this.chatType === 'project') {
            const conversationElement = document.querySelector(`[data-type="project"][data-id="${this.currentProjectId}"]`);
            if (conversationElement) {
                conversationElement.classList.add('active');
                console.log('Re-activated project conversation element after reload');
            }
        }
    }

    startPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
        
        this.pollInterval = setInterval(() => {
            if (this.currentRecipientId || this.currentProjectId) {
                this.loadMessages();
                this.loadConversations().then(() => {
                    this.maintainActiveConversation();
                });
            }
        }, 3000);
    }

    showFilePreview(file) {
        const preview = document.getElementById('file-preview');
        if (!preview) {
            console.warn('file-preview element not found');
            return;
        }
        
        preview.style.display = 'block';
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                    <div class="file-preview-item">
                        <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">
                        <button type="button" onclick="window.mainChatInstance.hideFilePreview()">✕</button>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = `
                <div class="file-preview-item">
                    <span>📎 ${file.name}</span>
                    <button type="button" onclick="window.mainChatInstance.hideFilePreview()">✕</button>
                </div>
            `;
        }
    }

    hideFilePreview() {
        const preview = document.getElementById('file-preview');
        if (preview) {
            preview.style.display = 'none';
            preview.innerHTML = '';
        }
        
        const mediaInput = document.getElementById('media-input');
        if (mediaInput) {
            mediaInput.value = '';
        }
    }

    cleanup() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
        // Reset header quando si chiude la chat
        this.renderChatHeader('Select a conversation', null, 'none');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for chatConfig');
    if (typeof window.chatConfig !== 'undefined') {
        console.log('ChatConfig found:', window.chatConfig);
        console.log('Creating MainChat instance...');
        try {
            window.mainChatInstance = new MainChat(window.chatConfig.currentUserId);
            console.log('MainChat instance created successfully');
        } catch (error) {
            console.error('Error creating MainChat instance:', error);
        }
        
        window.addEventListener('beforeunload', function() {
            if (window.mainChatInstance) {
                window.mainChatInstance.cleanup();
            }
        });
    } else {
        console.warn('ChatConfig not found - chat functionality will not be available');
    }
});
