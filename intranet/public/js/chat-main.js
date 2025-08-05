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

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tabName = e.target.dataset.tab;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.getElementById(tabName + '-tab').classList.add('active');
                
                if (tabName === 'users') {
                    this.loadUsers();
                }
            });
        });

        document.getElementById('user-search').addEventListener('input', (e) => {
            const query = e.target.value;
            if (query.length > 2) {
                this.searchUsers(query);
            } else {
                document.getElementById('users-list').innerHTML = '';
            }
        });

        document.getElementById('chat-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });

        document.getElementById('media-btn').addEventListener('click', () => {
            document.getElementById('media-input').click();
        });

        document.getElementById('media-input').addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                this.showFilePreview(file);
            }
        });

        this.loadUsers();
    }

    loadUsers() {
        fetch('/api/search/users?q=')
            .then(response => response.json())
            .then(users => {
                const usersList = document.getElementById('users-list');
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
            });
    }

    searchUsers(query) {
        fetch(`/api/search/users?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(users => {
                const usersList = document.getElementById('users-list');
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
            });
    }

    startPrivateChat(userId) {
        this.currentRecipientId = userId;
        this.currentProjectId = null;
        this.chatType = 'private';
        
        document.querySelectorAll('.user-item, .conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        const userElement = document.querySelector(`[data-user-id="${userId}"]`);
        if (userElement) {
            userElement.classList.add('active');
        }
        
        this.loadMessages();
        
        document.getElementById('chat-input-container').style.display = 'block';
        
        this.startPolling();
        
        fetch('/api/search/users?q=')
            .then(response => response.json())
            .then(users => {
                const user = users.find(u => u.id == userId);
                if (user) {
                    document.getElementById('chat-header').textContent = `${user.firstName} ${user.lastName}`;
                }
            });
    }

    loadMessages() {
        let url;
        if (this.chatType === 'private') {
            url = `/chat/api/messages/private/${this.currentRecipientId}`;
        } else {
            url = `/chat/api/messages/project/${this.currentProjectId}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(messages => {
                this.displayMessages(messages);
            })
            .catch(error => console.error('Error:', error));
    }

    displayMessages(messages) {
        const messagesContainer = document.getElementById('chat-messages');
        
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
        const content = input.value.trim();
        const file = fileInput.files[0];
        
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
                fileInput.value = '';
                this.hideFilePreview();
                this.loadMessages();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    showFilePreview(file) {
        const preview = document.getElementById('file-preview');
        const fileExt = file.name.split('.').pop().toLowerCase();
        
        let previewContent = '';
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewContent = `
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <img src="${e.target.result}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        <span>${file.name}</span>
                        <button type="button" onclick="window.mainChatInstance.hideFilePreview()" style="margin-left: auto; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;">×</button>
                    </div>
                `;
                preview.innerHTML = previewContent;
            };
            reader.readAsDataURL(file);
        } else {
            previewContent = `
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>📎 ${file.name}</span>
                    <button type="button" onclick="window.mainChatInstance.hideFilePreview()" style="margin-left: auto; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;">×</button>
                </div>
            `;
            preview.innerHTML = previewContent;
        }
        
        preview.style.display = 'block';
    }

    hideFilePreview() {
        const preview = document.getElementById('file-preview');
        const fileInput = document.getElementById('media-input');
        preview.style.display = 'none';
        preview.innerHTML = '';
        fileInput.value = '';
    }

    startPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
        
        this.pollInterval = setInterval(() => {
            if (this.currentRecipientId || this.currentProjectId) {
                this.loadMessages();
            }
        }, 3000);
    }

    cleanup() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.chatConfig !== 'undefined') {
        window.mainChatInstance = new MainChat(window.chatConfig.currentUserId);
        
        window.addEventListener('beforeunload', function() {
            window.mainChatInstance.cleanup();
        });
    }
});
