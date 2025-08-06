document.addEventListener('DOMContentLoaded', function() {
    const notificationsBtn = document.getElementById('notifications-btn');
    const notificationsDropdown = document.getElementById('notifications-dropdown');
    const notificationsCount = document.getElementById('notifications-count');
    const notificationsList = document.getElementById('notifications-list');
    
    let lastNotificationCount = 0;
    let previousNotifications = [];

    if (!notificationsBtn || !notificationsDropdown || !notificationsList) {
        console.log('Notifications elements not found - this page might not have notifications');
        return;
    }

    loadNotifications();
    
    notificationsBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (notificationsDropdown.style.display === 'none' || notificationsDropdown.style.display === '') {
            notificationsDropdown.style.display = 'block';
            loadNotifications();
            setTimeout(() => {
                markAllAsRead();
            }, 2000);
        } else {
            notificationsDropdown.style.display = 'none';
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!notificationsDropdown.contains(e.target) && !notificationsBtn.contains(e.target)) {
            notificationsDropdown.style.display = 'none';
        }
    });

    function loadNotifications() {
        fetch('/api/notifications', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            updateNotificationUI(data.notifications, data.unreadCount);
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            updateNotificationUI([], 0);
        });
    }

    function markAllAsRead() {
        const currentCount = parseInt(notificationsCount.getAttribute('data-count') || '0');
        if (currentCount === 0) return;

        fetch('/api/notifications/mark-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                notificationsCount.textContent = '';
                notificationsCount.style.display = 'none';
                notificationsCount.setAttribute('data-count', '0');
                
                loadNotifications();
            }
        })
        .catch(error => {
            console.error('Error marking notifications as read:', error);
        });
    }

    // Helper functions for better security and formatting
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function isValidUrl(string) {
        try {
            const url = new URL(string);
            return url.protocol === 'http:' || url.protocol === 'https:' || url.protocol === 'mailto:' || string.startsWith('/');
        } catch (_) {
            return string.startsWith('/');
        }
    }
    
    function formatTimestamp(timestamp) {
        if (!timestamp) return 'Just now';
        
        try {
            const date = new Date(timestamp);
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
        } catch (error) {
            console.error('Error formatting timestamp:', error);
            return 'Just now';
        }
    }

    function updateNotificationUI(notifications, count) {
        // Detect new notifications by comparing with previous state
        const newNotifications = [];
        if (count > lastNotificationCount && notifications.length > 0) {
            const newCount = count - lastNotificationCount;
            newNotifications.push(...notifications.slice(0, newCount));
        }
        
        lastNotificationCount = count;
        if (count > 0) {
            notificationsCount.textContent = count;
            notificationsCount.style.display = 'flex';
            notificationsCount.setAttribute('data-count', count);
            
            // Animate bell icon if there are new notifications
            if (newNotifications.length > 0) {
                notificationsBtn.style.animation = 'bellShake 0.6s ease-in-out';
                setTimeout(() => {
                    notificationsBtn.style.animation = '';
                }, 600);
            }
        } else {
            notificationsCount.textContent = '';
            notificationsCount.style.display = 'none';
            notificationsCount.setAttribute('data-count', '0');
        }

        if (notifications && notifications.length > 0) {
            const displayNotifications = notifications.slice(0, 5);
            
            notificationsList.innerHTML = displayNotifications.map((notification, index) => {
                let message, link, timestamp;
                
                if (typeof notification === 'string') {
                    message = notification;
                    link = null;
                    timestamp = 'Just now';
                } else {
                    message = notification.message || notification;
                    link = notification.link || null;
                    timestamp = formatTimestamp(notification.timestamp) || 'Just now';
                }
                
                // Sanitize and validate link
                const sanitizedLink = link && isValidUrl(link) ? escapeHtml(link) : null;
                
                const messageHtml = sanitizedLink ? 
                    `<a href="${sanitizedLink}" class="notification-link" style="color: inherit; text-decoration: none; cursor: pointer;">${escapeHtml(message)}</a>` : 
                    escapeHtml(message);
                
                // Check if this is a new notification
                const isNew = newNotifications.some(newNotif => {
                    const newMessage = typeof newNotif === 'string' ? newNotif : (newNotif.message || newNotif);
                    const currentMessage = typeof notification === 'string' ? notification : (notification.message || notification);
                    return newMessage === currentMessage;
                });
                
                return `<div class="notification-item ${isNew ? 'notification-new' : ''}" data-index="${index}">
                    <div class="notification-content">
                        ${messageHtml}
                        <div class="notification-time" style="font-size: 11px; color: #888; margin-top: 4px;">
                            ${timestamp}
                        </div>
                    </div>
                </div>`;
            }).join('');
            
            if (notifications.length > 5) {
                notificationsList.innerHTML += `
                    <div class="notification-item more-notifications" style="text-align: center; font-style: italic; color: #666; border-top: 1px solid #eee; padding-top: 10px;">
                        And ${notifications.length - 5} more notifications...
                    </div>`;
            }
            
            if (count > 0) {
                notificationsList.innerHTML += `
                    <div class="notification-actions" style="border-top: 1px solid #eee; padding: 10px; text-align: center;">
                        <button id="mark-all-read-btn" class="mark-all-read-btn" style="background: #28a745; color: white; border: none; border-radius: 4px; padding: 5px 10px; font-size: 12px; cursor: pointer;">
                            Mark All as Read
                        </button>
                    </div>`;
                
                const markAllBtn = document.getElementById('mark-all-read-btn');
                if (markAllBtn) {
                    markAllBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        markAllAsRead();
                    });
                }
            }
            
            // Remove new notification styling after 5 seconds
            if (newNotifications.length > 0) {
                setTimeout(() => {
                    const newNotificationElements = document.querySelectorAll('.notification-new');
                    newNotificationElements.forEach(element => {
                        element.classList.remove('notification-new');
                    });
                }, 5000);
            }
        } else {
            notificationsList.innerHTML = '<p class="no-notifications" style="padding: 20px; text-align: center; color: #666; margin: 0;">No notifications</p>';
        }
    }

    setInterval(function() {
        if (notificationsDropdown.style.display === 'none' || notificationsDropdown.style.display === '') {
            loadNotifications();
        }
    }, 15000);

    let focusInterval;
    
    window.addEventListener('focus', function() {
        loadNotifications();
        
        focusInterval = setInterval(function() {
            if (notificationsDropdown.style.display === 'none' || notificationsDropdown.style.display === '') {
                loadNotifications();
            }
        }, 5000);
    });
    
    window.addEventListener('blur', function() {
        if (focusInterval) {
            clearInterval(focusInterval);
        }
    });
});
