class PrivateChat {
    constructor(recipientId, currentUserId) {
        this.recipientId = recipientId;
        this.currentUserId = currentUserId;
        this.pollInterval = null;
        this.init();
    }

    init() {
        this.loadMessages();
        this.startPolling();
        
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
    }

    loadMessages() {
        fetch(`/chat/api/messages/private/${this.recipientId}`)
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
                    <div class="message-meta">${time}</div>
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
        formData.append('type', 'private');
        formData.append('recipientId', this.recipientId);
        
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
                        <button type="button" onclick="window.privateChatInstance.hideFilePreview()" style="margin-left: auto; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;">×</button>
                    </div>
                `;
                preview.innerHTML = previewContent;
            };
            reader.readAsDataURL(file);
        } else {
            previewContent = `
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>📎 ${file.name}</span>
                    <button type="button" onclick="window.privateChatInstance.hideFilePreview()" style="margin-left: auto; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;">×</button>
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
        this.pollInterval = setInterval(() => {
            this.loadMessages();
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
        window.privateChatInstance = new PrivateChat(
            window.chatConfig.recipientId,
            window.chatConfig.currentUserId
        );
        
        window.addEventListener('beforeunload', function() {
            window.privateChatInstance.cleanup();
        });
    }
});
